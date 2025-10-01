<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResidentAccountUpdateRequest;
use App\Services\ResidentProfileService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('resident/Account');
    }

    public function update(ResidentAccountUpdateRequest $request, ResidentProfileService $service): RedirectResponse
    {
        $service->update($request->user(), $request->validated());

        return redirect()->route('resident.account.edit');
    }
}


