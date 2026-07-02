@extends('layouts.app')

@section('content-header')
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0">Data PKS</h1></div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="bi bi-house-fill"></i> Simkerma</a></li>
                <li class="breadcrumb-item">Data Kerjasama</li>
                <li class="breadcrumb-item active">Data PKS</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="dm-badge-card dm-badge-blue">
            <div class="dm-badge-icon"><i class="bi bi-file-earmark-ruled"></i></div>
            <div class="dm-badge-body">
                <div class="dm-badge-num" id="totalPKS">—</div>
                <div class="dm-badge-label">Total Data PKS</div>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title">
            <i class="bi bi-file-earmark-ruled-fill me-2 text-primary"></i>
            <strong>DATA PKS</strong>
        </h3>
    </div>
    <div class="card-body">
        <div class="dm-controls">
            <div class="dm-controls-left">
                <label class="dm-length-label">Tampilkan
                    <select id="pksLength" class="dm-select">
                        <option value="10">10</option><option value="25">25</option>
                        <option value="50">50</option><option value="100">100</option>
                    </select> data
                </label>
            </div>
            <div class="dm-controls-right">
                <div class="dm-search-wrap">
                    <i class="bi bi-search dm-search-icon"></i>
                    <input type="text" id="pksSearch" class="dm-search-input" placeholder="Cari PKS...">
                    <button type="button" id="pksReset" class="dm-reset-btn" title="Reset" style="display:none">&#x2715;</button>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tablePKS" class="table table-bordered table-hover dm-table w-100">
                <thead><tr>
                    <th class="text-center" style="width:50px">#</th>
                    <th>Judul</th><th>Nama Mitra</th><th>Nomor Dokumen</th>
                    <th>Tahun</th><th>Tgl. Awal</th><th>Tgl. Akhir</th>
                    <th>Status</th><th class="text-center" style="width:80px">Aksi</th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="dm-footer">
            <div id="pksInfo" class="dm-info-text"></div>
            <div id="pksPaginate"></div>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#113261;color:#fff;">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="bi bi-file-earmark-ruled-fill me-2"></i><span id="modalTitle"></span>
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

    var lang = { processing:'Memuat data...', zeroRecords:'Tidak ada data yang sesuai', paginate:{previous:'‹',next:'›'} };

    function debounce(fn, ms) {
        var t; return function(){ var a=arguments,c=this; clearTimeout(t); t=setTimeout(function(){fn.apply(c,a);},ms); };
    }

    function statusBadge(s) {
        if (!s||s==='-') return '<span class="text-muted">-</span>';
        var u=s.toUpperCase();
        if (u==='AKTIF')        return '<span class="badge-aktif">'+s+'</span>';
        if (u.includes('HABIS'))return '<span class="badge-habis">'+s+'</span>';
        return '<span class="badge-lainnya">'+s+'</span>';
    }

    function dr(label, value) {
        return '<div class="detail-label">'+label+'</div><div class="detail-value">'+(value||'-')+'</div>';
    }
    function drFull(label, value) {
        var v=(value&&value!=='-'&&value.startsWith('http'))
            ? '<a href="'+value+'" target="_blank" rel="noopener">'+value+'</a>' : (value||'-');
        return '<div class="detail-label" style="grid-column:1/-1">'+label+'</div>'
              +'<div class="detail-value" style="grid-column:1/-1">'+v+'</div>';
    }

    var tablePKS = $('#tablePKS').DataTable({
        processing:true, serverSide:true,
        ajax:{ url:'{{ route("data-kerjasama.ajax.pks-dalam-negeri") }}', type:'GET' },
        columns:[
            {data:'no',orderable:false,searchable:false,className:'text-center'},
            {data:'judul'},{data:'nama_mitra'},{data:'nomor_dokumen'},
            {data:'tahun',className:'text-center'},
            {data:'tanggal_awal',className:'text-nowrap'},{data:'tanggal_akhir',className:'text-nowrap'},
            {data:'status',render:function(d){return statusBadge(d);}},
            {data:null,orderable:false,searchable:false,className:'text-center',
             render:function(){ return '<button class="btn-detail"><i class="bi bi-eye-fill me-1"></i>Detail</button>'; }},
        ],
        language:lang, order:[[1,'asc']], pageLength:10,
        dom:'<"d-none"l><"d-none"f>rt<"d-none"i><"d-none"p>',
        initComplete:function(){ $('#totalPKS').text(this.api().page.info().recordsTotal.toLocaleString('id-ID')); }
    });

    /* Controls */
    $('#pksLength').on('change',function(){ tablePKS.page.len(+this.value).draw(); });
    var deb=debounce(function(v){ tablePKS.search(v).draw(); },400);
    $('#pksSearch').on('input',function(){ $('#pksReset').toggle(this.value.length>0); deb(this.value); })
                   .on('keydown',function(e){ if(e.key==='Enter'){e.preventDefault();tablePKS.search(this.value).draw();} });
    $('#pksReset').on('click',function(){ $('#pksSearch').val(''); $(this).hide(); tablePKS.search('').draw(); });
    tablePKS.on('draw',function(){
        var i=tablePKS.page.info(), s=i.recordsTotal===0?0:i.start+1;
        var txt='Menampilkan '+s+'–'+i.end+' dari '+i.recordsDisplay.toLocaleString('id-ID')+' data';
        if(i.recordsDisplay!==i.recordsTotal) txt+=' (disaring dari '+i.recordsTotal.toLocaleString('id-ID')+' total)';
        $('#pksInfo').text(txt);
        $('#pksPaginate').html('').append($('#tablePKS_wrapper .dataTables_paginate').clone(true,true));
    });

    /* Detail */
    $('#tablePKS').on('click','.btn-detail',function(){
        var d=tablePKS.row($(this).closest('tr')).data();
        $('#modalTitle').text(d.judul||d.nama_mitra);
        $('#modalBody').html(
            '<div class="detail-grid">'
            +dr('Judul',          d.judul)
            +dr('Nama Mitra',     d.nama_mitra)
            +dr('Nomor Dokumen',  d.nomor_dokumen)
            +dr('No. Polinema',   d._nomor_polinema)
            +dr('No. Mitra',      d._nomor_mitra)
            +dr('Tahun',          d.tahun)
            +dr('Tgl. Berlaku',   d.tanggal_awal)
            +dr('Tgl. Berakhir',  d.tanggal_akhir)
            +dr('Status',         d.status)
            +drFull('Link Perbaikan', d._link_perbaikan)
            +drFull('Bukti Kegiatan', d._bukti_kegiatan)
            +'</div>'
        );
        new bootstrap.Modal(document.getElementById('detailModal')).show();
    });

});
</script>
@endpush
