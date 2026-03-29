<?php

use App\Services\AIDocumentGeneratorService;
use ReflectionClass;

it('includes a bullet checklist and emphasis rules for required dynamic fields', function () {
    $service = new AIDocumentGeneratorService;
    $method = (new ReflectionClass(AIDocumentGeneratorService::class))->getMethod('buildPrompt');
    $method->setAccessible(true);

    $inputFieldDefinitions = [
        [
            'key' => 'purpose',
            'label' => 'Purpose of certificate',
            'type' => 'textarea',
            'required' => true,
            'placeholder' => null,
            'options' => [],
        ],
        [
            'key' => 'remarks',
            'label' => 'Remarks',
            'type' => 'text',
            'required' => false,
            'placeholder' => null,
            'options' => [],
        ],
    ];

    $requestData = [
        'purpose' => 'Employment abroad',
        'remarks' => 'Rush processing',
    ];

    $prompt = $method->invoke(
        $service,
        ['name' => 'Sample Doc', 'description' => 'Test description'],
        [
            'name' => 'Maria Santos',
            'barangay' => 'Barangay Purisima',
        ],
        $requestData,
        [],
        $inputFieldDefinitions,
        null,
    );

    expect($prompt)
        ->toContain('REQUIRED DYNAMIC FIELDS (FROM THE OFFICIAL FORM')
        ->toContain('Purpose of certificate')
        ->toContain('Employment abroad')
        ->toContain('OPTIONAL DYNAMIC FIELDS')
        ->toContain('Remarks: Rush processing')
        ->toContain('wrap names, ID numbers, dates, purposes')
        ->toContain('Required field labels for this document type: Purpose of certificate');
});

it('lists required fields with no value submitted without omitting the checklist', function () {
    $service = new AIDocumentGeneratorService;
    $method = (new ReflectionClass(AIDocumentGeneratorService::class))->getMethod('buildPrompt');
    $method->setAccessible(true);

    $inputFieldDefinitions = [
        [
            'key' => 'tax_id',
            'label' => 'Tax identification number',
            'type' => 'text',
            'required' => true,
            'placeholder' => null,
            'options' => [],
        ],
    ];

    $prompt = $method->invoke(
        $service,
        ['name' => 'Tax Cert', 'description' => ''],
        ['name' => 'Juan'],
        [],
        [],
        $inputFieldDefinitions,
        null,
    );

    expect($prompt)
        ->toContain('Tax identification number')
        ->toContain('(no value submitted)');
});
