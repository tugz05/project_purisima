<?php

use App\Models\DocumentType;
use App\Services\ManualCertificateWizardService;

test('starter html is print body fragment without duplicate salutation', function () {
    $service = new ManualCertificateWizardService;
    $documentType = new DocumentType([
        'code' => 'other_doc',
        'name' => 'Generic permit',
        'required_fields' => [],
    ]);
    $merge = [
        'name' => 'TEST USER',
        'address' => 'Somewhere',
        'purpose' => 'Employment',
        'barangay' => 'Barangay Purisima',
        'municipality' => 'Tago',
        'province' => 'Surigao del Sur',
    ];

    $standardHtml = $service->buildStarterHtmlForDocumentType($documentType, $merge);

    $clearanceType = new DocumentType([
        'code' => 'barangay_clearance',
        'name' => 'Barangay Clearance',
        'required_fields' => [],
    ]);
    $clearanceMerge = array_merge($merge, [
        'birthdate' => '2000-01-01',
        'birthplace' => 'Municipality',
        'civil_status' => 'Single',
    ]);
    $clearanceHtml = $service->buildStarterHtmlForDocumentType($clearanceType, $clearanceMerge);

    expect($standardHtml)->not->toContain('TO WHOM IT MAY CONCERN')
        ->and($standardHtml)->toContain('THIS IS TO CERTIFY')
        ->and($clearanceHtml)->not->toContain('TO WHOM IT MAY CONCERN')
        ->and($clearanceHtml)->toContain('THIS IS TO CERTIFY');
});

test('field definitions merge layout base with document type extras', function () {
    $service = new ManualCertificateWizardService;
    $documentType = new DocumentType([
        'code' => 'event_permit',
        'name' => 'Event permit',
        'required_fields' => [
            ['label' => 'Organizer', 'key' => 'organizer', 'type' => 'text', 'required' => true],
            ['label' => 'Venue', 'key' => 'venue', 'type' => 'textarea', 'required' => false],
            ['label' => 'Duplicate purpose', 'key' => 'purpose', 'type' => 'text', 'required' => true],
        ],
    ]);

    $fields = $service->fieldDefinitionsForDocumentType($documentType);
    $keys = array_column($fields, 'name');

    expect($keys)->toContain('name')->toContain('address')->toContain('purpose')->toContain('remarks')
        ->toContain('organizer')->toContain('venue')
        ->and($service->additionalStaffFieldCount($documentType))->toBe(2);

    $aiDefs = $service->inputFieldDefinitionsForAi($documentType);
    expect(array_column($aiDefs, 'key'))->toContain('organizer')->toContain('venue');
});

test('resolve requestor display name accepts common keys', function () {
    $service = new ManualCertificateWizardService;

    expect($service->resolveRequestorDisplayName(['full_name' => '  Pat  ']))->toBe('Pat')
        ->and($service->resolveRequestorDisplayName(['name' => 'A', 'full_name' => 'B']))->toBe('A');
});
