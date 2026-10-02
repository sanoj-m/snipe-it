@props(['counts'])

{{-- Admin-only inventory summary row. Trying to determine the box sizes
for an unpredictable number of boxes (logged in user can only see accessories,
nothing else, etc makes for a really awkward display view --}}
{{-- [killa-v2] stat tiles: bg-* classes remain as the color signal for the
icon chips; structure inside is the v2 k-tile card styled in killa-v2.css --}}
<div class="row">

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('hardware.index') }}">
            <div class="dashboard small-box k-tile bg-teal">
                <div class="k-tile-body">
                    <span class="k-tile-icon" aria-hidden="true">
                        <x-icon type="assets"/>
                    </span>
                    <span class="k-tile-text">
                        <span class="k-tile-number">{{ number_format(\App\Models\Asset::AssetsForShow()->count()) }}</span>
                        <span class="k-tile-label">{{ trans('general.assets') }}</span>
                    </span>
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right"/>
                </span>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('licenses.index') }}" aria-hidden="true">
            <div class="dashboard small-box k-tile bg-maroon">
                <div class="k-tile-body">
                    <span class="k-tile-icon" aria-hidden="true">
                        <x-icon type="licenses"/>
                    </span>
                    <span class="k-tile-text">
                        <span class="k-tile-number">{{ number_format($counts['license']) }}</span>
                        <span class="k-tile-label">{{ trans('general.licenses') }}</span>
                    </span>
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right"/>
                </span>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('accessories.index') }}">
            <div class="dashboard small-box k-tile bg-orange">
                <div class="k-tile-body">
                    <span class="k-tile-icon" aria-hidden="true">
                        <x-icon type="accessories"/>
                    </span>
                    <span class="k-tile-text">
                        <span class="k-tile-number">{{ number_format($counts['accessory']) }}</span>
                        <span class="k-tile-label">{{ trans('general.accessories') }}</span>
                    </span>
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right"/>
                </span>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('consumables.index') }}">
            <div class="dashboard small-box k-tile bg-purple">
                <div class="k-tile-body">
                    <span class="k-tile-icon" aria-hidden="true">
                        <x-icon type="consumables"/>
                    </span>
                    <span class="k-tile-text">
                        <span class="k-tile-number">{{ number_format($counts['consumable']) }}</span>
                        <span class="k-tile-label">{{ trans('general.consumables') }}</span>
                    </span>
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right"/>
                </span>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('components.index') }}">
            <div class="dashboard small-box k-tile bg-yellow">
                <div class="k-tile-body">
                    <span class="k-tile-icon" aria-hidden="true">
                        <x-icon type="components"/>
                    </span>
                    <span class="k-tile-text">
                        <span class="k-tile-number">{{ number_format($counts['component']) }}</span>
                        <span class="k-tile-label">{{ trans('general.components') }}</span>
                    </span>
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right"/>
                </span>
            </div>
        </a>
    </div>

    <div class="col-lg-2 col-xs-6">
        <a href="{{ route('users.index') }}">
            <div class="dashboard small-box k-tile bg-light-blue">
                <div class="k-tile-body">
                    <span class="k-tile-icon" aria-hidden="true">
                        <x-icon type="users"/>
                    </span>
                    <span class="k-tile-text">
                        <span class="k-tile-number">{{ number_format($counts['user']) }}</span>
                        <span class="k-tile-label">{{ trans('general.people') }}</span>
                    </span>
                </div>
                <span class="small-box-footer">
                    {{ trans('general.view_all') }}
                    <x-icon type="arrow-circle-right"/>
                </span>
            </div>
        </a>
    </div>

</div>
