<?php

use App\Services\CertificateVerificationService;

test('find by verification token returns null for invalid format without querying', function () {
    $service = new CertificateVerificationService;

    expect($service->findTransactionByVerificationToken(''))->toBeNull()
        ->and($service->findTransactionByVerificationToken('not-a-uuid'))->toBeNull()
        ->and($service->findTransactionByVerificationToken(str_repeat('a', 100)))->toBeNull();
});
