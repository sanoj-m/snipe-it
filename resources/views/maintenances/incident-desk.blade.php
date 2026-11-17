@extends('layouts/default')

@section('title')
    {{ trans('admin/incident_desk/general.incident_desk') }}
@stop

@section('content')
<div class="incident-desk">
    <style>
        .incident-desk{
            --id-white:#FFFFFF;
            --id-paper:#FAFAF8;
            --id-ink:#121212;
            --id-ink-soft:#6B6862;
            --id-ink-faint:#A5A199;
            --id-line:#E4E1DA;
            --id-line-strong:#121212;
            --id-red:#EF3326;
            --id-font:'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif;
            --id-font-display:'Inter Tight', 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif;
            --id-font-mono:ui-monospace, 'Cascadia Mono', Menlo, Consolas, monospace;
            background:var(--id-white);
            color:var(--id-ink);
            font-family:var(--id-font);
            margin:-20px -15px;
        }
        .incident-desk *, .incident-desk *::before, .incident-desk *::after{box-sizing:border-box;}
        .incident-desk .mono{font-family:var(--id-font-mono);}
        .incident-desk .disp{font-family:var(--id-font-display);}

        /* ---------- page header ---------- */
        .incident-desk .pagebar{
            display:flex; align-items:center; gap:14px;
            padding:14px 28px; border-bottom:1px solid var(--id-line-strong);
            background:var(--id-white);
        }
        .incident-desk .brand-sub{
            font-family:var(--id-font-display); font-weight:600; font-size:12px;
            letter-spacing:.12em; text-transform:uppercase; color:var(--id-ink-soft);
        }
        .incident-desk .storage-badge{
            margin-left:auto; font-size:10.5px; letter-spacing:.04em; color:var(--id-ink-soft);
            display:flex; align-items:center; gap:6px;
        }
        .incident-desk .storage-dot{width:7px; height:7px; border-radius:50%; background:var(--id-red);}

        /* ---------- hero ---------- */
        .incident-desk .hero{
            position:relative;
            display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap;
            gap:24px; padding:56px 28px 36px; border-bottom:1px solid var(--id-line);
            background:
              linear-gradient(90deg, rgba(255,255,255,.97) 0%, rgba(255,255,255,.92) 38%, rgba(255,255,255,.55) 100%),
              url('https://commons.wikimedia.org/wiki/Special:FilePath/Museum%20of%20the%20Future.jpg?width=1800') center 38% / cover no-repeat,
              var(--id-ink);
        }
        .incident-desk .hero::after{
            content:''; position:absolute; left:0; right:0; bottom:0; height:3px; background:var(--id-red);
        }
        .incident-desk .hero-credit{
            position:absolute; right:28px; bottom:6px; font-size:8.5px; letter-spacing:.03em; color:var(--id-ink-faint);
            background:rgba(255,255,255,.75); padding:2px 6px;
        }
        .incident-desk .hero h1{
            font-family:var(--id-font-display); font-weight:700; font-size:38px; line-height:1.05;
            margin:0; letter-spacing:-.01em; max-width:12ch; color:var(--id-ink);
        }
        .incident-desk .hero h1 em{font-style:normal; color:var(--id-red);}
        .incident-desk .hero p{margin:12px 0 0; color:var(--id-ink-soft); font-size:13px; max-width:42ch; line-height:1.6;}
        .incident-desk .counter-block{text-align:right;}
        .incident-desk .counter-label{font-size:10px; letter-spacing:.16em; text-transform:uppercase; color:var(--id-ink-soft); margin-bottom:4px;}
        .incident-desk .counter-num{
            font-family:var(--id-font-display); font-weight:800; font-size:56px; line-height:1;
            color:var(--id-red); font-variant-numeric:tabular-nums;
        }

        /* ---------- stat strip ---------- */
        .incident-desk .stats{display:flex; border-bottom:1px solid var(--id-line); flex-wrap:wrap;}
        .incident-desk .stat{flex:1; min-width:110px; padding:16px 28px; border-right:1px solid var(--id-line);}
        .incident-desk .stat:last-child{border-right:none;}
        .incident-desk .stat .n{font-family:var(--id-font-display); font-weight:700; font-size:26px; font-variant-numeric:tabular-nums;}
        .incident-desk .stat .l{font-size:10px; letter-spacing:.12em; text-transform:uppercase; color:var(--id-ink-soft); margin-top:3px;}
        .incident-desk .stat.open .n{color:var(--id-red);}

        /* ---------- layout ---------- */
        .incident-desk .body-grid{display:grid; grid-template-columns:340px 1fr;}
        @media (max-width:900px){ .incident-desk .body-grid{grid-template-columns:1fr;} }
        .incident-desk .rail{border-right:1px solid var(--id-line); padding:26px 28px;}
        @media (max-width:900px){ .incident-desk .rail{border-right:none; border-bottom:1px solid var(--id-line);} }
        .incident-desk .main{padding:26px 28px;}

        .incident-desk .panel-title{
            font-family:var(--id-font-display); font-weight:700; font-size:11px;
            letter-spacing:.14em; text-transform:uppercase; margin:0 0 16px; color:var(--id-ink);
        }

        /* ---------- form ---------- */
        .incident-desk label{display:block; font-size:10px; letter-spacing:.1em; text-transform:uppercase; color:var(--id-ink-soft); margin:16px 0 6px; font-weight:600;}
        .incident-desk textarea,
        .incident-desk select,
        .incident-desk input[type=text],
        .incident-desk input[type=date]{
            width:100%; font-family:var(--id-font); font-size:13.5px; color:var(--id-ink);
            background:var(--id-white); border:1px solid var(--id-line-strong); padding:10px 11px;
            outline:none; border-radius:0; height:auto;
        }
        .incident-desk textarea{resize:vertical; min-height:66px;}
        .incident-desk textarea:focus, .incident-desk select:focus, .incident-desk input:focus{border-color:var(--id-red); box-shadow:0 0 0 1px var(--id-red);}
        .incident-desk .select2-container{width:100% !important;}
        .incident-desk .clock-row{
            display:flex; justify-content:space-between; align-items:center;
            border-top:1px dashed var(--id-line); border-bottom:1px dashed var(--id-line);
            padding:10px 2px; font-size:11.5px; color:var(--id-ink-soft); margin-top:16px;
        }
        .incident-desk .clock-time{color:var(--id-ink); font-weight:600;}
        .incident-desk .status-choices{display:flex; gap:0; border:1px solid var(--id-line-strong); margin-top:2px;}
        .incident-desk .status-choice{
            flex:1; text-align:center; padding:9px 4px; font-size:10.5px; letter-spacing:.05em;
            text-transform:uppercase; cursor:pointer; color:var(--id-ink-soft); border-right:1px solid var(--id-line-strong);
        }
        .incident-desk .status-choice:last-child{border-right:none;}
        .incident-desk .status-choice.active{background:var(--id-ink); color:var(--id-white); font-weight:600;}
        .incident-desk .status-choice.active[data-s="Open"]{background:var(--id-red);}
        .incident-desk .submit-btn{
            margin-top:18px; width:100%; padding:13px; border:1px solid var(--id-ink);
            background:var(--id-ink); color:var(--id-white); font-family:var(--id-font-display);
            font-weight:700; font-size:12px; letter-spacing:.1em; text-transform:uppercase;
            cursor:pointer; transition:background .15s, border-color .15s;
        }
        .incident-desk .submit-btn:hover:not(:disabled){background:var(--id-red); border-color:var(--id-red);}
        .incident-desk .submit-btn:disabled{background:var(--id-white); color:var(--id-ink-faint); border-color:var(--id-line); cursor:default;}
        .incident-desk .form-msg{font-size:11px; margin-top:10px; min-height:14px; color:var(--id-red);}

        .incident-desk .ghost-btn{
            text-align:center; padding:9px 6px; border:1px solid var(--id-line-strong);
            background:var(--id-white); color:var(--id-ink); font-family:var(--id-font-display);
            font-weight:600; font-size:10px; letter-spacing:.06em; text-transform:uppercase; cursor:pointer;
        }
        .incident-desk .ghost-btn:hover{background:var(--id-ink); color:var(--id-white);}

        /* ---------- donut ---------- */
        .incident-desk .donut-row{display:flex; align-items:center; gap:20px;}
        .incident-desk .donut{
            width:120px; height:120px; border-radius:50%; flex-shrink:0; position:relative;
            background:conic-gradient(var(--id-line) 0deg 360deg);
        }
        .incident-desk .donut-center{
            position:absolute; inset:16px; background:var(--id-white); border-radius:50%;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
        }
        .incident-desk .donut-total{font-family:var(--id-font-display); font-weight:800; font-size:24px; color:var(--id-ink); line-height:1;}
        .incident-desk .donut-total-label{font-size:9px; letter-spacing:.08em; text-transform:uppercase; color:var(--id-ink-faint); margin-top:2px;}
        .incident-desk .donut-legend{display:flex; flex-direction:column; gap:8px; font-size:11.5px;}
        .incident-desk .donut-legend-row{display:flex; align-items:center; gap:8px;}
        .incident-desk .donut-swatch{width:10px; height:10px; flex-shrink:0;}
        .incident-desk .donut-legend-label{color:var(--id-ink-soft); flex:1;}
        .incident-desk .donut-legend-count{font-family:var(--id-font-mono); font-weight:600; color:var(--id-ink);}

        /* ---------- ticket list controls ---------- */
        .incident-desk .list-controls{display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center;}
        .incident-desk .list-controls input[type=text]{flex:1; min-width:170px;}
        .incident-desk .list-controls input[type=date]{width:auto;}
        .incident-desk .filter-tabs{display:flex; border:1px solid var(--id-line-strong);}
        .incident-desk .filter-tab{
            padding:9px 13px; background:var(--id-white); border-right:1px solid var(--id-line-strong);
            font-size:10.5px; letter-spacing:.05em; text-transform:uppercase; cursor:pointer; color:var(--id-ink-soft);
            font-family:var(--id-font-display); font-weight:600;
        }
        .incident-desk .filter-tab:last-child{border-right:none;}
        .incident-desk .filter-tab.active{background:var(--id-ink); color:var(--id-white);}
        .incident-desk .count-line{font-size:11px; color:var(--id-ink-faint); margin-bottom:4px;}
        .incident-desk .date-chip{display:inline-flex; align-items:center; gap:8px; border:1px dashed var(--id-ink-faint); padding:6px 11px; font-size:11px; color:var(--id-ink); margin-bottom:12px;}
        .incident-desk .date-chip button{border:none; background:none; color:var(--id-red); cursor:pointer; font-family:inherit; font-weight:700;}

        /* ---------- ticket row ---------- */
        .incident-desk .ticket{
            position:relative;
            display:grid; grid-template-columns:56px 1fr auto; gap:16px; align-items:start;
            padding:16px 2px; border-bottom:1px solid var(--id-line);
        }
        .incident-desk .ticket:first-child{border-top:1px solid var(--id-line);}
        .incident-desk .t-id{font-family:var(--id-font-mono); font-size:11px; color:var(--id-ink-faint); padding-top:2px;}
        .incident-desk .t-body .t-details{font-size:14px; color:var(--id-ink); line-height:1.45; word-break:break-word;}
        .incident-desk .t-meta{font-family:var(--id-font-mono); font-size:10px; color:var(--id-ink-faint); margin-top:5px; letter-spacing:.02em;}
        .incident-desk .t-right{display:flex; flex-direction:column; align-items:flex-end; gap:7px;}
        .incident-desk .tag{
            font-family:var(--id-font-display); font-weight:700; font-size:9.5px;
            letter-spacing:.08em; text-transform:uppercase; padding:4px 9px; white-space:nowrap;
        }
        .incident-desk .tag.Closed{background:var(--id-ink); color:var(--id-white);}
        .incident-desk .tag.Open{background:var(--id-red); color:var(--id-white);}
        .incident-desk .tag.Need-Vendor{background:var(--id-white); color:var(--id-red); border:1px solid var(--id-red);}
        .incident-desk .t-actions{display:flex; gap:6px;}
        .incident-desk .t-actions select{font-size:9.5px; padding:3px 4px; width:auto; border:1px solid var(--id-line);}
        .incident-desk .t-del{background:none; border:1px solid var(--id-line); color:var(--id-ink-faint); font-size:9.5px; padding:3px 7px; cursor:pointer; font-family:var(--id-font);}
        .incident-desk .t-del:hover{border-color:var(--id-red); color:var(--id-red);}
        .incident-desk .empty-state{padding:36px 10px; text-align:center; color:var(--id-ink-faint); font-size:12.5px;}
        .incident-desk .list-scroll{max-height:660px; overflow-y:auto; padding-right:4px;}

        .incident-desk .edit-row{background:var(--id-paper); grid-template-columns:56px 1fr; padding:16px 12px;}
        .incident-desk .edit-body{display:flex; flex-direction:column;}
        .incident-desk .edit-body label{margin:10px 0 4px;}
        .incident-desk .edit-body label:first-child{margin-top:0;}
        .incident-desk .edit-body input, .incident-desk .edit-body select, .incident-desk .edit-body textarea{font-size:13px;}
        .incident-desk .edit-body textarea{min-height:54px;}
        .incident-desk .edit-actions{display:flex; gap:8px;}
        .incident-desk .edit-actions .submit-btn, .incident-desk .edit-actions .ghost-btn{flex:1;}
    </style>

    <div class="pagebar">
        <div class="brand-sub">{{ trans('admin/incident_desk/general.page_title') }}</div>
        <div class="storage-badge"><span class="storage-dot"></span><span>{{ trans('admin/incident_desk/general.shared_badge') }}</span></div>
    </div>

    <div class="hero">
        <div>
            <h1 class="disp">{{ trans('admin/incident_desk/general.hero_title') }} <em>{{ trans('admin/incident_desk/general.hero_title_em') }}</em></h1>
            <p>{{ trans('admin/incident_desk/general.hero_blurb') }}</p>
        </div>
        <div class="counter-block">
            <div class="counter-label">{{ trans('admin/incident_desk/general.tickets_on_file') }}</div>
            <div class="counter-num" id="idCounterNum">0</div>
        </div>
        <span class="hero-credit">Museum of the Future, Dubai &mdash; Killa Design</span>
    </div>

    <div class="stats" id="idStatsRow"></div>

    <div class="body-grid">
        <div class="rail">
            <p class="panel-title">{{ trans('admin/incident_desk/general.file_ticket') }}</p>
            <label for="idRequesterInput">{{ trans('admin/incident_desk/general.requester') }}</label>
            <input type="text" id="idRequesterInput" list="idUsersDatalist" placeholder="{{ trans('admin/incident_desk/general.requester_placeholder') }}" autocomplete="off">
            <datalist id="idUsersDatalist">
                @foreach ($requesters as $requesterName)
                    <option value="{{ $requesterName }}"></option>
                @endforeach
            </datalist>

            <label for="idCategorySelect">{{ trans('admin/incident_desk/general.category') }}</label>
            <select id="idCategorySelect">
                <option value="">{{ trans('admin/incident_desk/general.select_category') }}</option>
                @foreach ($taskCategories as $categoryName => $taskTypes)
                    <option value="{{ $categoryName }}">{{ $categoryName }}</option>
                @endforeach
            </select>

            <label for="idTaskTypeSelect">{{ trans('admin/incident_desk/general.task_type') }}</label>
            <select id="idTaskTypeSelect" disabled>
                <option value="">{{ trans('admin/incident_desk/general.select_category_first') }}</option>
            </select>

            <label for="idAssetSelect">{{ trans('admin/incident_desk/general.device_optional') }}</label>
            <select id="idAssetSelect" class="js-data-ajax" data-endpoint="hardware" data-placeholder="{{ trans('general.select_asset') }}" style="width:100%">
                <option value="" role="option">{{ trans('general.select_asset') }}</option>
            </select>

            <label for="idDetailsInput">{{ trans('admin/incident_desk/general.details') }} <span style="text-transform:none; letter-spacing:0; color:var(--id-ink-faint);">{{ trans('admin/incident_desk/general.details_optional') }}</span></label>
            <textarea id="idDetailsInput" placeholder="{{ trans('admin/incident_desk/general.details_placeholder') }}"></textarea>

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

            <button class="submit-btn" id="idSubmitBtn" disabled>{{ trans('admin/incident_desk/general.log_ticket') }}</button>
            <div class="form-msg" id="idFormMsg"></div>

            <button class="ghost-btn" id="idExportCsvBtn" type="button" style="width:100%; margin-top:18px;">{{ trans('admin/incident_desk/general.export_csv') }}</button>

            <p class="panel-title" style="margin-top:30px;">{{ trans('admin/incident_desk/general.status_breakdown') }} <span id="idDonutDateLabel" style="text-transform:none; letter-spacing:0; color:var(--id-ink-faint); font-weight:400;"></span></p>
            <div class="donut-row">
                <div class="donut" id="idDonutChart"><div class="donut-center"><div class="donut-total" id="idDonutTotal">0</div><div class="donut-total-label">{{ trans('admin/incident_desk/general.tickets') }}</div></div></div>
                <div class="donut-legend" id="idDonutLegend"></div>
            </div>
        </div>

        <div class="main">
            <p class="panel-title">{{ trans('admin/incident_desk/general.ticket_log') }}</p>
            <div class="list-controls">
                <input type="text" id="idSearchInput" placeholder="{{ trans('admin/incident_desk/general.search_placeholder') }}">
                <input type="date" id="idDateJump" title="{{ trans('admin/incident_desk/general.jump_to_date') }}">
                <div class="filter-tabs" id="idFilterTabs">
                    <div class="filter-tab active" data-f="All">{{ trans('admin/incident_desk/general.all') }}</div>
                    <div class="filter-tab" data-f="Closed">{{ trans('admin/incident_desk/general.closed') }}</div>
                    <div class="filter-tab" data-f="Open">{{ trans('admin/incident_desk/general.open') }}</div>
                    <div class="filter-tab" data-f="Need Vendor">{{ trans('admin/incident_desk/general.vendor') }}</div>
                </div>
            </div>
            <div class="count-line" id="idCountLine"></div>
            <div id="idDateChip" style="display:none;"></div>
            <div class="list-scroll"><div id="idTicketList"></div></div>
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
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const BASE_URL = (document.querySelector('meta[name="baseUrl"]') || {}).content || '/';

    let tickets = [];
    let currentFilter = 'All';
    let currentSearch = '';
    let currentDate = new Date().toISOString().slice(0,10);
    let activeStatus = 'Closed';
    let editingId = null;

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
        const total = tickets.length;
        const closed = tickets.filter(t=>t.status==='Closed').length;
        const open = tickets.filter(t=>t.status==='Open').length;
        const vendor = tickets.filter(t=>t.status==='Need Vendor').length;
        const today = new Date().toISOString().slice(0,10);
        const todayCount = tickets.filter(t=>(t.created_at||'').slice(0,10)===today).length;

        document.getElementById('idCounterNum').textContent = total;
        document.getElementById('idStatsRow').innerHTML =
            '<div class="stat"><div class="n">' + closed + '</div><div class="l">{{ trans('admin/incident_desk/general.closed') }}</div></div>' +
            '<div class="stat open"><div class="n">' + open + '</div><div class="l">{{ trans('admin/incident_desk/general.open') }}</div></div>' +
            '<div class="stat"><div class="n">' + vendor + '</div><div class="l">{{ trans('admin/incident_desk/general.need_vendor') }}</div></div>' +
            '<div class="stat"><div class="n">' + todayCount + '</div><div class="l">{{ trans('admin/incident_desk/general.logged_today') }}</div></div>';
    }

    function renderDonut(){
        const scope = currentDate ? tickets.filter(t=>(t.created_at||'').slice(0,10)===currentDate) : tickets;
        const closed = scope.filter(t=>t.status==='Closed').length;
        const open = scope.filter(t=>t.status==='Open').length;
        const vendor = scope.filter(t=>t.status==='Need Vendor').length;
        const total = scope.length;

        const label = document.getElementById('idDonutDateLabel');
        if(currentDate){
            const d = new Date(currentDate + 'T00:00:00');
            const isToday = currentDate === new Date().toISOString().slice(0,10);
            label.textContent = '— ' + (isToday ? '{{ trans('admin/incident_desk/general.today') }}' : d.toLocaleDateString(undefined,{month:'short',day:'2-digit'}));
        }else{
            label.textContent = '— {{ trans('admin/incident_desk/general.all_time') }}';
        }

        document.getElementById('idDonutTotal').textContent = total;

        const segments = [
            {label:'Closed', count:closed, color:'#121212'},
            {label:'Open', count:open, color:'#EF3326'},
            {label:'Need Vendor', count:vendor, color:'#C9C4BB'}
        ];

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
        if(currentFilter !== 'All') list = list.filter(t=>t.status===currentFilter);
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
                    '<select data-role="status-select">' +
                        ['Closed','Open','Need Vendor'].map(s=>'<option value="' + s + '"' + (t.status===s?' selected':'') + '>' + s + '</option>').join('') +
                    '</select>' +
                    '<button class="t-del" data-role="edit">{{ trans('admin/incident_desk/general.edit') }}</button>' +
                    '<button class="t-del" data-role="del">{{ trans('admin/incident_desk/general.delete') }}</button>' +
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
                '<input type="text" data-role="edit-requester" list="idUsersDatalist" value="' + escapeHtml(t.requester_name||'') + '">' +
                '<label>{{ trans('admin/incident_desk/general.category') }}</label>' +
                '<select data-role="edit-category"><option value="">{{ trans('admin/incident_desk/general.select_category') }}</option>' + catOptions + '</select>' +
                '<label>{{ trans('admin/incident_desk/general.task_type') }}</label>' +
                '<select data-role="edit-tasktype"><option value="">{{ trans('admin/incident_desk/general.select_type') }}</option>' + typeOptions + '</select>' +
                '<label>{{ trans('admin/incident_desk/general.device_optional') }}</label>' +
                '<select data-role="edit-asset" style="width:100%"><option value=""></option>' + assetOption + '</select>' +
                '<label>{{ trans('admin/incident_desk/general.notes') }}</label>' +
                '<textarea data-role="edit-notes">' + escapeHtml(t.issue||'') + '</textarea>' +
                '<label>{{ trans('admin/incident_desk/general.status') }}</label>' +
                '<div class="status-choices" data-role="edit-status">' +
                    '<div class="status-choice' + (t.status==='Closed'?' active':'') + '" data-s="Closed">{{ trans('admin/incident_desk/general.closed') }}</div>' +
                    '<div class="status-choice' + (t.status==='Open'?' active':'') + '" data-s="Open">{{ trans('admin/incident_desk/general.open') }}</div>' +
                    '<div class="status-choice' + (t.status==='Need Vendor'?' active':'') + '" data-s="Need Vendor">{{ trans('admin/incident_desk/general.vendor') }}</div>' +
                '</div>' +
                '<div class="edit-actions">' +
                    '<button class="submit-btn" style="margin-top:12px;" data-role="edit-save" type="button">{{ trans('admin/incident_desk/general.save_changes') }}</button>' +
                    '<button class="ghost-btn" style="margin-top:12px;" data-role="edit-cancel" type="button">{{ trans('admin/incident_desk/general.cancel') }}</button>' +
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

    document.getElementById('idSubmitBtn').addEventListener('click', async ()=>{
        const msg = document.getElementById('idFormMsg');
        const requester = document.getElementById('idRequesterInput').value.trim();
        const category = document.getElementById('idCategorySelect').value;
        const taskType = document.getElementById('idTaskTypeSelect').value;
        const notes = document.getElementById('idDetailsInput').value.trim();
        const assetId = document.getElementById('idAssetSelect').value || null;
        if(!category || !taskType){
            msg.style.color = 'var(--id-red)';
            msg.textContent = '{{ trans('admin/incident_desk/general.validation_category') }}';
            return;
        }
        try{
            const res = await api(URLS.store, {method:'POST', body:{
                requester_name: requester, category: category, task_type: taskType,
                issue: notes, status: activeStatus, asset_id: assetId
            }});
            tickets.push(res.ticket);
            document.getElementById('idDetailsInput').value = '';
            document.getElementById('idRequesterInput').value = '';
            document.getElementById('idCategorySelect').value = '';
            const typeSel = document.getElementById('idTaskTypeSelect');
            typeSel.disabled = true;
            typeSel.innerHTML = '<option value="">{{ trans('admin/incident_desk/general.select_category_first') }}</option>';
            if(window.jQuery) jQuery('#idAssetSelect').val(null).trigger('change');
            msg.style.color = 'var(--id-ink-soft)';
            msg.textContent = '{{ trans('admin/incident_desk/general.logged') }}'.replace(':id', padId(res.ticket));
            setTimeout(()=>{ msg.textContent=''; }, 2500);
            render();
        }catch(err){
            msg.style.color = 'var(--id-red)';
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
    document.getElementById('idFilterTabs').addEventListener('click', (e)=>{
        const el = e.target.closest('.filter-tab');
        if(!el) return;
        document.querySelectorAll('.filter-tab').forEach(c=>c.classList.remove('active'));
        el.classList.add('active');
        currentFilter = el.dataset.f;
        renderList();
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
