<div>
    <div class="header-section">
        <h2>Publikasi SIMKERMA</h2>
        <p>Jelajahi berbagai laporan Case Study dan daftar Mitra institusi kami.</p>
    </div>

    <!-- Tab Switcher -->
    <div class="tab-switcher">
        <button 
            wire:click="$set('activeTab', 'case-studies')" 
            class="tab-btn {{ $activeTab === 'case-studies' ? 'active' : '' }}">
            <i data-lucide="book-open" class="icon-sm"></i>
            Pelaporan Case Study
        </button>
        <button 
            wire:click="$set('activeTab', 'mitra')" 
            class="tab-btn {{ $activeTab === 'mitra' ? 'active' : '' }}">
            <i data-lucide="users" class="icon-sm"></i>
            Daftar Mitra
        </button>
    </div>

    <!-- Case Studies Tab -->
    @if($activeTab === 'case-studies')
    <div class="grid-container" wire:key="tab-case-studies">
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
    @endif

    <!-- Mitra Tab -->
    @if($activeTab === 'mitra')
    <div class="table-container" wire:key="tab-mitra">
        <div class="table-toolbar">
            <div class="search-box">
                <i data-lucide="search" class="search-icon"></i>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama mitra..." class="search-input">
            </div>
        </div>
        
        <table class="glass-table">
            <thead>
                <tr>
                    <th>Nama Mitra</th>
                    <th>Negara</th>
                    <th>Kategori</th>
                    <th>QS Rank</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mitras as $mitra)
                <tr>
                    <td>
                        <div class="mitra-name-cell">
                            <i data-lucide="building" class="icon-md text-muted"></i>
                            <span class="font-semibold">{{ $mitra->nama_mitra_display }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="country-cell">
                            <i data-lucide="map-pin" class="icon-sm"></i>
                            {{ $mitra->negara ? $mitra->negara->nama_negara : 'Indonesia' }}
                        </div>
                    </td>
                    <td>
                        @if($mitra->kategori)
                            <span class="badge badge-kategori">{{ $mitra->kategori->kategori }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        {{ $mitra->qs_rank ? '#' . $mitra->qs_rank : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-8">
                        <div class="empty-state-small">
                            <i data-lucide="users" class="empty-icon-small"></i>
                            <p>Belum ada data Mitra.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($mitras->hasPages())
            <div class="pagination-container">
                {{ $mitras->links() }}
            </div>
        @endif
    </div>
    @endif

    <style>
        .header-section {
            text-align: center;
            margin-bottom: 2rem;
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

        /* Tabs */
        .tab-switcher {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 3rem;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            padding: 0.5rem;
            border-radius: 999px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-btn:hover {
            color: var(--text-main);
        }

        .tab-btn.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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

        /* Table UI */
        .table-container {
            width: 100%;
            overflow-x: auto;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 1px;
            animation: fadeIn 0.4s ease-out;
        }

        .table-toolbar {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: flex-end;
            background: rgba(255, 255, 255, 0.5);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .search-box {
            position: relative;
            width: 300px;
            max-width: 100%;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
        }

        .search-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            border-radius: 99px;
            border: 1px solid #cbd5e1;
            background: white;
            font-size: 0.95rem;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .glass-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .glass-table th {
            background: rgba(248, 250, 252, 0.8);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--card-border);
        }

        .glass-table th:first-child { border-top-left-radius: 16px; }
        .glass-table th:last-child { border-top-right-radius: 16px; }

        .glass-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
            color: var(--text-muted);
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .glass-table tbody tr:last-child td {
            border-bottom: none;
        }

        .glass-table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.5);
        }

        .mitra-name-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-main);
        }

        .country-cell {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-kategori {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .empty-state-small {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-muted);
            gap: 8px;
        }

        .empty-icon-small {
            width: 32px;
            height: 32px;
            color: #cbd5e1;
        }
        
        .font-semibold { font-weight: 600; }
        .text-center { text-align: center; }
        .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
        
        .pagination-container {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--card-border);
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Fix for Livewire's default pagination SVGs missing Tailwind */
        .pagination-container nav svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .pagination-container .flex { display: flex; }
        .pagination-container .items-center { align-items: center; }
        .pagination-container .justify-between { justify-content: space-between; }
        .pagination-container .hidden { display: none; }
        .pagination-container .text-sm { font-size: 0.875rem; }
        
        @media (min-width: 640px) {
            .pagination-container .sm\:hidden { display: none; }
            .pagination-container .sm\:flex { display: flex; }
            .pagination-container .sm\:flex-1 { flex: 1 1 0%; }
            .pagination-container .sm\:items-center { align-items: center; }
            .pagination-container .sm\:justify-between { justify-content: space-between; }
        }
        
        .icon-sm { width: 14px; height: 14px; }
        .icon-md { width: 20px; height: 20px; }
        .text-muted { color: var(--text-muted); }
    </style>
    
    <script>
        document.addEventListener('livewire:navigated', () => {
            lucide.createIcons();
        });
        document.addEventListener('livewire:load', () => {
            lucide.createIcons();
        });
        document.addEventListener('livewire:update', () => {
            lucide.createIcons();
        });
    </script>
</div>
