<?php

namespace SnipeIt\FloatingLicenses\Http\Transformers;

use App\Helpers\Helper;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation;

/**
 * Transformer for floating license allocations, mirroring the core
 * transformer pattern (explicit field whitelist + id/name sub-objects).
 * Keeps internal model attributes (timestamps, soft-delete state, notes,
 * lease bookkeeping) out of API payloads.
 */
class FloatingLicenseAllocationsTransformer
{
    public function transformAllocation(FloatingLicenseAllocation $allocation): array
    {
        return [
            'id' => (int) $allocation->id,
            'license' => ($allocation->license) ? [
                'id' => (int) $allocation->license->id,
                'name' => e($allocation->license->name),
            ] : null,
            'user' => ($allocation->user) ? [
                'id' => (int) $allocation->user->id,
                'name' => e($allocation->user->display_name),
            ] : null,
            'asset' => ($allocation->asset) ? [
                'id' => (int) $allocation->asset->id,
                'name' => e($allocation->asset->display_name),
            ] : null,
            'status' => $allocation->status,
            'allocated_at' => Helper::getFormattedDateObject($allocation->allocated_at, 'datetime'),
            'expires_at' => Helper::getFormattedDateObject($allocation->expires_at, 'datetime'),
            'allocated_cost' => $allocation->allocated_cost,
        ];
    }

    /**
     * @param  iterable<FloatingLicenseAllocation>  $allocations
     */
    public function transformAllocations(iterable $allocations): array
    {
        $array = [];

        foreach ($allocations as $allocation) {
            $array[] = $this->transformAllocation($allocation);
        }

        return $array;
    }
}
