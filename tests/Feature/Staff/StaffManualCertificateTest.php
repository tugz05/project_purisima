<?php

use App\Models\CertificateTemplate;
use App\Models\DocumentType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

test('staff can view walk-in certificate page', function () {
    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->get(route('staff.certificates.manual'))
        ->assertOk();
});

test('resident cannot access walk-in certificate page', function () {
    $resident = User::factory()->create(['role' => 'resident']);

    $this->actingAs($resident)
        ->get(route('staff.certificates.manual'))
        ->assertForbidden();
});

test('manual certificate schema uses standard print layout without DB template', function () {
    $code = 'biz_schema_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Business Permit',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 994,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)
        ->getJson(route('staff.certificates.manual.schema', ['document_type_id' => $documentType->id]));

    $response->assertOk();
    expect($response->json('print_layout'))->toBe('standard');
    expect($response->json('fields_source'))->toBe('layout_only');
    expect($response->json('extra_field_count'))->toBe(0);
    $names = array_column($response->json('fields'), 'name');
    expect($names)->toContain('name')->and($names)->toContain('address')->and($names)->toContain('purpose');
});

test('manual certificate schema returns document type fields when required_fields configured', function () {
    $code = 'dyn_schema_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Event permit',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [
            ['label' => 'Event name', 'key' => 'event_name', 'type' => 'text', 'required' => true],
            ['label' => 'Audience', 'key' => 'audience', 'type' => 'select', 'required' => true, 'options' => ['Public', 'Private']],
        ],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 992,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)
        ->getJson(route('staff.certificates.manual.schema', ['document_type_id' => $documentType->id]));

    $response->assertOk();
    expect($response->json('fields_source'))->toBe('layout_with_extras');
    expect($response->json('extra_field_count'))->toBe(2);
    $names = array_column($response->json('fields'), 'name');
    expect($names)->toContain('name')->toContain('address')->toContain('purpose')->toContain('event_name')->toContain('audience');
    $audienceField = null;
    foreach ($response->json('fields') as $f) {
        if (($f['name'] ?? '') === 'audience') {
            $audienceField = $f;
            break;
        }
    }
    expect($audienceField)->not->toBeNull()
        ->and($audienceField['options'] ?? [])->toBe(['Public', 'Private']);
});

test('manual certificate schema uses clearance print layout for barangay clearance', function () {
    $documentType = DocumentType::create([
        'code' => 'barangay_clearance',
        'name' => 'Clearance copy',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 993,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)
        ->getJson(route('staff.certificates.manual.schema', ['document_type_id' => $documentType->id]));

    $response->assertOk();
    expect($response->json('print_layout'))->toBe('clearance');
    $names = array_column($response->json('fields'), 'name');
    expect($names)->toContain('birthdate')->and($names)->toContain('purpose');
});

test('manual certificate load-template builds starter html without DB template', function () {
    $code = 'manual_cert_load_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Manual Cert Load',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 993,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)
        ->postJson(route('staff.certificates.manual.load-template'), [
            'document_type_id' => $documentType->id,
            'field_values' => [
                'name' => 'Ana Cruz',
                'address' => 'Purok 2, Barangay Purisima',
                'purpose' => 'Employment',
            ],
        ]);

    $response->assertOk();
    expect($response->json('content'))->toContain('Ana Cruz')
        ->and($response->json('content'))->toContain('Purok 2');
});

test('manual certificate generate-ai returns html when OpenAI responds', function () {
    config(['services.openai.api_key' => 'sk-test-fake-key']);

    $html = '<p style="text-align:center;margin-bottom:0.5em;"><strong>THIS IS TO CERTIFY that:</strong></p>'
        .'<p><strong>Name:</strong> Ana Cruz</p>'
        .'<p>Additional narrative for the certificate body that meets minimum length requirements for validation.</p>';

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => $html,
                    ],
                ],
            ],
        ], 200),
    ]);

    $code = 'manual_cert_ai_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Manual Cert AI',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 992,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)
        ->postJson(route('staff.certificates.manual.generate-ai'), [
            'document_type_id' => $documentType->id,
            'field_values' => [
                'name' => 'Ana Cruz',
                'address' => 'Somewhere',
                'purpose' => 'Test',
            ],
        ]);

    $response->assertOk();
    expect($response->json('content'))->toContain('THIS IS TO CERTIFY');
});

