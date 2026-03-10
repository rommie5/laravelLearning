<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(string $actionType, $model = null, array $oldValues = null, array $newValues = null)
    {
        $user = Auth::user();
        
        AuditLog::create([
            'user_id' => $user?->id,
            'role' => $user?->getRoleNames()->first(),
            'ip_address' => request()->ip(),
            'action_type' => $actionType,
            'model_affected' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
}
