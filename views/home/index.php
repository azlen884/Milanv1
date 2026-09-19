<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    <!-- Top Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-brand-700 via-rose-600 to-amber-600 p-6 sm:p-10 text-white shadow-xl shadow-rose-900/10 mb-8">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold mb-3">
                    <span>Namaste, <?= htmlspecialchars($user['name'] ?? 'Friend') ?></span>
                    <?php if (($user['kyc_status'] ?? '') === 'verified'): ?>
                    <i data-lucide="badge-check" class="w-3.5 h-3.5 fill-emerald-400 text-white"></i>
                    <?php endif; ?>
                </div>
                <h1 class="font-serif text-2xl sm:text-4xl font-bold tracking-tight">
                    Explore Meaningful Connections
                </h1>
                <p class="text-rose-100 text-sm sm:text-base mt-2 max-w-xl">
                    New singles are active in your city today. Express interest and discover who is looking for someone like you.
                </p>
            </div>
            
            <div class="flex items-center gap-3 flex-shrink-0">
                <a href="/discover" class="px-6 py-3.5 rounded-full bg-white text-brand-700 font-bold text-sm shadow-md hover:bg-rose-50 transition-all flex items-center gap-2">
                    <i data-lucide="compass" class="w-4 h-4"></i>
                    <span>Explore Discover</span>
                </a>
            </div>
        </div>

        <!-- Ambient decorative circles -->
        <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- Quick Status Cards (Real Counts Only) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <!-- Matches -->
        <a href="/matches" class="p-5 rounded-2xl bg-white border border-rose-100/80 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Matches</span>
                <div class="w-8 h-8 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="heart-handshake" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2"><?= $matchesCount ?></div>
            <span class="text-[11px] text-brand-600 font-medium mt-1 inline-flex items-center gap-1">
                View matches <i data-lucide="chevron-right" class="w-3 h-3"></i>
            </span>
        </a>

        <!-- Interests Received -->
        <a href="/matches?tab=received" class="p-5 rounded-2xl bg-white border border-rose-100/80 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Interests In You</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="heart" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-slate-900 mt-2"><?= $interestsReceivedCount ?></div>
            <span class="text-[11px] text-rose-600 font-medium mt-1 inline-flex items-center gap-1">
                Respond now <i data-lucide="chevron-right" class="w-3 h-3"></i>
            </span>
        </a>

        <!-- Verification Status -->
        <a href="/settings?tab=kyc" class="p-5 rounded-2xl bg-white border border-rose-100/80 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Verification</span>
                <div class="w-8 h-8 rounded-xl <?= ($user['kyc_status'] === 'verified') ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="badge-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-lg font-bold capitalize mt-2 <?= ($user['kyc_status'] === 'verified') ? 'text-emerald-700' : 'text-slate-800' ?>">
                <?= ($user['kyc_status'] === 'verified') ? 'Verified' : (($user['kyc_status'] === 'pending') ? 'In Review' : 'Get Verified') ?>
            </div>
            <span class="text-[11px] text-slate-400 font-medium mt-1 inline-flex items-center gap-1">
                <?= ($user['kyc_status'] === 'verified') ? 'Active badge' : 'Upload ID' ?> <i data-lucide="chevron-right" class="w-3 h-3"></i>
            </span>
        </a>

        <!-- Subscription / Plan -->
        <a href="/subscription" class="p-5 rounded-2xl bg-white border border-rose-100/80 shadow-sm hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Plan</span>
                <div class="w-8 h-8 rounded-xl <?= ($user['is_paid']) ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500' ?> flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="crown" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-lg font-bold text-slate-900 mt-2 capitalize">
                <?= htmlspecialchars($user['subscription']['name'] ?? 'Free Plan') ?>
            </div>
            <span class="text-[11px] text-brand-600 font-medium mt-1 inline-flex items-center gap-1">
                <?= $user['is_paid'] ? 'Manage plan' : 'Upgrade' ?> <i data-lucide="chevron-right" class="w-3 h-3"></i>
            </span>
        </a>
    </div>

    <!-- Suggested Profiles For You (REAL Registered Users Only) -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-serif text-2xl font-bold text-slate-900">Suggested For You</h2>
                <p class="text-xs text-slate-500 mt-0.5">Based on your preferences and location</p>
            </div>
            <a href="/discover" class="text-xs font-bold text-brand-600 hover:text-brand-700 flex items-center gap-1">
                <span>View all</span>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>

        <?php if (empty($suggestedProfiles)): ?>
        <div class="p-8 rounded-2xl bg-white border border-rose-100 text-center text-slate-500">
            <p class="text-sm">No profiles found matching your current filters.</p>
            <a href="/settings?tab=preferences" class="inline-block mt-3 text-xs font-bold text-brand-600 underline">Adjust Dating Preferences</a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach ($suggestedProfiles as $p): ?>
            <div class="group bg-white rounded-2xl overflow-hidden border border-rose-100/80 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col">
                <a href="/profile?id=<?= $p['id'] ?>" class="relative aspect-[3/4] overflow-hidden bg-slate-100">
                    <img src="<?= htmlspecialchars($p['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80') ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    
                    <?php if (!empty($p['is_boosted'])): ?>
                    <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white shadow-sm flex items-center gap-1">
                        <i data-lucide="zap" class="w-3 h-3 fill-current"></i> Boosted
                    </span>
                    <?php endif; ?>

                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                    <div class="absolute bottom-3 left-3 right-3 text-white">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-base font-serif"><?= htmlspecialchars($p['name']) ?>, <?= (int)$p['age'] ?></span>
                            <?php if (($p['kyc_status'] ?? '') === 'verified'): ?>
                            <i data-lucide="badge-check" class="w-4 h-4 fill-emerald-500 text-white flex-shrink-0"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs text-slate-200 flex items-center gap-1 mt-0.5">
                            <i data-lucide="map-pin" class="w-3 h-3 text-rose-300"></i>
                            <?= htmlspecialchars($p['city']) ?>
                        </span>
                    </div>
                </a>

                <div class="p-3.5 flex items-center justify-between gap-2 border-t border-slate-100 bg-white">
                    <a href="/profile?id=<?= $p['id'] ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900">View Details</a>
                    
                    <?php if (!empty($p['has_sent_interest'])): ?>
                    <span class="px-3 py-1.5 rounded-full text-[11px] font-semibold bg-rose-50 text-brand-600">Sent</span>
                    <?php else: ?>
                    <button onclick="sendInterest(<?= $p['id'] ?>, this)" class="px-3.5 py-1.5 rounded-full bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-1">
                        <i data-lucide="heart" class="w-3.5 h-3.5 fill-current"></i>
                        <span>Interest</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recently Joined Members -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-serif text-xl font-bold text-slate-900">New On Milan</h2>
            <span class="text-xs text-slate-400">Recently verified users</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
            <?php foreach ($newMembers as $nm): ?>
            <a href="/profile?id=<?= $nm['id'] ?>" class="group p-3 rounded-2xl bg-white border border-rose-100/70 hover:shadow-md transition-all text-center">
                <div class="w-16 h-16 rounded-full overflow-hidden mx-auto mb-2 ring-2 ring-rose-200 group-hover:ring-brand-500 transition-all">
                    <img src="<?= htmlspecialchars($nm['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80') ?>" alt="" class="w-full h-full object-cover">
                </div>
                <div class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($nm['name']) ?></div>
                <div class="text-[10px] text-slate-400"><?= htmlspecialchars($nm['city']) ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    async function sendInterest(targetId, btn) {
        if (!btn || btn.dataset.processing === '1') return;
        btn.dataset.processing = '1';

        const originalHtml = btn.innerHTML;
        const originalClass = btn.className;
        btn.disabled = true;
        btn.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span><span>Sending...</span>';

        const formData = new FormData();
        formData.append('target_id', targetId);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/interest/send', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json().catch(() => null);

            if (res.ok && data && data.success) {
                if (data.is_match) {
                    showToast("🎉 It's a Match! You both expressed interest in each other.", 'success');
                } else {
                    showToast(data.message || 'Interest sent!', 'success');
                }
                btn.className = 'px-3 py-1.5 rounded-full text-[11px] font-semibold bg-rose-50 text-brand-600 cursor-default';
                btn.innerHTML = 'Sent';
            } else {
                showToast(data?.error || data?.message || 'Could not send interest.', 'error');
                btn.disabled = false;
                btn.className = originalClass;
                btn.innerHTML = originalHtml;
                delete btn.dataset.processing;
                lucide.createIcons();
            }
        } catch (e) {
            showToast('Unable to connect to server. Please try again.', 'error');
            btn.disabled = false;
            btn.className = originalClass;
            btn.innerHTML = originalHtml;
            delete btn.dataset.processing;
            lucide.createIcons();
        }
    }
</script>
