<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDocumentGeneratorService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Generate document content using Gemini AI
     * This method ONLY uses AI generation - no fallback content
     */
    public function generateDocumentContent(array $documentType, array $residentData, array $requestData = [], array $requiredDocuments = []): string
    {
        // Validate API key
        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }

        $prompt = $this->buildPrompt($documentType, $residentData, $requestData, $requiredDocuments);

        Log::info('Generating content with Gemini AI', [
            'document_type' => $documentType['name'] ?? 'Unknown',
            'prompt_length' => strlen($prompt)
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$this->baseUrl}/models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4,
                'topK' => 32,
                'topP' => 1.0,
                'maxOutputTokens' => 2048,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ]);

        if (!$response->successful()) {
            $errorBody = $response->body();
            $errorData = $response->json();

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $errorBody,
                'response' => $errorData
            ]);

            throw new \Exception('Gemini API request failed: ' . ($errorData['error']['message'] ?? $errorBody));
        }

        $data = $response->json();

        // Check if response has candidates
        if (!isset($data['candidates']) || empty($data['candidates'])) {
            Log::error('Gemini API: No candidates in response', ['response' => $data]);
            throw new \Exception('Gemini API returned no content candidates. Response: ' . json_encode($data));
        }

        // Check for safety ratings that blocked content
        if (isset($data['candidates'][0]['safetyRatings'])) {
            $blocked = false;
            foreach ($data['candidates'][0]['safetyRatings'] as $rating) {
                if ($rating['blocked'] ?? false) {
                    $blocked = true;
                    Log::error('Gemini API: Content blocked by safety filter', ['rating' => $rating]);
                    throw new \Exception('Content generation was blocked by safety filter: ' . ($rating['category'] ?? 'Unknown'));
                }
            }
        }

        // Extract content from Gemini response
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($content)) {
            Log::error('Gemini API: Empty content in response', ['response' => $data]);
            throw new \Exception('Gemini API returned empty content. Response: ' . json_encode($data));
        }

        // Clean up the content - remove any unwanted headers or structure
        $content = $this->cleanGeneratedContent($content);

        if (empty($content) || strlen(strip_tags($content)) < 50) {
            Log::error('Gemini API: Content too short after cleaning', ['original_length' => strlen($data['candidates'][0]['content']['parts'][0]['text'] ?? '')]);
            throw new \Exception('Generated content is too short or invalid after cleaning.');
        }

        Log::info('Successfully generated content with Gemini AI', [
            'content_length' => strlen($content)
        ]);

        return $content;
    }

    /**
     * Build the prompt for Gemini AI
     * Optimized for generating only the main content body of the certificate
     */
    private function buildPrompt(array $documentType, array $residentData, array $requestData = [], array $requiredDocuments = []): string
    {
        $documentName = $documentType['name'];
        $description = $documentType['description'] ?? '';
        $currentDate = now()->format('F d, Y');

        $prompt = "You are an AI assistant specialized in generating official Philippine barangay certificate/permit content. ";
        $prompt .= "Generate ONLY the main body content text for a {$documentName}.\n\n";

        $prompt .= "IMPORTANT: Generate ONLY the main certification/certificate content. ";
        $prompt .= "DO NOT include headers, document titles, salutations, signature blocks, or any structural elements. ";
        $prompt .= "Generate ONLY the paragraph content that certifies or permits the information below.\n\n";

        $prompt .= "DOCUMENT TYPE: {$documentName}\n";
        if ($description) {
            $prompt .= "DESCRIPTION: {$description}\n";
        }

        $prompt .= "\nRESIDENT INFORMATION TO INCLUDE:\n";
        foreach ($residentData as $key => $value) {
            if (!empty($value) && !in_array($key, ['email', 'phone'])) {
                $prompt .= "- {$key}: {$value}\n";
            }
        }

        if (!empty($requestData)) {
            $prompt .= "\nADDITIONAL INFORMATION PROVIDED BY RESIDENT:\n";
            foreach ($requestData as $key => $value) {
                if (!empty($value)) {
                    $prompt .= "- {$key}: {$value}\n";
                }
            }
        }

        if (!empty($requiredDocuments)) {
            $prompt .= "\nREQUIRED DOCUMENTS SUBMITTED:\n";
            foreach ($requiredDocuments as $doc) {
                $prompt .= "- {$doc}\n";
            }
        }

        $prompt .= "\nCONTENT REQUIREMENTS:\n";
        $prompt .= "1. Start with 'TO WHOM IT MAY CONCERN:' followed by the certification/permit text\n";
        $prompt .= "2. Include the resident's full name: " . ($residentData['name'] ?? 'N/A') . "\n";
        $prompt .= "3. Include the resident's address: " . ($residentData['address'] ?? 'N/A') . "\n";
        if (!empty($residentData['purok'])) {
            $prompt .= "4. Mention Purok: " . $residentData['purok'] . "\n";
        }
        $prompt .= "5. Include all relevant information from the additional fields provided above\n";
        if (!empty($requiredDocuments)) {
            $prompt .= "6. Acknowledge that required documents have been submitted\n";
        }
        $prompt .= "7. Include the issuance date: {$currentDate}\n";
        $prompt .= "8. Include the location: Barangay Purisima, Tago, Surigao del Sur\n";
        $prompt .= "9. Use formal, official Philippine government document language\n";
        $prompt .= "10. Make it professional, legal, and appropriate for official use\n\n";

        $prompt .= "OUTPUT FORMAT:\n";
        $prompt .= "- Use HTML paragraph tags (<p>) for each paragraph\n";
        $prompt .= "- Use <strong> tags for names, dates, and important details\n";
        $prompt .= "- Do NOT include headers, titles, signatures, or any structural elements\n";
        $prompt .= "- Do NOT include HTML head, body, style, or document structure tags\n";
        $prompt .= "- Generate ONLY the main content paragraphs\n";
        $prompt .= "- Start directly with the salutation and certification text\n\n";

        $prompt .= "Generate the certificate/permit MAIN CONTENT ONLY (HTML formatted paragraphs, no structure):";

        return $prompt;
    }

    /**
     * Generate certificate content for a transaction
     */
    public function generateCertificateContent(\App\Models\Transaction $transaction): string
    {
        $transaction->load(['resident', 'documentType']);

        if (!$transaction->documentType) {
            throw new \Exception('Document type not found for this transaction.');
        }

        $documentType = [
            'name' => $transaction->documentType->name,
            'description' => $transaction->documentType->description,
        ];

        // Get resident data
        $resident = $transaction->resident;
        $nameParts = explode(' ', $resident->name ?? '');
        $firstName = $nameParts[0] ?? '';
        $lastName = end($nameParts) ?? '';

        $residentData = [
            'name' => $resident->name ?? '',
            'first_name' => $resident->first_name ?? $firstName,
            'middle_name' => $resident->middle_name ?? '',
            'last_name' => $resident->last_name ?? $lastName,
            'email' => $resident->email ?? '',
            'phone' => $resident->phone ?? '',
            'address' => $resident->address ?? '',
            'purok' => $resident->purok ?? '',
            'barangay' => $resident->barangay ?? 'Barangay Purisima',
            'municipality' => $resident->municipality ?? 'Tago',
            'province' => $resident->province ?? 'Surigao del Sur',
        ];

        // Get required fields data
        $requestData = $transaction->resident_input_data ?? [];

        // Get required documents
        $requiredDocuments = $transaction->required_documents ?? [];

        return $this->generateDocumentContent($documentType, $residentData, $requestData, $requiredDocuments);
    }

    /**
     * Clean generated content to ensure it's only the main body
     */
    private function cleanGeneratedContent(string $content): string
    {
        // Remove any HTML document structure
        $content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $content);
        $content = preg_replace('/<html[^>]*>/i', '', $content);
        $content = preg_replace('/<\/html>/i', '', $content);
        $content = preg_replace('/<head>.*?<\/head>/is', '', $content);
        $content = preg_replace('/<body[^>]*>/i', '', $content);
        $content = preg_replace('/<\/body>/i', '', $content);
        $content = preg_replace('/<style>.*?<\/style>/is', '', $content);

        // Remove common header patterns
        $content = preg_replace('/<h[1-6][^>]*>.*?<\/h[1-6]>/is', '', $content);
        $content = preg_replace('/<div[^>]*class=["\'].*header.*["\'][^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/<div[^>]*class=["\'].*title.*["\'][^>]*>.*?<\/div>/is', '', $content);

        // Remove signature blocks
        $content = preg_replace('/<div[^>]*class=["\'].*signature.*["\'][^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/EMMANUEL P\. ISIANG.*?Punong Barangay/is', '', $content);

        // Remove any remaining structural divs that might contain headers
        $content = preg_replace('/<div[^>]*>[\s]*Republic of the Philippines.*?<\/div>/is', '', $content);
        $content = preg_replace('/<div[^>]*>[\s]*BARANGAY PURISIMA.*?<\/div>/is', '', $content);

        // Trim whitespace
        $content = trim($content);

        return $content;
    }

    /**
     * Generate dynamic form fields based on document type
     */
    public function generateFormFields(string $documentType): array
    {
        $baseFields = [
            'name' => ['label' => 'Full Name', 'type' => 'text', 'required' => true],
            'address' => ['label' => 'Complete Address', 'type' => 'textarea', 'required' => true],
            'birthdate' => ['label' => 'Birth Date', 'type' => 'date', 'required' => false],
            'civil_status' => ['label' => 'Civil Status', 'type' => 'select', 'options' => ['Single', 'Married', 'Widowed', 'Divorced'], 'required' => false],
            'nationality' => ['label' => 'Nationality', 'type' => 'text', 'required' => false, 'default' => 'Filipino'],
            'purpose' => ['label' => 'Purpose of Document', 'type' => 'textarea', 'required' => true],
        ];

        // Add specific fields based on document type
        $specificFields = $this->getDocumentSpecificFields($documentType);

        return array_merge($baseFields, $specificFields);
    }

    /**
     * Get document-specific fields
     */
    private function getDocumentSpecificFields(string $documentType): array
    {
        $fields = [];

        switch (strtolower($documentType)) {
            case 'barangay clearance':
                $fields = [
                    'business_type' => ['label' => 'Business/Activity Type', 'type' => 'text', 'required' => true],
                    'validity_period' => ['label' => 'Validity Period (months)', 'type' => 'number', 'required' => false, 'default' => 6],
                ];
                break;

            case 'certificate of residency':
                $fields = [
                    'residence_period' => ['label' => 'How long residing in barangay', 'type' => 'text', 'required' => false],
                    'previous_address' => ['label' => 'Previous Address (if applicable)', 'type' => 'textarea', 'required' => false],
                ];
                break;

            case 'certificate of indigency':
                $fields = [
                    'family_income' => ['label' => 'Monthly Family Income', 'type' => 'number', 'required' => true],
                    'family_size' => ['label' => 'Number of Family Members', 'type' => 'number', 'required' => true],
                    'assistance_type' => ['label' => 'Type of Assistance Needed', 'type' => 'select', 'options' => ['Medical', 'Educational', 'Financial', 'Food'], 'required' => true],
                ];
                break;

            case 'barangay zoning certification':
                $fields = [
                    'property_address' => ['label' => 'Property Address', 'type' => 'textarea', 'required' => true],
                    'tax_declaration_no' => ['label' => 'Tax Declaration Number', 'type' => 'text', 'required' => true],
                    'zone_type' => ['label' => 'Zone Type', 'type' => 'select', 'options' => ['Agricultural', 'Residential', 'Commercial', 'Industrial'], 'required' => true],
                ];
                break;

            case 'event permit':
                $fields = [
                    'event_name' => ['label' => 'Event Name', 'type' => 'text', 'required' => true],
                    'event_date' => ['label' => 'Event Date', 'type' => 'date', 'required' => true],
                    'event_time' => ['label' => 'Event Time', 'type' => 'text', 'required' => true],
                    'event_location' => ['label' => 'Event Location', 'type' => 'textarea', 'required' => true],
                    'expected_attendees' => ['label' => 'Expected Number of Attendees', 'type' => 'number', 'required' => false],
                ];
                break;

            case 'certification of death':
                $fields = [
                    'deceased_name' => ['label' => 'Deceased Name', 'type' => 'text', 'required' => true],
                    'date_of_death' => ['label' => 'Date of Death', 'type' => 'date', 'required' => true],
                    'time_of_death' => ['label' => 'Time of Death', 'type' => 'text', 'required' => true],
                    'place_of_death' => ['label' => 'Place of Death', 'type' => 'textarea', 'required' => true],
                    'requestor_relationship' => ['label' => 'Relationship to Deceased', 'type' => 'text', 'required' => true],
                ];
                break;
        }

        return $fields;
    }
}

