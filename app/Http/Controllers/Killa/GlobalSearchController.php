<?php

namespace App\Http\Controllers\Killa;

use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\CustomField;
use App\Models\Department;
use App\Models\License;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\PredefinedKit;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * [killa global-search] KCP-061: aggregated typeahead endpoint for the header
 * global search. One request fans out across all main entity types (each
 * behind the same permission gate as its UI section), returning up to 5
 * matches per group. Minimum query length 2 — the JS debounces and the UI
 * renders groups.
 *
 * Amended: covers all primary entities (models, categories, locations,
 * companies, departments, manufacturers, suppliers, kits on top of the six
 * header modules) and widens the asset fields (notes, order number).
 */
class GlobalSearchController extends Controller
{
    private const PER_GROUP_LIMIT = 5;

    /**
     * Group specs: permission ability, columns searched, route, FMCS company
     * scoping, and how title/subtitle are built. Order = dropdown order.
     *
     * @return array<string, array{model: class-string<Model>, ability: string, columns: array<int, string>, route: string, scoped: bool, title: callable(Model): string, subtitle: callable(Model): ?string}>
     */
    private function groups(): array
    {
        return [
            'assets' => [
                'model' => Asset::class,
                'ability' => 'index',
                'columns' => ['asset_tag', 'name', 'serial', 'order_number', 'notes'],
                'route' => 'hardware.show',
                'scoped' => true,
                'title' => fn (Asset $asset) => $asset->present()->fullName ?: (string) $asset->asset_tag,
                'subtitle' => fn (Asset $asset) => implode(' · ', array_filter([$asset->asset_tag, $asset->serial])),
            ],
            'licenses' => [
                'model' => License::class,
                'ability' => 'view',
                'columns' => ['name', 'serial', 'license_name', 'license_email', 'order_number', 'notes'],
                'route' => 'licenses.show',
                'scoped' => true,
                'title' => fn (License $license) => (string) $license->name,
                'subtitle' => fn (License $license) => $license->serial,
            ],
            'accessories' => [
                'model' => Accessory::class,
                'ability' => 'index',
                'columns' => ['name', 'model_number', 'legacy_order_number'],
                'route' => 'accessories.show',
                'scoped' => true,
                'title' => fn (Accessory $accessory) => (string) $accessory->name,
                'subtitle' => fn (Accessory $accessory) => $accessory->model_number,
            ],
            'consumables' => [
                'model' => Consumable::class,
                'ability' => 'index',
                'columns' => ['name', 'model_number', 'legacy_order_number'],
                'route' => 'consumables.show',
                'scoped' => true,
                'title' => fn (Consumable $consumable) => (string) $consumable->name,
                'subtitle' => fn (Consumable $consumable) => $consumable->model_number,
            ],
            'components' => [
                'model' => Component::class,
                'ability' => 'view',
                'columns' => ['name', 'serial', 'legacy_order_number'],
                'route' => 'components.show',
                'scoped' => true,
                'title' => fn (Component $component) => (string) $component->name,
                'subtitle' => fn (Component $component) => $component->serial,
            ],
            'users' => [
                'model' => User::class,
                'ability' => 'index',
                'columns' => ['first_name', 'last_name', 'username', 'email', 'employee_num'],
                'route' => 'users.show',
                'scoped' => true,
                'title' => fn (User $user) => trim($user->first_name.' '.$user->last_name) ?: (string) $user->username,
                'subtitle' => fn (User $user) => implode(' · ', array_filter([$user->username, $user->email])),
            ],
            'models' => [
                'model' => AssetModel::class,
                'ability' => 'view',
                'columns' => ['name', 'model_number'],
                'route' => 'models.show',
                'scoped' => false,
                'title' => fn (AssetModel $model) => (string) $model->name,
                'subtitle' => fn (AssetModel $model) => $model->model_number,
            ],
            'categories' => [
                'model' => Category::class,
                'ability' => 'view',
                'columns' => ['name'],
                'route' => 'categories.show',
                'scoped' => false,
                'title' => fn (Category $category) => (string) $category->name,
                'subtitle' => fn (Category $category) => null,
            ],
            'locations' => [
                'model' => Location::class,
                'ability' => 'view',
                'columns' => ['name', 'address', 'city'],
                'route' => 'locations.show',
                'scoped' => true,
                'title' => fn (Location $location) => (string) $location->name,
                'subtitle' => fn (Location $location) => implode(', ', array_filter([$location->address, $location->city])),
            ],
            'companies' => [
                'model' => Company::class,
                'ability' => 'view',
                'columns' => ['name'],
                'route' => 'companies.show',
                'scoped' => false,
                'title' => fn (Company $company) => (string) $company->name,
                'subtitle' => fn (Company $company) => null,
            ],
            'departments' => [
                'model' => Department::class,
                'ability' => 'view',
                'columns' => ['name'],
                'route' => 'departments.show',
                'scoped' => true,
                'title' => fn (Department $department) => (string) $department->name,
                'subtitle' => fn (Department $department) => null,
            ],
            'manufacturers' => [
                'model' => Manufacturer::class,
                'ability' => 'view',
                'columns' => ['name'],
                'route' => 'manufacturers.show',
                'scoped' => false,
                'title' => fn (Manufacturer $manufacturer) => (string) $manufacturer->name,
                'subtitle' => fn (Manufacturer $manufacturer) => null,
            ],
            'suppliers' => [
                'model' => Supplier::class,
                'ability' => 'view',
                'columns' => ['name', 'email', 'phone'],
                'route' => 'suppliers.show',
                'scoped' => false,
                'title' => fn (Supplier $supplier) => (string) $supplier->name,
                'subtitle' => fn (Supplier $supplier) => implode(' · ', array_filter([$supplier->email, $supplier->phone])),
            ],
            'kits' => [
                'model' => PredefinedKit::class,
                'ability' => 'view',
                'columns' => ['name'],
                'route' => 'kits.show',
                'scoped' => false,
                'title' => fn (PredefinedKit $kit) => (string) $kit->name,
                'subtitle' => fn (PredefinedKit $kit) => null,
            ],
        ];
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $like = self::likePattern($q);
        $groups = [];

        foreach ($this->groups() as $key => $spec) {
            if (! Gate::allows($spec['ability'], $spec['model'])) {
                continue;
            }

            $results = $this->queryGroup($spec, $like, self::PER_GROUP_LIMIT);

            if ($results->isEmpty()) {
                continue;
            }

            $attached = $key === 'users' ? $this->userAttachments($results) : [];

            $groups[] = [
                'key' => $key,
                'label' => trans('general.'.($key === 'models' ? 'asset_models' : $key)),
                'results' => $results->map(function (Model $item) use ($spec, $attached) {
                    $row = [
                        'title' => ($spec['title'])($item),
                        'subtitle' => ($spec['subtitle'])($item),
                        'url' => route($spec['route'], $item->id),
                    ];

                    if (isset($attached[$item->id])) {
                        $row['attached'] = $attached[$item->id];
                    }

                    return $row;
                })->all(),
            ];
        }

        return response()->json(['groups' => $groups]);
    }

