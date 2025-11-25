<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateTemplateService
{
    /**
     * Get all templates for a document type
     */
    public function getTemplatesForDocumentType(int $documentTypeId, array $filters = [])
    {
        $query = CertificateTemplate::where('document_type_id', $documentTypeId)
            ->ordered();

        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Create a new certificate template
     */
    public function create(array $data): CertificateTemplate
    {
        return DB::transaction(function () use ($data) {
            // If this is set as default, unset other defaults for this document type
            if (isset($data['is_default']) && $data['is_default']) {
                CertificateTemplate::where('document_type_id', $data['document_type_id'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Auto-extract tags from template content if not provided
            if (empty($data['available_tags']) && !empty($data['template_content'])) {
                $template = new CertificateTemplate($data);
                $extractedTags = $template->extractTags();
                $data['available_tags'] = $this->formatTags($extractedTags);
            }

            return CertificateTemplate::create($data);
        });
    }

    /**
     * Update a certificate template
     */
    public function update(CertificateTemplate $template, array $data): CertificateTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            // If this is set as default, unset other defaults for this document type
            if (isset($data['is_default']) && $data['is_default'] && !$template->is_default) {
                CertificateTemplate::where('document_type_id', $template->document_type_id)
                    ->where('id', '!=', $template->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            // Auto-extract tags from template content if content changed
            if (isset($data['template_content']) && $data['template_content'] !== $template->template_content) {
                $tempTemplate = new CertificateTemplate($data);
                $extractedTags = $tempTemplate->extractTags();
                $data['available_tags'] = $this->formatTags($extractedTags);
            }

            $template->update($data);
            return $template->fresh();
        });
    }

    /**
     * Delete a certificate template
     */
    public function delete(CertificateTemplate $template): bool
    {
        return DB::transaction(function () use ($template) {
            return $template->delete();
        });
    }

    /**
     * Set a template as default
     */
    public function setAsDefault(CertificateTemplate $template): CertificateTemplate
    {
        return DB::transaction(function () use ($template) {
            // Unset other defaults
            CertificateTemplate::where('document_type_id', $template->document_type_id)
                ->where('id', '!=', $template->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $template->update(['is_default' => true]);
            return $template->fresh();
        });
    }

    /**
     * Process template and replace tags with values
     */
    public function processTemplate(CertificateTemplate $template, array $data): string
    {
        $content = $template->template_content;
        
        // Replace all tags with values
        foreach ($data as $key => $value) {
            $tag = '{{' . $key . '}}';
            $content = str_replace($tag, $value ?? '', $content);
        }

        // Replace any remaining tags with empty string
        $content = preg_replace('/\{\{\w+\}\}/', '', $content);

        return $content;
    }

    /**
     * Get required fields from template
     */
    public function getRequiredFields(CertificateTemplate $template): array
    {
        if ($template->required_fields && is_array($template->required_fields)) {
            return $template->required_fields;
        }

        // Auto-generate required fields from tags
        $tags = $template->extractTags();
        $fields = [];

        foreach ($tags as $tag) {
            // Skip system tags
            if (in_array($tag, ['date', 'time', 'year', 'month', 'day'])) {
                continue;
            }

            $fields[] = [
                'name' => $tag,
                'label' => $this->formatLabel($tag),
                'type' => $this->inferFieldType($tag),
                'required' => true,
                'placeholder' => 'Enter ' . $this->formatLabel($tag),
            ];
        }

        return $fields;
    }

    /**
     * Format tags array with descriptions
     */
    private function formatTags(array $tags): array
    {
        $formatted = [];
        foreach ($tags as $tag) {
            $formatted[] = [
                'tag' => $tag,
                'label' => $this->formatLabel($tag),
                'description' => $this->getTagDescription($tag),
            ];
        }
        return $formatted;
    }

    /**
     * Format tag name to readable label
     */
    private function formatLabel(string $tag): string
    {
        return Str::title(str_replace('_', ' ', $tag));
    }

    /**
     * Get tag description
     */
    private function getTagDescription(string $tag): string
    {
        $descriptions = [
            'name' => 'Full name of the person',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'middle_name' => 'Middle name',
            'age' => 'Age in years',
            'address' => 'Complete address',
            'purok' => 'Purok/Zone name',
            'barangay' => 'Barangay name',
            'municipality' => 'Municipality name',
            'province' => 'Province name',
            'purpose' => 'Purpose of the certification',
            'date' => 'Current date',
            'date_issued' => 'Date of issuance',
            'date_of_birth' => 'Date of birth',
            'date_of_death' => 'Date of death',
            'time' => 'Current time',
            'year' => 'Current year',
            'month' => 'Current month',
            'day' => 'Current day',
            'punong_barangay' => 'Name of Punong Barangay',
            'officer_of_day' => 'Name of Officer of the Day',
            'event_name' => 'Name of the event',
            'event_date' => 'Date of the event',
            'event_time' => 'Time of the event',
            'event_location' => 'Location of the event',
        ];

        return $descriptions[$tag] ?? 'Custom field';
    }

    /**
     * Infer field type from tag name
     */
    private function inferFieldType(string $tag): string
    {
        if (str_contains($tag, 'date')) {
            return 'date';
        }
        if (str_contains($tag, 'time')) {
            return 'time';
        }
        if (str_contains($tag, 'age') || str_contains($tag, 'number')) {
            return 'number';
        }
        if (str_contains($tag, 'email')) {
            return 'email';
        }
        if (str_contains($tag, 'description') || str_contains($tag, 'purpose') || str_contains($tag, 'notes')) {
            return 'textarea';
        }

        return 'text';
    }
}

