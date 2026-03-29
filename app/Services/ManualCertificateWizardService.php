<?php

namespace App\Services;

use App\Models\DocumentType;
use Illuminate\Support\Str;

/**
 * Walk-in certificate wizard: two printable shells (see PrintCertificate.vue — clearance vs standard).
 * Staff always get layout-appropriate base fields (name, address, purpose, …); document_types.required_fields
 * adds extra inputs (unique keys only) that are included in default body, AI, and finalize.
 */
class ManualCertificateWizardService
{
    public const LAYOUT_CLEARANCE = 'clearance';

    public const LAYOUT_STANDARD = 'standard';

    /**
     * @var list<string>
     */
    private const REQUESTOR_NAME_KEYS = ['name', 'full_name', 'requestor_name', 'applicant_name'];

    /**
     * Match PrintCertificate.vue: barangay clearance uses the orange-sidebar layout; everything else uses standard.
     */
    public function resolvePrintLayout(DocumentType $documentType): string
    {
        $code = strtolower((string) $documentType->code);
        $name = strtolower((string) $documentType->name);

        if ($code === 'barangay_clearance' || str_contains($name, 'barangay clearance')) {
            return self::LAYOUT_CLEARANCE;
        }

        return self::LAYOUT_STANDARD;
    }

    public function layoutDisplayName(string $layout): string
    {
        return $layout === self::LAYOUT_CLEARANCE
            ? 'Barangay clearance (official print layout)'
            : 'Standard certificate (official print layout)';
    }

