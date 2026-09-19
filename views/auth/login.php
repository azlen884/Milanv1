<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl p-8 sm:p-10 border border-rose-100 shadow-xl shadow-rose-100/50">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="lock" class="w-6 h-6"></i>
            </div>
            <h1 class="font-serif text-2xl font-bold text-slate-900">Welcome Back</h1>
            <p class="text-sm text-slate-500 mt-1">Sign in to continue your conversations</p>
        </div>

        <form action="/login" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </span>
                    <input type="email" id="email" name="email" required placeholder="you@example.com"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <i data-lucide="key" class="w-4 h-4"></i>
                    </span>
                    <input type="password" id="password" name="password" required placeholder="••••••••"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all">
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-600/25 transition-all duration-200 mt-2">
                Sign In
            </button>
        </form>

        <!-- Quick Demo Switcher for Easy Verification -->
        <div class="mt-8 pt-6 border-t border-slate-100">
            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider text-center mb-3">Quick Demo Profiles</span>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button onclick="fillLogin('priya.sharma@example.com', 'Password@123')" class="p-2 rounded-lg bg-slate-50 hover:bg-rose-50 text-slate-700 hover:text-brand-600 border border-slate-200/80 text-left transition-colors">
                    <span class="font-semibold block">Priya S. (Verified)</span>
                    <span class="text-[10px] text-slate-400">Free Member</span>
                </button>
                <button onclick="fillLogin('aarav.patel@example.com', 'Password@123')" class="p-2 rounded-lg bg-slate-50 hover:bg-rose-50 text-slate-700 hover:text-brand-600 border border-slate-200/80 text-left transition-colors">
                    <span class="font-semibold block">Aarav P.</span>
                    <span class="text-[10px] text-slate-400">Free Member</span>
                </button>
            </div>
        </div>

        <div class="text-center mt-6 text-xs text-slate-500">
            Don't have an account? 
            <a href="/register" class="font-bold text-brand-600 hover:underline">Create One Now</a>
        </div>
    </div>
</div>

<script>
    function fillLogin(email, pass) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
    }
</script>
