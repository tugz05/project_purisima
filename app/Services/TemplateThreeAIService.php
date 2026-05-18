<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI service for template_three (Business/Vehicle Permit — positioned overlay layout).
 *
 * Standard fields read from document type input_fields (keys below):
 *   type           – NEW or RENEW
 *   control_no     – Control number (e.g. 047-2025)
 *   full_name      – Full name of applicant
 *   address        – Address
 *   vehicle_info   – Number and type of vehicle (e.g. "one (1) unit of Tricycle/Bao-bao")
 *   year_permitted – Year (e.g. 2025)
 *   date_issued    – Date (any parseable format → split into day/month/year)
 *
 * Returns a flat array keyed by these field names, plus:
 *   issued_day   – ordinal day (e.g. "30th")
 *   issued_month – full month name (e.g. "September")
 *   issued_year  – 4-digit year (e.g. "2025")
 */
class TemplateThreeAIService
{
    private string $apiKey;

    private string $model;

    /** Standard field keys for template_three. */
    public const STANDARD_FIELDS = [
        ['key' => 'type',           'label' => 'Type',                       'type' => 'select',   'options' => ['NEW', 'RENEW']],
        ['key' => 'control_no',     'label' => 'Control No',                  'type' => 'text'],
        ['key' => 'full_name',      'label' => 'Full Name',                   'type' => 'text'],
        ['key' => 'address',        'label' => 'Address',                     'type' => 'textarea'],
        ['key' => 'vehicle_info',   'label' => 'Number and Type of Vehicle',  'type' => 'text'],
        ['key' => 'year_permitted', 'label' => 'Year Permitted',              'type' => 'text'],
        ['key' => 'date_issued',    'label' => 'Date Issued',                 'type' => 'date'],
    ];

    public function __construct()
    {
        $this->apiKey = (string) config('services.openai.api_key', '');
        $this->model  = (string) config('services.openai.model', 'gpt-4o');
    }

    /**
     * Build and format the full structured content for the template_three layout.
     *
     * @return array{
     *   type: string, control_no: string, full_name: string, address: string,
     *   vehicle_info: string, year_permitted: string, date_issued: string,
     *   issued_day: string, issued_month: string, issued_year: string
     * }
     */
    public function generateContent(Transaction $transaction): array
    {
        $transaction->load(['resident', 'documentType']);

        $inputData    = is_array($transaction->resident_input_data) ? $transaction->resident_input_data : [];
        $residentData = $transaction->resolveCertificateResidentData();
        $resident     = $transaction->resident;
        $docName      = (string) ($transaction->documentType?->name ?? 'Barangay Clearance');

        // Build raw values from input_data with resident-model fallbacks
        $fullNameFallback = (string) ($residentData['name'] ?? '');

        $addressFallback = (string) ($inputData['address'] ?? $residentData['address'] ?? '');
        if ($addressFallback === '' && $resident) {
            $parts = array_filter([
                $resident->purok ? 'Purok ' . $resident->purok : null,
                $resident->barangay     ?? null,
                $resident->municipality ?? null,
                $resident->province     ?? null,
            ]);
            $addressFallback = implode(', ', $parts);
        }

        $raw = [
            'type'           => (string) ($inputData['type'] ?? ''),
            'control_no'     => (string) ($inputData['control_no'] ?? ''),
            'full_name'      => (string) ($inputData['full_name'] ?? $fullNameFallback),
            'address'        => (string) ($inputData['address'] ?? $addressFallback),
            'vehicle_info'   => (string) ($inputData['vehicle_info'] ?? ''),
            'year_permitted' => (string) ($inputData['year_permitted'] ?? ''),
            'date_issued'    => (string) ($inputData['date_issued'] ?? ''),
        ];

        if ($this->apiKey === '') {
            return $this->formatWithoutAI($raw);
        }

        try {
            return $this->agenticFormat($raw, $docName);
        } catch (\Exception $e) {
            Log::warning('TemplateThreeAIService: agentic call failed, falling back', [
                'transaction_id' => $transaction->id,
                'error'          => $e->getMessage(),
            ]);

            return $this->formatWithoutAI($raw);
        }
    }

    /**
     * @param  array<string, string>  $raw
     * @return array<string, string>
     */
    private function agenticFormat(array $raw, string $documentTypeName): array
    {
        $today = now()->format('m/d/Y');

        $fieldBlock = '';
        foreach ($raw as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            $fieldBlock .= "  {$label}: " . ($value !== '' ? $value : '(empty)') . "\n";
        }

        $systemPrompt = <<<SYS
You are an expert Philippine barangay document processing agent.
Today's date is {$today}.

Format the following field values for an official barangay business/vehicle permit:

FORMATTING RULES:
- type: Uppercase (NEW or RENEW). If "(empty)", return "".
- control_no: Keep exactly as given. If "(empty)", return "".
- full_name: ALL CAPS (e.g. "JIMBOY LARAGA"). NEVER invent data. If "(empty)", return "".
- address: Title case. If "(empty)", return "".
- vehicle_info: Keep as given or infer from context. If "(empty)", return "".
- year_permitted: 4-digit year. If "(empty)", use current year ({$today} year part).
- date_issued: Keep MM/DD/YYYY or convert to it. If "(empty)", use today ({$today}).

ALSO derive and return these three additional fields from the resolved date_issued:
- issued_day: ordinal day number (e.g. "30th", "1st", "22nd")
- issued_month: full English month name (e.g. "September")
- issued_year: 4-digit year string (e.g. "2025")

EMPTY FIELD RULE: return "" for any field that is "(empty)" with no listed default.

OUTPUT: Return ONLY valid JSON with exactly these keys:
type, control_no, full_name, address, vehicle_info, year_permitted, date_issued,
issued_day, issued_month, issued_year

No markdown, no explanation, no extra keys.
SYS;

        $userPrompt = "Document type: {$documentTypeName}\n\nRaw field values:\n{$fieldBlock}\n"
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
            'max_tokens'      => 400,
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

        Log::info('TemplateThreeAIService: agentic format succeeded');

        $expected = array_merge($raw, ['issued_day' => '', 'issued_month' => '', 'issued_year' => '']);
        foreach ($parsed as $key => $value) {
            if (array_key_exists($key, $expected) && is_string($value)) {
                $expected[$key] = $value;
            }
        }

        return $expected;
    }

    /**
     * @param  array<string, string>  $raw
     * @return array<string, string>
     */
    private function formatWithoutAI(array $raw): array
    {
        $result = $raw;

        if ($result['type'] !== '') {
            $result['type'] = mb_strtoupper($result['type']);
        }
        if ($result['full_name'] !== '') {
            $result['full_name'] = mb_strtoupper($result['full_name']);
        }

        // Resolve date_issued
        $dateStr = trim($result['date_issued']);
        $carbon  = null;
        if ($dateStr !== '') {
            try {
                $carbon = Carbon::parse($dateStr);
            } catch (\Exception) {
            }
        }
        $carbon ??= now();

        if (trim($result['date_issued']) === '') {
            $result['date_issued'] = $carbon->format('m/d/Y');
        }

        if (trim($result['year_permitted']) === '') {
            $result['year_permitted'] = $carbon->format('Y');
        }

        $result['issued_day']   = $this->ordinal((int) $carbon->format('j'));
        $result['issued_month'] = $carbon->format('F');
        $result['issued_year']  = $carbon->format('Y');

        return $result;
    }

    private function ordinal(int $n): string
    {
        $suffix = ['th', 'st', 'nd', 'rd'];
        $v      = $n % 100;

        return $n . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
    }
}