    /**
     * [killa global-search] Extended full-page results (KCP-074): same group
     * specs and gates as the quick search, higher limits, plus device-spec
     * search/display (custom-field columns on assets), category browsing
     * (a query matching a category name also lists its assets), and rich user
     * sections with everything checked out to them.
     *
     * KCP-074 amendment (narrowed search): GET-driven scope tab + narrowing
     * filters — asset category/status/spec fields, user department / has-
     * items toggle. All filters are plain GET params so views are shareable.
     */
    public function page(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $specColumns = $this->specColumns();
        $sections = [];

        $scopes = ['all', 'assets', 'users', 'licenses', 'accessories', 'consumables', 'components', 'categories'];
        $scope = in_array($request->input('scope'), $scopes, true) ? $request->input('scope') : 'all';

        // Narrowing filters (all optional, all shareable GET params)
        $filters = [
            'category' => trim((string) $request->input('category', '')),
            'status' => in_array($request->input('status'), ['RTD', 'Deployed', 'Pending'], true) ? $request->input('status') : '',
            'cpu' => trim((string) $request->input('cpu', '')),
            'graphics' => trim((string) $request->input('graphics', '')),
            'ram' => trim((string) $request->input('ram', '')),
            'department' => (int) $request->input('department', 0),
            'has_items' => $request->input('has_items') === '1',
        ];
        $hasAssetFilters = (bool) array_filter([$filters['category'], $filters['status'], $filters['cpu'], $filters['graphics'], $filters['ram']]);
        $hasUserFilters = $filters['department'] > 0 || $filters['has_items'];

        // Narrowed asset/user searches run even without a q term; group
        // searches still require 2 chars.
        $runAssets = in_array($scope, ['all', 'assets'], true) && (mb_strlen($q) >= 2 || $hasAssetFilters);
        $runUsers = in_array($scope, ['all', 'users'], true) && (mb_strlen($q) >= 2 || $hasUserFilters);
        $like = self::likePattern($q !== '' ? $q : '');

        $assetCategories = Category::where('category_type', 'asset')->orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')->get(['id', 'name']);

        if ($runAssets && Gate::allows('index', Asset::class)) {
            $columns = ['asset_tag', 'name', 'serial', 'order_number', 'notes'];

            if ($specColumns !== []) {
                $columns = array_merge($columns, array_values($specColumns));
            }

            $assetsQuery = Company::scopeCompanyables(Asset::query())
                ->with('model.category', 'assignedTo', 'status');

            if (mb_strlen($q) >= 2) {
                $assetsQuery->where(function (Builder $sub) use ($columns, $like) {
                    foreach ($columns as $i => $column) {
                        $sub->{$i === 0 ? 'where' : 'orWhere'}($column, 'LIKE', $like);
                    }
                });
            }

            $this->applyAssetFilters($assetsQuery, $filters, $specColumns);

            $assets = $assetsQuery->orderBy('id', 'desc')->limit(50)->get();

            if ($assets->isNotEmpty()) {
                $sections[] = [
                    'key' => 'assets',
                    'label' => trans('general.assets'),
                    'results' => $assets,
                ];
            }
        }

        if ($runUsers && Gate::allows('index', User::class)) {
            $usersQuery = Company::scopeCompanyables(User::query())
                ->with([
                    'department', 'manager', 'userloc',
                    'assets.model', 'licenses', 'accessories', 'consumables',
                ])
                ->withCount(['assets', 'licenses', 'accessories', 'consumables']);

            if (mb_strlen($q) >= 2) {
                $usersQuery->where(function (Builder $sub) use ($like) {
                    $sub->where('first_name', 'LIKE', $like)
                        ->orWhere('last_name', 'LIKE', $like)
                        ->orWhere('username', 'LIKE', $like)
                        ->orWhere('email', 'LIKE', $like)
                        ->orWhere('employee_num', 'LIKE', $like);
                });
            }

            if ($filters['department'] > 0) {
                $usersQuery->where('department_id', $filters['department']);
            }

            if ($filters['has_items']) {
                $usersQuery->where(function (Builder $sub) {
                    $sub->whereHas('assets')->orWhereHas('licenses')->orWhereHas('accessories')->orWhereHas('consumables');
                });
            }

            $users = $usersQuery->orderBy('id', 'desc')->limit(10)->get();

            if ($users->isNotEmpty()) {
                $sections[] = [
                    'key' => 'users',
                    'label' => trans('general.users'),
                    'results' => $users,
                ];
            }
        }

        if (mb_strlen($q) >= 2) {
            foreach ($this->groups() as $key => $spec) {
                // Assets and users have their own richer pipelines above.
                if (in_array($key, ['assets', 'users'], true)) {
                    continue;
                }

                if ($scope !== 'all' && $scope !== $key) {
                    continue;
                }

                if ($scope === 'all' && $key === 'categories') {
                    // categories section renders below via the same loop; keep it
                }

                if (! Gate::allows($spec['ability'], $spec['model'])) {
                    continue;
                }

                $results = $this->queryGroup($spec, $like, 25);

                if ($results->isEmpty()) {
                    continue;
                }

                $sections[] = [
                    'key' => $key,
                    'label' => trans('general.'.($key === 'models' ? 'asset_models' : $key)),
                    'spec' => $spec,
                    'results' => $results,
                ];
            }

            // Category browsing: a query that matches category names also
            // surfaces the assets inside those categories.
            if (in_array($scope, ['all', 'assets'], true) && Gate::allows('view', Category::class) && Gate::allows('index', Asset::class)) {
                $matchedCategories = Category::where('name', 'LIKE', $like)->orderBy('name')->limit(5)->get();

                if ($matchedCategories->isNotEmpty()) {
                    $categoryAssetsQuery = Company::scopeCompanyables(Asset::query())
                        ->whereHas('model', fn (Builder $query) => $query->whereIn('category_id', $matchedCategories->pluck('id')))
                        ->with('model.category', 'assignedTo', 'status');

                    $this->applyAssetFilters($categoryAssetsQuery, $filters, $specColumns);

                    $categoryAssets = $categoryAssetsQuery->orderBy('asset_tag')->limit(50)->get();

                    if ($categoryAssets->isNotEmpty()) {
                        $sections[] = [
                            'key' => 'category_assets',
                            'label' => trans('killa.search.assets_in_category', ['categories' => $matchedCategories->pluck('name')->join(', ')]),
                            'results' => $categoryAssets,
                        ];
                    }
                }
            }
        }

        return view('killa.search', [
            'q' => $q,
            'sections' => $sections,
            'specColumns' => $specColumns,
            'scope' => $scope,
            'scopes' => $scopes,
            'filters' => $filters,
            'assetCategories' => $assetCategories,
            'departments' => $departments,
        ]);
    }

