<?php

namespace Tests\Feature\UAT;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exports\LeaveBalanceLogExport;

class ExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_balance_log_export_class_exists()
    {
        // Lightweight assertion: export class exists and is autoloadable.
        $this->assertTrue(class_exists(LeaveBalanceLogExport::class));
    }
}
