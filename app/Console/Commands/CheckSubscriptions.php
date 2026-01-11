<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientPricing;
use Carbon\Carbon;

class CheckSubscriptions extends Command
{
    protected $signature = 'subscriptions:check';
    protected $description = 'Check and expire outdated subscriptions';

    public function handle()
    {
        $now = Carbon::now();

        // تحديث الاشتراكات المنتهية الصلاحية
        ClientPricing::where('status', 'active')
            ->where(function($query) use ($now) {
                $query->where('expired_at', '<=', $now)
                      ->orWhere('ramin_order', '<=', 0);
            })
            ->update(['status' => 'expired']);

        $this->info('Subscriptions checked successfully.');
    }
}
