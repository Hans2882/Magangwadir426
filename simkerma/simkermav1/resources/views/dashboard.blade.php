@extends('layouts.app')

@section('content-header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0">Sistem Informasi Kerjasama</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="#"><i class="bi bi-house-fill"></i> Simkerma</a></li>
                <li class="breadcrumb-item"><a href="#">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Welcome</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')

    {{-- ===== SELAMAT DATANG BANNER ===== --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-chat-left-text-fill me-2 text-primary"></i>
                        <strong>SELAMAT DATANG</strong>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== STAT CARDS ===== --}}
    <div class="row g-3 mb-4">

        {{-- Card: Jumlah Mitra --}}
        <div class="col-12 col-md-6">
            <div class="simkerma-stat-card simkerma-card-blue">
                <div class="simkerma-stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="simkerma-stat-content">
                    <div class="simkerma-stat-number">1507</div>
                    <div class="simkerma-stat-label">Jumlah Mitra</div>
                </div>
                <div class="simkerma-stat-footer">
                    <a href="#" class="simkerma-detail-link">
                        DETAIL <i class="bi bi-plus-circle ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Card: Jumlah Kerjasama --}}
        <div class="col-12 col-md-6">
            <div class="simkerma-stat-card simkerma-card-red">
                <div class="simkerma-stat-icon">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div class="simkerma-stat-content">
                    <div class="simkerma-stat-number">1,199</div>
                    <div class="simkerma-stat-label">Jumlah Kerjasama</div>
                </div>
                <div class="simkerma-stat-footer">
                    <a href="#" class="simkerma-detail-link">
                        DETAIL <i class="bi bi-plus-circle ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== CHARTS ROW ===== --}}
    <div class="row g-3">

        {{-- Chart: Jenis Kerjasama --}}
        <div class="col-12 col-md-6">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                        <strong>JENIS KERJASAMA</strong>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse" title="Collapse">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <button type="button" class="btn btn-tool" title="Reload">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button type="button" class="btn btn-tool" title="Maximize">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button type="button" class="btn btn-tool" title="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Statistik</p>
                    <div class="chart-container" style="position:relative; height:280px;">
                        <canvas id="chartJenis"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart: Status Kerjasama --}}
        <div class="col-12 col-md-6">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                        <strong>STATUS KERJASAMA</strong>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse" title="Collapse">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <button type="button" class="btn btn-tool" title="Reload">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button type="button" class="btn btn-tool" title="Maximize">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <button type="button" class="btn btn-tool" title="Close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Statistik</p>
                    <div class="chart-container" style="position:relative; height:280px;">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('styles')
<style>
    /* ======= Page Header ======= */
    .app-content-header h1 {
        font-size: 1.6rem;
        font-weight: 600;
        color: #333;
    }

    /* ======= Stat Cards ======= */
    .simkerma-stat-card {
        position: relative;
        border-radius: 6px;
        padding: 20px 20px 0 20px;
        color: #fff;
        min-height: 120px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }

    .simkerma-card-blue  { background: linear-gradient(135deg, #4e8fcb 0%, #5d9fd8 60%, #4a8bc5 100%); }
    .simkerma-card-red   { background: linear-gradient(135deg, #d9534f 0%, #e05c58 60%, #c9433f 100%); }

    .simkerma-stat-icon {
        position: absolute;
        left: 18px;
        top: 18px;
        font-size: 4.5rem;
        opacity: 0.25;
        line-height: 1;
    }

    .simkerma-stat-content {
        text-align: right;
        padding-bottom: 10px;
    }

    .simkerma-stat-number {
        font-size: 2.8rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .simkerma-stat-label {
        font-size: 0.95rem;
        opacity: 0.9;
    }

    .simkerma-stat-footer {
        border-top: 1px solid rgba(255,255,255,0.25);
        padding: 8px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .simkerma-detail-link {
        color: rgba(255,255,255,0.85);
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-decoration: none;
    }

    .simkerma-detail-link:hover {
        color: #fff;
        text-decoration: underline;
    }

    /* ======= Chart Cards ======= */
    .card-outline.card-primary > .card-header {
        border-top: 3px solid #007bff;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ---- Chart: Jenis Kerjasama ----
    const ctxJenis = document.getElementById('chartJenis').getContext('2d');
    new Chart(ctxJenis, {
        type: 'pie',
        data: {
            labels: ['MoU: 44.43%', 'PKS: 44.25%', 'IA: 11.31%'],
            datasets: [{
                data: [44.43, 44.25, 11.31],
                backgroundColor: ['#c0392b', '#e67e22', '#f39c12'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 12 }, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.label;
                        }
                    }
                }
            }
        }
    });

    // ---- Chart: Status Kerjasama ----
    const ctxStatus = document.getElementById('chartStatus').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: ['Aktif: 51.13%', 'Habis: 40.87%', 'undefined: 8.01%'],
            datasets: [{
                data: [51.13, 40.87, 8.01],
                backgroundColor: ['#c0392b', '#e67e22', '#f39c12'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 12 }, padding: 15 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.label;
                        }
                    }
                }
            }
        }
    });

});
</script>
@endpush
