<?php

use App\Services\RegistrationGeoService;

it('accepts coordinates inside the Tago bounding box', function () {
    $service = app(RegistrationGeoService::class);

    expect($service->isWithinTagoSurigaoDelSurBounds(9.0211, 126.2318))->toBeTrue();
});

it('rejects coordinates outside the Tago bounding box', function () {
    $service = app(RegistrationGeoService::class);

    expect($service->isWithinTagoSurigaoDelSurBounds(14.5995, 120.9842))->toBeFalse();
});
