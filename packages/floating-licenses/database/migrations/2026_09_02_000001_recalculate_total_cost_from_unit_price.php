<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * purchase_cost on a license is the per-unit (per seat) price, so a pool's
 * total_cost must be purchase_cost × pool_size. Earlier syncs stored the raw
 * purchase_cost as total_cost; this rewrites existing configs and realigns
 * the allocated_cost snapshots on active allocations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $configs = DB::table('floating_license_configs')
            ->join('licenses', 'licenses.id', '=', 'floating_license_configs.license_id')
            ->whereNotNull('licenses.purchase_cost')
            ->select([
                'floating_license_configs.id',
                'floating_license_configs.license_id',
                'floating_license_configs.pool_size',
                'floating_license_configs.cost_mode',
                'licenses.purchase_cost',
            ])
            ->get();

        foreach ($configs as $config) {
            $total = round(((float) $config->purchase_cost) * max(1, (int) $config->pool_size), 2);

            DB::table('floating_license_configs')
                ->where('id', $config->id)
                ->update(['total_cost' => $total, 'updated_at' => now()]);

            if ($total <= 0) {
                continue;
            }

            $active = DB::table('floating_license_allocations')
                ->where('license_id', $config->license_id)
                ->where('status', 'active')
                ->count();

            if ($config->cost_mode === 'active_user') {
                $allocated = $active > 0 ? round($total / $active, 2) : 0.0;
            } else {
                $allocated = ((int) $config->pool_size) > 0 ? round($total / (int) $config->pool_size, 2) : 0.0;
            }

            DB::table('floating_license_allocations')
                ->where('license_id', $config->license_id)
                ->where('status', 'active')
                ->update(['allocated_cost' => $allocated, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Irreversible: the previous total_cost values are not recoverable.
    }
};
