<?php

use App\Models\Tenant;
use App\Models\User;
use App\Database\TenantConnectionManager;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Physical Isolation...\n";

// 1. Check Landlord Connection
try {
    $tenantCount = Tenant::count();
    echo "Landlord connection OK. Found $tenantCount tenants.\n";
} catch (\Exception $e) {
    echo "Landlord connection FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Test Switching to STORE001
try {
    TenantConnectionManager::switchToTenant('STORE001');
    $userCount = User::count();
    echo "Switch to STORE001 OK. Found $userCount users in tenant DB.\n";
    
    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        echo "Found admin user in STORE001 DB: {$admin->username}\n";
    } else {
        echo "No admin user found in STORE001 DB.\n";
    }
} catch (\Exception $e) {
    echo "Switch to STORE001 FAILED: " . $e->getMessage() . "\n";
}

// 3. Verify Landlord models didn't switch (Should still point to landlord.sqlite)
try {
    $landlordTenant = Tenant::first();
    echo "Landlord model still accessible: {$landlordTenant->tenant_code}\n";
} catch (\Exception $e) {
    echo "Landlord model access FAILED after switch: " . $e->getMessage() . "\n";
}

echo "Verification COMPLETE.\n";
