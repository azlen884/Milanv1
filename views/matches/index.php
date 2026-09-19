<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    
    <div class="mb-6">
        <h1 class="font-serif text-3xl font-bold text-slate-900">Matches & Interests</h1>
        <p class="text-sm text-slate-500 mt-1">Genuine connections formed on mutual appreciation</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-rose-100 pb-3 mb-8 overflow-x-auto scrollbar-none">
        <a href="/matches?tab=matches" class="px-5 py-2.5 rounded-full text-xs font-bold transition-all flex items-center gap-2 <?= ($currentTab === 'matches') ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <i data-lucide="heart-handshake" class="w-4 h-4"></i>
            <span>Mutual Matches</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= ($currentTab === 'matches') ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-700' ?>"><?= $matchesCount ?></span>
        </a>

        <a href="/matches?tab=received" class="px-5 py-2.5 rounded-full text-xs font-bold transition-all flex items-center gap-2 <?= ($currentTab === 'received') ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <i data-lucide="inbox" class="w-4 h-4"></i>
            <span>Interests Received</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= ($currentTab === 'received') ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-700' ?>"><?= $receivedCount ?></span>
        </a>

        <a href="/matches?tab=sent" class="px-5 py-2.5 rounded-full text-xs font-bold transition-all flex items-center gap-2 <?= ($currentTab === 'sent') ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' ?>">
            <i data-lucide="send" class="w-4 h-4"></i>
            <span>Interests Sent</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] <?= ($currentTab === 'sent') ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-700' ?>"><?= $sentCount ?></span>
        </a>
    </div>

    <!-- TAB 1: Mutual Matches -->
    <?php if ($currentTab === 'matches'): ?>
        <?php if (empty($matches)): ?>
        <div class="p-12 rounded-3xl bg-white border border-rose-100 text-center shadow-sm">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-brand-600 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="sparkles" class="w-7 h-7"></i>
            </div>
            <h3 class="font-serif text-xl font-bold text-slate-900">No Matches Yet</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                When you and another member both express interest in each other, you'll match here!
            </p>
            <a href="/discover" class="inline-block mt-5 px-6 py-2.5 rounded-full bg-brand-600 text-white font-bold text-xs shadow-md shadow-brand-600/20">
                Explore Discover
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($matches as $m): ?>
            <div class="bg-white rounded-3xl overflow-hidden border border-rose-100 shadow-sm hover:shadow-md transition-shadow flex flex-col">
                <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
                    <img src="<?= htmlspecialchars($m['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80') ?>" alt="" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                    <div class="absolute bottom-3 left-3 right-3 text-white">
                        <div class="flex items-center gap-1.5 font-bold font-serif text-lg">
                            <span><?= htmlspecialchars($m['name']) ?>, <?= (int)$m['age'] ?></span>
                            <?php if (($m['kyc_status'] ?? '') === 'verified'): ?>
                            <i data-lucide="badge-check" class="w-4 h-4 fill-emerald-500 text-white"></i>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs text-slate-200"><?= htmlspecialchars($m['city']) ?></span>
                    </div>
                </div>
                <div class="p-4 flex items-center justify-between gap-3">
                    <span class="text-[11px] text-slate-400">Matched <?= date('M j', strtotime($m['matched_at'])) ?></span>
                    <a href="/chat?with=<?= $m['other_user_id'] ?>" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                        <span>Start Chat</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <!-- TAB 2: Interests Received -->
    <?php elseif ($currentTab === 'received'): ?>
        <?php if (empty($received)): ?>
        <div class="p-12 rounded-3xl bg-white border border-rose-100 text-center shadow-sm">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="inbox" class="w-7 h-7"></i>
            </div>
            <h3 class="font-serif text-xl font-bold text-slate-900">No Pending Interests</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                Profiles that express interest in you will appear here for you to accept.
            </p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($received as $r): ?>
            <div class="p-4 rounded-3xl bg-white border border-rose-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img src="<?= htmlspecialchars($r['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80') ?>" alt="" class="w-16 h-16 rounded-2xl object-cover ring-1 ring-slate-200 flex-shrink-0">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <a href="/profile?id=<?= $r['user_id'] ?>" class="font-bold text-base font-serif text-slate-900 hover:text-brand-600">
                                <?= htmlspecialchars($r['name']) ?>, <?= (int)$r['age'] ?>
                            </a>
                            <?php if (($r['kyc_status'] ?? '') === 'verified'): ?>
                            <i data-lucide="badge-check" class="w-4 h-4 fill-emerald-500 text-white"></i>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($r['city']) ?> • <?= htmlspecialchars($r['occupation'] ?? 'Member') ?></p>
                        <?php if (!empty($r['bio'])): ?>
                        <p class="text-xs text-slate-600 mt-1 line-clamp-1 italic">"<?= htmlspecialchars($r['bio']) ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="/profile?id=<?= $r['user_id'] ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:text-slate-900 text-xs font-semibold">
                        View Profile
                    </a>
                    <button onclick="acceptInterest(<?= $r['user_id'] ?>, this)" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 flex items-center gap-1.5">
                        <i data-lucide="heart" class="w-3.5 h-3.5 fill-current"></i>
                        <span>Match Back</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <!-- TAB 3: Interests Sent -->
    <?php elseif ($currentTab === 'sent'): ?>
        <?php if (empty($sent)): ?>
        <div class="p-12 rounded-3xl bg-white border border-rose-100 text-center shadow-sm">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-slate-400 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="send" class="w-7 h-7"></i>
            </div>
            <h3 class="font-serif text-xl font-bold text-slate-900">No Sent Interests</h3>
            <p class="text-sm text-slate-500 mt-2 max-w-sm mx-auto">
                Express interest in members on Discover to initiate conversations.
            </p>
            <a href="/discover" class="inline-block mt-5 px-6 py-2.5 rounded-full bg-brand-600 text-white font-bold text-xs">
                Browse Discover
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($sent as $s): ?>
            <div class="p-4 rounded-3xl bg-white border border-rose-100 shadow-sm flex items-center gap-4">
                <img src="<?= htmlspecialchars($s['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80') ?>" alt="" class="w-14 h-14 rounded-2xl object-cover flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <a href="/profile?id=<?= $s['user_id'] ?>" class="font-bold text-sm text-slate-900 hover:text-brand-600 truncate block">
                        <?= htmlspecialchars($s['name']) ?>, <?= (int)$s['age'] ?>
                    </a>
                    <span class="text-xs text-slate-400 block"><?= htmlspecialchars($s['city']) ?></span>
                    <span class="text-[10px] text-amber-600 font-semibold mt-1 inline-flex items-center gap-1">
                        <i data-lucide="clock" class="w-3 h-3"></i> Awaiting response
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    async function acceptInterest(targetId, btn) {
        btn.disabled = true;
        btn.innerHTML = 'Connecting...';

        const formData = new FormData();
        formData.append('target_id', targetId);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/interest/send', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showToast("🎉 It's a Match! You are now connected.", 'success');
                setTimeout(() => window.location.href = '/matches?tab=matches', 1000);
            } else {
                showToast(data.error || 'Failed to match.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Match Back';
            }
        } catch (e) {
            showToast('Network error.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Match Back';
        }
    }
</script>
