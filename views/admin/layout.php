<?php
/**
 * Dedicated Super Admin Master Layout
 * Clean Rose + White Theme • Full Accessibility • Zero HTML Tables
 */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
$pageTitle = $pageTitle ?? 'Admin Dashboard';
$adminUser = $currentAdmin ?? \App\Helpers\AdminAuth::admin();
$counts = $sidebarCounts ?? [
    'pending_kyc' => 0,
    'open_reports' => 0,
    'active_boosts' => 0,
    'maintenance_mode' => false,
];

// Helper to determine active sidebar link
$isActive = function(string $route, bool $exact = false) use ($currentPath): bool {
    if ($exact) {
        return $currentPath === $route;
    }
    return strpos($currentPath, $route) === 0;
};
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-[#FDFBFB]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Milan Super Admin Console</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN with Rose Theme config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Playfair Display"', 'serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        /* Custom scrollbar and animations */
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        
        #page-loader-bar {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.2s ease-out forwards;
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#FDFBFB]">
    <!-- Top Progress Bar for page transitions -->
    <div id="page-loader-bar" class="fixed top-0 left-0 h-1 bg-gradient-to-r from-rose-500 via-rose-600 to-pink-600 z-50 w-0 opacity-0 pointer-events-none"></div>

    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-40 lg:hidden opacity-0 pointer-events-none transition-opacity duration-300"></div>

    <!-- Dedicated Admin Sidebar -->
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 w-72 bg-white border-r border-rose-100 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-lg lg:shadow-none">
        <!-- Sidebar Brand Header -->
        <div class="h-16 px-6 border-b border-rose-100/80 flex items-center justify-between bg-white">
            <a href="/admin/dashboard" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-pink-500 text-white flex items-center justify-center shadow-md shadow-rose-500/20">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="font-serif font-bold text-lg text-slate-900 leading-none">Milan</div>
                    <div class="text-[10px] font-bold text-rose-600 uppercase tracking-widest mt-1 flex items-center gap-1">
                        <span>Super Admin</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    </div>
                </div>
            </a>
            <button id="sidebar-close-btn" class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-rose-50">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-6 scrollbar-none">
            <!-- MAIN -->
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">Overview</div>
                <div class="space-y-1">
                    <a href="/admin/dashboard" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= ($isActive('/admin/dashboard', true) || $isActive('/admin', true)) ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 <?= ($isActive('/admin/dashboard', true) || $isActive('/admin', true)) ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Dashboard</span>
                        </div>
                    </a>
                    <a href="/admin/analytics" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/analytics') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="trending-up" class="w-4 h-4 <?= $isActive('/admin/analytics') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Analytics</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- USERS & SAFETY -->
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">Users & Safety</div>
                <div class="space-y-1">
                    <a href="/admin/users" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/users') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="users" class="w-4 h-4 <?= $isActive('/admin/users') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>All Users</span>
                        </div>
                    </a>
                    <a href="/admin/bots" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/bots') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="bot" class="w-4 h-4 <?= $isActive('/admin/bots') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Admin Bots</span>
                        </div>
                    </a>
                    <a href="/admin/reports" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/reports') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="flag" class="w-4 h-4 <?= $isActive('/admin/reports') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Safety Reports</span>
                        </div>
                        <?php if ($counts['open_reports'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700"><?= $counts['open_reports'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- VERIFICATION -->
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">Compliance</div>
                <div class="space-y-1">
                    <a href="/admin/kyc" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/kyc') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="badge-check" class="w-4 h-4 <?= $isActive('/admin/kyc') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>KYC Verification</span>
                        </div>
                        <?php if ($counts['pending_kyc'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-600 text-white animate-pulse"><?= $counts['pending_kyc'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- MONETIZATION -->
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">Monetization</div>
                <div class="space-y-1">
                    <a href="/admin/subscriptions" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/subscriptions') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="crown" class="w-4 h-4 <?= $isActive('/admin/subscriptions') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Subscription Plans</span>
                        </div>
                    </a>
                    <a href="/admin/payments" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/payments') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="receipt" class="w-4 h-4 <?= $isActive('/admin/payments') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Transactions</span>
                        </div>
                    </a>
                    <a href="/admin/razorpay" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/razorpay') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="credit-card" class="w-4 h-4 <?= $isActive('/admin/razorpay') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Razorpay Gateway</span>
                        </div>
                    </a>
                    <a href="/admin/boost" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/boost') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="zap" class="w-4 h-4 <?= $isActive('/admin/boost') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Profile Boost</span>
                        </div>
                        <?php if ($counts['active_boosts'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200"><?= $counts['active_boosts'] ?> active</span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- DATING & CONTENT -->
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">Dating & Content</div>
                <div class="space-y-1">
                    <a href="/admin/discovery" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/discovery') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="sparkles" class="w-4 h-4 <?= $isActive('/admin/discovery') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Discovery & Matching</span>
                        </div>
                    </a>
                    <a href="/admin/landing-profiles" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/landing-profiles') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="sparkle" class="w-4 h-4 <?= $isActive('/admin/landing-profiles') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Landing Profiles</span>
                        </div>
                    </a>
                    <a href="/admin/content" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/content') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="file-text" class="w-4 h-4 <?= $isActive('/admin/content') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Site Content & FAQ</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- SYSTEM & AUDIT -->
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">System & Logs</div>
                <div class="space-y-1">
                    <a href="/admin/settings" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/settings') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="settings" class="w-4 h-4 <?= $isActive('/admin/settings') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Platform Settings</span>
                        </div>
                    </a>
                    <a href="/admin/audit-logs" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/audit-logs') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="history" class="w-4 h-4 <?= $isActive('/admin/audit-logs') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>Security Audit Logs</span>
                        </div>
                    </a>
                    <a href="/admin/system" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all <?= $isActive('/admin/system') ? 'bg-rose-50 text-rose-700 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="cpu" class="w-4 h-4 <?= $isActive('/admin/system') ? 'text-rose-600' : 'text-slate-400' ?>"></i>
                            <span>System & Runtime</span>
                        </div>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Super Admin Footer Profile Card -->
        <div class="p-4 border-t border-rose-100/80 bg-rose-50/40">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-rose-600 text-white font-bold flex items-center justify-center text-sm shadow-xs shrink-0">
                        SA
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($adminUser['username'] ?? 'Super Admin') ?></div>
                        <div class="text-[11px] text-rose-600 font-medium">Super Admin</div>
                    </div>
                </div>
                <form action="/admin/logout" method="POST" class="shrink-0" onsubmit="return confirm('Are you sure you want to log out from the Super Admin Console?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <button type="submit" title="Logout" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-white transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="lg:pl-72 flex flex-col min-h-screen">
        <!-- Dedicated Admin Header (NO public navbar, NO Sign In, NO Get Started) -->
        <header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur-md border-b border-rose-100/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <button id="sidebar-open-btn" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-rose-50 hover:text-rose-600 transition-colors" aria-label="Open navigation">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="flex items-center gap-2 text-xs font-medium text-slate-400">
                    <span class="hidden sm:inline">Super Admin Console</span>
                    <span class="hidden sm:inline">/</span>
                    <h1 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
            </div>

            <!-- Top Right Admin Controls -->
            <div class="flex items-center gap-3 sm:gap-4">
                <?php if ($counts['maintenance_mode']): ?>
                    <a href="/admin/settings" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500 text-white shadow-xs animate-pulse">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        <span>Maintenance Active</span>
                    </a>
                <?php endif; ?>

                <!-- Frontsite Preview Link (opens in new tab) -->
                <a href="/" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50/50 transition-all">
                    <span>View Public Site</span>
                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                </a>

                <!-- Quick Settings Link -->
                <a href="/admin/settings" class="p-2 rounded-xl text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Settings">
                    <i data-lucide="sliders" class="w-4 h-4"></i>
                </a>

                <!-- Super Admin Account Badge -->
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs">
                        SA
                    </div>
                    <span class="hidden md:block text-xs font-bold text-slate-700"><?= htmlspecialchars($adminUser['username'] ?? 'Super Admin') ?></span>
                </div>
            </div>
        </header>

        <!-- Flash Messages Toast -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="flex items-center justify-between p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-xs animate-fadeIn">
                    <div class="flex items-center gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                        <span class="font-medium"><?= htmlspecialchars($flashSuccess) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                <div class="flex items-center justify-between p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs animate-fadeIn">
                    <div class="flex items-center gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                        <span class="font-medium"><?= htmlspecialchars($flashError) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main View Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 animate-fadeIn">
            <?= $content ?>
        </main>

        <!-- Footer -->
        <footer class="border-t border-rose-100/60 bg-white py-4 px-6 text-center text-xs text-slate-400">
            Milan Dating &copy; <?= date('Y') ?> &bull; Super Admin Console &bull; Confidential & Compliance Controlled
        </footer>
    </div>

    <!-- Vanilla JS Interactions -->
    <script>
        // Initialize Lucide Icons
        if (window.lucide) {
            lucide.createIcons();
        }

        // Mobile Sidebar Controls
        const sidebar = document.getElementById('admin-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const openBtn = document.getElementById('sidebar-open-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
        }

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        // Auto close mobile sidebar when link is clicked
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    closeSidebar();
                }
            });
        });

        // Smooth Page Loader Bar on navigation
        document.querySelectorAll('a[href^="/admin"]').forEach(a => {
            a.addEventListener('click', (e) => {
                const href = a.getAttribute('href');
                if (href && !href.startsWith('#') && !a.hasAttribute('target')) {
                    const bar = document.getElementById('page-loader-bar');
                    if (bar) {
                        bar.classList.remove('opacity-0');
                        bar.style.width = '70%';
                    }
                }
            });
        });

        // Form Submit Loading State (prevent double-submits and UI stutter)
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                const btn = form.querySelector('button[type="submit"]');
                if (btn && !btn.dataset.noLoading) {
                    btn.disabled = true;
                    btn.classList.add('opacity-75', 'cursor-not-allowed');
                }
                const bar = document.getElementById('page-loader-bar');
                if (bar) {
                    bar.classList.remove('opacity-0');
                    bar.style.width = '85%';
                }
            });
        });
    </script>
</body>
</html>
