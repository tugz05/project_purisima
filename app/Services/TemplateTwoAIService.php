<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Agentic AI service for template_two (Barangay Clearance — full dynamic layout).
 *
 * Responsibilities:
 *  1. Format every resident data field for official display
 *     (name → ALL-CAPS surname-first, birthdate → "Month DD, YYYY", fees → "0.00", etc.)
 *  2. Infer reasonable defaults for empty optional fields
 *  3. Generate the Philippine-government-style "Issued this …" body statement
 *     from the date_issued field (or today as fallback)
 *
 * Returns a structured array that PrintCertificate.vue overlays onto
 * the template_two CSS layout (orange sidebar + main content).
 */
class TemplateTwoAIService
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key', '');
        $this->model = (string) config('services.openai.model', 'gpt-4o');
    }

    /**
     * Generate the full structured content array for the template_two layout.
     *
     * @return array{
     *   name: string, birthdate: string, birthplace: string, civil_status: string,
     *   nationality: string, address: string, purpose: string,
     *   purok_cert_no: string, ctc_no: string, date_issued: string,
     *   or_no: string, certification_fee: string, doc_stamp: string, amount_paid: string,
     *   body_issued_statement: string
     * }
     */
    public function generateContent(Transaction $transaction): array
    {
        $transaction->load(['resident', 'documentType']);

        $inputData = is_array($transaction->resident_input_data) ? $transaction->resident_input_data : [];
        $residentData = $transaction->resolveCertificateResidentData();
        $resident = $transaction->resident;

        // Birthdate: input first, then resident profile (birth_date column)
        $birthdateFallback = '';
        if (isset($inputData['birthdate']) && $inputData['birthdate'] !== '') {
            $birthdateFallback = (string) $inputData['birthdate'];
        } elseif ($resident?->birth_date) {
            try {
                $birthdateFallback = \Illuminate\Support\Carbon::parse($resident->birth_date)->format('m/d/Y');
            } catch (\Exception) {}
        }

        // Address: input → residentData (which already merges purok/barangay/municipality/province)
        $addressFallback = (string) ($inputData['address'] ?? $residentData['address'] ?? '');
        if ($addressFallback === '' && $resident) {
            $parts = array_filter([
                $resident->purok ? 'Purok '.$resident->purok : null,
                $resident->barangay ?? null,
                $resident->municipality ?? null,
                $resident->province ?? null,
            ]);
            $addressFallback = implode(', ', $parts);
        }

        $rawFields = [
            'name'             => (string) ($inputData['name'] ?? $residentData['name'] ?? ''),
            'birthdate'        => $birthdateFallback,
            'birthplace'       => (string) ($inputData['birthplace'] ?? ''),
            'civil_status'     => (string) ($inputData['civil_status'] ?? $resident?->civil_status ?? ''),
            'nationality'      => (string) ($inputData['nationality'] ?? ''),
            'address'          => $addressFallback,
            'purpose'          => (string) ($inputData['purpose'] ?? ''),
            'purok_cert_no'    => (string) ($inputData['purok_cert_no'] ?? ''),
            'ctc_no'           => (string) ($inputData['ctc_no'] ?? ''),
            'date_issued'      => (string) ($inputData['date_issued'] ?? ''),
            'or_no'            => (string) ($inputData['or_no'] ?? ''),
            'certification_fee'=> (string) ($inputData['certification_fee'] ?? ''),
            'doc_stamp'        => (string) ($inputData['doc_stamp'] ?? ''),
            'amount_paid'      => (string) ($inputData['amount_paid'] ?? ''),
        ];

        $docName = (string) ($transaction->documentType?->name ?? 'Barangay Clearance');

        if ($this->apiKey === '') {
            return $this->formatWithoutAI($rawFields);
        }

        try {
            return $this->agenticFormat($rawFields, $docName);
        } catch (\Exception $e) {
            Log::warning('TemplateTwoAIService: agentic call failed, falling back', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return $this->formatWithoutAI($rawFields);
        }
    }

    /**
     * Agentic OpenAI call: format all fields AND generate the "Issued this …" statement.
     *
     * @param  array<string, string>  $raw
     * @return array<string, string>
     */
    private function agenticFormat(array $raw, string $documentTypeName): array
    {
        $fieldBlock = '';
        foreach ($raw as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $fieldBlock .= "  {$label}: ".($value !== '' ? $value : '(empty)')."\n";
        }

        $today = now()->format('m/d/Y');

        $systemPrompt = <<<SYS
You are an expert Philippine barangay document processing agent.
Your task is to format and complete field values for an official barangay document form,
then generate the "Issued this …" certification statement.

Today's date is {$today}.

FORMATTING RULES — apply to every field:
- name: ALL CAPS, surname-first format (e.g. "DELA CRUZ, JUAN C.").
  If surname cannot be determined, capitalize the full name entirely.
- birthdate: Convert ANY date format → "Month DD, YYYY" (e.g. "June 12, 1977").
  If value is "(empty)", return "".
- birthplace: Title case (e.g. "Tago, Surigao del Sur").
  If value is "(empty)", return "".
- civil_status: Title case. Accepted values: Single, Married, Widowed, Separated, Annulled.
  If value is "(empty)", return "".
- nationality: Title case. ALWAYS default to "Filipino" when empty or "(empty)".
- address: Title case. Preserve internal line breaks as \\n.
  If value is "(empty)", return "".
- purpose: ALL CAPS (e.g. "MOTORCYCLE LOAN", "LOCAL EMPLOYMENT", "BANK LOAN").
  If value is "(empty)", return "".
- purok_cert_no, ctc_no, or_no: Keep exactly as given. If "(empty)", return "".
- date_issued: Keep in MM/DD/YYYY or convert to it.
  If "(empty)", use today's date ({$today}).
- certification_fee, doc_stamp, amount_paid: Format as "0.00" decimal.
  If "(empty)" or unknown, return "".
- body_issued_statement: ALWAYS generate this using the resolved date_issued value.
  Format: "Issued this {ordinal} day of {Month}, {Year} at the office of the Punong Barangay."
  Example: "Issued this 29th day of September, 2025 at the office of the Punong Barangay."
  Ordinal suffixes: 1st, 2nd, 3rd, 4th–20th all use "th", 21st, 22nd, 23rd, 24th–30th "th", 31st.

EMPTY FIELD RULE:
- If a field is "(empty)" and has NO default listed above → return "" (empty string).
- NEVER invent or fabricate personal data (name, birthdate, birthplace, address, etc.).
- Only apply the explicit defaults described above (nationality → "Filipino", date_issued → today).

OUTPUT: Return ONLY a valid JSON object with these exact keys:
name, birthdate, birthplace, civil_status, nationality, address, purpose,
purok_cert_no, ctc_no, date_issued, or_no, certification_fee, doc_stamp, amount_paid,
body_issued_statement

No markdown, no explanation, no text outside the JSON object.
SYS;

        $userPrompt = "Document type: {$documentTypeName}\n\nRaw field values:\n{$fieldBlock}\n"
            ."Process and return the formatted JSON.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.1,
            'max_tokens' => 600,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $response->successful()) {
            throw new \Exception('OpenAI API error: HTTP '.$response->status());
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $parsed = json_decode($content, true);

        if (! is_array($parsed)) {
            throw new \Exception('OpenAI returned invalid JSON');
        }

        Log::info('TemplateTwoAIService: agentic format succeeded', [
            'keys_returned' => array_keys($parsed),
        ]);

        // Merge: AI values override raw; fallback for any missing keys
        $expected = array_merge($raw, ['body_issued_statement' => '']);
        foreach ($parsed as $key => $value) {
            if (array_key_exists($key, $expected) && is_string($value) && trim($value) !== '' && trim($value) !== '(empty)') {
                $expected[$key] = $value;
            }
        }

        return $expected;
    }

    /**
     * Local fallback when no API key is available.
     *
     * @param  array<string, string>  $raw
     * @return array<string, string>
     */
    private function formatWithoutAI(array $raw): array
    {
        $result = $raw;

        if ($result['name'] !== '') {
            $result['name'] = mb_strtoupper($result['name']);
        }
        if ($result['purpose'] !== '') {
            $result['purpose'] = mb_strtoupper($result['purpose']);
        }
        if (trim($result['nationality']) === '') {
            $result['nationality'] = 'Filipino';
        }

        // Generate issued statement from date_issued or today
        $dateStr = trim($result['date_issued']);
        $carbon = null;
        if ($dateStr !== '') {
            try {
                $carbon = \Illuminate\Support\Carbon::parse($dateStr);
            } catch (\Exception) {
                $carbon = null;
            }
        }
        $carbon ??= now();

        if (trim($result['date_issued']) === '') {
            $result['date_issued'] = $carbon->format('m/d/Y');
        }

        $result['body_issued_statement'] = sprintf(
            'Issued this %s day of %s, %s at the office of the Punong Barangay.',
            $this->ordinal((int) $carbon->format('j')),
            $carbon->format('F'),
            $carbon->format('Y')
        );

        return $result;
    }

    private function ordinal(int $n): string
    {
        $suffix = ['th', 'st', 'nd', 'rd'];
        $v = $n % 100;

        return $n.($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
    }
}
