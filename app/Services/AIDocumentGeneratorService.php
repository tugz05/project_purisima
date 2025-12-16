<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDocumentGeneratorService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private string $model = 'gpt-4o';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o');
    }

    /**
     * Generate document content using OpenAI AI
     * This method ONLY uses AI generation - no fallback content
     */
    public function generateDocumentContent(array $documentType, array $residentData, array $requestData = [], array $requiredDocuments = []): string
    {
        // Validate API key
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        $prompt = $this->buildPrompt($documentType, $residentData, $requestData, $requiredDocuments);

        Log::info('Generating content with OpenAI AI', [
            'document_type' => $documentType['name'] ?? 'Unknown',
            'prompt_length' => strlen($prompt),
            'model' => $this->model
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->timeout(30)->post("{$this->baseUrl}/chat/completions", [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI assistant specialized in generating concise, clear, and authentic official Philippine barangay certificate/permit content. Generate EXACTLY 3 paragraphs (approximately 60-100 words total) that are rich in Philippine local context, formal government language, and legal terminology. Use proper Philippine address formats, local terminology (Purok, Barangay, Municipality, Province), and formal government document language. Be brief and direct.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.4,
            'max_tokens' => 800,
        ]);

        if (!$response->successful()) {
            $errorBody = $response->body();
            $errorData = $response->json();

            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'body' => $errorBody,
                'response' => $errorData
            ]);

            throw new \Exception('OpenAI API request failed: ' . ($errorData['error']['message'] ?? $errorBody));
        }

        $data = $response->json();

        // Check if response has choices
        if (!isset($data['choices']) || empty($data['choices'])) {
            Log::error('OpenAI API: No choices in response', ['response' => $data]);
            throw new \Exception('OpenAI API returned no content choices. Response: ' . json_encode($data));
        }

        // Extract content from OpenAI response
        $content = $data['choices'][0]['message']['content'] ?? '';

        if (empty($content)) {
            Log::error('OpenAI API: Empty content in response', ['response' => $data]);
            throw new \Exception('OpenAI API returned empty content. Response: ' . json_encode($data));
        }

        // Clean up the content - remove any unwanted headers or structure
        $content = $this->cleanGeneratedContent($content);

        if (empty($content) || strlen(strip_tags($content)) < 50) {
            Log::error('OpenAI API: Content too short after cleaning', ['original_length' => strlen($data['choices'][0]['message']['content'] ?? '')]);
            throw new \Exception('Generated content is too short or invalid after cleaning.');
        }

        Log::info('Successfully generated content with OpenAI AI', [
            'content_length' => strlen($content)
        ]);

        return $content;
    }

    /**
     * Build the prompt for OpenAI AI
     * Optimized for generating only the main content body of the certificate
     */
    private function buildPrompt(array $documentType, array $residentData, array $requestData = [], array $requiredDocuments = []): string
    {
        $documentName = $documentType['name'];
        $description = $documentType['description'] ?? '';
        $currentDate = now()->format('F d, Y');
        $currentYear = now()->format('Y');
        $barangay = $residentData['barangay'] ?? 'Barangay Purisima';
        $municipality = $residentData['municipality'] ?? 'Tago';
        $province = $residentData['province'] ?? 'Surigao del Sur';
        $purok = $residentData['purok'] ?? '';

        $prompt = "You are an AI assistant specialized in generating concise, clear, and authentic official Philippine barangay certificate/permit content. ";
        $prompt .= "Generate CONCISE but COMPLETE content (2-3 paragraphs, approximately 200-300 words) that is rich in Philippine local context, formal government language, and legal terminology.\n\n";

        $prompt .= "IMPORTANT INSTRUCTIONS:\n";
        $prompt .= "- Generate ONLY the main certification/certificate content body (NO headers, titles, signatures, or structural elements)\n";
        $prompt .= "- The content should be CONCISE but COMPLETE (aim for 2-3 paragraphs, approximately 200-300 words)\n";
        $prompt .= "- Use authentic Philippine government document language with proper legal terminology\n";
        $prompt .= "- Include brief references to Philippine laws and local government units\n";
        $prompt .= "- Make it professional, clear, and appropriate for official use\n\n";

        $prompt .= "DOCUMENT TYPE: {$documentName}\n";
        if ($description) {
            $prompt .= "DESCRIPTION: {$description}\n";
        }

        $prompt .= "\nRESIDENT INFORMATION TO INCLUDE (USE ALL OF THESE IN DETAIL):\n";
        foreach ($residentData as $key => $value) {
            if (!empty($value) && !in_array($key, ['email', 'phone'])) {
                $prompt .= "- {$key}: {$value}\n";
            }
        }

        if (!empty($requestData)) {
            $prompt .= "\nADDITIONAL INFORMATION PROVIDED BY RESIDENT (ELABORATE ON THESE):\n";
            foreach ($requestData as $key => $value) {
                if (!empty($value)) {
                    $prompt .= "- {$key}: {$value}\n";
                }
            }
        }

        if (!empty($requiredDocuments)) {
            $prompt .= "\nREQUIRED DOCUMENTS SUBMITTED (MENTION THESE):\n";
            foreach ($requiredDocuments as $doc) {
                $prompt .= "- {$doc}\n";
            }
        }

        $day = now()->format('jS');
        $month = now()->format('F');
        $year = now()->format('Y');
        
        $prompt .= "\nCONTENT REQUIREMENTS (FOLLOW THIS EXACT FORMAT):\n";
        $prompt .= "Generate EXACTLY 3 paragraphs following this structure:\n\n";
        
        $prompt .= "PARAGRAPH 1: Start with 'THIS IS TO CERTIFY that' followed by the certification statement. ";
        $prompt .= "Include the resident's name: " . ($residentData['name'] ?? 'N/A') . ". ";
        if (!empty($requestData)) {
            $prompt .= "Include relevant business/activity details from the request data if applicable. ";
        }
        $prompt .= "Include address: ";
        if (!empty($purok)) {
            $prompt .= "Purok {$purok}, ";
        }
        $prompt .= "{$barangay}, {$municipality}, {$province}, Philippines. ";
        $prompt .= "Keep this paragraph factual and concise (2-3 sentences maximum).\n\n";
        
        $prompt .= "PARAGRAPH 2: Start with 'This certification is being issued upon the request of' followed by the purpose. ";
        if (!empty($requestData['purpose'])) {
            $prompt .= "Mention: " . $requestData['purpose'] . ". ";
        }
        $prompt .= "End with 'and for whatever legal purpose it may serve best.' ";
        $prompt .= "Keep this paragraph to one sentence only.\n\n";
        
        $prompt .= "PARAGRAPH 3: Start with 'Issued this {$day} day of {$month}, {$year}' ";
        $prompt .= "and end with 'at Barangay Hall of {$barangay}, {$municipality}, {$province}.' ";
        $prompt .= "Keep this paragraph to one sentence only.\n\n";
        
        $prompt .= "IMPORTANT:\n";
        $prompt .= "- Generate EXACTLY 3 paragraphs, no more, no less\n";
        $prompt .= "- Do NOT include 'TO WHOM IT MAY CONCERN:' - start directly with 'THIS IS TO CERTIFY that'\n";
        $prompt .= "- Each paragraph should be concise (1-2 sentences)\n";
        $prompt .= "- Total word count should be approximately 60-100 words\n";
        $prompt .= "- Use formal, official Philippine government document language\n";
        $prompt .= "- Use proper Philippine address format\n";
        $prompt .= "- Be direct and factual, avoid unnecessary elaboration\n\n";

        $prompt .= "OUTPUT FORMAT:\n";
        $prompt .= "- Use HTML paragraph tags (<p>) for each paragraph\n";
        $prompt .= "- Use <strong> tags for names, dates, addresses, and important details\n";
        $prompt .= "- Do NOT include headers, titles, signatures, or any structural elements\n";
        $prompt .= "- Do NOT include HTML head, body, style, or document structure tags\n";
        $prompt .= "- Generate EXACTLY 3 paragraphs only\n";
        $prompt .= "- Start directly with 'THIS IS TO CERTIFY that' (NO 'TO WHOM IT MAY CONCERN:')\n";
        $prompt .= "- Keep paragraphs concise (1-2 sentences each)\n\n";

        $prompt .= "PHILIPPINE LOCAL CONTEXT TO INCLUDE:\n";
        $prompt .= "- Brief reference to Local Government Code of 1991 (RA 7160)\n";
        $prompt .= "- Barangay authority and governance\n";
        $prompt .= "- Philippine address format and local terminology (Purok, Barangay, Municipality, Province)\n";
        $prompt .= "- Formal Philippine government document language\n";
        $prompt .= "- Legal validity and official recognition\n\n";

        $prompt .= "Generate a CONCISE but COMPLETE certificate/permit content (2-3 paragraphs, approximately 200-300 words) with Philippine local context:";

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
            'punong_barangay' => 'EMMANUEL P. ISIANG',
            'officer_of_the_day' => $transaction->officer_of_the_day ?? null,
        ];

        // Get required fields data
        $requestData = $transaction->resident_input_data ?? [];
        
        // Include officer of the day in request data if it exists
        if (!empty($transaction->officer_of_the_day)) {
            $requestData['officer_of_the_day'] = $transaction->officer_of_the_day;
        }

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

