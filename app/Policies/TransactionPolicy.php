<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->id === $transaction->resident_id) {
            return true;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'staff') {
            return $this->staffCanHandle($user, $transaction);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === 'resident';
    }

    /**
     * Staff may update/process a transaction only if they are assigned to its document type
     * (or if the document type has no specific staff assigned — open to all).
     * Admins are always allowed.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        if ($user->role === 'resident') {
            return $user->id === $transaction->resident_id &&
                   in_array($transaction->status, ['pending', 'rejected']);
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'staff') {
            return $this->staffCanHandle($user, $transaction);
        }

        return false;
    }

    /** Same rule applies to deletion. */
    public function delete(User $user, Transaction $transaction): bool
    {
        if ($user->role === 'resident') {
            return $user->id === $transaction->resident_id &&
                   in_array($transaction->status, ['pending']);
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'staff') {
            return $this->staffCanHandle($user, $transaction);
        }

        return false;
    }

    /**
     * Can this staff member act on this transaction?
     *
     * Two-tier check:
     *  1. If the transaction has been claimed by a specific staff member (staff_id is set),
     *     only that person may view or process it.
     *  2. If the transaction is still unassigned (staff_id is null), any staff who is
     *     permitted to handle the document type may act on it.
     */
    private function staffCanHandle(User $user, Transaction $transaction): bool
    {
        // Tier 1 — transaction already claimed by a specific staff member
        if ($transaction->staff_id !== null) {
            return $transaction->staff_id === $user->id;
        }

        // Tier 2 — unassigned transaction: check document-type-level permission
        $transaction->loadMissing('documentType');
        $documentType = $transaction->documentType;

        if (! $documentType) {
            return true;
        }

        return $documentType->canBeHandledBy($user);
    }
}