    /**
     * Number of fields from document_types.required_fields whose keys are not already provided by the print layout.
     */
    public function additionalStaffFieldCount(DocumentType $documentType): int
    {
        $layout = $this->resolvePrintLayout($documentType);
        $baseKeys = array_flip(array_column($this->fieldDefinitions($layout), 'name'));
        $n = 0;
        foreach (DocumentType::normalizeDynamicInputFields($documentType->required_fields ?? []) as $d) {
            if (! isset($baseKeys[$d['key']])) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @return array<int, array{name: string, label: string, type: string, required: bool, placeholder: string, options: array<int, string>}>
     */
    public function fieldDefinitionsForDocumentType(DocumentType $documentType): array
    {
        $base = $this->baseLayoutFieldsAsUi($documentType);
        $baseKeys = array_flip(array_column($base, 'name'));

        foreach (DocumentType::normalizeDynamicInputFields($documentType->required_fields ?? []) as $d) {
            $key = $d['key'];
            if (isset($baseKeys[$key])) {
                continue;
            }
            $base[] = [
                'name' => $key,
                'label' => $d['label'],
                'type' => $d['type'],
                'required' => $d['required'],
                'placeholder' => $d['placeholder'] ?? '',
                'options' => $d['options'] ?? [],
            ];
            $baseKeys[$key] = true;
        }

        return $base;
    }

    /**
     * Same shape as {@see DocumentType::normalizeDynamicInputFields} for {@see AIDocumentGeneratorService::generateDocumentContent}.
     *
     * @return array<int, array{key: string, label: string, type: string, required: bool, placeholder: ?string, options: array<int, string>}>
     */
    public function inputFieldDefinitionsForAi(DocumentType $documentType): array
    {
        $layout = $this->resolvePrintLayout($documentType);
        $defs = array_map(fn (array $f): array => [
            'key' => $f['name'],
            'label' => $f['label'],
            'type' => $f['type'],
            'required' => $f['required'],
            'placeholder' => ($f['placeholder'] ?? '') !== '' ? (string) $f['placeholder'] : null,
            'options' => [],
        ], $this->fieldDefinitions($layout));

        $keys = array_flip(array_column($defs, 'key'));
        foreach (DocumentType::normalizeDynamicInputFields($documentType->required_fields ?? []) as $d) {
            if (isset($keys[$d['key']])) {
                continue;
            }
            $defs[] = $d;
            $keys[$d['key']] = true;
        }

        return $defs;
    }

    /**
     * @return array<int, array{name: string, label: string, type: string, required: bool, placeholder: string, options: array<int, string>}>
     */
    private function baseLayoutFieldsAsUi(DocumentType $documentType): array
    {
        $layout = $this->resolvePrintLayout($documentType);

        return array_map(fn (array $f): array => [
            'name' => $f['name'],
            'label' => $f['label'],
            'type' => $f['type'],
            'required' => $f['required'],
            'placeholder' => $f['placeholder'] ?? '',
            'options' => [],
        ], $this->fieldDefinitions($layout));
    }

    /**
     * @return array<int, string>
     */
    public function allowedFieldKeysForDocumentType(DocumentType $documentType): array
    {
        return array_column($this->fieldDefinitionsForDocumentType($documentType), 'name');
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, string>
     */
    public function filterFieldValuesForDocumentType(DocumentType $documentType, array $raw): array
    {
        $allowed = array_flip($this->allowedFieldKeysForDocumentType($documentType));
        $out = [];

        foreach ($raw as $key => $value) {
            if (! is_string($key) || $key === '' || ! isset($allowed[$key])) {
                continue;
            }
            $out[$key] = is_string($value) ? trim($value) : '';
        }

        return $out;
    }

    /**
     * System placeholders + allowed user fields (starter HTML, merge tags).
     *
     * @param  array<string, mixed>  $fieldValues
     * @return array<string, string>
     */
    public function buildMergeDataForDocumentType(DocumentType $documentType, array $fieldValues): array
    {
        $now = now();
        $system = [
            'date' => $now->format('m/d/Y'),
            'year' => $now->format('Y'),
            'month' => $now->format('F'),
            'day' => $now->format('jS'),
            'time' => $now->format('g:i A'),
            'punong_barangay' => 'EMMANUEL P. ISIANG',
            'barangay' => 'Barangay Purisima',
            'municipality' => 'Tago',
            'province' => 'Surigao del Sur',
        ];

        return array_merge($system, $this->filterFieldValuesForDocumentType($documentType, $fieldValues));
    }

    /**
     * Resident-shaped payload for {@see AIDocumentGeneratorService::generateDocumentContent} (walk-in, no account).
     *
     * @param  array<string, string>  $merge  Output of {@see buildMergeDataForDocumentType}
     * @return array<string, string>
     */
    public function walkInResidentDataForAi(array $merge): array
    {
        $name = '';
        foreach (self::REQUESTOR_NAME_KEYS as $key) {
            $name = trim((string) ($merge[$key] ?? ''));
            if ($name !== '') {
                break;
            }
        }

        $address = trim((string) ($merge['address'] ?? $merge['complete_address'] ?? ''));

        return [
            'name' => $name,
            'address' => $address,
            'purok' => trim((string) ($merge['purok'] ?? '')),
            'barangay' => $merge['barangay'] ?? 'Barangay Purisima',
            'municipality' => $merge['municipality'] ?? 'Tago',
            'province' => $merge['province'] ?? 'Surigao del Sur',
        ];
    }

    /**
     * @param  array<string, string>  $sanitizedFields  From {@see filterFieldValuesForDocumentType}
     */
    public function resolveRequestorDisplayName(array $sanitizedFields): string
    {
        foreach (self::REQUESTOR_NAME_KEYS as $key) {
            $v = trim((string) ($sanitizedFields[$key] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    /**
     * HTML fragment for the rich text editor: only the main body that PrintCertificate.vue injects
     * after its fixed salutation ("TO WHOM IT MAY CONCERN:"), letterhead, and title — not a full certificate.
     *
     * @param  array<string, string>  $merge
     */
    public function buildStarterHtmlForDocumentType(DocumentType $documentType, array $merge): string
    {
        $layout = $this->resolvePrintLayout($documentType);
        $userKeys = $this->allowedFieldKeysForDocumentType($documentType);
        $blocks = [
            '<p style="text-align:center;margin-bottom:0.5em;"><strong>THIS IS TO CERTIFY that:</strong></p>',
        ];

        foreach ($userKeys as $key) {
            $value = $merge[$key] ?? '';
            if ($value === '') {
                continue;
            }
            $label = Str::title(str_replace('_', ' ', $key));
            $blocks[] = '<p><strong>'.e($label).':</strong> '.e($value).'</p>';
        }

        if ($layout === self::LAYOUT_CLEARANCE) {
            $blocks[] = '<p style="text-align:center;margin-top:1em;"><em><strong>Has no criminal nor administrative case/s filed in this office as of this date.</strong></em></p>';
            $day = $merge['day'] ?? now()->format('jS');
            $month = $merge['month'] ?? now()->format('F');
            $year = $merge['year'] ?? now()->format('Y');
            $blocks[] = '<p>This certification is being issued upon the request of the above-named person for the purpose stated above.</p>';
            $blocks[] = '<p>Issued this <strong>'.e((string) $day).'</strong> day of <strong>'.e((string) $month).'</strong>, <strong>'.e((string) $year).'</strong> at the office of the Punong Barangay.</p>';
        }

        return implode("\n", $blocks) ?: '<p>&nbsp;</p>';
    }

    /**
     * @return array<int, array{name: string, label: string, type: string, required: bool, placeholder: string}>
     */
    private function fieldDefinitions(string $layout): array
    {
        if ($layout === self::LAYOUT_CLEARANCE) {
            return [
                ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'required' => true, 'placeholder' => 'LAST NAME, FIRST NAME M.I.'],
                ['name' => 'birthdate', 'label' => 'Birthdate', 'type' => 'date', 'required' => true, 'placeholder' => ''],
                ['name' => 'birthplace', 'label' => 'Birthplace', 'type' => 'text', 'required' => true, 'placeholder' => 'Municipality, Province'],
                ['name' => 'civil_status', 'label' => 'Civil status', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Married'],
                ['name' => 'nationality', 'label' => 'Nationality', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. Filipino'],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Purok, Barangay, Municipality, Province'],
                ['name' => 'purpose', 'label' => 'Purpose / subject', 'type' => 'textarea', 'required' => true, 'placeholder' => 'e.g. MOTORCYCLE LOAN'],
                ['name' => 'purok_cert_no', 'label' => 'Purok Cert. No.', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 152-2025'],
                ['name' => 'ctc_no', 'label' => 'CTC No.', 'type' => 'text', 'required' => false, 'placeholder' => ''],
                ['name' => 'date_issued', 'label' => 'Date issued', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 09/29/2025'],
                ['name' => 'or_no', 'label' => 'OR No.', 'type' => 'text', 'required' => false, 'placeholder' => ''],
                ['name' => 'certification_fee', 'label' => 'Certification (fee)', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 150.00'],
                ['name' => 'doc_stamp', 'label' => 'Doc stamp (fee)', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 30.00'],
                ['name' => 'amount_paid', 'label' => 'Amount paid (total)', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 180.00'],
            ];
        }

        return [
            ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'required' => true, 'placeholder' => 'Requestor full name'],
            ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Complete address'],
            ['name' => 'purpose', 'label' => 'Purpose', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Purpose of this certificate'],
            ['name' => 'remarks', 'label' => 'Additional details', 'type' => 'textarea', 'required' => false, 'placeholder' => 'Optional notes for the body'],
        ];
    }
}
