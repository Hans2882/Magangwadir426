<div>
    <div class="header-section">
        <h2>Publikasi Pelaporan Case Study</h2>
        <p>Jelajahi berbagai laporan Case Study yang telah diselesaikan oleh Mitra dan Institusi kami.</p>
    </div>

    <div class="grid-container">
        @forelse($caseStudies as $study)
            <div class="glass-card">
                <div class="card-header">
                    <span class="badge {{ $study->jenis === 'Dalam Negeri' ? 'badge-dn' : 'badge-ln' }}">
                        <i data-lucide="{{ $study->jenis === 'Dalam Negeri' ? 'building-2' : 'globe-2' }}" class="icon-sm"></i>
                        {{ $study->jenis }}
                    </span>
                    <span class="date">{{ $study->tanggal_awal ? $study->tanggal_awal->format('d F Y') : '-' }}</span>
                </div>
                
                <h3 class="title">{{ $study->judul }}</h3>
                
                <div class="mitra-info">
                    <i data-lucide="briefcase" class="icon-md text-muted"></i>
                    <div>
                        <p class="mitra-name">{{ $study->public_mitra_name }}</p>
                        <p class="mitra-country">
                            {{ $study->jenis === 'Luar Negeri' ? ($study->mitra->negara->nama_negara ?? '-') : 'Indonesia' }}
                        </p>
                    </div>
                </div>

                @if($study->link_dokumen)
                    <div class="card-footer">
                        @php
                            $path = is_array($study->link_dokumen) ? array_values($study->link_dokumen)[0] : $study->link_dokumen;
                        @endphp
                        <a href="{{ route('view-dokumen', ['path' => $path]) }}" target="_blank" class="btn-primary">
                            <i data-lucide="file-text" class="icon-sm"></i>
                            Lihat Dokumen
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <i data-lucide="folder-search" class="empty-icon"></i>
                <p>Belum ada pelaporan Case Study yang diselesaikan.</p>
            </div>
        @endforelse
    </div>

    <style>
        .header-section {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .header-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -1px;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }
        
        .header-section p {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 255, 255, 0.8);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-dn {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .badge-ln {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        
        .date {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .title {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 1.5rem;
            color: var(--text-main);
            flex: 1;
        }
        
        .mitra-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 1rem;
            background: rgba(248, 250, 252, 0.5);
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .mitra-name {
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.95rem;
            margin-bottom: 2px;
        }
        
        .mitra-country {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .card-footer {
            margin-top: auto;
        }
        
        .btn-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 0.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px dashed #cbd5e1;
        }
        
        .empty-icon {
            width: 48px;
            height: 48px;
            color: #94a3b8;
            margin: 0 auto 1rem;
        }
        
        .icon-sm { width: 14px; height: 14px; }
        .icon-md { width: 20px; height: 20px; }
        .text-muted { color: var(--text-muted); }
    </style>
</div>
