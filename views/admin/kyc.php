<?php
/**
 * Super Admin KYC Compliance Management
 * Pure Responsive Cards & Modals • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-6">
    <!-- Header & Global Enforcement Bar -->
    <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">KYC Compliance Center</h2>
                <p class="text-xs text-slate-500 mt-0.5">Audit identity documents, government IDs, and selfie liveness</p>
            </div>
            
            <!-- Global Enforcement Toggle -->
            <form method="POST" action="/admin/kyc/toggle-global" class="flex items-center gap-3 bg-slate-50 p-2.5 rounded-2xl border border-slate-200">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="text-xs">
                    <span class="font-bold text-slate-800">Global KYC Gate:</span>
                    <span class="text-slate-500 ml-1"><?= $kycGloballyEnabled ? 'Mandatory for Chat' : 'Optional' ?></span>
                </div>
                <input type="hidden" name="kyc_globally_enabled" value="<?= $kycGloballyEnabled ? '0' : '1' ?>">
                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-xs <?= $kycGloballyEnabled ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-300 text-slate-700 hover:bg-slate-400' ?>">
                    <?= $kycGloballyEnabled ? 'Enabled' : 'Disabled' ?>
                </button>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
            <?php
            $tabs = [
                'pending' => 'Pending Review',
                'verified' => 'Verified',
                'resubmission' => 'Resubmission Requested',
                'rejected' => 'Rejected',
                'all' => 'All Submissions',
            ];
            ?>
            <?php foreach ($tabs as $key => $label): ?>
                <a href="/admin/kyc?status=<?= $key ?>"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-colors <?= $statusFilter === $key ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KYC Submissions Queue (Card Rows, No Tables) -->
    <div class="space-y-4">
        <?php if (empty($records)): ?>
            <div class="bg-white p-12 rounded-3xl border border-rose-100/80 text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="check-check" class="w-6 h-6"></i>
                </div>
                <h3 class="font-serif text-base font-bold text-slate-900">Queue is Clear</h3>
                <p class="text-xs text-slate-400 mt-1">No submissions currently matching status "<?= htmlspecialchars($statusFilter) ?>".</p>
            </div>
        <?php else: ?>
            <?php foreach ($records as $r): ?>
                <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4 hover:border-rose-300 transition-colors">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- User Meta -->
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                                <?php if (!empty($r['primary_photo'])): ?>
                                    <img src="<?= htmlspecialchars($r['primary_photo']) ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-400 font-bold text-base">
                                        <?= strtoupper(substr($r['user_name'] ?? $r['user_email'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($r['user_name'] ?: 'Incomplete Profile') ?></span>
                                    <?php if ($r['age']): ?>
                                        <span class="text-xs text-slate-500">• <?= (int)$r['age'] ?> yrs</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    <span><?= htmlspecialchars($r['user_email']) ?></span>
                                    <?php if (!empty($r['city'])): ?>
                                        <span class="ml-1">• <?= htmlspecialchars($r['city']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    <span>Submitted: <?= date('M j, Y • g:i a', strtotime($r['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Document Details & Previews -->
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold uppercase tracking-wider">
                                Doc: <?= htmlspecialchars(str_replace('_', ' ', $r['document_type'])) ?>
                            </div>

                            <!-- Document Stream Link -->
                            <a href="/admin/kyc/view?id=<?= $r['id'] ?>&type=doc" target="_blank"
                               class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold flex items-center gap-1.5 transition-colors">
                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                <span>Inspect ID Document</span>
                            </a>

                            <!-- Selfie Stream Link -->
                            <a href="/admin/kyc/view?id=<?= $r['id'] ?>&type=selfie" target="_blank"
                               class="px-3 py-1.5 rounded-xl bg-pink-50 hover:bg-pink-100 text-pink-700 border border-pink-200 text-xs font-bold flex items-center gap-1.5 transition-colors">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                                <span>Inspect Selfie</span>
                            </a>

                            <!-- Status Badge -->
                            <?php
                            $statusStyles = [
                                'verified' => 'bg-emerald-100 text-emerald-800',
                                'pending' => 'bg-amber-100 text-amber-800',
                                'rejected' => 'bg-rose-100 text-rose-800',
                                'resubmission' => 'bg-purple-100 text-purple-800',
                            ];
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $statusStyles[$r['status']] ?? 'bg-slate-100 text-slate-700' ?>">
                                <?= $r['status'] ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($r['rejection_reason'])): ?>
                        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700">
                            <span class="font-bold">Admin Feedback:</span> <?= htmlspecialchars($r['rejection_reason']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action Controls for Pending / Resubmission -->
                    <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-xs text-slate-400">
                            Submission Record #<?= $r['id'] ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Quick Approve Button -->
                            <form method="POST" action="/admin/kyc/review" onsubmit="return confirm('Approve this KYC submission and verify the user?');" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="record_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="decision" value="approve">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                    <span>Approve & Verify</span>
                                </button>
                            </form>

                            <!-- Request Resubmission Form/Button -->
                            <button type="button" onclick="openReviewModal(<?= $r['id'] ?>, 'resubmission')" class="px-4 py-2 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-bold transition-colors flex items-center gap-1.5">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                <span>Request Resubmission</span>
                            </button>

                            <!-- Reject Form/Button -->
                            <button type="button" onclick="openReviewModal(<?= $r['id'] ?>, 'reject')" class="px-4 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-colors flex items-center gap-1.5">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                <span>Reject</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Shared Review Modal for Reject / Resubmission -->
<div id="reviewModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 id="modalTitle" class="font-serif text-lg font-bold text-slate-900">Review KYC Submission</h3>
            <button type="button" onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/kyc/review" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="record_id" id="modalRecordId" value="">
            <input type="hidden" name="decision" id="modalDecision" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Reason / Guidance for User</label>
                <textarea name="reason" id="modalReason" required rows="3" placeholder="e.g. Document image is blurry, government ID number is cut off, or selfie does not match photo."
                          class="w-full p-3 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-500 transition-colors"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeReviewModal()" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit" id="modalSubmitBtn" class="px-5 py-2 rounded-xl text-white text-xs font-bold transition-colors">Submit Decision</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openReviewModal(recordId, decision) {
        document.getElementById('modalRecordId').value = recordId;
        document.getElementById('modalDecision').value = decision;
        const title = document.getElementById('modalTitle');
        const btn = document.getElementById('modalSubmitBtn');
        const reason = document.getElementById('modalReason');

        if (decision === 'resubmission') {
            title.innerText = 'Request KYC Resubmission';
            btn.innerText = 'Send Resubmission Request';
            btn.className = 'px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition-colors';
            reason.placeholder = 'Explain what needs to be re-uploaded (e.g. please upload clear front side of Aadhaar card).';
        } else {
            title.innerText = 'Reject KYC Submission';
            btn.innerText = 'Confirm Rejection';
            btn.className = 'px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors';
            reason.placeholder = 'Reason for rejection (e.g. Invalid government document or underage applicant).';
        }

        document.getElementById('reviewModal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').classList.add('hidden');
    }
</script>
