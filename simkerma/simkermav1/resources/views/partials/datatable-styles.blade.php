{{--
    Shared DataTable styles used by all kerjasama/mitra pages.
    @push('styles') this partial to include it.
--}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    /* ── Badges ── */
    .dm-badge-card { display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:10px; color:#fff; box-shadow:0 4px 16px rgba(0,0,0,.12); }
    .dm-badge-blue { background:linear-gradient(135deg,#1e5fa8,#2d7dd2); }
    .dm-badge-teal { background:linear-gradient(135deg,#0d7377,#14a085); }
    .dm-badge-icon { font-size:2rem; opacity:.75; flex-shrink:0; }
    .dm-badge-num  { font-size:1.75rem; font-weight:700; line-height:1.1; }
    .dm-badge-label{ font-size:.8rem; opacity:.9; letter-spacing:.02em; }

    /* ── Tabs ── */
    .dm-tab-btn { border-radius:0!important; padding:12px 22px; font-weight:500; color:#555; border:none!important; border-bottom:3px solid transparent!important; transition:all .2s; }
    .dm-tab-btn:hover { color:#1e5fa8; background:rgba(30,95,168,.05); border-bottom-color:rgba(30,95,168,.3)!important; }
    .dm-tab-btn.active { color:#1e5fa8!important; background:#fff!important; border-bottom-color:#1e5fa8!important; font-weight:600; }

    /* ── Controls ── */
    .dm-controls { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
    .dm-length-label { display:flex; align-items:center; gap:8px; font-size:.875rem; color:#444; font-weight:500; margin:0; }
    .dm-select { appearance:auto; border:1px solid #ced4da; border-radius:6px; padding:5px 10px; font-size:.875rem; color:#333; background:#fff; cursor:pointer; outline:none; transition:border-color .15s; }
    .dm-select:focus { border-color:#1e5fa8; box-shadow:0 0 0 2px rgba(30,95,168,.12); }
    .dm-search-wrap { position:relative; display:flex; align-items:center; }
    .dm-search-icon { position:absolute; left:10px; color:#888; font-size:.85rem; pointer-events:none; }
    .dm-search-input { padding:7px 12px 7px 32px; border:1px solid #ced4da; border-radius:8px; font-size:.875rem; width:260px; outline:none; transition:border-color .15s,box-shadow .15s; background:#fff; color:#333; }
    .dm-search-input:focus { border-color:#1e5fa8; box-shadow:0 0 0 3px rgba(30,95,168,.12); }
    .dm-search-input::placeholder { color:#aaa; }
    .dm-reset-btn { position:absolute; right:8px; background:none; border:none; color:#999; font-size:.85rem; line-height:1; padding:0 2px; cursor:pointer; transition:color .15s; }
    .dm-reset-btn:hover { color:#dc3545; }

    /* ── Table ── */
    .dm-table thead th { background-color:#113261; color:#fff; font-size:.82rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; border-color:#1a3f7a; vertical-align:middle; white-space:nowrap; }
    .dm-table thead .sorting:after,.dm-table thead .sorting_asc:after,.dm-table thead .sorting_desc:after,
    .dm-table thead .sorting:before,.dm-table thead .sorting_asc:before,.dm-table thead .sorting_desc:before { color:rgba(255,255,255,.5)!important; }
    .dm-table tbody tr:hover td { background-color:rgba(30,95,168,.05); }
    .dm-table tbody td { vertical-align:middle; font-size:.875rem; }

    /* ── Status badges ── */
    .badge-aktif   { background:#198754; color:#fff; padding:3px 8px; border-radius:4px; font-size:.75rem; font-weight:600; }
    .badge-habis   { background:#6c757d; color:#fff; padding:3px 8px; border-radius:4px; font-size:.75rem; font-weight:600; }
    .badge-lainnya { background:#0d6efd; color:#fff; padding:3px 8px; border-radius:4px; font-size:.75rem; font-weight:600; }

    /* ── Footer ── */
    .dm-footer { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-top:12px; }
    .dm-info-text { font-size:.82rem; color:#666; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius:6px!important; font-size:.82rem; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background:#113261!important; border-color:#113261!important; color:#fff!important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background:rgba(17,50,97,.08)!important; border-color:transparent!important; color:#113261!important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover { color:#aaa!important; cursor:default; }
    .dataTables_wrapper .dataTables_processing { background:rgba(255,255,255,.9); border:1px solid #dee2e6; border-radius:8px; color:#113261; font-size:.85rem; padding:10px 20px; }

    /* ── Detail button ── */
    .btn-detail { background:#113261; color:#fff; border:none; border-radius:6px; padding:3px 10px; font-size:.78rem; cursor:pointer; transition:background .15s; }
    .btn-detail:hover { background:#1a4a88; color:#fff; }

    /* ── Detail modal grid ── */
    .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; }
    .detail-row { display:contents; }
    .detail-label { background:#f8f9fa; padding:9px 14px; font-size:.8rem; font-weight:600; color:#555; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e9ecef; border-right:1px solid #e9ecef; }
    .detail-value { padding:9px 14px; font-size:.875rem; color:#333; border-bottom:1px solid #e9ecef; word-break:break-word; }
    .detail-value a { color:#1e5fa8; }
    .detail-full { grid-column:1/-1; }
    .detail-full .detail-label,.detail-full .detail-value { display:block; }
    @media(max-width:576px){ .detail-grid { grid-template-columns:1fr; } .detail-label,.detail-value { border-right:none; } }
</style>
