<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
    
    <!-- Title & Filter Toggle -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-3xl font-bold text-slate-900">Discover Matches</h1>
            <p class="text-sm text-slate-500 mt-1">Real singles looking for genuine connections</p>
        </div>

        <!-- Filter toggle button on mobile -->
        <button onclick="document.getElementById('filter-panel').classList.toggle('hidden')" class="sm:hidden px-4 py-2 rounded-xl bg-white border border-rose-200 text-slate-700 font-semibold text-xs flex items-center justify-center gap-2">
            <i data-lucide="filter" class="w-4 h-4 text-brand-600"></i>
            <span>Filter Profiles</span>
        </button>
    </div>

    <!-- Filters Panel -->
    <div id="filter-panel" class="hidden sm:block bg-white rounded-3xl p-6 border border-rose-100 shadow-sm mb-8">
        <form action="/discover" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <!-- Gender -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Gender</label>
                <select name="gender" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 bg-white">
                    <option value="">All Genders</option>
                    <option value="female" <?= ($filters['gender'] === 'female') ? 'selected' : '' ?>>Women</option>
                    <option value="male" <?= ($filters['gender'] === 'male') ? 'selected' : '' ?>>Men</option>
                    <option value="other" <?= ($filters['gender'] === 'other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <!-- City -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">City</label>
                <select name="city" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 bg-white">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= ($filters['city'] === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Age Range -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Age Range</label>
                <div class="flex items-center gap-2">
                    <input type="number" name="age_min" value="<?= (int)$filters['age_min'] ?>" min="18" max="70" class="w-1/2 px-2.5 py-2.5 rounded-xl border border-slate-200 text-xs text-center font-semibold">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="number" name="age_max" value="<?= (int)$filters['age_max'] ?>" min="18" max="70" class="w-1/2 px-2.5 py-2.5 rounded-xl border border-slate-200 text-xs text-center font-semibold">
                </div>
            </div>

            <!-- Interests -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Interest</label>
                <select name="interest" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 bg-white">
                    <option value="">Any Interest</option>
                    <?php foreach ($interests as $in): ?>
                    <option value="<?= htmlspecialchars($in['name']) ?>" <?= ($filters['interest'] === $in['name']) ? 'selected' : '' ?>><?= htmlspecialchars($in['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Toggles -->
            <div class="space-y-1.5 pb-1">
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="kyc_verified" value="1" <?= $filters['kyc_verified'] ? 'checked' : '' ?> class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Verified Only</span>
                </label>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="recently_active" value="1" <?= $filters['recently_active'] ? 'checked' : '' ?> class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span>Active Recently</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm transition-colors flex items-center justify-center gap-1.5">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Apply</span>
                </button>
                <a href="/discover" class="px-3 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs flex items-center justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Profiles Grid (Only REAL registered users) -->
    <?php if (empty($profiles)): ?>
    <div class="p-16 rounded-3xl bg-white border border-rose-100 text-center shadow-sm">
        <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="users" class="w-7 h-7"></i>
        </div>
        <h3 class="font-serif text-xl font-bold text-slate-900">No Profiles Match Your Criteria</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
            Try broadening your search filters by expanding age range or exploring other vibrant cities.
        </p>
        <a href="/discover" class="inline-block mt-5 px-6 py-2.5 rounded-full bg-brand-600 text-white font-bold text-xs shadow-sm">
            Reset All Filters
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($profiles as $p): ?>
        <div class="group bg-white rounded-3xl overflow-hidden border border-rose-100/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col">
            <!-- Main Photo & Overlays -->
            <div class="relative aspect-[4/5] bg-slate-100 overflow-hidden">
                <a href="/profile?id=<?= $p['id'] ?>">
                    <img src="<?= htmlspecialchars($p['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&auto=format&fit=crop&q=80') ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                </a>

                <!-- Badges -->
                <div class="absolute top-4 left-4 right-4 flex items-center justify-between pointer-events-none">
                    <?php if (($p['kyc_status'] ?? '') === 'verified'): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/95 text-white backdrop-blur-md shadow-sm">
                        <i data-lucide="badge-check" class="w-3.5 h-3.5 fill-current"></i>
                        <span>Verified</span>
                    </span>
                    <?php else: ?>
                    <span></span>
                    <?php endif; ?>

                    <?php if (!empty($p['is_boosted'])): ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-500 text-white shadow-sm">
                        <i data-lucide="zap" class="w-3.5 h-3.5 fill-current"></i>
                        <span>Boosted</span>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Gradient with Info Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent flex flex-col justify-end p-5 text-white pointer-events-none">
                    <div class="flex items-baseline gap-2">
                        <h3 class="font-serif text-2xl font-bold tracking-tight"><?= htmlspecialchars($p['name']) ?>, <?= (int)$p['age'] ?></h3>
                    </div>

                    <div class="flex items-center gap-3 text-xs text-slate-200 mt-1">
                        <span class="flex items-center gap-1">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-rose-400"></i>
                            <?= htmlspecialchars($p['city']) ?>
                        </span>
                        <?php if (!empty($p['occupation'])): ?>
                        <span>•</span>
                        <span><?= htmlspecialchars($p['occupation']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($p['bio'])): ?>
                    <p class="text-xs text-slate-300 mt-2 line-clamp-2 leading-relaxed">
                        <?= htmlspecialchars($p['bio']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="p-4 bg-white border-t border-slate-100 flex items-center justify-between gap-3">
                <a href="/profile?id=<?= $p['id'] ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:text-brand-600 hover:border-brand-300 font-semibold text-xs transition-colors flex items-center gap-1.5">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>View Profile</span>
                </a>

                <?php if (!empty($p['match_id'])): ?>
                <a href="/chat?with=<?= $p['id'] ?>" class="px-5 py-2.5 rounded-xl bg-brand-50 text-brand-700 hover:bg-brand-100 font-bold text-xs transition-all flex items-center gap-1.5">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span>Chat</span>
                </a>
                <?php elseif (!empty($p['has_sent_interest'])): ?>
                <span class="px-4 py-2.5 rounded-xl bg-rose-50 text-brand-600 font-semibold text-xs flex items-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Interest Sent</span>
                </span>
                <?php else: ?>
                <button onclick="sendInterest(<?= $p['id'] ?>, this)" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition-all flex items-center gap-1.5">
                    <i data-lucide="heart" class="w-4 h-4 fill-current"></i>
                    <span>Send Interest</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
    async function sendInterest(targetId, btn) {
        if (!btn || btn.dataset.processing === '1') return;
        btn.dataset.processing = '1';

        const originalHtml = btn.innerHTML;
        const originalClass = btn.className;
        btn.disabled = true;
        btn.innerHTML = '<span class="inline-block animate-spin mr-1">⏳</span><span>Expressing...</span>';

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
                    showToast("🎉 It's a Match! You both liked each other.", 'success');
                } else {
                    showToast(data.message || 'Interest expressed successfully!', 'success');
                }
                btn.className = 'px-4 py-2.5 rounded-xl bg-rose-50 text-brand-600 font-semibold text-xs flex items-center gap-1.5 cursor-default';
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>Interest Sent</span>';
                lucide.createIcons();
            } else {
                showToast(data?.error || data?.message || 'Could not send interest. Please try again.', 'error');
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
