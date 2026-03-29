<?php

use App\Models\DocumentType;
use App\Models\Transaction;
use Inertia\Testing\AssertableInertia;

test('certificate verification page rejects malformed token', function () {
    $this->get(route('certificate.verify', ['token' => 'not-a-valid-uuid']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CertificateVerification')
            ->where('valid', false));
});

test('certificate verification page rejects unknown uuid', function () {
    $this->get(route('certificate.verify', ['token' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a99']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CertificateVerification')
            ->where('valid', false));
});

test('certificate verification page shows record when token matches transaction', function () {
    $code = 'verify_doc_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Verification Test Doc',
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
        'sort_order' => 900,
        'notes' => null,
    ]);

    $token = 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380a22';

    Transaction::factory()->create([
        'resident_id' => null,
        'document_type_id' => $documentType->id,
        'type' => $code,
        'title' => 'Verify test',
        'status' => 'in_progress',
        'generated_document_data' => [
            'content' => '<p>Cert</p>',
            'verification_token' => $token,
        ],
        'resident_input_data' => [
            '__manual_requestor' => ['full_name' => 'Pat Test'],
        ],
    ]);

    $this->get(route('certificate.verify', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('CertificateVerification')
            ->where('valid', true)
            ->where('documentTypeName', 'Verification Test Doc')
            ->where('requestorDisplay', 'Pat Test'));
});
