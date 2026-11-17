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
                'columns' => ['name', 'model_number', 'order_number'],
                'route' => 'accessories.show',
                'scoped' => true,
                'title' => fn (Accessory $accessory) => (string) $accessory->name,
                'subtitle' => fn (Accessory $accessory) => $accessory->model_number,
            ],
            'consumables' => [
                'model' => Consumable::class,
                'ability' => 'index',
                'columns' => ['name', 'model_number', 'order_number'],
                'route' => 'consumables.show',
                'scoped' => true,
                'title' => fn (Consumable $consumable) => (string) $consumable->name,
                'subtitle' => fn (Consumable $consumable) => $consumable->model_number,
            ],
            'components' => [
                'model' => Component::class,
                'ability' => 'view',
                'columns' => ['name', 'serial', 'order_number'],
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

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $groups = [];

        foreach ($this->groups() as $key => $spec) {
            if (! Gate::allows($spec['ability'], $spec['model'])) {
                continue;
            }

            $query = $spec['model']::query();

            if ($spec['scoped']) {
                $query = Company::scopeCompanyables($query);
            }

            $results = $query
                ->where(function (Builder $sub) use ($spec, $like) {
                    foreach ($spec['columns'] as $i => $column) {
                        $sub->{$i === 0 ? 'where' : 'orWhere'}($column, 'LIKE', $like);
                    }
                })
                ->orderBy('id', 'desc')
                ->limit(self::PER_GROUP_LIMIT)
                ->get();

            if ($results->isEmpty()) {
                continue;
            }

            $groups[] = [
                'key' => $key,
                'label' => trans('general.'.($key === 'models' ? 'asset_models' : $key)),
                'results' => $results->map(fn (Model $item) => [
                    'title' => ($spec['title'])($item),
                    'subtitle' => ($spec['subtitle'])($item),
                    'url' => route($spec['route'], $item->id),
                ])->all(),
            ];
        }

        return response()->json(['groups' => $groups]);
    }
}
