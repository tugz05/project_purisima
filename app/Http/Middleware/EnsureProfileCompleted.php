<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only apply to residents
        if ($user && $user->role === 'resident') {
            // If profile not completed, redirect to onboarding
            if (empty($user->profile_completed_at)) {
                // Allow access to onboarding and account pages
                $allowedRoutes = [
                    'resident.onboarding.show',
                    'resident.onboarding.store',
                    'resident.account.edit',
                    'resident.account.update',
                ];

                $routeName = $request->route()?->getName();

                if (!in_array($routeName, $allowedRoutes)) {
                    return redirect()->route('resident.onboarding.show')
                        ->with('error', 'Please complete your profile first to access this feature.');
                }
            }
        }

        return $next($request);
    }
}
