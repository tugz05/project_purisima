<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationGeoVerified
{
    /**
     * Residents must complete Tago-area location verification before using the app.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== 'resident') {
            return $next($request);
        }

        if ($user->registration_geo_verified_at !== null) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, [
            'registration.verify-location.show',
            'registration.verify-location.store',
            'logout',
        ], true)) {
            return $next($request);
        }

        return redirect()->route('registration.verify-location.show')
            ->with('error', 'Please confirm you are in Tago, Surigao del Sur to continue.');
    }
}
