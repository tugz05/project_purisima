<?php

use App\Models\DocumentType;

test('normalizes legacy string entries to field definitions', function () {
    $out = DocumentType::normalizeDynamicInputFields(['Purpose', 'Business Name']);

    expect($out)->toHaveCount(2)
        ->and($out[0]['label'])->toBe('Purpose')
        ->and($out[0]['key'])->toBe('purpose')
        ->and($out[0]['type'])->toBe('text')
        ->and($out[0]['required'])->toBeTrue()
        ->and($out[1]['key'])->toBe('business_name');
});

test('normalizes structured field definitions including select options', function () {
    $out = DocumentType::normalizeDynamicInputFields([
        ['label' => 'Reason', 'type' => 'textarea', 'required' => false, 'key' => 'reason'],
        ['label' => 'Category', 'type' => 'select', 'options' => ['A', 'B']],
    ]);

    expect($out[0]['required'])->toBeFalse()
        ->and($out[1]['type'])->toBe('select')
        ->and($out[1]['options'])->toBe(['A', 'B']);
});
