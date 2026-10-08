<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log(
        string $activityType,
        string $description,
        ?string $relatedTable = null,
        ?int $relatedId = null
    ): void {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'activity_type' => $activityType,
            'description' => $description,
            'related_table' => $relatedTable,
            'related_id' => $relatedId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ]);
    }
}
