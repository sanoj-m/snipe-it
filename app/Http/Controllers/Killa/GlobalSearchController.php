<?php

namespace App\Http\Controllers\Killa;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * [killa global-search] KCP-061: aggregated typeahead endpoint for the header
 * global search. One request fans out across the six header modules (same
 * permission gates as the nav chips), returning up to 5 matches per group.
 * Minimum query length 2 — the JS debounces and the UI renders groups.
 */
class GlobalSearchController extends Controller
{
    private const PER_GROUP_LIMIT = 5;

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $groups = [];

        if (Gate::allows('index', Asset::class)) {
            $assets = Company::scopeCompanyables(Asset::query())
                ->where(fn ($query) => $query->where('asset_tag', 'LIKE', $like)
                    ->orWhere('name', 'LIKE', $like)
                    ->orWhere('serial', 'LIKE', $like))
                ->orderBy('id', 'desc')
                ->limit(self::PER_GROUP_LIMIT)
                ->get(['id', 'asset_tag', 'name', 'serial']);

            if ($assets->isNotEmpty()) {
                $groups[] = [
                    'key' => 'assets',
                    'label' => trans('general.assets'),
                    'results' => $assets->map(fn (Asset $asset) => [
                        'title' => $asset->present()->fullName ?: $asset->asset_tag,
                        'subtitle' => implode(' · ', array_filter([$asset->asset_tag, $asset->serial])),
                        'url' => route('hardware.show', $asset->id),
                    ])->all(),
                ];
            }
        }

        if (Gate::allows('view', License::class)) {
            $licenses = Company::scopeCompanyables(License::query())
                ->where(fn ($query) => $query->where('name', 'LIKE', $like)
                    ->orWhere('serial', 'LIKE', $like)
                    ->orWhere('license_name', 'LIKE', $like)
                    ->orWhere('license_email', 'LIKE', $like))
                ->orderBy('id', 'desc')
                ->limit(self::PER_GROUP_LIMIT)
                ->get(['id', 'name', 'serial']);

            if ($licenses->isNotEmpty()) {
                $groups[] = [
                    'key' => 'licenses',
                    'label' => trans('general.licenses'),
                    'results' => $licenses->map(fn (License $license) => [
                        'title' => $license->name,
                        'subtitle' => $license->serial,
                        'url' => route('licenses.show', $license->id),
                    ])->all(),
                ];
            }
        }

        if (Gate::allows('index', Accessory::class)) {
            $accessories = Company::scopeCompanyables(Accessory::query())
                ->where('name', 'LIKE', $like)
                ->orderBy('id', 'desc')
                ->limit(self::PER_GROUP_LIMIT)
                ->get(['id', 'name']);

            if ($accessories->isNotEmpty()) {
                $groups[] = [
                    'key' => 'accessories',
                    'label' => trans('general.accessories'),
                    'results' => $accessories->map(fn (Accessory $accessory) => [
                        'title' => $accessory->name,
                        'subtitle' => null,
                        'url' => route('accessories.show', $accessory->id),
                    ])->all(),
                ];
            }
        }

        if (Gate::allows('index', Consumable::class)) {
            $consumables = Company::scopeCompanyables(Consumable::query())
                ->where('name', 'LIKE', $like)
                ->orderBy('id', 'desc')
                ->limit(self::PER_GROUP_LIMIT)
                ->get(['id', 'name']);

            if ($consumables->isNotEmpty()) {
                $groups[] = [
                    'key' => 'consumables',
                    'label' => trans('general.consumables'),
                    'results' => $consumables->map(fn (Consumable $consumable) => [
                        'title' => $consumable->name,
                        'subtitle' => null,
                        'url' => route('consumables.show', $consumable->id),
                    ])->all(),
                ];
            }
        }

        if (Gate::allows('view', Component::class)) {
            $components = Company::scopeCompanyables(Component::query())
                ->where(fn ($query) => $query->where('name', 'LIKE', $like)
                    ->orWhere('serial', 'LIKE', $like))
                ->orderBy('id', 'desc')
                ->limit(self::PER_GROUP_LIMIT)
                ->get(['id', 'name', 'serial']);

            if ($components->isNotEmpty()) {
                $groups[] = [
                    'key' => 'components',
                    'label' => trans('general.components'),
                    'results' => $components->map(fn (Component $component) => [
                        'title' => $component->name,
                        'subtitle' => $component->serial,
                        'url' => route('components.show', $component->id),
                    ])->all(),
                ];
            }
        }

        if (Gate::allows('index', User::class)) {
            $users = Company::scopeCompanyables(User::query())
                ->where(fn ($query) => $query->where('first_name', 'LIKE', $like)
                    ->orWhere('last_name', 'LIKE', $like)
                    ->orWhere('username', 'LIKE', $like)
                    ->orWhere('email', 'LIKE', $like))
                ->orderBy('id', 'desc')
                ->limit(self::PER_GROUP_LIMIT)
                ->get(['id', 'first_name', 'last_name', 'username', 'email']);

            if ($users->isNotEmpty()) {
                $groups[] = [
                    'key' => 'users',
                    'label' => trans('general.users'),
                    'results' => $users->map(fn (User $user) => [
                        'title' => trim($user->first_name.' '.$user->last_name) ?: $user->username,
                        'subtitle' => implode(' · ', array_filter([$user->username, $user->email])),
                        'url' => route('users.show', $user->id),
                    ])->all(),
                ];
            }
        }

        return response()->json(['groups' => $groups]);
    }
}
