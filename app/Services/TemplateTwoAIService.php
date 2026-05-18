<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Agentic AI service for template_two (Barangay Clearance — full dynamic layout).
 *
 * Reads the document type's input_fields to build dynamic labeled rows,
 * then formats every value (name → CAPS, date → "Month DD, YYYY", etc.)
 * and generates the "Issued this …" body statement.
 *
 * Returns:
 *   fields              – [{key, label, value}] from input_fields (data table rows)
 *   purpose             – ALL CAPS purpose text
 *   purok_cert_no, ctc_no, date_issued, or_no,
 *   certification_fee, doc_stamp, amount_paid  – admin/transaction fields
 *   body_issued_statement – "Issued this Nth day of Month, YYYY …"
 */
class TemplateTwoAIService
{
    private string $apiKey;

    private string $model;

    /** Keys treated as administrative (rendered in the transaction grid, not the data table). */
    private const ADMIN_KEYS = [
        'purpose', 'purok_cert_no', 'ctc_no', 'date_issued',
        'or_no', 'certification_fee', 'doc_stamp', 'amount_paid',
    ];

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key', '');
        $this->model  = (string) config('services.openai.model', 'gpt-4o');
    }

    /**
     * Build and format the full structured content for the template_two layout.
     *
     * @return array{
     *   fields: list<array{key: string, label: string, value: string}>,
     *   purpose: string, purok_cert_no: string, ctc_no: string, date_issued: string,
     *   or_no: string, certification_fee: string, doc_stamp: string, amount_paid: string,
     *   body_issued_statement: string
     * }
     */
    public function generateContent(Transaction $transaction): array
    {
        $transaction->load(['resident', 'documentType']);

        $inputData    = is_array($transaction->resident_input_data) ? $transaction->resident_input_data : [];
        $residentData = $transaction->resolveCertificateResidentData();
        $resident     = $transaction->resident;
        $allFields    = $transaction->documentType->input_fields ?? [];
        $docName      = (string) ($transaction->documentType?->name ?? 'Barangay Clearance');

        // ── Build raw data-table rows from the document type's input_fields ──
        $rawDataFields = [];
        foreach ($allFields as $field) {
            $key = $field['key'];

            // Administrative fields go in the transaction grid, not the data table
            if (in_array($key, self::ADMIN_KEYS, true)) {
                continue;
            }

            $value = (string) ($inputData[$key] ?? '');

            // Resident-model fallbacks for well-known field patterns
            if ($value === '' && preg_match('/^(name|full_name)$/i', $key)) {
                $value = (string) ($residentData['name'] ?? '');
            }
            if ($value === '' && preg_match('/birth_?date|date_of_birth/i', $key)) {
                if ($resident?->birth_date) {
                    try {
                        $value = Carbon::parse($resident->birth_date)->format('m/d/Y');
                    } catch (\Exception) {
                    }
                }
            }
            if ($value === '' && preg_match('/civil_?status/i', $key)) {
                $value = (string) ($resident?->civil_status ?? '');
            }
            if ($value === '' && $key === 'nationality') {
                $value = 'Filipino';
            }
            if ($value === '' && $key === 'address') {
                $value = (string) ($residentData['address'] ?? '');
                if ($value === '' && $resident) {
                    $parts = array_filter([
                        $resident->purok ? 'Purok ' . $resident->purok : null,
                        $resident->barangay     ?? null,
                        $resident->municipality ?? null,
                        $resident->province     ?? null,
                    ]);
                    $value = implode(', ', $parts);
                }
            }

            $rawDataFields[] = [
                'key'   => $key,
                'label' => $field['label'],
                'type'  => $field['type'],
                'value' => $value,
            ];
        }

        // ── Administrative / transaction fields (always present) ──
        $rawAdminFields = [
            'purpose'           => (string) ($inputData['purpose'] ?? ''),
            'purok_cert_no'     => (string) ($inputData['purok_cert_no'] ?? ''),
            'ctc_no'            => (string) ($inputData['ctc_no'] ?? ''),
            'date_issued'       => (string) ($inputData['date_issued'] ?? ''),
            'or_no'             => (string) ($inputData['or_no'] ?? ''),
            'certification_fee' => (string) ($inputData['certification_fee'] ?? ''),
            'doc_stamp'         => (string) ($inputData['doc_stamp'] ?? ''),
            'amount_paid'       => (string) ($inputData['amount_paid'] ?? ''),
        ];

        if ($this->apiKey === '') {
            return $this->formatWithoutAI($rawDataFields, $rawAdminFields);
        }

        try {
            return $this->agenticFormat($rawDataFields, $rawAdminFields, $docName);
        } catch (\Exception $e) {
            Log::warning('TemplateTwoAIService: agentic call failed, falling back', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);

            return $this->formatWithoutAI($rawDataFields, $rawAdminFields);
        }
    }

    /**
     * @param  list<array{key: string, label: string, type: string, value: string}>  $rawDataFields
     * @param  array<string, string>  $rawAdminFields
     * @return array<string, mixed>
     */
    private function agenticFormat(array $rawDataFields, array $rawAdminFields, string $documentTypeName): array
    {
        $today = now()->format('m/d/Y');

        $dataJson  = json_encode($rawDataFields,  JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $adminJson = json_encode($rawAdminFields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYS
You are an expert Philippine barangay document processing agent.
Today's date is {$today}.

You receive two groups of fields:

GROUP 1 — data_fields (resident information; each has key, label, type, value):
Formatting rules:
- type "date" → "Month DD, YYYY" (e.g. "June 12, 1977"). If empty, return "".
- key matches /name/i → ALL CAPS, surname-first (e.g. "DELA CRUZ, JUAN C."). If empty, return "".
- key "nationality" → default to "Filipino" when empty.
- key "address" → Title case; preserve internal line breaks as \\n. If empty, return "".
- key "civil_status" → Title case (Single, Married, Widowed, Separated, Annulled). If empty, return "".
- All other text fields → Title case the value. If empty, return "".
- NEVER fabricate or invent personal data.

GROUP 2 — admin_fields (transaction metadata):
- purpose → ALL CAPS. If empty, return "".
- date_issued → keep MM/DD/YYYY or convert to it. If empty, use today ({$today}).
- purok_cert_no, ctc_no, or_no → keep exactly as given.
- certification_fee, doc_stamp, amount_paid → "0.00" decimal. If empty, return "".

ALSO generate:
- body_issued_statement using the resolved date_issued:
  Format: "Issued this {ordinal} day of {Month}, {Year} at the office of the Punong Barangay."
  Example: "Issued this 29th day of September, 2025 at the office of the Punong Barangay."
  Ordinal suffixes: 1st 2nd 3rd, 4-20 = th, 21st 22nd 23rd, 24-30 = th, 31st.

EMPTY FIELD RULE: return "" for empty values that have no listed default.

OUTPUT — return ONLY valid JSON (no markdown, no extra text):
{
  "fields": [{"key": "...", "label": "...", "value": "..."}],
  "purpose": "...",
  "purok_cert_no": "...",
  "ctc_no": "...",
  "date_issued": "...",
  "or_no": "...",
  "certification_fee": "...",
  "doc_stamp": "...",
  "amount_paid": "...",
  "body_issued_statement": "..."
}
The "fields" array must keep the same order and keys as the input data_fields.
SYS;

        $userPrompt = "Document type: {$documentTypeName}\n\n"
            . "data_fields:\n{$dataJson}\n\n"
            . "admin_fields:\n{$adminJson}\n\n"
            . "Process and return the formatted JSON.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model'           => $this->model,
            'messages'        => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature'     => 0.1,
            'max_tokens'      => 800,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful()) {
            throw new \Exception('OpenAI API error: HTTP ' . $response->status());
        }

        $data    = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $parsed  = json_decode($content, true);

        if (! is_array($parsed)) {
            throw new \Exception('OpenAI returned invalid JSON');
        }

        Log::info('TemplateTwoAIService: agentic format succeeded', [
            'fields_count' => count($parsed['fields'] ?? []),
        ]);

        // Ensure fields array is valid; fall back to raw if AI dropped it
        if (empty($parsed['fields']) || ! is_array($parsed['fields'])) {
            $parsed['fields'] = array_map(fn ($f) => [
                'key'   => $f['key'],
                'label' => $f['label'],
                'value' => $f['value'],
            ], $rawDataFields);
        }

        // Ensure all admin keys are present
        $adminDefaults = array_merge($rawAdminFields, ['body_issued_statement' => '']);
        foreach ($adminDefaults as $key => $default) {
            if (! isset($parsed[$key]) || ! is_string($parsed[$key])) {
                $parsed[$key] = $default;
            }
        }

        return $parsed;
    }

    /**
     * Local fallback when no API key is configured.
     *
     * @param  list<array{key: string, label: string, type: string, value: string}>  $rawDataFields
     * @param  array<string, string>  $rawAdminFields
     * @return array<string, mixed>
     */
    private function formatWithoutAI(array $rawDataFields, array $rawAdminFields): array
    {
        $fields = [];
        foreach ($rawDataFields as $field) {
            $key   = $field['key'];
            $label = $field['label'];
            $value = $field['value'];
            $type  = $field['type'];

            if ($type === 'date' && $value !== '') {
                try {
                    $value = Carbon::parse($value)->format('F d, Y');
                } catch (\Exception) {
                }
            } elseif (preg_match('/name/i', $key) && $value !== '') {
                $value = mb_strtoupper($value);
            } elseif ($key === 'nationality' && $value === '') {
                $value = 'Filipino';
            }

            $fields[] = ['key' => $key, 'label' => $label, 'value' => $value];
        }

        $admin = $rawAdminFields;

        if ($admin['purpose'] !== '') {
            $admin['purpose'] = mb_strtoupper($admin['purpose']);
        }

        $dateStr = trim($admin['date_issued']);
        $carbon  = null;
        if ($dateStr !== '') {
            try {
                $carbon = Carbon::parse($dateStr);
            } catch (\Exception) {
            }
        }
        $carbon ??= now();

        if (trim($admin['date_issued']) === '') {
            $admin['date_issued'] = $carbon->format('m/d/Y');
        }

        $admin['body_issued_statement'] = sprintf(
            'Issued this %s day of %s, %s at the office of the Punong Barangay.',
            $this->ordinal((int) $carbon->format('j')),
            $carbon->format('F'),
            $carbon->format('Y')
        );

        return array_merge(['fields' => $fields], $admin);
    }

    private function ordinal(int $n): string
    {
        $suffix = ['th', 'st', 'nd', 'rd'];
        $v      = $n % 100;

        return $n . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
    }
}
