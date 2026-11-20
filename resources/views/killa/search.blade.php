@extends('layouts/default')

{{-- [killa global-search] KCP-074: full-page results for the header global
     search (quick dropdown: KCP-061). Killa-owned view, killa-v2 styled. --}}

@section('title')
    {{ trans('killa.search.title') }}
@stop

@section('content')
<div class="killa-search-page">
    <style>
        .killa-search-page{ color: var(--k-text, #1f2933); }
        .killa-search-page .ks-head{
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 4px;
        }
        .killa-search-page .ks-title{
            font-family: var(--k-font-display, 'Inter Tight', 'Inter', sans-serif);
            font-size: 22px; font-weight: 700; letter-spacing: -0.01em; margin: 0;
        }
        .killa-search-page .ks-sub{ font-size: 13px; color: var(--k-text-muted, #6b7280); margin-top: 2px; }
        .killa-search-page .ks-form{ display: flex; gap: 8px; min-width: 320px; }
        .killa-search-page .ks-form input{ flex: 1; }
        .killa-search-page .table{ margin-bottom: 0; }
        .killa-search-page .table td{ vertical-align: middle; }
        .killa-search-page .spec{ font-size: 11.5px; color: var(--k-text-muted, #6b7280); }
        .killa-search-page .user-card-head{
            display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;
        }
        .killa-search-page .user-card-head h4{
            margin: 0; font-family: var(--k-font-display, 'Inter Tight', 'Inter', sans-serif);
            font-size: 16px; font-weight: 700;
        }
        .killa-search-page .user-meta{ font-size: 12px; color: var(--k-text-muted, #6b7280); }
        .killa-search-page .user-meta span + span::before{ content: ' · '; }
        .killa-search-page .attached-label{
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .05em; color: var(--k-text-muted, #6b7280); margin: 14px 0 6px;
        }
        .killa-search-page .chip-list{ display: flex; flex-wrap: wrap; gap: 6px; }
        .killa-search-page .chip{
            display: inline-block; padding: 4px 10px; font-size: 12px;
            border: 1px solid var(--k-border, #e5e7eb); border-radius: var(--k-radius-pill, 999px);
            background: var(--k-surface, #fff); color: var(--k-text, #1f2933);
        }
        .killa-search-page .chip:hover{ border-color: var(--k-accent, #E63B2E); color: var(--k-accent, #E63B2E); text-decoration: none; }
        .killa-search-page .empty-note{ padding: 28px 16px; text-align: center; color: var(--k-text-muted, #6b7280); font-size: 13px; }

        /* KCP-074 narrowed search: scope tabs + filter panel + chips */
        .killa-search-page .ks-tabs{
            display: flex; gap: 4px; flex-wrap: wrap; margin: 14px 0 12px;
            border-bottom: 1px solid var(--k-border, #e5e7eb); padding-bottom: 0;
        }
        .killa-search-page .ks-tab{
            padding: 8px 14px; font-size: 12.5px; font-weight: 600;
            color: var(--k-text-muted, #6b7280); text-decoration: none;
            border: 1px solid transparent; border-bottom: none;
            border-radius: var(--k-radius-sm, 6px) var(--k-radius-sm, 6px) 0 0;
        }
        .killa-search-page .ks-tab:hover{ color: var(--k-text, #1f2933); background: var(--k-hover-tint, #f3f4f6); }
        .killa-search-page .ks-tab.active{
            color: var(--k-accent, #E63B2E);
            border-color: var(--k-border, #e5e7eb);
            background: var(--k-surface, #fff);
            box-shadow: inset 0 -2px 0 var(--k-accent, #E63B2E);
        }
        .killa-search-page .ks-filters{ display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
        .killa-search-page .ks-filter-field{ min-width: 150px; flex: 1; }
        .killa-search-page .ks-filter-field label{
            display: block; font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .05em; color: var(--k-text-muted, #6b7280); margin-bottom: 3px;
        }
        .killa-search-page .ks-filter-field.narrow{ min-width: 110px; flex: 0 1 130px; }
        .killa-search-page .ks-filter-actions{ display: flex; gap: 8px; align-items: center; }
        .killa-search-page .ks-check{ display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--k-text, #1f2933); padding-bottom: 7px; }
        .killa-search-page .ks-group-label{
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: var(--k-text-muted, #6b7280); width: 100%; margin-bottom: -2px;
        }
        .killa-search-page .ks-chips{ display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-bottom: 12px; }
        .killa-search-page .ks-chips-label{ font-size: 12px; color: var(--k-text-muted, #6b7280); }
        .killa-search-page .ks-chip{
            display: inline-flex; align-items: center; gap: 7px; padding: 3px 6px 3px 10px; font-size: 12px;
            border: 1px solid color-mix(in srgb, var(--k-accent, #E63B2E) 30%, transparent);
            border-radius: var(--k-radius-pill, 999px);
            background: color-mix(in srgb, var(--k-accent, #E63B2E) 8%, var(--k-surface, #fff));
            color: var(--k-accent, #E63B2E);
        }
        .killa-search-page .ks-chip a{
            color: inherit; font-weight: 700; text-decoration: none; padding: 0 3px;
            border-radius: 50%; line-height: 1;
        }
        .killa-search-page .ks-chip a:hover{ color: var(--k-accent-hover, #c93024); }
    </style>

    @php
        $scopeLabels = [
            'all' => trans('killa.search.scope_all'),
            'assets' => trans('general.assets'),
            'users' => trans('general.users'),
            'licenses' => trans('general.licenses'),
            'accessories' => trans('general.accessories'),
            'consumables' => trans('general.consumables'),
            'components' => trans('general.components'),
            'categories' => trans('general.categories'),
        ];
        $statusLabels = [
            'RTD' => trans('killa.search.filter_status_rtd'),
            'Deployed' => trans('killa.search.filter_status_deployed'),
            'Pending' => trans('killa.search.filter_status_pending'),
        ];
        $baseQuery = ['q' => $q];
        $currentQuery = request()->query();
        $chipDefs = [];
        if ($filters['category'] !== '') {
            $catName = is_numeric($filters['category'])
                ? ($assetCategories->firstWhere('id', (int) $filters['category'])?->name ?? $filters['category'])
                : $filters['category'];
            $chipDefs['category'] = trans('killa.search.filter_category').': '.$catName;
        }
        if ($filters['status'] !== '') {
            $chipDefs['status'] = $statusLabels[$filters['status']];
        }
        foreach (['cpu', 'graphics', 'ram'] as $specKey) {
            if ($filters[$specKey] !== '') {
                $chipDefs[$specKey] = trans('killa.search.filter_'.$specKey).': '.$filters[$specKey];
            }
        }
        if ($filters['department'] > 0) {
            $chipDefs['department'] = trans('killa.search.filter_department').': '.($departments->firstWhere('id', $filters['department'])?->name ?? $filters['department']);
        }
        if ($filters['has_items']) {
            $chipDefs['has_items'] = trans('killa.search.filter_has_items');
        }
    @endphp

    <div class="ks-head">
        <div>
            <h2 class="ks-title">{{ trans('killa.search.heading') }}</h2>
            @if ($q !== '')
                <div class="ks-sub">{{ trans('killa.search.query_label') }} &ldquo;{{ $q }}&rdquo;</div>
            @endif
        </div>
        <form class="ks-form" method="get" action="{{ route('killa.search') }}" role="search">
            <input type="hidden" name="scope" value="{{ $scope }}">
            @foreach (['category', 'status', 'cpu', 'graphics', 'ram', 'department'] as $keepKey)
                @if ($filters[$keepKey] !== '' && $filters[$keepKey] !== 0)
                    <input type="hidden" name="{{ $keepKey }}" value="{{ $filters[$keepKey] }}">
                @endif
            @endforeach
            @if ($filters['has_items'])
                <input type="hidden" name="has_items" value="1">
            @endif
            <input type="search" name="q" class="form-control" value="{{ $q }}" placeholder="{{ trans('killa.global_search.placeholder') }}" autofocus>
            <button type="submit" class="btn btn-primary">{{ trans('killa.search.title') }}</button>
        </form>
    </div>

    <div class="ks-tabs" role="tablist">
        @foreach ($scopes as $scopeKey)
            <a class="ks-tab{{ $scope === $scopeKey ? ' active' : '' }}" role="tab" aria-selected="{{ $scope === $scopeKey ? 'true' : 'false' }}"
               href="{{ route('killa.search', array_filter(array_merge($currentQuery, ['scope' => $scopeKey]), fn ($v) => $v !== '' && $v !== 0 && $v !== null)) }}">{{ $scopeLabels[$scopeKey] }}</a>
        @endforeach
    </div>

    @if (in_array($scope, ['all', 'assets', 'users'], true))
        <div class="box box-default">
            <div class="box-body">
                <form class="ks-filters" method="get" action="{{ route('killa.search') }}">
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="scope" value="{{ $scope }}">

                    @if (in_array($scope, ['all', 'assets'], true))
                        <div class="ks-group-label">{{ trans('general.assets') }}</div>
                        <div class="ks-filter-field">
                            <label for="fCategory">{{ trans('killa.search.filter_category') }}</label>
                            <select id="fCategory" name="category" class="form-control">
                                <option value="">{{ trans('killa.search.filter_any') }}</option>
                                @foreach ($assetCategories as $assetCategory)
                                    <option value="{{ $assetCategory->id }}"{{ (string) $filters['category'] === (string) $assetCategory->id || $filters['category'] === $assetCategory->name ? ' selected' : '' }}>{{ $assetCategory->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ks-filter-field narrow">
                            <label for="fStatus">{{ trans('killa.search.filter_status') }}</label>
                            <select id="fStatus" name="status" class="form-control">
                                <option value="">{{ trans('killa.search.filter_any') }}</option>
                                @foreach ($statusLabels as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}"{{ $filters['status'] === $statusValue ? ' selected' : '' }}>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (isset($specColumns['CPU']))
                            <div class="ks-filter-field narrow">
                                <label for="fCpu">{{ trans('killa.search.filter_cpu') }}</label>
                                <input type="text" id="fCpu" name="cpu" class="form-control" value="{{ $filters['cpu'] }}" placeholder="i9">
                            </div>
                        @endif
                        @if (isset($specColumns['Graphics']))
                            <div class="ks-filter-field narrow">
                                <label for="fGraphics">{{ trans('killa.search.filter_graphics') }}</label>
                                <input type="text" id="fGraphics" name="graphics" class="form-control" value="{{ $filters['graphics'] }}" placeholder="RTX">
                            </div>
                        @endif
                        @if (isset($specColumns['RAM']))
                            <div class="ks-filter-field narrow">
                                <label for="fRam">{{ trans('killa.search.filter_ram') }}</label>
                                <input type="text" id="fRam" name="ram" class="form-control" value="{{ $filters['ram'] }}" placeholder="128">
                            </div>
                        @endif
                    @endif

                    @if (in_array($scope, ['all', 'users'], true))
                        <div class="ks-group-label">{{ trans('general.users') }}</div>
                        <div class="ks-filter-field">
                            <label for="fDepartment">{{ trans('killa.search.filter_department') }}</label>
                            <select id="fDepartment" name="department" class="form-control">
                                <option value="0">{{ trans('killa.search.filter_any') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"{{ $filters['department'] === $department->id ? ' selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="ks-check">
                            <input type="checkbox" name="has_items" value="1"{{ $filters['has_items'] ? ' checked' : '' }}>
                            {{ trans('killa.search.filter_has_items') }}
                        </label>
                    @endif

                    <div class="ks-filter-actions">
                        <button type="submit" class="btn btn-primary">{{ trans('killa.search.filter_apply') }}</button>
                        <a class="btn btn-default" href="{{ route('killa.search', array_filter(['q' => $q, 'scope' => $scope === 'all' ? '' : $scope])) }}">{{ trans('killa.search.filter_clear') }}</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($chipDefs !== [])
        <div class="ks-chips">
            <span class="ks-chips-label">{{ trans('killa.search.filter_active') }}</span>
            @foreach ($chipDefs as $chipKey => $chipLabel)
                <span class="ks-chip">
                    {{ $chipLabel }}
                    <a href="{{ route('killa.search', array_filter(array_merge($currentQuery, [$chipKey => null]), fn ($v) => $v !== '' && $v !== 0 && $v !== null)) }}" aria-label="{{ trans('general.delete') }}">&times;</a>
                </span>
            @endforeach
        </div>
    @endif

    @if ($q === '' && $chipDefs === [])
        <div class="box box-default">
            <div class="box-body empty-note">{{ trans('killa.search.empty_query') }}</div>
        </div>
    @elseif ($sections === [])
        <div class="box box-default">
            <div class="box-body empty-note">{{ $q !== '' ? trans('killa.search.no_results', ['query' => $q]) : trans('killa.search.no_results_filtered') }}</div>
        </div>
    @else
        @foreach ($sections as $section)
            @if ($section['key'] === 'users')
                {{-- rich user cards --}}
                @foreach ($section['results'] as $user)
                    <div class="box box-default">
                        <div class="box-body">
                            <div class="user-card-head">
                                <h4><a href="{{ route('users.show', $user->id) }}">{{ trim($user->first_name.' '.$user->last_name) ?: $user->username }}</a></h4>
                                <div class="user-meta">
                                    @if ($user->jobtitle)<span>{{ $user->jobtitle }}</span>@endif
                                    @if ($user->email)<span>{{ $user->email }}</span>@endif
                                    @if ($user->department)<span>{{ trans('killa.search.col_department') }}: {{ $user->department->name }}</span>@endif
                                    @if ($user->manager)<span>{{ trans('killa.search.col_manager') }}: {{ trim($user->manager->first_name.' '.$user->manager->last_name) ?: $user->manager->username }}</span>@endif
                                    @if ($user->userloc)<span>{{ trans('killa.search.col_location') }}: {{ $user->userloc->name }}</span>@endif
                                </div>
                            </div>

                            @if ($user->assets->isNotEmpty())
                                <div class="attached-label">{{ trans('killa.search.attached_assets', ['count' => $user->assets_count]) }}</div>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('killa.search.col_asset') }}</th>
                                                <th>{{ trans('killa.search.col_model') }}</th>
                                                @foreach (['CPU' => 'col_cpu', 'RAM' => 'col_ram', 'Graphics' => 'col_graphics'] as $specName => $specKey)
                                                    @if (isset($specColumns[$specName]))
                                                        <th>{{ trans('killa.search.'.$specKey) }}</th>
                                                    @endif
                                                @endforeach
                                                @if (isset($specColumns['Storage 1']) || isset($specColumns['Storage 2']))
                                                    <th>{{ trans('killa.search.col_storage') }}</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($user->assets as $asset)
                                                <tr>
                                                    <td><a href="{{ route('hardware.show', $asset->id) }}">{{ trim($asset->asset_tag.' '.$asset->name) }}</a></td>
                                                    <td>{{ $asset->model?->name }}</td>
                                                    @foreach (['CPU', 'RAM', 'Graphics'] as $specName)
                                                        @if (isset($specColumns[$specName]))
                                                            <td class="spec">{{ $asset->{$specColumns[$specName]} }}</td>
                                                        @endif
                                                    @endforeach
                                                    @if (isset($specColumns['Storage 1']) || isset($specColumns['Storage 2']))
                                                        <td class="spec">{{ implode(' + ', array_filter([$asset->{$specColumns['Storage 1'] ?? ''} ?? null, $asset->{$specColumns['Storage 2'] ?? ''} ?? null])) }}</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if ($user->licenses->isNotEmpty())
                                <div class="attached-label">{{ trans('killa.search.attached_licenses', ['count' => $user->licenses_count]) }}</div>
                                <div class="chip-list">
                                    @foreach ($user->licenses as $license)
                                        <a class="chip" href="{{ route('licenses.show', $license->id) }}">{{ $license->name }}</a>
                                    @endforeach
                                </div>
                            @endif

                            @if ($user->accessories->isNotEmpty())
                                <div class="attached-label">{{ trans('killa.search.attached_accessories', ['count' => $user->accessories_count]) }}</div>
                                <div class="chip-list">
                                    @foreach ($user->accessories as $accessory)
                                        <a class="chip" href="{{ route('accessories.show', $accessory->id) }}">{{ $accessory->name }}</a>
                                    @endforeach
                                </div>
                            @endif

                            @if ($user->consumables->isNotEmpty())
                                <div class="attached-label">{{ trans('killa.search.attached_consumables', ['count' => $user->consumables_count]) }}</div>
                                <div class="chip-list">
                                    @foreach ($user->consumables as $consumable)
                                        <a class="chip" href="{{ route('consumables.show', $consumable->id) }}">{{ $consumable->name }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @elseif (in_array($section['key'], ['assets', 'category_assets'], true))
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $section['label'] }}</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('killa.search.col_asset') }}</th>
                                    <th>{{ trans('killa.search.col_model') }}</th>
                                    <th>{{ trans('killa.search.col_category') }}</th>
                                    @foreach (['CPU' => 'col_cpu', 'RAM' => 'col_ram', 'Graphics' => 'col_graphics'] as $specName => $specKey)
                                        @if (isset($specColumns[$specName]))
                                            <th>{{ trans('killa.search.'.$specKey) }}</th>
                                        @endif
                                    @endforeach
                                    @if (isset($specColumns['Storage 1']) || isset($specColumns['Storage 2']))
                                        <th>{{ trans('killa.search.col_storage') }}</th>
                                    @endif
                                    <th>{{ trans('killa.search.col_status') }}</th>
                                    <th>{{ trans('killa.search.col_assigned_to') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['results'] as $asset)
                                    <tr>
                                        <td><a href="{{ route('hardware.show', $asset->id) }}">{{ trim($asset->asset_tag.' '.$asset->name) }}</a></td>
                                        <td>{{ $asset->model?->name }}</td>
                                        <td>{{ $asset->model?->category?->name }}</td>
                                        @foreach (['CPU', 'RAM', 'Graphics'] as $specName)
                                            @if (isset($specColumns[$specName]))
                                                <td class="spec">{{ $asset->{$specColumns[$specName]} }}</td>
                                            @endif
                                        @endforeach
                                        @if (isset($specColumns['Storage 1']) || isset($specColumns['Storage 2']))
                                            <td class="spec">{{ implode(' + ', array_filter([$asset->{$specColumns['Storage 1'] ?? ''} ?? null, $asset->{$specColumns['Storage 2'] ?? ''} ?? null])) }}</td>
                                        @endif
                                        <td>{{ $asset->status?->name }}</td>
                                        <td>
                                            @if ($asset->assignedTo)
                                                @if ($asset->assignedType() === 'user')
                                                    <a href="{{ route('users.show', $asset->assigned_to) }}">{{ $asset->assignedTo->display_name }}</a>
                                                @else
                                                    {{ $asset->assignedTo->display_name ?? $asset->assignedTo->name }}
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $section['label'] }}</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('killa.search.col_name') }}</th>
                                    <th>{{ trans('killa.search.col_details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['results'] as $item)
                                    <tr>
                                        <td><a href="{{ route($section['spec']['route'], $item->id) }}">{{ ($section['spec']['title'])($item) }}</a></td>
                                        <td class="spec">{{ ($section['spec']['subtitle'])($item) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>
@stop
