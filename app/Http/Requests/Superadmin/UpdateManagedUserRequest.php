<?php

namespace App\Http\Requests\Superadmin;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'superadmin';
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', 'string', Rule::in(['resident', 'staff', 'admin', 'enforcer', 'superadmin'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->route('user');
            if (! $user instanceof User) {
                return;
            }

            $newRole = (string) $this->input('role');

            if ($user->role === 'superadmin' && $newRole !== 'superadmin') {
                $otherSuperadmins = User::query()
                    ->where('role', 'superadmin')
                    ->where('id', '!=', $user->id)
                    ->count();

                if ($otherSuperadmins < 1) {
                    $validator->errors()->add('role', 'At least one superadmin account must remain in the system.');
                }
            }
        });
    }
}
