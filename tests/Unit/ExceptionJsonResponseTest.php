<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

it('returns json for exceptions when the client expects json', function () {
    Route::get('/__exception_json_probe', function () {
        throw new RuntimeException('probe failure');
    });

    $response = $this->getJson('/__exception_json_probe');

    $response->assertStatus(500);
    $response->assertJsonFragment(['message' => 'probe failure']);
});

it('does not force json for regular browser requests', function () {
    Route::get('/__exception_html_probe', function () {
        throw new RuntimeException('probe failure');
    });

    $response = $this->get('/__exception_html_probe');

    $response->assertStatus(500);
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});
