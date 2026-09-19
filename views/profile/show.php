<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    
    <!-- Profile Card Container -->
    <div class="bg-white rounded-3xl border border-rose-100/90 shadow-xl shadow-rose-100/40 overflow-hidden mb-8">
        
        <!-- Hero Photo Gallery Header -->
        <div class="relative bg-slate-900 aspect-[16/9] sm:aspect-[21/9] overflow-hidden">
            <img src="<?= htmlspecialchars($profile['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=1200&auto=format&fit=crop&q=80') ?>" alt="<?= htmlspecialchars($profile['name']) ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/30 to-transparent"></div>

            <!-- Verification & Action Badges -->
            <div class="absolute top-4 left-4 flex items-center gap-2">
                <?php if (($profile['kyc_status'] ?? '') === 'verified'): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-500 text-white shadow-md">
                    <i data-lucide="badge-check" class="w-4 h-4 fill-current"></i>
                    <span>Verified Identity</span>
                </span>
                <?php endif; ?>
            </div>

            <!-- Header Content Overlay -->
            <div class="absolute bottom-6 left-6 right-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4 text-white">
                <div>
                    <h1 class="font-serif text-3xl sm:text-4xl font-bold tracking-tight">
                        <?= htmlspecialchars($profile['name']) ?>, <?= (int)$profile['age'] ?>
                    </h1>
                    <p class="text-sm text-slate-200 mt-1 flex items-center gap-2">
                        <span class="flex items-center gap-1">
                            <i data-lucide="map-pin" class="w-4 h-4 text-rose-400"></i>
                            <?= htmlspecialchars($profile['city']) ?>
                        </span>
                        <?php if (!empty($profile['occupation'])): ?>
                        <span>•</span>
                        <span><?= htmlspecialchars($profile['occupation']) ?></span>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Primary Action Buttons -->
                <div class="flex items-center gap-3">
                    <?php if ($isOwnProfile): ?>
                    <a href="/settings?tab=profile" class="px-5 py-2.5 rounded-full bg-white text-slate-900 hover:bg-slate-100 font-bold text-xs shadow-md transition-colors flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Edit Profile</span>
                    </a>
                    <?php else: ?>
                        <?php if ($isMatched): ?>
                        <a href="/chat?with=<?= $profile['id'] ?>" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                            <span>Message</span>
                        </a>
                        <?php elseif ($hasSentInterest): ?>
                        <span class="px-5 py-2.5 rounded-full bg-rose-50 text-brand-700 font-bold text-xs flex items-center gap-1.5">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Interest Sent</span>
                        </span>
                        <?php else: ?>
                        <button onclick="sendInterest(<?= $profile['id'] ?>, this)" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
                            <i data-lucide="heart" class="w-4 h-4 fill-current"></i>
                            <span>Express Interest</span>
                        </button>
                        <?php endif; ?>

                        <!-- Dropdown Options (Report / Block) -->
                        <div class="relative">
                            <button onclick="document.getElementById('profile-options-menu').classList.toggle('hidden')" class="p-3 rounded-full bg-black/40 hover:bg-black/60 text-white backdrop-blur-md transition-colors">
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                            <div id="profile-options-menu" class="hidden absolute right-0 bottom-full mb-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-20 text-slate-700 text-xs">
                                <button onclick="openReportModal()" class="w-full px-4 py-2.5 text-left hover:bg-slate-50 flex items-center gap-2 font-medium">
                                    <i data-lucide="flag" class="w-4 h-4 text-amber-500"></i>
                                    <span>Report Profile</span>
                                </button>
                                <button onclick="blockUser(<?= $profile['id'] ?>)" class="w-full px-4 py-2.5 text-left hover:bg-rose-50 text-red-600 flex items-center gap-2 font-medium">
                                    <i data-lucide="slash" class="w-4 h-4 text-red-500"></i>
                                    <span>Block User</span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Details Body -->
        <div class="p-6 sm:p-8 space-y-8">
            <!-- About Me / Bio -->
            <?php if (!empty($profile['bio'])): ?>
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">About Me</h3>
                <p class="text-slate-700 text-base leading-relaxed whitespace-pre-line">
                    <?= htmlspecialchars($profile['bio']) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Vital Profile Info Grid -->
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Key Details</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-xs text-slate-400 block mb-1">Gender</span>
                        <span class="font-bold text-slate-800 capitalize"><?= htmlspecialchars($profile['gender']) ?></span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-xs text-slate-400 block mb-1">Height</span>
                        <span class="font-bold text-slate-800"><?= !empty($profile['height_cm']) ? $profile['height_cm'] . ' cm' : 'Not specified' ?></span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-xs text-slate-400 block mb-1">Education</span>
                        <span class="font-bold text-slate-800"><?= !empty($profile['education']) ? htmlspecialchars($profile['education']) : 'Not specified' ?></span>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-xs text-slate-400 block mb-1">Languages</span>
                        <span class="font-bold text-slate-800"><?= !empty($profile['languages']) ? htmlspecialchars($profile['languages']) : 'English, Hindi' ?></span>
                    </div>
                </div>
            </div>

            <!-- Interests & Passions -->
            <?php if (!empty($interests)): ?>
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Interests & Passions</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($interests as $in): ?>
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-brand-700 border border-rose-100">
                        <?= htmlspecialchars($in['name']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Photo Gallery (If more photos exist) -->
            <?php if (count($photos) > 1): ?>
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Photo Gallery</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php foreach ($photos as $ph): ?>
                    <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">
                        <img src="<?= htmlspecialchars($ph['photo_url']) ?>" alt="Photo" class="w-full h-full object-cover hover:scale-105 transition-transform">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div id="report-modal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100">
        <h3 class="font-serif text-xl font-bold text-slate-900 mb-2">Report Profile</h3>
        <p class="text-xs text-slate-500 mb-4">Help us keep Milan safe. Reports are reviewed privately by our safety team.</p>
        
        <form id="report-form" onsubmit="submitReport(event)" class="space-y-3">
            <input type="hidden" name="target_id" value="<?= $profile['id'] ?>">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Reason</label>
                <select name="reason" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold">
                    <option value="fake_profile">Fake Profile or Impersonation</option>
                    <option value="harassment">Harassment or Inappropriate Messages</option>
                    <option value="spam">Commercial Spam / Solicitation</option>
                    <option value="scam">Financial Scam or Fraud</option>
                    <option value="inappropriate_content">Explicit or Inappropriate Photos</option>
                    <option value="other">Other Violation</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Details (Optional)</label>
                <textarea name="description" rows="3" placeholder="Provide any details to help our review..." class="w-full p-3 rounded-xl border border-slate-200 text-xs outline-none focus:border-brand-500"></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-xs">Submit Report</button>
                <button type="button" onclick="document.getElementById('report-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-semibold text-xs">Cancel</button>
            </div>
        </form>
    </div>
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
                    showToast("🎉 It's a Match! You both expressed interest in each other.", 'success');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast(data.message || 'Interest expressed successfully!', 'success');
                    btn.className = 'px-5 py-2.5 rounded-full bg-rose-50 text-brand-700 font-bold text-xs flex items-center gap-1.5 cursor-default';
                    btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>Interest Sent</span>';
                    lucide.createIcons();
                }
            } else {
                showToast(data?.error || data?.message || 'Failed to send interest.', 'error');
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

    function openReportModal() {
        document.getElementById('profile-options-menu').classList.add('hidden');
        document.getElementById('report-modal').classList.remove('hidden');
    }

    async function submitReport(e) {
        e.preventDefault();
        const form = document.getElementById('report-form');
        const formData = new FormData(form);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/profile/report', { method: 'POST', body: formData });
            const data = await res.json();
            document.getElementById('report-modal').classList.add('hidden');
            if (data.success) {
                showToast('Report submitted. Thank you for keeping Milan safe.', 'success');
            } else {
                showToast(data.error || 'Failed to submit report.', 'error');
            }
        } catch (err) {
            showToast('Network error.', 'error');
        }
    }

    async function blockUser(targetId) {
        if (!confirm('Are you sure you want to block this user? You will no longer see each other.')) {
            return;
        }
        const formData = new FormData();
        formData.append('target_id', targetId);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/profile/block', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showToast('User has been blocked.', 'success');
                setTimeout(() => window.location.href = '/discover', 1000);
            } else {
                showToast(data.error || 'Failed to block user.', 'error');
            }
        } catch (err) {
            showToast('Network error.', 'error');
        }
    }
</script>
