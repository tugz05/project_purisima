<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->id === $transaction->resident_id ||
               in_array($user->role, ['admin', 'staff']);
    }

    public function create(User $user): bool
    {
        return $user->role === 'resident';
    }

    public function update(User $user, Transaction $transaction): bool
    {
        if ($user->role === 'resident') {
            return $user->id === $transaction->resident_id &&
                   in_array($transaction->status, ['pending']);
        }

        return in_array($user->role, ['admin', 'staff']);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        if ($user->role === 'resident') {
            return $user->id === $transaction->resident_id &&
                   in_array($transaction->status, ['pending']);
        }

        return in_array($user->role, ['admin', 'staff']);
    }
}
