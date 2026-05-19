<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $roles)) {
            // Send the user to their own home — never abort with 403 on an Inertia request
            $home = match ($user->role) {
                'admin'    => route('admin.dashboard'),
                'staff'    => route('staff.dashboard'),
                'enforcer' => route('enforcer.dashboard'),
                'resident' => route('resident.dashboard'),
                default    => route('dashboard'),
            };

            return redirect($home);
        }

        return $next($request);
    }
}
