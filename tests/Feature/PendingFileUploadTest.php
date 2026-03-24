<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guest cannot store pending upload', function () {
    Storage::fake('public');

    $response = $this->postJson(route('uploads.pending.store'), [
        'purpose' => 'transaction_submission',
        'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ]);

    $response->assertUnauthorized();
});

test('resident can store pending transaction submission upload', function () {
    Storage::fake('public');

    $resident = User::factory()->create(['role' => 'resident']);

    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $response = $this->actingAs($resident)->postJson(route('uploads.pending.store'), [
        'purpose' => 'transaction_submission',
        'file' => $file,
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['id', 'original_name', 'size', 'mime_type']);
    expect($response->json('id'))->not->toBeEmpty();
});

test('staff can delete own pending upload', function () {
    Storage::fake('public');

    $staff = User::factory()->create(['role' => 'staff']);

    $file = UploadedFile::fake()->create('proof.jpg', 50, 'image/jpeg');

    $store = $this->actingAs($staff)->postJson(route('uploads.pending.store'), [
        'purpose' => 'payment_proof',
        'file' => $file,
    ]);

    $store->assertOk();
    $id = $store->json('id');

    $delete = $this->actingAs($staff)->deleteJson(route('uploads.pending.destroy', ['pendingFileUpload' => $id]));

    $delete->assertOk();
    $delete->assertJson(['success' => true]);
});
