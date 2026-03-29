<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

it('stores google given_name and family_name for new oauth user', function () {
    $oauthUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-name-1');
    $oauthUser->shouldReceive('getNickname')->andReturn(null);
    $oauthUser->shouldReceive('getName')->andReturn('Juan Dela Cruz');
    $oauthUser->shouldReceive('getEmail')->andReturn('google-names@example.com');
    $oauthUser->shouldReceive('getAvatar')->andReturn(null);
    $oauthUser->shouldReceive('getRaw')->andReturn([
        'given_name' => 'Juan',
        'family_name' => 'Dela Cruz',
    ]);

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($oauthUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('oauth.callback', ['provider' => 'google']))
        ->assertRedirect(route('registration.verify-location.show', absolute: false));

    $row = User::query()->where('email', 'google-names@example.com')->first();
    expect($row)->not->toBeNull()
        ->and($row->first_name)->toBe('Juan')
        ->and($row->last_name)->toBe('Dela Cruz');
});

it('backfills name parts for existing resident on oauth when split fields are empty', function () {
    $user = User::factory()->create([
        'email' => 'backfill@example.com',
        'role' => 'resident',
    ]);

    DB::table('users')->where('id', $user->id)->update([
        'first_name' => null,
        'middle_name' => null,
        'last_name' => null,
        'name' => 'Legacy User',
    ]);

    $user->refresh();

    $oauthUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-backfill');
    $oauthUser->shouldReceive('getNickname')->andReturn(null);
    $oauthUser->shouldReceive('getName')->andReturn('Maria Santos');
    $oauthUser->shouldReceive('getEmail')->andReturn('backfill@example.com');
    $oauthUser->shouldReceive('getAvatar')->andReturn(null);
    $oauthUser->shouldReceive('getRaw')->andReturn([
        'given_name' => 'Maria',
        'family_name' => 'Santos',
    ]);

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($oauthUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('oauth.callback', ['provider' => 'google']))
        ->assertRedirect(route('resident.onboarding.show', absolute: false));

    $user->refresh();
    expect($user->first_name)->toBe('Maria')
        ->and($user->last_name)->toBe('Santos');
});
