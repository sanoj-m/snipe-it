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
    </style>

    <div class="ks-head">
        <div>
            <h2 class="ks-title">{{ trans('killa.search.heading') }}</h2>
            @if ($q !== '')
                <div class="ks-sub">{{ trans('killa.search.query_label') }} &ldquo;{{ $q }}&rdquo;</div>
            @endif
        </div>
        <form class="ks-form" method="get" action="{{ route('killa.search') }}" role="search">
            <input type="search" name="q" class="form-control" value="{{ $q }}" placeholder="{{ trans('killa.global_search.placeholder') }}" autofocus>
            <button type="submit" class="btn btn-primary">{{ trans('killa.search.title') }}</button>
        </form>
    </div>

    @if ($q === '')
        <div class="box box-default">
            <div class="box-body empty-note">{{ trans('killa.search.empty_query') }}</div>
        </div>
    @elseif ($sections === [])
        <div class="box box-default">
            <div class="box-body empty-note">{{ trans('killa.search.no_results', ['query' => $q]) }}</div>
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