    /**
     * Shared narrowing for the two asset queries (direct + category browsing).
     * Category accepts an id or an exact-ish name so shareable links can use
     * either (/search?category=4 or ?category=Laptop).
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $specColumns
     */
    private function applyAssetFilters(Builder $query, array $filters, array $specColumns): void
    {
        if ($filters['category'] !== '') {
            if (is_numeric($filters['category'])) {
                $query->whereHas('model', fn (Builder $sub) => $sub->where('category_id', (int) $filters['category']));
            } else {
                $query->whereHas('model.category', fn (Builder $sub) => $sub->where('name', 'LIKE', self::likePattern($filters['category'])));
            }
        }

        switch ($filters['status']) {
            case 'Deployed':
                $query->whereNotNull('assets.assigned_to');
                break;
            case 'RTD':
                $query->whereNull('assets.assigned_to')
                    ->whereHas('status', fn (Builder $sub) => $sub->where('deployable', 1)->where('pending', 0)->where('archived', 0));
                break;
            case 'Pending':
                $query->whereHas('status', fn (Builder $sub) => $sub->where('deployable', 0)->where('pending', 1)->where('archived', 0));
                break;
        }

        foreach (['cpu' => 'CPU', 'graphics' => 'Graphics', 'ram' => 'RAM'] as $filterKey => $specName) {
            if ($filters[$filterKey] !== '' && isset($specColumns[$specName])) {
                $query->where($specColumns[$specName], 'LIKE', self::likePattern($filters[$filterKey]));
            }
        }
    }

