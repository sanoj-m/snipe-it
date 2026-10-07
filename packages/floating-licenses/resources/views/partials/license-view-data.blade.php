{{-- Floating-licenses addon (master switch in Admin > Settings > General).
     Data prep for the license view page: resolves the pool config, active
     allocations, availability stats and per-user cost. Included from the
     core licenses/view.blade.php; variables leak into the including view's
     scope (Blade includes share scope). --}}
@php
    $floatingMasterOn = (($snipeSettings->floating_licenses_enabled ?? '0') == '1');
    $explicitFloatingConfig = $floatingMasterOn
        ? \SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig::where('license_id', $license->id)->first()
        : null;
    $floatingConfig = $floatingMasterOn
        ? \SnipeIt\FloatingLicenses\Support\FloatingLicenseSync::configForLicense($license)
        : null;
    $floatingService = app(\SnipeIt\FloatingLicenses\Services\FloatingLicenseService::class);
    $floatingAllocations = $floatingConfig
        ? $floatingConfig->activeAllocations()->with(['user.company', 'user.location', 'asset'])->orderBy('allocated_at', 'desc')->get()
        : collect();
    $floatingStats = $floatingConfig ? $floatingService->availability($floatingConfig) : null;
    $floatingCostPerUser = $floatingConfig ? $floatingService->costPerUser($floatingConfig) : 0.0;
@endphp
