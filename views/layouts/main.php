<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Milan Dating — Meaningful Connections</title>
    <meta name="description" content="India's verified dating platform for meaningful relationships, authentic profiles, and real connections.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Play CDN with Rose Theme) -->
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
    <!-- Razorpay Checkout -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <style>
        /* Smooth scrolling and native touch feel */
        html, body {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom, 16px);
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-none {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="min-h-full flex flex-col text-slate-900 bg-[#FDFBFB] selection:bg-brand-500 selection:text-white pb-24 lg:pb-0">

    <!-- Global Flash Notification Toasts -->
    <?php if (!empty($flashSuccess)): ?>
    <div id="toast-success" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-xl shadow-slate-900/20 text-sm font-medium border border-slate-800 transition-all duration-300">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400"></i>
        <span><?= htmlspecialchars($flashSuccess) ?></span>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white ml-2"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
    <div id="toast-error" class="fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-red-600 text-white px-5 py-3.5 rounded-2xl shadow-xl shadow-red-600/20 text-sm font-medium transition-all duration-300">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-200"></i>
        <span><?= htmlspecialchars($flashError) ?></span>
        <button onclick="this.parentElement.remove()" class="text-red-200 hover:text-white ml-2"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    <?php endif; ?>

    <!-- Header Navigation -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-rose-100/70">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="<?= $currentUser ? '/home' : '/' ?>" class="flex items-center gap-2.5 group">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-rose-400 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform duration-200">
                    <i data-lucide="heart" class="w-5 h-5 fill-current"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-serif text-2xl font-bold tracking-tight bg-gradient-to-r from-brand-700 to-rose-600 bg-clip-text text-transparent">Milan</span>
                    <span class="text-[10px] tracking-wider uppercase font-bold text-slate-400 -mt-1">Dating</span>
                </div>
            </a>

            <!-- Desktop Nav Links (If Logged In) -->
            <?php if ($currentUser): ?>
            <nav class="hidden lg:flex items-center gap-1">
                <a href="/home" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors <?= ($_SERVER['REQUEST_URI'] === '/home') ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">Home</a>
                <a href="/discover" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors <?= (str_starts_with($_SERVER['REQUEST_URI'], '/discover')) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">Discover</a>
                <a href="/matches" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors <?= (str_starts_with($_SERVER['REQUEST_URI'], '/matches')) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">Matches</a>
                <a href="/messages" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors relative <?= (str_starts_with($_SERVER['REQUEST_URI'], '/messages')) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                    Messages
                    <?php if (($currentUser['unread_messages'] ?? 0) > 0): ?>
                        <span class="absolute top-1.5 right-1 w-2 h-2 rounded-full bg-brand-600"></span>
                    <?php endif; ?>
                </a>
                <a href="/visitors" class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors <?= (str_starts_with($_SERVER['REQUEST_URI'], '/visitors')) ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">Visitors</a>
            </nav>

            <!-- Right Profile & Subscription Controls -->
            <div class="flex items-center gap-3">
                <?php if (empty($currentUser['is_paid'])): ?>
                <a href="/upgrade" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-gradient-to-r from-amber-500 to-rose-500 text-white shadow-sm hover:opacity-95 transition-all">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Upgrade</span>
                </a>
                <?php else: ?>
                <span class="hidden sm:inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200/80">
                    <i data-lucide="crown" class="w-3.5 h-3.5 text-amber-600"></i>
                    <span>Premium</span>
                </span>
                <?php endif; ?>

                <!-- User Avatar dropdown trigger -->
                <a href="/profile" class="flex items-center gap-2 p-1 pl-2 bg-slate-50 hover:bg-rose-50 rounded-full border border-slate-200/70 transition-colors">
                    <span class="text-xs font-semibold text-slate-700 hidden md:inline"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
                    <img src="<?= htmlspecialchars($currentUser['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80') ?>" alt="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" class="w-8 h-8 rounded-full object-cover ring-2 ring-brand-500/30">
                </a>

                <a href="/settings" title="Settings" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                </a>
            </div>

            <?php else: ?>
            <!-- Guest Header (Public Landing) -->
            <div class="flex items-center gap-3">
                <a href="/login" class="px-4 py-2 text-sm font-semibold text-slate-700 hover:text-brand-600 transition-colors">Sign In</a>
                <a href="/register" class="px-5 py-2.5 rounded-full text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white shadow-md shadow-brand-500/25 transition-all duration-200">Get Started</a>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1">
        <?= $content ?>
    </main>

    <!-- AUTHENTICATED FIXED BOTTOM NAVIGATION (5 Items, STRICTLY FOR LOGGED IN USERS) -->
    <?php if ($currentUser): ?>
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-lg border-t border-rose-100 safe-bottom shadow-[0_-4px_20px_rgba(0,0,0,0.04)]">
        <div class="grid grid-cols-5 h-16 items-center px-2 max-w-lg mx-auto">
            <!-- 1. Home -->
            <a href="/home" class="flex flex-col items-center justify-center gap-1 py-1 <?= ($_SERVER['REQUEST_URI'] === '/home') ? 'text-brand-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <i data-lucide="home" class="w-5 h-5 <?= ($_SERVER['REQUEST_URI'] === '/home') ? 'stroke-[2.5]' : 'stroke-2' ?>"></i>
                <span class="text-[10px] font-semibold tracking-tight">Home</span>
            </a>

            <!-- 2. Discover -->
            <a href="/discover" class="flex flex-col items-center justify-center gap-1 py-1 <?= (str_starts_with($_SERVER['REQUEST_URI'], '/discover')) ? 'text-brand-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <i data-lucide="compass" class="w-5 h-5 <?= (str_starts_with($_SERVER['REQUEST_URI'], '/discover')) ? 'stroke-[2.5]' : 'stroke-2' ?>"></i>
                <span class="text-[10px] font-semibold tracking-tight">Discover</span>
            </a>

            <!-- 3. Messages -->
            <a href="/messages" class="flex flex-col items-center justify-center gap-1 py-1 relative <?= (str_starts_with($_SERVER['REQUEST_URI'], '/messages')) ? 'text-brand-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <div class="relative">
                    <i data-lucide="message-circle" class="w-5 h-5 <?= (str_starts_with($_SERVER['REQUEST_URI'], '/messages')) ? 'stroke-[2.5]' : 'stroke-2' ?>"></i>
                    <?php if (($currentUser['unread_messages'] ?? 0) > 0): ?>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-brand-600 rounded-full ring-2 ring-white"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] font-semibold tracking-tight">Messages</span>
            </a>

            <!-- 4. Matches -->
            <a href="/matches" class="flex flex-col items-center justify-center gap-1 py-1 <?= (str_starts_with($_SERVER['REQUEST_URI'], '/matches')) ? 'text-brand-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <i data-lucide="heart-handshake" class="w-5 h-5 <?= (str_starts_with($_SERVER['REQUEST_URI'], '/matches')) ? 'stroke-[2.5]' : 'stroke-2' ?>"></i>
                <span class="text-[10px] font-semibold tracking-tight">Matches</span>
            </a>

            <!-- 5. Profile -->
            <a href="/profile" class="flex flex-col items-center justify-center gap-1 py-1 <?= (str_starts_with($_SERVER['REQUEST_URI'], '/profile')) ? 'text-brand-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <div class="w-5 h-5 rounded-full overflow-hidden ring-1 ring-slate-300">
                    <img src="<?= htmlspecialchars($currentUser['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80') ?>" alt="" class="w-full h-full object-cover">
                </div>
                <span class="text-[10px] font-semibold tracking-tight">Profile</span>
            </a>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Global Scripts -->
    <script>
        lucide.createIcons();

        // Global Helper for Authenticated AJAX Requests
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        // Global Toast Trigger
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-xl text-sm font-medium transition-all duration-300 ${type === 'success' ? 'bg-slate-900 text-white border border-slate-800' : 'bg-red-600 text-white'}`;
            toast.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-2 text-slate-300 hover:text-white">&times;</button>
            `;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 4000);
        }

        // Auto-dismiss initial toasts
        setTimeout(() => {
            document.querySelectorAll('#toast-success, #toast-error').forEach(el => {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            });
        }, 4000);
    </script>
</body>
</html>
