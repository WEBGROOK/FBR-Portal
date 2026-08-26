<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public static function log(string $action, string $entityType, ?string $entityId = null, ?array $details = null, ?string $userId = null, ?string $ipAddress = null): void
    {
        try {
            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details,
                'ip_address' => $ipAddress ?? request()->ip(),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to log audit event: ' . $e->getMessage());
        }
    }
}