    /**
     * Run one group spec's LIKE query. Column list is a parameter so the
     * page action can widen it (e.g. asset spec columns).
     *
     * @param  array{model: class-string<Model>, columns: array<int, string>, scoped: bool}  $spec
     * @param  array<int, string>|null  $columns
     * @return \Illuminate\Support\Collection<int, Model>
     */
    private function queryGroup(array $spec, string $like, int $limit, ?array $columns = null)
    {
        $columns = $columns ?? $spec['columns'];
        $query = $spec['model']::query();

        if ($spec['scoped']) {
            $query = Company::scopeCompanyables($query);
        }

        return $query
            ->where(function (Builder $sub) use ($columns, $like) {
                foreach ($columns as $i => $column) {
                    $sub->{$i === 0 ? 'where' : 'orWhere'}($column, 'LIKE', $like);
                }
            })
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Attached-items summary for the users group in the quick dropdown:
     * one assets query for the sample labels, withCount for the rest —
     * no N+1. Keyed by user id.
     *
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @return array<int, array{assets_count: int, assets_sample: array<int, string>, licenses_count: int, accessories_count: int, consumables_count: int}>
     */
    private function userAttachments($users): array
    {
        $users->loadCount(['assets', 'licenses', 'accessories', 'consumables']);

        $sampleAssets = Asset::whereIn('assigned_to', $users->pluck('id'))
            ->where('assigned_type', User::class)
            ->orderByDesc('last_checkout')
            ->get(['id', 'assigned_to', 'asset_tag', 'name'])
            ->groupBy('assigned_to');

        $out = [];

        foreach ($users as $user) {
            $sample = ($sampleAssets[$user->id] ?? collect())->take(3)
                ->map(fn (Asset $asset) => trim($asset->asset_tag.' '.$asset->name))
                ->values()->all();

            $out[$user->id] = [
                'assets_count' => (int) $user->assets_count,
                'assets_sample' => $sample,
                'licenses_count' => (int) $user->licenses_count,
                'accessories_count' => (int) $user->accessories_count,
                'consumables_count' => (int) $user->consumables_count,
            ];
        }

        return $out;
    }

    /**
     * Spec-bearing custom-field columns on the assets table, resolved by
     * field name so the mapping survives different Snipe-IT instances.
     *
     * @return array<string, string> field label => db column
     */
    private function specColumns(): array
    {
        return CustomField::whereIn('name', ['CPU', 'RAM', 'Storage 1', 'Storage 2', 'Graphics'])
            ->pluck('db_column', 'name')
            ->all();
    }

    private static function likePattern(string $q): string
    {
        return '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
    }
}
