<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FBR Digital Invoicing') - Enterprise Portal</title>

    <!-- Bootstrap 5 CSS & Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .navbar-brand-logo {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #fff;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            min-height: calc(100vh - 60px);
        }
        .sidebar .nav-link {
            color: #64748b;
            font-weight: 500;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #0284c7;
            background-color: #f0f9ff;
        }
        .sidebar .nav-link i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }
        .main-content {
            flex: 1;
            padding: 1.75rem;
            max-width: 1400px;
        }
        .card-summary {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: transform 0.15s ease;
        }
        .card-summary:hover {
            transform: translateY(-2px);
        }
        .badge-fbr-live {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-fbr-mock {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark" href="{{ auth()->check() ? route('dashboard') : route('login') }}">
                <span class="navbar-brand-logo">FBR</span>
                <span>Invoicing Gateway</span>
            </a>

            <div class="d-flex align-items-center gap-3 ms-auto">
                <!-- FBR Environment Status Pill -->
                <span class="badge {{ config('services.fbr.environment', 'mock') === 'live' ? 'badge-fbr-live' : 'badge-fbr-mock' }} px-3 py-2 rounded-pill font-monospace">
                    <i class="bi bi-broadcast me-1"></i> FBR MODE: {{ strtoupper(config('services.fbr.environment', 'MOCK')) }}
                </span>

                <!-- User Dropdown -->
                @auth
                <div class="dropdown">
                    <button class="btn btn-light btn-sm border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:28px; height:28px; font-size:12px; font-weight:700;">
                            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        </div>
                        <span class="d-none d-md-inline fw-semibold text-dark">{{ auth()->user()->name ?? 'Admin User' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><span class="dropdown-header">POS: {{ auth()->user()->pos_id ?? env('FBR_POS_ID', 'POS-100234') }}</span></li>
                        <li><span class="dropdown-header text-muted">NTN: {{ auth()->user()->seller_ntn ?? env('FBR_SELLER_NTN', '7890123-4') }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i> Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary fw-semibold">Sign In</a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-primary fw-semibold">Register Shop</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <!-- Sidebar Navigation -->
        @auth
        <aside class="sidebar p-3 d-none d-lg-block">
            <div class="text-uppercase text-muted px-3 mb-2" style="font-size:11px; font-weight:700; letter-spacing:0.5px;">Navigation</div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('invoices.upload') ? 'active' : '' }}" href="{{ route('invoices.upload') }}">
                        <i class="bi bi-cloud-arrow-up"></i> Upload Invoice File
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('invoices.preview') ? 'active' : '' }}" href="{{ route('invoices.preview') }}">
                        <i class="bi bi-eye"></i> Invoice Preview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('invoices.submit') ? 'active' : '' }}" href="{{ route('invoices.submit') }}">
                        <i class="bi bi-send-check"></i> FBR Submissions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('invoices.failed') ? 'active' : '' }}" href="{{ route('invoices.failed') }}">
                        <i class="bi bi-exclamation-triangle"></i> Failed Submissions
                        @php $failedBadgeCount = \App\Models\Invoice::where('user_id', auth()->id())->where('fbr_status', 'FAILED')->count(); @endphp
                        @if($failedBadgeCount > 0)
                            <span class="badge bg-danger rounded-pill float-end">{{ $failedBadgeCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('invoices.index') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                        <i class="bi bi-archive"></i> Invoice Archive
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                        <i class="bi bi-bar-chart-line"></i> Reports & Export
                    </a>
                </li>
            </ul>
        </aside>
        @endauth

        <!-- Main Content View -->
        <main class="main-content">
            <!-- Flash Message Alerts -->
            <x-alert />

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
