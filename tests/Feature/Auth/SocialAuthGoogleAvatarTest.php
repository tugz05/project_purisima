<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

it('stores google avatar for new oauth user', function () {
    $oauthUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-xyz');
    $oauthUser->shouldReceive('getNickname')->andReturn(null);
    $oauthUser->shouldReceive('getName')->andReturn('Oauth User');
    $oauthUser->shouldReceive('getEmail')->andReturn('newoauth@example.com');
    $oauthUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/a/test');

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($oauthUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('oauth.callback', ['provider' => 'google']))
        ->assertRedirect();

    expect(User::query()->where('email', 'newoauth@example.com')->value('photo_url'))
        ->toBe('https://lh3.googleusercontent.com/a/test');
});

it('does not replace storage photo with google avatar', function () {
    User::factory()->create([
        'email' => 'keep@example.com',
        'photo_url' => '/storage/photos/local.jpg',
    ]);

    $oauthUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-abc');
    $oauthUser->shouldReceive('getNickname')->andReturn(null);
    $oauthUser->shouldReceive('getName')->andReturn('Keep User');
    $oauthUser->shouldReceive('getEmail')->andReturn('keep@example.com');
    $oauthUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/a/new');

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('user')->andReturn($oauthUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('oauth.callback', ['provider' => 'google']))
        ->assertRedirect();

    expect(User::query()->where('email', 'keep@example.com')->value('photo_url'))
        ->toBe('/storage/photos/local.jpg');
});
