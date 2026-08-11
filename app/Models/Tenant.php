<?php

namespace App\Models;

use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use Billable, HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'plan',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
        ];
    }

    // Cashier's Billable expects a `name` accessor + an `email` for receipts.
    public function stripeEmail(): ?string
    {
        // Use the first owner's email as the billing contact for receipts.
        return $this->run(function () {
            return \App\Models\User::role('owner')->first()?->email;
        });
    }
}
