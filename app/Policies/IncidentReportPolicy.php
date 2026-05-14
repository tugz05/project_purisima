<?php

namespace App\Policies;

use App\Models\IncidentReport;
use App\Models\User;

class IncidentReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Both residents and staff can view reports
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IncidentReport $incidentReport): bool
    {
        // Residents can only view their own reports
        if ($user->role === 'resident') {
            return $incidentReport->resident_id === $user->id;
        }

        // Staff can view all reports
        return in_array($user->role, ['staff', 'admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'resident';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IncidentReport $incidentReport): bool
    {
        // Residents can update their own reports (for location updates)
        if ($user->role === 'resident') {
            return $incidentReport->resident_id === $user->id;
        }

        // Staff can update all reports
        return in_array($user->role, ['staff', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IncidentReport $incidentReport): bool
    {
        // Only staff/admin can delete reports
        return in_array($user->role, ['staff', 'admin']);
    }
}
