@extends('layouts.app')

@section('content-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Data Mitra</h1></div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="bi bi-house-fill"></i> Simkerma</a></li>
                <li class="breadcrumb-item">Data Mitra</li>
                <li class="breadcrumb-item active">Data Mitra</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')

{{-- Summary badges --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dm-badge-card dm-badge-blue">
            <div class="dm-badge-icon"><i class="bi bi-building"></i></div>
            <div class="dm-badge-body">
                <div class="dm-badge-num" id="totalDalamNegeri">—</div>
                <div class="dm-badge-label">Mitra Dalam Negeri</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dm-badge-card dm-badge-teal">
            <div class="dm-badge-icon"><i class="bi bi-globe2"></i></div>
            <div class="dm-badge-body">
                <div class="dm-badge-num" id="totalLuarNegeri">—</div>
                <div class="dm-badge-label">Mitra Luar Negeri</div>
            </div>
        </div>
    </div>
</div>

{{-- Tab card --}}
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" id="mitraTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active dm-tab-btn" id="tab-dn-btn" data-bs-toggle="tab" data-bs-target="#tab-dn" type="button" role="tab">
                    <i class="bi bi-building me-1"></i> Dalam Negeri
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link dm-tab-btn" id="tab-ln-btn" data-bs-toggle="tab" data-bs-target="#tab-ln" type="button" role="tab">
                    <i class="bi bi-globe2 me-1"></i> Luar Negeri
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            {{-- DN tab --}}
            <div class="tab-pane fade show active" id="tab-dn" role="tabpanel">
                <div class="dm-controls">
                    <div class="dm-controls-left">
                        <label class="dm-length-label">Tampilkan
                            <select id="dnLength" class="dm-select">
                                <option value="10">10</option><option value="25">25</option>
                                <option value="50">50</option><option value="100">100</option>
                            </select> data
                        </label>
                    </div>
                    <div class="dm-controls-right">
                        <div class="dm-search-wrap">
                            <i class="bi bi-search dm-search-icon"></i>
                            <input type="text" id="dnSearch" class="dm-search-input" placeholder="Cari mitra...">
                            <button type="button" id="dnReset" class="dm-reset-btn" title="Reset" style="display:none">&#x2715;</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tableDalamNegeri" class="table table-bordered table-hover dm-table w-100">
                        <thead><tr>
                            <th class="text-center" style="width:50px">#</th>
                            <th>Nama Mitra</th><th>Kategori</th>
                            <th>Bidang Kerja Sama</th><th>No. Telepon</th>
                            <th>Email</th><th class="text-center" style="width:80px">Aksi</th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="dm-footer">
                    <div id="dnInfo" class="dm-info-text"></div>
                    <div id="dnPaginate"></div>
                </div>
            </div>

            {{-- LN tab --}}
            <div class="tab-pane fade" id="tab-ln" role="tabpanel">
                <div class="dm-controls">
                    <div class="dm-controls-left">
                        <label class="dm-length-label">Tampilkan
                            <select id="lnLength" class="dm-select">
                                <option value="10">10</option><option value="25">25</option>
                                <option value="50">50</option><option value="100">100</option>
                            </select> data
                        </label>
                    </div>
                    <div class="dm-controls-right">
                        <div class="dm-search-wrap">
                            <i class="bi bi-search dm-search-icon"></i>
                            <input type="text" id="lnSearch" class="dm-search-input" placeholder="Cari mitra...">
                            <button type="button" id="lnReset" class="dm-reset-btn" title="Reset" style="display:none">&#x2715;</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tableLuarNegeri" class="table table-bordered table-hover dm-table w-100">
                        <thead><tr>
                            <th class="text-center" style="width:50px">#</th>
                            <th>Nama Mitra</th><th>Negara</th>
                            <th>Kategori</th><th class="text-center" style="width:80px">Aksi</th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="dm-footer">
                    <div id="lnInfo" class="dm-info-text"></div>
                    <div id="lnPaginate"></div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#113261;color:#fff;">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="bi bi-info-circle-fill me-2"></i><span id="modalTitle"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="modalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
    @include('partials.datatable-styles')
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {

    /* ── helpers ── */
    var lang = {
        processing:'Memuat data...', zeroRecords:'Tidak ada data yang sesuai',
        paginate:{previous:'‹',next:'›'}
    };

    function debounce(fn, ms) {
        var t;
        return function() { var a=arguments,c=this; clearTimeout(t); t=setTimeout(function(){fn.apply(c,a);},ms); };
    }

    function statusBadge(s) {
        if (!s||s==='-') return '<span class="text-muted">-</span>';
        var u=s.toUpperCase();
        if (u==='AKTIF')        return '<span class="badge-aktif">'+s+'</span>';
        if (u.includes('HABIS'))return '<span class="badge-habis">'+s+'</span>';
        return '<span class="badge-lainnya">'+s+'</span>';
    }

    function dr(label, value, full) {
        var cls = full ? 'detail-label detail-full' : 'detail-label';
        var vcls= full ? 'detail-value detail-full' : 'detail-value';
        return '<div class="'+cls+'">'+label+'</div><div class="'+vcls+'">'+(value||'-')+'</div>';
    }

    function linkVal(v) {
        if (!v||v==='-') return '-';
        if (v.startsWith('http')) return '<a href="'+v+'" target="_blank" rel="noopener">'+v+'</a>';
        return v;
    }

    function wire(tableObj, searchId, resetId, lengthId, infoId, pagId, wrapId) {
        $(lengthId).on('change', function(){ tableObj.page.len(+this.value).draw(); });
        var deb = debounce(function(v){ tableObj.search(v).draw(); }, 400);
        $(searchId).on('input', function(){
            $(resetId).toggle(this.value.length>0);
            deb(this.value);
        }).on('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();tableObj.search(this.value).draw();} });
        $(resetId).on('click', function(){ $(searchId).val(''); $(this).hide(); tableObj.search('').draw(); });
        tableObj.on('draw', function(){
            var info=tableObj.page.info();
            var s=info.recordsTotal===0?0:info.start+1;
            var txt='Menampilkan '+s+'–'+info.end+' dari '+info.recordsDisplay.toLocaleString('id-ID')+' data';
            if(info.recordsDisplay!==info.recordsTotal) txt+=' (disaring dari '+info.recordsTotal.toLocaleString('id-ID')+' total)';
            $(infoId).text(txt);
            $(pagId).html('').append($(wrapId+' .dataTables_paginate').clone(true,true));
        });
    }

    function showModal(title, bodyHtml) {
        $('#modalTitle').text(title);
        $('#modalBody').html(bodyHtml);
        new bootstrap.Modal(document.getElementById('detailModal')).show();
    }

    var detailBtn = function(){
        return '<button class="btn-detail"><i class="bi bi-eye-fill me-1"></i>Detail</button>';
    };

    /* ══ DALAM NEGERI ══ */
    var tableDN = $('#tableDalamNegeri').DataTable({
        processing:true, serverSide:true,
        ajax:{ url:'{{ route("data-mitra.ajax.dalam-negeri") }}', type:'GET' },
        columns:[
            {data:'no', orderable:false, searchable:false, className:'text-center'},
            {data:'nama_mitra'}, {data:'kategori_mitra'},
            {data:'bidang_kerja_sama'}, {data:'nomor_telepon', className:'text-nowrap'},
            {data:'email'},
            {data:null, orderable:false, searchable:false, className:'text-center', render:detailBtn},
        ],
        language:lang, order:[[1,'asc']], pageLength:10,
        dom:'<"d-none"l><"d-none"f>rt<"d-none"i><"d-none"p>',
        initComplete:function(){ $('#totalDalamNegeri').text(this.api().page.info().recordsTotal.toLocaleString('id-ID')); }
    });
    wire(tableDN,'#dnSearch','#dnReset','#dnLength','#dnInfo','#dnPaginate','#tableDalamNegeri_wrapper');

    $('#tableDalamNegeri').on('click','.btn-detail',function(){
        var d = tableDN.row($(this).closest('tr')).data();
        showModal(d.nama_mitra,
            '<div class="detail-grid">'
            +dr('Nama Mitra',      d.nama_mitra)
            +dr('Kategori',        d.kategori_mitra)
            +dr('Bidang Kerja Sama',d.bidang_kerja_sama)
            +dr('No. Telepon',     d.nomor_telepon)
            +dr('Email',           d.email)
            +'</div>'
        );
    });

    /* ══ LUAR NEGERI (lazy) ══ */
    var tableLN = null;
    $('#tab-ln-btn').one('shown.bs.tab', function(){
        tableLN = $('#tableLuarNegeri').DataTable({
            processing:true, serverSide:true,
            ajax:{ url:'{{ route("data-mitra.ajax.luar-negeri") }}', type:'GET' },
            columns:[
                {data:'no', orderable:false, searchable:false, className:'text-center'},
                {data:'nama_mitra'}, {data:'negara'}, {data:'kategori_mitra'},
                {data:null, orderable:false, searchable:false, className:'text-center', render:detailBtn},
            ],
            language:lang, order:[[1,'asc']], pageLength:10,
            dom:'<"d-none"l><"d-none"f>rt<"d-none"i><"d-none"p>',
            initComplete:function(){ $('#totalLuarNegeri').text(this.api().page.info().recordsTotal.toLocaleString('id-ID')); }
        });
        wire(tableLN,'#lnSearch','#lnReset','#lnLength','#lnInfo','#lnPaginate','#tableLuarNegeri_wrapper');

        $('#tableLuarNegeri').on('click','.btn-detail',function(){
            var d = tableLN.row($(this).closest('tr')).data();
            showModal(d.nama_mitra,
                '<div class="detail-grid">'
                +dr('Nama Mitra', d.nama_mitra)
                +dr('Negara',     d.negara)
                +dr('Kategori',   d.kategori_mitra)
                +'</div>'
            );
        });
    });

    $('#tab-ln-btn').on('shown.bs.tab',function(){ if(tableLN) tableLN.columns.adjust(); });
    $('#tab-dn-btn').on('shown.bs.tab',function(){ tableDN.columns.adjust(); });

});
</script>
@endpush
