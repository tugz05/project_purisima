<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

test('staff certificate template management routes are not registered', function () {
    $removed = [
        'staff.document-types.certificate-templates.index',
        'staff.document-types.certificate-templates.create',
        'staff.document-types.certificate-templates.store',
        'staff.document-types.certificate-templates.show',
        'staff.document-types.certificate-templates.edit',
        'staff.document-types.certificate-templates.update',
        'staff.document-types.certificate-templates.destroy',
    ];

    foreach ($removed as $name) {
        expect(Route::has($name))->toBeFalse();
    }
});
