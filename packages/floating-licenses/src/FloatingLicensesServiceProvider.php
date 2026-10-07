<?php

namespace SnipeIt\FloatingLicenses;

use App\Models\License;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SnipeIt\FloatingLicenses\Console\ConvertFloatingLicensesToStandard;
use SnipeIt\FloatingLicenses\Console\ExpireFloatingAllocations;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;
use SnipeIt\FloatingLicenses\Policies\FloatingLicenseConfigPolicy;
use SnipeIt\FloatingLicenses\Support\FloatingLicenseSync;

class FloatingLicensesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/floating-licenses.php', 'floating-licenses');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'floating-licenses');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'floating-licenses');

        Route::group(['middleware' => ['web', 'auth']], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->registerGates();
        $this->mergePermissions();
        $this->registerLicenseFormSync();

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireFloatingAllocations::class,
                ConvertFloatingLicensesToStandard::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/floating-licenses.php' => config_path('floating-licenses.php'),
            ], 'floating-licenses-config');
        }
    }

    /**
     * Sync a license's floating pool config after the license create/edit
     * form is saved. Previously an explicit call in the core
     * LicensesController store()/update() methods; moved here so no core
     * controller is touched.
     *
     * Gated to the licenses.store / licenses.update routes so other save
     * paths (API, CSV importer, checkout/checkin, console) keep their exact
     * upstream behavior — the API deliberately never synced, and the
     * importer must not soft-delete configs. Outside an HTTP route match
     * (console, tests calling syncFromRequest() directly) this is a no-op.
     */
    protected function registerLicenseFormSync(): void
    {
        License::saved(function (License $license) {
            $route = request()->route();

            if ($route && $route->named('licenses.store', 'licenses.update')) {
                FloatingLicenseSync::syncFromRequest($license, request());
            }
        });
    }

    /**
     * Define authorization gates, mirroring how AuthServiceProvider defines
     * gates such as reports.view. Kept here so no core files are touched.
     *
     * The string gates stay even though a policy class now covers the
     * model-level checks: they feed the runtime-merged config('permissions')
     * UI and the non-model authorize() calls (license-bound routes,
     * own-vs-admin release/heartbeat ownership).
     */
    protected function registerGates(): void
    {
        foreach (self::permissions() as $permission) {
            Gate::define($permission, fn ($user) => $user->hasAccess($permission));
        }

        Gate::policy(FloatingLicenseConfig::class, FloatingLicenseConfigPolicy::class);
    }

    /**
     * Merge a "Floating Licenses" section into config('permissions') at
     * runtime so the group permission checkboxes UI picks it up without
     * editing config/permissions.php (which is marked DO NOT EDIT).
     */
    protected function mergePermissions(): void
    {
        $section = [];
        foreach (self::permissions() as $permission) {
            $section[] = [
                'permission' => $permission,
                'display' => true,
            ];
        }

        config(['permissions' => array_merge(config('permissions', []), ['Floating Licenses' => $section])]);
    }

    /**
     * The full list of permissions this package provides.
     *
     * @return string[]
     */
    public static function permissions(): array
    {
        return [
            'floating_licenses.view',
            'floating_licenses.manage',
            'floating_licenses.allocate',
            'floating_licenses.release',
            'floating_licenses.costs',
            'floating_licenses.history',
        ];
    }
}
