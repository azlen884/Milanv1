<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    
    <div class="mb-6">
        <h1 class="font-serif text-3xl font-bold text-slate-900">Profile Visitors</h1>
        <p class="text-sm text-slate-500 mt-1">See members who viewed your profile recently</p>
    </div>

    <?php if ($hasOptedOut): ?>
    <!-- Opted Out Notice -->
    <div class="p-10 rounded-3xl bg-white border border-rose-100 text-center shadow-sm">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="eye-off" class="w-7 h-7"></i>
        </div>
        <h3 class="font-serif text-xl font-bold text-slate-900">Visitor Tracking Is Paused</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
            You have chosen to opt out of appearing in visitor lists. In accordance with privacy rules, your own visitor list is also hidden.
        </p>
        <a href="/settings?tab=privacy" class="inline-block mt-5 px-6 py-2.5 rounded-full bg-brand-600 text-white font-bold text-xs shadow-sm">
            Adjust Privacy Settings
        </a>
    </div>

    <?php elseif (!$isPaid): ?>
    <!-- Paid Lock Gate for Free Users -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-brand-950 to-slate-900 p-8 sm:p-12 text-white shadow-2xl text-center">
        <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-amber-400 to-amber-600 text-slate-950 flex items-center justify-center mx-auto mb-6 shadow-lg shadow-amber-500/20">
            <i data-lucide="crown" class="w-8 h-8"></i>
        </div>
        
        <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Exclusive Premium Feature</span>
        <h2 class="font-serif text-2xl sm:text-4xl font-bold mt-2">See Who Visited Your Profile</h2>
        
        <p class="text-slate-300 text-sm sm:text-base mt-3 max-w-lg mx-auto leading-relaxed">
            Upgrade to a Milan Premium membership to discover who viewed your profile, enjoy unlimited messaging, and connect faster.
        </p>

        <!-- Blurred Preview Hint -->
        <div class="mt-8 flex items-center justify-center gap-3 filter blur-sm select-none opacity-40 pointer-events-none max-w-md mx-auto">
            <div class="w-12 h-12 rounded-full bg-slate-700"></div>
            <div class="w-12 h-12 rounded-full bg-slate-700"></div>
            <div class="w-12 h-12 rounded-full bg-slate-700"></div>
            <div class="w-12 h-12 rounded-full bg-slate-700"></div>
        </div>

        <div class="mt-8">
            <a href="/upgrade" class="inline-flex items-center gap-2 px-8 py-4 rounded-full bg-gradient-to-r from-amber-500 to-rose-500 hover:from-amber-600 hover:to-rose-600 text-white font-bold text-sm shadow-xl shadow-rose-900/30 transition-all">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Upgrade to View Visitors</span>
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- Authenticated Paid Visitor List -->
        <?php if (empty($visitors)): ?>
        <div class="p-12 rounded-3xl bg-white border border-rose-100 text-center shadow-sm">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-brand-600 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="users" class="w-7 h-7"></i>
            </div>
            <h3 class="font-serif text-xl font-bold text-slate-900">No Visitors Yet</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                Profiles viewing your profile will appear here. Boost your profile to increase your visibility on Discover!
            </p>
            <a href="/subscription" class="inline-block mt-4 text-xs font-bold text-brand-600 underline">Boost Profile</a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($visitors as $v): ?>
            <div class="bg-white rounded-3xl overflow-hidden border border-rose-100 shadow-sm hover:shadow-md transition-shadow p-5 flex items-center gap-4">
                <img src="<?= htmlspecialchars($v['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80') ?>" alt="" class="w-16 h-16 rounded-2xl object-cover ring-1 ring-slate-200 flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <a href="/profile?id=<?= $v['user_id'] ?>" class="font-bold text-sm text-slate-900 hover:text-brand-600 truncate block">
                            <?= htmlspecialchars($v['name']) ?>, <?= (int)$v['age'] ?>
                        </a>
                        <?php if (($v['kyc_status'] ?? '') === 'verified'): ?>
                        <i data-lucide="badge-check" class="w-4 h-4 fill-emerald-500 text-white flex-shrink-0"></i>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs text-slate-400 block"><?= htmlspecialchars($v['city']) ?></span>
                    <span class="text-[10px] text-slate-400 mt-1 block">Visited <?= date('M j', strtotime($v['visited_at'])) ?></span>
                </div>
                <a href="/profile?id=<?= $v['user_id'] ?>" class="p-2.5 rounded-xl bg-rose-50 text-brand-700 hover:bg-rose-100 transition-colors" title="View Profile">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
