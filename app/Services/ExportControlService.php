<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\User;

class ExportControlService
{
    /**
     * Check if a user can export a specific contract.
     * Returns true, false, or 'requires_head', 'requires_admin'
     */
    public function canExport(User $user, Contract $contract)
    {
        if ($user->hasRole('Admin')) {
            // Admin can export system data, but contract content export is limited
            return true; // We'll log it as "Admin Oversight"
        }

        if ($user->hasRole('Head')) {
            // Head can export all, but sensitive might flag admin oversight
            return true;
        }

        if ($user->hasRole('Officer')) {
            switch ($contract->priority) {
                case 'low':
                case 'medium':
                    return true;
                case 'high':
                    return 'requires_head';
                case 'sensitive':
                    return 'requires_head_admin';
            }
        }

        return false;
    }
}
