<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SIMKERMA') }}</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

    <style>
        /* ================================================
           BRAND COLOR: #113261 for sidebar + navbar
        ================================================ */

        /* Navbar */
        .app-header.navbar {
            background-color: #113261 !important;
            border-bottom: none !important;
        }

        .app-header .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
        }

        .app-header .nav-link:hover {
            color: #fff !important;
        }

        .navbar-user-name {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        /* Sidebar */
        .app-sidebar {
            background-color: #113261 !important;
        }

        .app-sidebar .sidebar-brand {
            background-color: #0d2850 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .app-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75) !important;
        }

        .app-sidebar .nav-link:hover,
        .app-sidebar .nav-link.active {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        .app-sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15) !important;
        }

        .app-sidebar .nav-treeview .nav-link {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        .app-sidebar .nav-treeview .nav-link:hover {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        /* Active sub-menu item — darker highlight like the screenshot */
        .app-sidebar .nav-treeview .nav-link.active {
            color: #fff !important;
            background-color: rgba(0, 0, 0, 0.25) !important;
            font-weight: 600;
        }

        /* Sidebar scrollbar track */
        .app-sidebar::-webkit-scrollbar-track {
            background: #113261;
        }

        .app-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        /* ================================================
           LOGO AREA
        ================================================ */
        .sidebar-brand .brand-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            text-decoration: none;
            overflow: hidden;
        }

        .sidebar-brand .simkerma-logo-placeholder {
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .sidebar-brand .brand-text {
            transition: max-width 0.3s ease, opacity 0.3s ease !important;
            overflow: hidden;
            white-space: nowrap;
        }

        .simkerma-logo-placeholder {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: linear-gradient(135deg, #4e9fd4, #2e7da8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #ffffffff;
            flex-shrink: 0;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .brand-text {
            font-size: 1rem;
            font-weight: 600;
            color: #ffffffff;
            white-space: nowrap;
            overflow: hidden;
        }

        /* ================================================
           USER DROPDOWN (top-right)
        ================================================ */
        .navbar-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
        }

        .navbar-user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Dropdown caret color on dark navbar */
        #userDropdown::after {
            border-top-color: rgba(255, 255, 255, 0.7) !important;
        }

        .user-dropdown-menu {
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
            border: 1px solid #e2e8f0;
            padding: 0;
            overflow: hidden;
        }

        .user-dropdown-header {
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e9ecef;
        }

        .user-dropdown-header .user-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1e293b;
        }

        .user-dropdown-header .user-role {
            font-size: 0.75rem;
            color: #64748b;
        }

        .user-dropdown-menu .dropdown-item {
            font-size: 0.875rem;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            transition: background 0.15s;
        }

        .user-dropdown-menu .dropdown-item:hover {
            background-color: #f1f5f9;
        }

        .user-dropdown-menu .dropdown-item.text-danger:hover {
            background-color: #fff5f5;
            color: #dc2626 !important;
        }
    </style>

    @stack('styles')
</head>

<body class="layout-fixed sidebar-mini sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">

        {{-- ===== TOP NAVBAR ===== --}}
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">

                {{-- Left side: toggle + home --}}
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list fs-5"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="/" class="nav-link">Home</a>
                    </li>
                </ul>

                {{-- Right side: User dropdown --}}
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 pe-1" href="#"
                            id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="navbar-user-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="navbar-user-name d-none d-sm-inline">Magang</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu" aria-labelledby="userDropdown">
                            {{-- Profile header --}}
                            <li>
                                <div class="user-dropdown-header">
                                    <div class="user-name">Magang</div>
                                    <div class="user-role">Pengguna Sistem</div>
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider m-0">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="#">
                                    <i class="bi bi-box-arrow-right"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

            </div>
        </nav>

        {{-- ===== SIDEBAR ===== --}}
        {{-- Remove data-bs-theme="dark" so our #113261 CSS takes full control --}}
        <aside class="app-sidebar shadow">

            {{-- Brand / Logo --}}
            {{-- Single static element: SK badge always stays, only brand-text fades --}}
            <div class="sidebar-brand">
                <a href="/" class="brand-link">
                    <div class="simkerma-logo-placeholder">SK</div>
                    <span class="brand-text fw-semibold">SIMKERMA</span>
                </a>
            </div>

            {{-- Nav Items --}}
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu"
                        data-accordion="false">

                        <li class="nav-item">
                            <a href="{{ url('/') }}" class="nav-link {{ request()->is('/') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-house-fill"></i>
                                <p>Beranda</p>
                            </a>
                        </li>

                        {{-- Data Mitra --}}
                        <li class="nav-item has-treeview {{ request()->is('data-mitra*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('data-mitra*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-database-fill"></i>
                                <p>Data Mitra <i class="nav-arrow bi bi-chevron-right ms-auto"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('data-mitra.kategori-mitra') }}"
                                       class="nav-link {{ request()->routeIs('data-mitra.kategori-mitra') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dash"></i>
                                        <p>Kategori Mitra</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('data-mitra.data-mitra') }}"
                                       class="nav-link {{ request()->routeIs('data-mitra.data-mitra') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dash"></i>
                                        <p>Data Mitra</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        {{-- Data Kerjasama --}}
                        <li class="nav-item has-treeview {{ request()->is('data-kerjasama*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('data-kerjasama*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-folder-fill"></i>
                                <p>Data Kerjasama <i class="nav-arrow bi bi-chevron-right ms-auto"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('data-kerjasama.data-mou') }}"
                                       class="nav-link {{ request()->routeIs('data-kerjasama.data-mou') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dash"></i>
                                        <p>Data MOU</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('data-kerjasama.data-pks') }}"
                                       class="nav-link {{ request()->routeIs('data-kerjasama.data-pks') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dash"></i>
                                        <p>Data PKS</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('data-kerjasama.data-ia') }}"
                                       class="nav-link {{ request()->routeIs('data-kerjasama.data-ia') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dash"></i>
                                        <p>Data IA</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        {{-- Simmagang --}}
                        <li class="nav-item has-treeview {{ request()->is('simmagang*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('simmagang*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-people-fill"></i>
                                <p>Simmagang <i class="nav-arrow bi bi-chevron-right ms-auto"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('simmagang.permintaan-kerjasama') }}"
                                       class="nav-link {{ request()->routeIs('simmagang.permintaan-kerjasama') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dash"></i>
                                        <p>Permintaan Kerjasama</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </nav>
            </div>
        </aside>

        {{-- ===== MAIN CONTENT ===== --}}
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    @yield('content-header')
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </main>

        {{-- ===== FOOTER ===== --}}
        <footer class="app-footer">
            {{-- <div class="float-end d-none d-sm-inline">Magang Polinema</div> --}}
            <strong>&copy; 2026 <a href="#">SIMKERMA</a>.</strong> All rights reserved.
        </footer>

    </div>

    {{-- Bootstrap JS (required for dropdowns, modals, etc.) --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
    @stack('scripts')

    {{-- Persist sidebar collapsed state across page reloads --}}
    <script>
        (function () {
            var STORAGE_KEY = 'simkerma_sidebar_collapsed';

            // Restore state as early as possible to avoid layout flash
            if (localStorage.getItem(STORAGE_KEY) === 'true') {
                document.body.classList.add('sidebar-collapse');
            } else {
                document.body.classList.remove('sidebar-collapse');
            }

            // After page loads, watch for AdminLTE toggle events
            document.addEventListener('DOMContentLoaded', function () {
                var observer = new MutationObserver(function () {
                    var isCollapsed = document.body.classList.contains('sidebar-collapse');
                    localStorage.setItem(STORAGE_KEY, isCollapsed ? 'true' : 'false');
                });

                observer.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            });
        })();
    </script>
</body>

</html>
