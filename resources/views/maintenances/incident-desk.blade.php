@extends('layouts/default')

@section('title')
    {{ trans('admin/incident_desk/general.incident_desk') }}
@stop

@section('content')
<div class="incident-desk">
    <style>
        /* Incident Desk — killa-v2 native styling. All colors read the app's
           --k-* theme tokens (with light-theme fallbacks so the page still
           renders sanely in Classic mode where the v2 layer is not loaded).
           Dark-mode parity comes for free from the token layer. */
        .incident-desk{ color: var(--k-text, #1f2933); }
        .incident-desk *, .incident-desk *::before, .incident-desk *::after{ box-sizing: border-box; }
        .incident-desk .mono{ font-family: ui-monospace, 'Cascadia Mono', Menlo, Consolas, monospace; }

        /* donut status colors (semantic, theme-aware) */
        .incident-desk{
            --id-closed: var(--k-success, #2f9e6e);
            --id-open: var(--k-accent, #E63B2E);
            --id-vendor: var(--k-warning, #b97d10);
            --id-muted: var(--k-text-muted, #6b7280);
            --id-line: var(--k-border-soft, #eef0f2);
            --id-border: var(--k-border, #e5e7eb);
            --id-surface: var(--k-surface, #fff);
        }

        /* ---------- header stat tiles (full-width strip) ---------- */
        .incident-desk .id-tiles{
            display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px;
            margin-bottom: 18px;
        }
        @media (max-width: 1200px){
            .incident-desk .id-tiles{ grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
        }
        .incident-desk .id-tile{
            position: relative; overflow: hidden;
            border-radius: 14px; padding: 15px 16px;
            background: var(--id-surface);
            border: 1px solid color-mix(in srgb, var(--tile) 34%, var(--id-border));
            box-shadow: var(--k-shadow-sm, 0 1px 2px rgba(16,24,40,.06));
            transition: box-shadow var(--k-motion, 160ms ease), transform var(--k-motion, 160ms ease);
        }
        .incident-desk .id-tile:hover{
            transform: translateY(-1px);
            box-shadow: 0 0 26px -8px color-mix(in srgb, var(--tile) 50%, transparent);
        }
        .incident-desk .id-tile--link{ cursor: pointer; }
        .incident-desk .id-tile--link:focus-visible{ outline: 2px solid var(--tile); outline-offset: 2px; }
        .incident-desk .id-tile--link.active{
            box-shadow: 0 0 0 2px var(--tile), 0 0 26px -8px color-mix(in srgb, var(--tile) 50%, transparent);
        }
        .incident-desk .id-tile-glow{ /* decorative corner blob */
            position: absolute; bottom: -22px; right: -22px; width: 84px; height: 84px;
            border-radius: 50%; background: var(--tile); opacity: .15; filter: blur(22px);
            pointer-events: none;
        }
        .incident-desk .id-tile-icon{
            position: absolute; top: 12px; right: 12px;
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: color-mix(in srgb, var(--tile) 12%, transparent);
            color: var(--tile); font-size: 14px;
        }
        .incident-desk .id-tile-number{
            display: block; position: relative;
            font-family: var(--k-font-display, 'Inter Tight', 'Inter', sans-serif);
            font-size: 24px; font-weight: 700; line-height: 1.1; letter-spacing: -.01em;
            color: var(--k-text, #1f2933); font-variant-numeric: tabular-nums;
        }
        .incident-desk .id-tile-label{
            display: block; margin-top: 3px; position: relative;
            font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
            color: var(--id-muted);
        }

        /* ---------- cards ---------- */
        .incident-desk .id-grid{
            display: grid; grid-template-columns: 370px minmax(0, 1fr); gap: 16px; align-items: start;
        }
        @media (max-width: 991px){
            .incident-desk .id-grid{ grid-template-columns: 1fr; }
        }
        .incident-desk .id-card{
            --card-accent: var(--k-accent, #E63B2E);
            background: var(--id-surface);
            border: 1px solid var(--id-border);
            border-radius: 14px; overflow: hidden;
            box-shadow: var(--k-shadow-sm, 0 1px 2px rgba(16,24,40,.06));
            margin-bottom: 16px;
        }
        .incident-desk .id-card-head{
            display: flex; align-items: center; gap: 10px;
            padding: 11px 16px; border-bottom: 1px solid var(--id-border);
            background: color-mix(in srgb, var(--card-accent) 6%, var(--id-surface));
        }
        .incident-desk .id-card-icon{
            width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: color-mix(in srgb, var(--card-accent) 15%, transparent);
            color: var(--card-accent); font-size: 13px;
        }
        .incident-desk .id-card-head h3{
            margin: 0; font-size: 14px; font-weight: 700; letter-spacing: -.01em;
            color: var(--k-text, #1f2933);
        }
        .incident-desk .id-card-body{ padding: 16px; }
        .incident-desk .id-card-foot{
            padding: 12px 16px; border-top: 1px solid var(--id-border);
            background: color-mix(in srgb, var(--card-accent) 3%, var(--id-surface));
        }

        /* form rhythm inside the file-a-ticket box */
        .incident-desk .id-form label{
            display: block; margin: 12px 0 4px;
            font-size: 12px; font-weight: 600; color: var(--k-label, #374151);
        }
        .incident-desk .id-form label:first-of-type{ margin-top: 0; }
        .incident-desk .id-form .form-control{ width: 100%; }
        .incident-desk .id-form textarea.form-control{ min-height: 70px; resize: vertical; }
        .incident-desk .id-form .select2-container{ width: 100% !important; }

        /* segmented status control */
        .incident-desk .status-choices{
            display: flex; border: 1px solid var(--id-border);
            border-radius: var(--k-radius-sm, 6px); overflow: hidden;
        }
        .incident-desk .status-choice{
            flex: 1; text-align: center; padding: 7px 4px;
            font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
            cursor: pointer; color: var(--id-muted);
            background: var(--k-surface, #fff);
            border-right: 1px solid var(--id-border);
            transition: background var(--k-motion, 160ms ease), color var(--k-motion, 160ms ease);
        }
        .incident-desk .status-choice:last-child{ border-right: none; }
        .incident-desk .status-choice:hover{ background: var(--k-hover-tint, #f3f4f6); }
        .incident-desk .status-choice.active{
            background: color-mix(in srgb, var(--id-closed) 12%, var(--k-surface, #fff));
            color: var(--id-closed);
        }
        .incident-desk .status-choice.active[data-s="Open"]{
            background: color-mix(in srgb, var(--id-open) 12%, var(--k-surface, #fff));
            color: var(--id-open);
        }
        .incident-desk .status-choice.active[data-s="Need Vendor"]{
            background: color-mix(in srgb, var(--id-vendor) 14%, var(--k-surface, #fff));
            color: var(--id-vendor);
        }

        .incident-desk .clock-row{
            display: flex; justify-content: space-between; align-items: center;
            border-top: 1px dashed var(--id-border); border-bottom: 1px dashed var(--id-border);
            padding: 8px 2px; font-size: 12px; color: var(--id-muted); margin-top: 14px;
        }
        .incident-desk .clock-time{ color: var(--k-text, #1f2933); font-weight: 600; }
        .incident-desk .form-msg{ font-size: 12px; margin-top: 8px; min-height: 16px; color: var(--k-danger, #c0392b); }

        /* donut */
        .incident-desk .donut-row{ display: flex; align-items: center; gap: 20px; }
        .incident-desk .donut{
            width: 132px; height: 132px; border-radius: 50%; flex-shrink: 0; position: relative;
            background: conic-gradient(var(--id-line) 0deg 360deg);
        }
        .incident-desk .donut-center{
            position: absolute; inset: 18px; background: var(--k-surface, #fff); border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .incident-desk .donut-total{
            font-family: var(--k-font-display, 'Inter Tight', 'Inter', sans-serif);
            font-weight: 700; font-size: 24px; color: var(--k-text, #1f2933); line-height: 1;
        }
        .incident-desk .donut-total-label{
            font-size: 10px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
            color: var(--id-muted); margin-top: 3px;
        }
        .incident-desk .donut-legend{ display: flex; flex-direction: column; gap: 8px; font-size: 12px; }
        .incident-desk .donut-legend-row{ display: flex; align-items: center; gap: 8px; min-width: 150px; }
        .incident-desk .donut-swatch{ width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
        .incident-desk .donut-legend-label{ color: var(--id-muted); flex: 1; }
        .incident-desk .donut-legend-count{ font-weight: 600; color: var(--k-text, #1f2933); font-variant-numeric: tabular-nums; }
        .incident-desk .donut-scope{ font-weight: 400; color: var(--id-muted); text-transform: none; }

        /* ticket log controls */
        .incident-desk .list-controls{ display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; align-items: center; }
        .incident-desk .list-controls input[type=text]{ flex: 1; min-width: 170px; }
        .incident-desk .list-controls input[type=date]{ width: auto; }
        .incident-desk .filter-tabs{
            display: flex; border: 1px solid var(--id-border);
            border-radius: var(--k-radius-sm, 6px); overflow: hidden;
        }
        .incident-desk .filter-tab{
            padding: 7px 12px; background: var(--k-surface, #fff);
            border-right: 1px solid var(--id-border);
            font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
            cursor: pointer; color: var(--id-muted);
        }
        .incident-desk .filter-tab:last-child{ border-right: none; }
        .incident-desk .filter-tab:hover{ background: var(--k-hover-tint, #f3f4f6); }
        .incident-desk .filter-tab.active{
            background: color-mix(in srgb, var(--k-accent, #E63B2E) 10%, var(--k-surface, #fff));
            color: var(--k-accent, #E63B2E);
        }
        .incident-desk .count-line{ font-size: 11.5px; color: var(--id-muted); margin-bottom: 6px; }
        .incident-desk .date-chip{
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px dashed var(--id-border); border-radius: var(--k-radius-sm, 6px);
            padding: 5px 10px; font-size: 12px; color: var(--k-text, #1f2933); margin-bottom: 10px;
        }
        .incident-desk .date-chip button{
            border: none; background: none; color: var(--k-link, #c52d21);
            cursor: pointer; font-family: inherit; font-weight: 600;
        }

        /* ticket rows */
        .incident-desk .ticket{
            position: relative;
            display: grid; grid-template-columns: 64px 1fr auto; gap: 14px; align-items: start;
            padding: 12px 2px; border-bottom: 1px solid var(--id-line);
        }
        .incident-desk .ticket:first-child{ border-top: 1px solid var(--id-line); }
        .incident-desk .t-id{ font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; color: var(--id-muted); padding-top: 3px; }
        .incident-desk .t-body .t-details{ font-size: 13.5px; color: var(--k-text, #1f2933); line-height: 1.45; word-break: break-word; }
        .incident-desk .t-meta{ font-size: 11px; color: var(--id-muted); margin-top: 4px; }
        .incident-desk .t-right{ display: flex; flex-direction: column; align-items: flex-end; gap: 7px; }
        .incident-desk .tag{
            font-weight: 600; font-size: 10px; letter-spacing: .05em; text-transform: uppercase;
            padding: 3px 9px; border-radius: var(--k-radius-pill, 999px); white-space: nowrap;
        }
        .incident-desk .tag.Closed{
            background: color-mix(in srgb, var(--id-closed) 12%, transparent);
            color: var(--id-closed);
        }
        .incident-desk .tag.Open{
            background: color-mix(in srgb, var(--id-open) 12%, transparent);
            color: var(--id-open);
        }
        .incident-desk .tag.Need-Vendor{
            background: color-mix(in srgb, var(--id-vendor) 14%, transparent);
            color: var(--id-vendor);
        }
        .incident-desk .t-actions{ display: flex; gap: 6px; align-items: center; }
        .incident-desk .t-actions select{ width: auto; height: 26px; padding: 2px 6px; font-size: 11px; }
        .incident-desk .empty-state{ padding: 36px 10px; text-align: center; color: var(--id-muted); font-size: 13px; }
        .incident-desk .list-scroll{ max-height: 640px; overflow-y: auto; padding-right: 4px; }

        /* inline edit row */
        .incident-desk .edit-row{
            background: var(--k-hover-tint, #f3f4f6);
            grid-template-columns: 64px 1fr; padding: 14px 12px;
            border-radius: var(--k-radius-sm, 6px);
        }
        .incident-desk .edit-body{ display: flex; flex-direction: column; }
        .incident-desk .edit-body label{ margin: 10px 0 4px; font-size: 12px; font-weight: 600; color: var(--k-label, #374151); }
        .incident-desk .edit-body label:first-child{ margin-top: 0; }
        .incident-desk .edit-body textarea.form-control{ min-height: 54px; }
        .incident-desk .edit-actions{ display: flex; gap: 8px; margin-top: 12px; }
    </style>

    {{-- header stat tiles: full-width strip, one accent family per tile --}}
    <div class="id-tiles">
        <div class="id-tile" style="--tile:#7c5cd6;">
            <span class="id-tile-glow" aria-hidden="true"></span>
            <span class="id-tile-icon" aria-hidden="true"><i class="fas fa-ticket"></i></span>
            <span class="id-tile-number" id="idStatTotal">0</span>
            <span class="id-tile-label">{{ trans('admin/incident_desk/general.tickets_on_file') }}</span>
        </div>
        <div class="id-tile" style="--tile:#2f9e6e;">
            <span class="id-tile-glow" aria-hidden="true"></span>
            <span class="id-tile-icon" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
            <span class="id-tile-number" id="idStatClosed">0</span>
            <span class="id-tile-label">{{ trans('admin/incident_desk/general.closed') }}</span>
        </div>
        <div class="id-tile" style="--tile:#E63B2E;">
            <span class="id-tile-glow" aria-hidden="true"></span>
            <span class="id-tile-icon" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
            <span class="id-tile-number" id="idStatOpen">0</span>
            <span class="id-tile-label">{{ trans('admin/incident_desk/general.open') }}</span>
        </div>
        <div class="id-tile" style="--tile:#d97706;">
            <span class="id-tile-glow" aria-hidden="true"></span>
            <span class="id-tile-icon" aria-hidden="true"><i class="fas fa-truck"></i></span>
            <span class="id-tile-number" id="idStatVendor">0</span>
            <span class="id-tile-label">{{ trans('admin/incident_desk/general.need_vendor') }}</span>
        </div>
        <div class="id-tile" style="--tile:#0284c7;">
            <span class="id-tile-glow" aria-hidden="true"></span>
            <span class="id-tile-icon" aria-hidden="true"><i class="fas fa-calendar-day"></i></span>
            <span class="id-tile-number" id="idStatToday">0</span>
            <span class="id-tile-label">{{ trans('admin/incident_desk/general.logged_today') }}</span>
        </div>
        <div class="id-tile id-tile--link" id="idTileUntagged" style="--tile:#64748b;" role="button" tabindex="0"
             title="{{ trans('admin/incident_desk/general.untagged_hint') }}">
            <span class="id-tile-glow" aria-hidden="true"></span>
            <span class="id-tile-icon" aria-hidden="true"><i class="fas fa-link-slash"></i></span>
            <span class="id-tile-number" id="idStatUntagged">0</span>
            <span class="id-tile-label">{{ trans('admin/incident_desk/general.untagged') }}</span>
        </div>
    </div>

    <div class="id-grid">
        <div>
            <div class="id-card" style="--card-accent: var(--k-accent, #E63B2E);">
                <div class="id-card-head">
                    <span class="id-card-icon" aria-hidden="true"><i class="fas fa-plus"></i></span>
                    <h3>{{ trans('admin/incident_desk/general.file_ticket') }}</h3>
                </div>
                <div class="id-card-body id-form">
                    <label for="idRequesterSelect">{{ trans('admin/incident_desk/general.requester') }}</label>
                    <select id="idRequesterSelect" class="js-data-ajax form-control" data-endpoint="users" data-placeholder="{{ trans('admin/incident_desk/general.requester_placeholder') }}" style="width:100%">
                        <option value="" role="option">{{ trans('admin/incident_desk/general.requester_placeholder') }}</option>
                    </select>
                    <datalist id="idUsersDatalist">
                        @foreach ($requesters as $requesterName)
                            <option value="{{ $requesterName }}"></option>
                        @endforeach
                    </datalist>

                    <label for="idCategorySelect">{{ trans('admin/incident_desk/general.category') }}</label>
                    <select id="idCategorySelect" class="form-control">
                        <option value="">{{ trans('admin/incident_desk/general.select_category') }}</option>
                        @foreach ($taskCategories as $categoryName => $taskTypes)
                            <option value="{{ $categoryName }}">{{ $categoryName }}</option>
                        @endforeach
                    </select>

                    <label for="idTaskTypeSelect">{{ trans('admin/incident_desk/general.task_type') }}</label>
                    <select id="idTaskTypeSelect" class="form-control" disabled>
                        <option value="">{{ trans('admin/incident_desk/general.select_category_first') }}</option>
                    </select>

                    <label for="idAssetSelect">{{ trans('admin/incident_desk/general.device_optional') }} <span style="color:var(--k-danger,#c0392b);">*</span></label>
                    <select id="idAssetSelect" class="js-data-ajax form-control" data-endpoint="hardware" data-placeholder="{{ trans('general.select_asset') }}" style="width:100%">
                        <option value="" role="option">{{ trans('general.select_asset') }}</option>
                    </select>

                    <label for="idDetailsInput">{{ trans('admin/incident_desk/general.details') }} <span style="font-weight:400; color:var(--k-text-muted,#6b7280);">{{ trans('admin/incident_desk/general.details_optional') }}</span></label>
                    <textarea id="idDetailsInput" class="form-control" placeholder="{{ trans('admin/incident_desk/general.details_placeholder') }}"></textarea>

                    <label>{{ trans('admin/incident_desk/general.status') }}</label>
                    <div class="status-choices" id="idStatusChoices">
                        <div class="status-choice active" data-s="Closed">{{ trans('admin/incident_desk/general.closed') }}</div>
                        <div class="status-choice" data-s="Open">{{ trans('admin/incident_desk/general.open') }}</div>
                        <div class="status-choice" data-s="Need Vendor">{{ trans('admin/incident_desk/general.vendor') }}</div>
                    </div>

                    <div class="clock-row">
                        <span>{{ trans('admin/incident_desk/general.timestamp') }}</span>
                        <span class="clock-time mono" id="idLiveClock">&mdash;</span>
                    </div>

                    <button class="btn btn-primary btn-block" id="idSubmitBtn" style="margin-top:14px;" disabled>{{ trans('admin/incident_desk/general.log_ticket') }}</button>
                    <div class="form-msg" id="idFormMsg"></div>
                </div>
                <div class="id-card-foot">
                    <button class="btn btn-default btn-block" id="idExportCsvBtn" type="button">
                        <i class="fas fa-download" aria-hidden="true"></i> {{ trans('admin/incident_desk/general.export_csv') }}
                    </button>
                </div>
            </div>

            <div class="id-card" style="--card-accent:#7c5cd6;">
                <div class="id-card-head">
                    <span class="id-card-icon" aria-hidden="true"><i class="fas fa-chart-pie"></i></span>
                    <h3>{{ trans('admin/incident_desk/general.status_breakdown') }} <span class="donut-scope" id="idDonutDateLabel"></span></h3>
                </div>
                <div class="id-card-body">
                    <div class="donut-row">
                        <div class="donut" id="idDonutChart"><div class="donut-center"><div class="donut-total" id="idDonutTotal">0</div><div class="donut-total-label">{{ trans('admin/incident_desk/general.tickets') }}</div></div></div>
                        <div class="donut-legend" id="idDonutLegend"></div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="id-card" style="--card-accent:#0284c7;">
                <div class="id-card-head">
                    <span class="id-card-icon" aria-hidden="true"><i class="fas fa-list"></i></span>
                    <h3>{{ trans('admin/incident_desk/general.ticket_log') }}</h3>
                </div>
                <div class="id-card-body">
                    <div class="list-controls">
                        <input type="text" id="idSearchInput" class="form-control" placeholder="{{ trans('admin/incident_desk/general.search_placeholder') }}">
                        <input type="date" id="idDateJump" class="form-control" title="{{ trans('admin/incident_desk/general.jump_to_date') }}">
                        <div class="filter-tabs" id="idFilterTabs">
                            <div class="filter-tab active" data-f="All">{{ trans('admin/incident_desk/general.all') }}</div>
                            <div class="filter-tab" data-f="Closed">{{ trans('admin/incident_desk/general.closed') }}</div>
                            <div class="filter-tab" data-f="Open">{{ trans('admin/incident_desk/general.open') }}</div>
                            <div class="filter-tab" data-f="Need Vendor">{{ trans('admin/incident_desk/general.vendor') }}</div>
                            <div class="filter-tab" data-f="Untagged">{{ trans('admin/incident_desk/general.untagged') }}</div>
                        </div>
                    </div>
                    <div class="count-line" id="idCountLine"></div>
                    <div id="idDateChip" style="display:none;"></div>
                    <div class="list-scroll"><div id="idTicketList"></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
(function(){
    const TASK_CATEGORIES = @json($taskCategories);
    const URLS = {
        list:    @json(route('maintenances.incident-desk.tickets')),
        store:   @json(route('maintenances.incident-desk.tickets.store')),
        update:  @json(url('maintenances/incident-desk/tickets')),
        destroy: @json(url('maintenances/incident-desk/tickets')),
        userDevice: @json(url('maintenances/incident-desk/user-device')),
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const BASE_URL = (document.querySelector('meta[name="baseUrl"]') || {}).content || '/';

    let tickets = [];
    let currentFilter = 'All';
    let currentSearch = '';
    let currentDate = new Date().toISOString().slice(0,10);
    let activeStatus = 'Closed';
    let editingId = null;

    function themeVar(name){
        return getComputedStyle(document.querySelector('.incident-desk')).getPropertyValue(name).trim();
    }
    function donutColors(){
        return {
            Closed: themeVar('--id-closed') || '#2f9e6e',
            Open: themeVar('--id-open') || '#E63B2E',
            'Need Vendor': themeVar('--id-vendor') || '#b97d10'
        };
    }

    async function api(url, options){
        options = options || {};
        options.headers = Object.assign({
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }, options.headers || {});
        if(options.body && typeof options.body === 'object'){
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }
        const res = await fetch(url, options);
        if(!res.ok){
            let msg = 'Request failed (' + res.status + ').';
            try{
                const data = await res.json();
                if(data.errors){ msg = Object.values(data.errors).flat().join(' '); }
                else if(data.message){ msg = data.message; }
            }catch(e){}
            throw new Error(msg);
        }
        return res.status === 204 ? null : res.json();
    }

    function padId(t){ return t.display_id || ('INC-' + String(t.id).padStart(4,'0')); }

    function fmtDate(iso){
        const d = new Date(iso);
        return d.toLocaleDateString(undefined,{year:'numeric',month:'short',day:'2-digit'}) + '  ' +
               d.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'});
    }
    function statusClass(s){ return s.replace(/ /g, '-'); }

    function renderStats(){
        const today = new Date().toISOString().slice(0,10);
        document.getElementById('idStatTotal').textContent = tickets.length;
        document.getElementById('idStatClosed').textContent = tickets.filter(t=>t.status==='Closed').length;
        document.getElementById('idStatOpen').textContent = tickets.filter(t=>t.status==='Open').length;
        document.getElementById('idStatVendor').textContent = tickets.filter(t=>t.status==='Need Vendor').length;
        document.getElementById('idStatToday').textContent = tickets.filter(t=>(t.created_at||'').slice(0,10)===today).length;
        document.getElementById('idStatUntagged').textContent = tickets.filter(t=>!t.asset_id).length;
    }

    function renderDonut(){
        const scope = currentDate ? tickets.filter(t=>(t.created_at||'').slice(0,10)===currentDate) : tickets;
        const total = scope.length;
        const colors = donutColors();
        const segments = [
            {label:'Closed', count:scope.filter(t=>t.status==='Closed').length, color:colors.Closed},
            {label:'Open', count:scope.filter(t=>t.status==='Open').length, color:colors.Open},
            {label:'Need Vendor', count:scope.filter(t=>t.status==='Need Vendor').length, color:colors['Need Vendor']}
        ];

        const label = document.getElementById('idDonutDateLabel');
        if(currentDate){
            const d = new Date(currentDate + 'T00:00:00');
            const isToday = currentDate === new Date().toISOString().slice(0,10);
            label.textContent = '— ' + (isToday ? '{{ trans('admin/incident_desk/general.today') }}' : d.toLocaleDateString(undefined,{month:'short',day:'2-digit'}));
        }else{
            label.textContent = '— {{ trans('admin/incident_desk/general.all_time') }}';
        }

        document.getElementById('idDonutTotal').textContent = total;

        const donut = document.getElementById('idDonutChart');
        const center = donut.querySelector('.donut-center');
        if(total === 0){
            donut.style.background = 'conic-gradient(var(--id-line) 0deg 360deg)';
        }else{
            let acc = 0;
            const stops = [];
            segments.forEach(s=>{
                if(s.count <= 0) return;
                const start = acc / total * 360;
                acc += s.count;
                const end = acc / total * 360;
                stops.push(s.color + ' ' + start.toFixed(2) + 'deg ' + end.toFixed(2) + 'deg');
            });
            donut.style.background = 'conic-gradient(' + stops.join(', ') + ')';
        }
        donut.innerHTML = '';
        donut.appendChild(center);

        document.getElementById('idDonutLegend').innerHTML = segments.map(s =>
            '<div class="donut-legend-row">' +
                '<span class="donut-swatch" style="background:' + s.color + ';"></span>' +
                '<span class="donut-legend-label">' + s.label + '</span>' +
                '<span class="donut-legend-count">' + s.count + '</span>' +
            '</div>'
        ).join('');
    }

    function renderList(){
        let list = tickets.slice().sort((a,b)=> (b.created_at||'').localeCompare(a.created_at||'') || b.id-a.id);
        if(currentFilter === 'Untagged') list = list.filter(t=>!t.asset_id);
        else if(currentFilter !== 'All') list = list.filter(t=>t.status===currentFilter);
        if(currentDate) list = list.filter(t=>(t.created_at||'').slice(0,10)===currentDate);
        if(currentSearch.trim()){
            const q = currentSearch.trim().toLowerCase();
            list = list.filter(t => (t.details||'').toLowerCase().includes(q) || padId(t).toLowerCase().includes(q));
        }
        document.getElementById('idCountLine').textContent = list.length + ' {{ trans('admin/incident_desk/general.tickets_shown') }}';

        const chip = document.getElementById('idDateChip');
        if(currentDate){
            const isToday = currentDate === new Date().toISOString().slice(0,10);
            const label = isToday ? 'Today &mdash; ' + new Date(currentDate + 'T00:00:00').toLocaleDateString(undefined,{month:'short',day:'2-digit'}) :
                new Date(currentDate + 'T00:00:00').toLocaleDateString(undefined,{weekday:'short',year:'numeric',month:'short',day:'2-digit'});
            chip.style.display = 'block';
            chip.innerHTML = '';
            const span = document.createElement('span');
            span.className = 'date-chip';
            span.innerHTML = '{{ trans('admin/incident_desk/general.showing') }} ' + label + ' <button id="idClearDateBtn">{{ trans('admin/incident_desk/general.view_all_days') }}</button>';
            chip.appendChild(span);
            document.getElementById('idClearDateBtn').addEventListener('click', ()=>{
                currentDate = ''; document.getElementById('idDateJump').value = ''; renderList(); renderDonut();
            });
        }else{
            chip.style.display = 'none'; chip.innerHTML = '';
        }

        const container = document.getElementById('idTicketList');
        if(list.length === 0){
            container.innerHTML = '<div class="empty-state">{{ trans('admin/incident_desk/general.no_tickets') }}</div>';
            return;
        }
        container.innerHTML = list.map(t => t.id === editingId ? renderEditRow(t) : renderTicketRow(t)).join('');

        container.querySelectorAll('[data-role="status-select"]').forEach(sel=>{
            sel.addEventListener('change', async (e)=>{
                const id = Number(e.target.closest('.ticket').dataset.id);
                try{
                    const res = await api(URLS.update + '/' + id, {method:'PUT', body:{status: e.target.value}});
                    replaceTicket(res.ticket);
                    render();
                }catch(err){ alert(err.message); renderList(); }
            });
        });
        container.querySelectorAll('[data-role="del"]').forEach(btn=>{
            btn.addEventListener('click', async (e)=>{
                const id = Number(e.target.closest('.ticket').dataset.id);
                const t = tickets.find(x=>x.id===id);
                if(!confirm('{{ trans('admin/incident_desk/general.delete_confirm') }}'.replace(':id', t ? padId(t) : id))) return;
                try{
                    await api(URLS.destroy + '/' + id, {method:'DELETE'});
                    tickets = tickets.filter(x=>x.id!==id);
                    if(editingId === id) editingId = null;
                    render();
                }catch(err){ alert(err.message); }
            });
        });
        container.querySelectorAll('[data-role="edit"]').forEach(btn=>{
            btn.addEventListener('click', (e)=>{
                editingId = Number(e.target.closest('.ticket').dataset.id);
                renderList();
                initEditAssetSelect();
            });
        });
        container.querySelectorAll('[data-role="edit-cancel"]').forEach(btn=>{
            btn.addEventListener('click', ()=>{ editingId = null; renderList(); });
        });
        container.querySelectorAll('[data-role="edit-category"]').forEach(sel=>{
            sel.addEventListener('change', (e)=>{
                const wrap = e.target.closest('.ticket');
                const typeSel = wrap.querySelector('[data-role="edit-tasktype"]');
                const cat = e.target.value;
                typeSel.innerHTML = '<option value="">{{ trans('admin/incident_desk/general.select_type') }}</option>' +
                    (TASK_CATEGORIES[cat]||[]).map(tt=>'<option value="' + escapeHtml(tt) + '">' + escapeHtml(tt) + '</option>').join('');
            });
        });
        container.querySelectorAll('[data-role="edit-status"]').forEach(wrap=>{
            wrap.querySelectorAll('.status-choice').forEach(chipEl=>{
                chipEl.addEventListener('click', ()=>{
                    wrap.querySelectorAll('.status-choice').forEach(c=>c.classList.remove('active'));
                    chipEl.classList.add('active');
                });
            });
        });
        container.querySelectorAll('[data-role="edit-save"]').forEach(btn=>{
            btn.addEventListener('click', async (e)=>{
                const wrap = e.target.closest('.ticket');
                const id = Number(wrap.dataset.id);
                const requester = wrap.querySelector('[data-role="edit-requester"]').value.trim();
                const category = wrap.querySelector('[data-role="edit-category"]').value;
                const taskType = wrap.querySelector('[data-role="edit-tasktype"]').value;
                const notes = wrap.querySelector('[data-role="edit-notes"]').value.trim();
                const statusEl = wrap.querySelector('.status-choice.active');
                const status = statusEl ? statusEl.dataset.s : null;
                const assetSel = wrap.querySelector('[data-role="edit-asset"]');
                const assetId = assetSel ? (assetSel.value || null) : null;
                if(!category || !taskType){ alert('{{ trans('admin/incident_desk/general.validation_category') }}'); return; }
                if(!assetId){ alert('{{ trans('admin/incident_desk/general.validation_asset') }}'); return; }
                try{
                    const res = await api(URLS.update + '/' + id, {method:'PUT', body:{
                        requester_name: requester, category: category, task_type: taskType,
                        issue: notes, status: status, asset_id: assetId
                    }});
                    replaceTicket(res.ticket);
                    editingId = null;
                    render();
                }catch(err){ alert(err.message); }
            });
        });
    }

    function replaceTicket(updated){
        const i = tickets.findIndex(x=>x.id===updated.id);
        if(i >= 0){ tickets[i] = updated; } else { tickets.push(updated); }
    }

    function initEditAssetSelect(){
        const sel = document.querySelector('.ticket.edit-row [data-role="edit-asset"]');
        if(!sel || !window.jQuery || !jQuery.fn.select2) return;
        jQuery(sel).select2({
            placeholder: '{{ trans('general.select_asset') }}',
            allowClear: true,
            ajax: {
                url: BASE_URL + 'api/v1/hardware/selectlist',
                dataType: 'json',
                delay: 250,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF},
                data: function(params){ return {search: params.term, page: params.page || 1}; },
                processResults: function(data){
                    return {
                        results: (data.results || []).map(o => ({id: o.id, text: o.text})),
                        pagination: {more: (data.pagination || {}).more}
                    };
                }
            }
        });
    }

    function renderTicketRow(t){
        const metaBits = [];
        if(t.category) metaBits.push(escapeHtml(t.category));
        if(t.requester_name) metaBits.push(escapeHtml(t.requester_name));
        if(t.asset_label) metaBits.push(escapeHtml(t.asset_label));
        metaBits.push(fmtDate(t.created_at));
        return '' +
        '<div class="ticket" data-id="' + t.id + '">' +
            '<div class="t-id">' + escapeHtml(padId(t)) + '</div>' +
            '<div class="t-body">' +
                '<div class="t-details">' + escapeHtml(t.details || '') + '</div>' +
                '<div class="t-meta">' + metaBits.join(' &middot; ') + '</div>' +
            '</div>' +
            '<div class="t-right">' +
                '<div class="tag ' + statusClass(t.status) + '">' + escapeHtml(t.status) + '</div>' +
                '<div class="t-actions">' +
                    '<select data-role="status-select" class="form-control input-sm">' +
                        ['Closed','Open','Need Vendor'].map(s=>'<option value="' + s + '"' + (t.status===s?' selected':'') + '>' + s + '</option>').join('') +
                    '</select>' +
                    '<button class="btn btn-default btn-sm" data-role="edit">{{ trans('admin/incident_desk/general.edit') }}</button>' +
                    '<button class="btn btn-danger btn-sm" data-role="del">{{ trans('admin/incident_desk/general.delete') }}</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function renderEditRow(t){
        const catOptions = Object.keys(TASK_CATEGORIES).map(c=>'<option value="' + escapeHtml(c) + '"' + (t.category===c?' selected':'') + '>' + escapeHtml(c) + '</option>').join('');
        const typeOptions = (t.category ? (TASK_CATEGORIES[t.category]||[]) : []).map(tt=>'<option value="' + escapeHtml(tt) + '"' + (t.task_type===tt?' selected':'') + '>' + escapeHtml(tt) + '</option>').join('');
        const assetOption = t.asset_id ? '<option value="' + t.asset_id + '" selected>' + escapeHtml(t.asset_label || ('#' + t.asset_id)) + '</option>' : '';
        return '' +
        '<div class="ticket edit-row" data-id="' + t.id + '">' +
            '<div class="t-id">' + escapeHtml(padId(t)) + '</div>' +
            '<div class="t-body edit-body">' +
                '<label>{{ trans('admin/incident_desk/general.requester') }}</label>' +
                '<input type="text" class="form-control" data-role="edit-requester" list="idUsersDatalist" value="' + escapeHtml(t.requester_name||'') + '">' +
                '<label>{{ trans('admin/incident_desk/general.category') }}</label>' +
                '<select class="form-control" data-role="edit-category"><option value="">{{ trans('admin/incident_desk/general.select_category') }}</option>' + catOptions + '</select>' +
                '<label>{{ trans('admin/incident_desk/general.task_type') }}</label>' +
                '<select class="form-control" data-role="edit-tasktype"><option value="">{{ trans('admin/incident_desk/general.select_type') }}</option>' + typeOptions + '</select>' +
                '<label>{{ trans('admin/incident_desk/general.device_optional') }}</label>' +
                '<select data-role="edit-asset" style="width:100%"><option value=""></option>' + assetOption + '</select>' +
                '<label>{{ trans('admin/incident_desk/general.notes') }}</label>' +
                '<textarea class="form-control" data-role="edit-notes">' + escapeHtml(t.issue||'') + '</textarea>' +
                '<label>{{ trans('admin/incident_desk/general.status') }}</label>' +
                '<div class="status-choices" data-role="edit-status">' +
                    '<div class="status-choice' + (t.status==='Closed'?' active':'') + '" data-s="Closed">{{ trans('admin/incident_desk/general.closed') }}</div>' +
                    '<div class="status-choice' + (t.status==='Open'?' active':'') + '" data-s="Open">{{ trans('admin/incident_desk/general.open') }}</div>' +
                    '<div class="status-choice' + (t.status==='Need Vendor'?' active':'') + '" data-s="Need Vendor">{{ trans('admin/incident_desk/general.vendor') }}</div>' +
                '</div>' +
                '<div class="edit-actions">' +
                    '<button class="btn btn-primary" data-role="edit-save" type="button">{{ trans('admin/incident_desk/general.save_changes') }}</button>' +
                    '<button class="btn btn-default" data-role="edit-cancel" type="button">{{ trans('admin/incident_desk/general.cancel') }}</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function escapeHtml(str){
        return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }

    function render(){ renderStats(); renderDonut(); renderList(); }

    function tickClock(){
        document.getElementById('idLiveClock').textContent = new Date().toLocaleString(undefined,{
            year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit'
        });
    }
    tickClock(); setInterval(tickClock, 1000);

    document.getElementById('idCategorySelect').addEventListener('change', (e)=>{
        const sel = document.getElementById('idTaskTypeSelect');
        sel.innerHTML = '';
        if(!e.target.value){
            sel.disabled = true;
            sel.innerHTML = '<option value="">{{ trans('admin/incident_desk/general.select_category_first') }}</option>';
            return;
        }
        sel.disabled = false;
        sel.innerHTML = '<option value="">{{ trans('admin/incident_desk/general.select_type') }}</option>' +
            (TASK_CATEGORIES[e.target.value]||[]).map(t=>'<option value="' + escapeHtml(t) + '">' + escapeHtml(t) + '</option>').join('');
    });

    document.getElementById('idStatusChoices').addEventListener('click', (e)=>{
        const el = e.target.closest('.status-choice');
        if(!el) return;
        document.querySelectorAll('#idStatusChoices .status-choice').forEach(c=>c.classList.remove('active'));
        el.classList.add('active');
        activeStatus = el.dataset.s;
    });

    /* Auto-suggest the requester's checked-out device when the device
       picker is still empty; the technician can change or clear it. */
    if(window.jQuery){
        jQuery('#idRequesterSelect').on('change', async function(){
            const uid = jQuery(this).val();
            const assetSel = jQuery('#idAssetSelect');
            if(!uid || (assetSel.val() && assetSel.val() !== '')) return;
            try{
                const res = await api(URLS.userDevice + '/' + uid);
                if(res.device){
                    assetSel.append(new Option(res.device.text, res.device.id, true, true)).trigger('change');
                }
            }catch(e){ /* no device or lookup failed — leave the picker empty */ }
        });
    }

    document.getElementById('idSubmitBtn').addEventListener('click', async ()=>{        const msg = document.getElementById('idFormMsg');
        const requesterOption = document.querySelector('#idRequesterSelect option:checked');
        const requester = requesterOption && requesterOption.value
            ? requesterOption.textContent.trim().replace(/\s*\(.*$/, '')
            : '';
        const category = document.getElementById('idCategorySelect').value;
        const taskType = document.getElementById('idTaskTypeSelect').value;
        const notes = document.getElementById('idDetailsInput').value.trim();
        const assetId = document.getElementById('idAssetSelect').value || null;
        if(!category || !taskType){
            msg.style.color = 'var(--k-danger, #c0392b)';
            msg.textContent = '{{ trans('admin/incident_desk/general.validation_category') }}';
            return;
        }
        if(!assetId){
            msg.style.color = 'var(--k-danger, #c0392b)';
            msg.textContent = '{{ trans('admin/incident_desk/general.validation_asset') }}';
            return;
        }
        try{
            const res = await api(URLS.store, {method:'POST', body:{
                requester_name: requester, category: category, task_type: taskType,
                issue: notes, status: activeStatus, asset_id: assetId
            }});
            tickets.push(res.ticket);
            document.getElementById('idDetailsInput').value = '';
            if(window.jQuery) jQuery('#idRequesterSelect').val(null).trigger('change');
            document.getElementById('idCategorySelect').value = '';
            const typeSel = document.getElementById('idTaskTypeSelect');
            typeSel.disabled = true;
            typeSel.innerHTML = '<option value="">{{ trans('admin/incident_desk/general.select_category_first') }}</option>';
            if(window.jQuery) jQuery('#idAssetSelect').val(null).trigger('change');
            msg.style.color = 'var(--k-success, #2f9e6e)';
            msg.textContent = '{{ trans('admin/incident_desk/general.logged') }}'.replace(':id', padId(res.ticket));
            setTimeout(()=>{ msg.textContent=''; }, 2500);
            render();
        }catch(err){
            msg.style.color = 'var(--k-danger, #c0392b)';
            msg.textContent = err.message;
        }
    });

    document.getElementById('idExportCsvBtn').addEventListener('click', ()=>{
        const rows = tickets.slice().sort((a,b)=> (a.created_at||'').localeCompare(b.created_at||'')).map(t=>({
            'Ticket ID': padId(t),
            'Requester': t.requester_name || '',
            'Category': t.category || '',
            'Problem / Incident Type': t.task_type || '',
            'Notes': t.issue || '',
            'Details': t.details || '',
            'Asset': t.asset_label || '',
            'Status': t.status,
            'Date': (t.created_at||'').slice(0,10),
            'Time': t.created_at ? new Date(t.created_at).toLocaleTimeString() : ''
        }));
        const header = Object.keys(rows[0] || {'Ticket ID':''});
        const csv = [header.join(',')].concat(rows.map(r => header.map(k => '"' + String(r[k]).replace(/"/g,'""') + '"').join(','))).join('\r\n');
        const blob = new Blob(['﻿' + csv], {type:'text/csv;charset=utf-8;'});
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'killa-design-incident-tickets-' + new Date().toISOString().slice(0,10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(a.href);
    });

    document.getElementById('idSearchInput').addEventListener('input', (e)=>{ currentSearch = e.target.value; renderList(); });
    function setFilter(f){
        currentFilter = f;
        document.querySelectorAll('#idFilterTabs .filter-tab').forEach(c=>c.classList.toggle('active', c.dataset.f===f));
        const untaggedTile = document.getElementById('idTileUntagged');
        if(untaggedTile) untaggedTile.classList.toggle('active', f==='Untagged');
        renderList();
    }

    document.getElementById('idFilterTabs').addEventListener('click', (e)=>{
        const el = e.target.closest('.filter-tab');
        if(!el) return;
        setFilter(el.dataset.f);
    });

    const untaggedTile = document.getElementById('idTileUntagged');
    function toggleUntagged(){
        setFilter(currentFilter === 'Untagged' ? 'All' : 'Untagged');
    }
    untaggedTile.addEventListener('click', toggleUntagged);
    untaggedTile.addEventListener('keydown', (e)=>{
        if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); toggleUntagged(); }
    });
    document.getElementById('idDateJump').addEventListener('change', (e)=>{ currentDate = e.target.value; renderList(); renderDonut(); });

    document.getElementById('idDateJump').value = currentDate;
    api(URLS.list).then(data => {
        tickets = data.tickets || [];
        document.getElementById('idSubmitBtn').disabled = false;
        render();
    }).catch(err => {
        document.getElementById('idFormMsg').textContent = err.message;
    });
})();
</script>
@stop
