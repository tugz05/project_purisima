<?php

use App\Models\DocumentType;
use App\Models\Transaction;
use App\Models\User;

test('resident transaction validation enforces required dynamic fields from document type', function () {
    $code = 'test_doc_dyn_'.uniqid();

    $documentType = DocumentType::create([
        'code' => $code,
        'name' => 'Test Dynamic Doc',
        'description' => 'Test',
        'fee_amount' => 0,
        'required_documents' => [],
        'required_fields' => [
            [
                'key' => 'purpose',
                'label' => 'Purpose',
                'type' => 'text',
                'required' => true,
                'placeholder' => null,
                'options' => [],
            ],
        ],
        'processing_steps' => ['Submit'],
        'processing_days' => 1,
        'is_active' => true,
        'requires_payment' => false,
        'requires_approval' => false,
        'category' => 'other',
        'sort_order' => 999,
        'notes' => null,
    ]);

    $resident = User::factory()->create([
        'role' => 'resident',
        'profile_completed_at' => now(),
    ]);

    $this->actingAs($resident)
        ->post(route('resident.transactions.store'), [
            'type' => $documentType->code,
            'title' => 'My request',
            'description' => '',
            'required_fields' => [],
            'fee_amount' => 0,
        ])
        ->assertSessionHasErrors('required_fields.purpose');

    $this->actingAs($resident)
        ->post(route('resident.transactions.store'), [
            'type' => $documentType->code,
            'title' => 'My request',
            'description' => '',
            'required_fields' => ['purpose' => 'Travel abroad'],
            'fee_amount' => 0,
        ])
        ->assertRedirect(route('resident.transactions.index'));

    $transaction = Transaction::query()->where('resident_id', $resident->id)->where('type', $documentType->code)->latest('id')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->resident_input_data)->toMatchArray(['purpose' => 'Travel abroad']);
});
