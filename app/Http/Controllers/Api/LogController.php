<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BackupLog;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function auditLogs()
    {
        return response()->json(AuditLog::with('user')->latest()->take(100)->get());
    }

    public function backupLogs()
    {
        return response()->json(BackupLog::with('creator')->latest()->get());
    }

    public function paymentLogs()
    {
        return response()->json(PaymentLog::latest()->take(100)->get());
    }
}