test('staff can print manual certificate via inertia with print layout prop', function () {
    $code = 'manual_cert_print_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Barangay Clearance',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 991,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->post(route('staff.certificates.manual.print'), [
            'document_type_id' => $documentType->id,
            'content' => '<p>Certificate body for printing.</p>',
            'officer_of_the_day' => null,
        ])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Staff/transactions/PrintCertificate')
            ->where('documentTypeName', 'Barangay Clearance')
            ->where('printLayout', 'clearance')
            ->where('content', '<p>Certificate body for printing.</p>')
            ->where('previewQrUrl', route('certificate.verify.about')));
});

test('manual certificate finalize creates walk-in transaction with generated document body', function () {
    $code = 'manual_finalize_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Manual Finalize Doc',
        'description' => 'Test',
        'fee_amount' => 100,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 989,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->post(route('staff.certificates.manual.finalize'), [
            'document_type_id' => $documentType->id,
            'field_values' => [
                'name' => 'Lina Walkin',
                'address' => 'Barangay Purisima',
                'purpose' => 'ID application',
            ],
            'document_content' => '<p>Certificate HTML saved from wizard.</p>',
            'officer_of_the_day' => null,
        ])
        ->assertRedirect();

    $transaction = Transaction::query()
        ->where('document_type_id', $documentType->id)
        ->whereNull('resident_id')
        ->latest('id')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->status)->toBe('in_progress')
        ->and($transaction->staff_id)->toBe($staff->id)
        ->and(data_get($transaction->resident_input_data, '__manual_requestor.full_name'))->toBe('Lina Walkin')
        ->and(data_get($transaction->generated_document_data, 'content'))->toContain('Certificate HTML saved from wizard');
});

test('manual certificate finalize accepts full_name when document type uses that key', function () {
    $code = 'manual_finalize_fname_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Custom fields doc',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [
            ['label' => 'Full name', 'key' => 'full_name', 'type' => 'text', 'required' => true],
            ['label' => 'Purpose', 'key' => 'purpose', 'type' => 'textarea', 'required' => true],
        ],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 987,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->post(route('staff.certificates.manual.finalize'), [
            'document_type_id' => $documentType->id,
            'field_values' => [
                'full_name' => 'Jam Walkin',
                'purpose' => 'Identification',
            ],
            'document_content' => '<p>Certificate HTML.</p>',
            'officer_of_the_day' => null,
        ])
        ->assertRedirect();

    $transaction = Transaction::query()
        ->where('document_type_id', $documentType->id)
        ->whereNull('resident_id')
        ->latest('id')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and(data_get($transaction->resident_input_data, '__manual_requestor.full_name'))->toBe('Jam Walkin');
});

test('manual certificate finalize requires requestor name in field values', function () {
    $code = 'manual_finalize_noname_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Manual No Name',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 988,
        'notes' => null,
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $this->actingAs($staff)
        ->post(route('staff.certificates.manual.finalize'), [
            'document_type_id' => $documentType->id,
            'field_values' => [
                'name' => '   ',
                'address' => 'Somewhere',
                'purpose' => 'Test',
            ],
            'document_content' => '<p>Body</p>',
            'officer_of_the_day' => null,
        ])
        ->assertSessionHasErrors('field_values.name');
});

test('staff can load default certificate template for a walk-in transaction', function () {
    $code = 'tx_walk_in_tpl_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Walk-in Template Doc',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 990,
        'notes' => null,
    ]);

    CertificateTemplate::create([
        'document_type_id' => $documentType->id,
        'name' => 'Default walk-in template',
        'description' => null,
        'template_content' => '<p>THIS CERTIFIES {{name}} of {{purok}}</p>',
        'available_tags' => null,
        'required_fields' => null,
        'is_active' => true,
        'is_default' => true,
        'sort_order' => 0,
    ]);

    $transaction = Transaction::factory()->create([
        'resident_id' => null,
        'document_type_id' => $documentType->id,
        'type' => $code,
        'title' => 'Walk-in cert test',
        'status' => 'in_progress',
        'resident_input_data' => [
            '__manual_requestor' => [
                'full_name' => 'Pedro Walkin',
                'purok' => 'Purok 5',
            ],
        ],
    ]);

    $staff = User::factory()->create(['role' => 'staff']);

    $response = $this->actingAs($staff)
        ->postJson(route('staff.transactions.load-template', $transaction));

    $response->assertOk();
    expect($response->json('content'))->toContain('Pedro Walkin')
        ->and($response->json('content'))->toContain('Purok 5')
        ->and($response->json('template_name'))->toBe('Default walk-in template');
});
