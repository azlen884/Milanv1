<?php
/**
 * Super Admin User Directory
 * Pure Responsive Cards & List Rows • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-6">
    <!-- Header & Filters Bar -->
    <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">Member Directory</h2>
                <p class="text-xs text-slate-500 mt-0.5">Manage member accounts, safety statuses, and compliance</p>
            </div>
            <div class="text-xs font-bold text-slate-400">
                <span>Total Displayed: <?= count($users) ?></span>
            </div>
        </div>

        <!-- Search & Filter Controls Form -->
        <form method="GET" action="/admin/users" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
            <!-- Search Query -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, city..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-500 focus:bg-white transition-all">
            </div>

            <!-- Status Filter -->
            <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition-all">
                <option value="">All Account Statuses</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Members</option>
                <option value="banned" <?= $statusFilter === 'banned' ? 'selected' : '' ?>>Banned Accounts</option>
                <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>

            <!-- KYC Filter -->
            <select name="kyc" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition-all">
                <option value="">All KYC Statuses</option>
                <option value="verified" <?= $kycFilter === 'verified' ? 'selected' : '' ?>>Verified KYC</option>
                <option value="pending" <?= $kycFilter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                <option value="rejected" <?= $kycFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="not_submitted" <?= $kycFilter === 'not_submitted' ? 'selected' : '' ?>>Not Submitted</option>
            </select>

            <!-- Actions Button Group -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-1.5">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Apply Filters</span>
                </button>
                <?php if ($search !== '' || $statusFilter !== '' || $kycFilter !== '' || $planFilter !== ''): ?>
                    <a href="/admin/users" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors" title="Reset Filters">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- User Cards List (No HTML Tables) -->
    <div class="space-y-3">
        <?php if (empty($users)): ?>
            <div class="bg-white p-12 rounded-3xl border border-rose-100/80 text-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="user-x" class="w-6 h-6"></i>
                </div>
                <h3 class="font-serif text-base font-bold text-slate-900">No members found</h3>
                <p class="text-xs text-slate-400 mt-1">Try broadening your search query or removing active filters.</p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
                <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs hover:border-rose-300 transition-colors flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Member Info Block -->
                    <div class="flex items-center gap-4 min-w-0">
                        <!-- Profile Photo -->
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200/80 relative">
                            <?php if (!empty($u['primary_photo'])): ?>
                                <img src="<?= htmlspecialchars($u['primary_photo']) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-400 font-bold text-base">
                                    <?= strtoupper(substr($u['name'] ?? $u['email'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($u['kyc_status'] === 'verified'): ?>
                                <div class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center text-[9px] border border-white" title="Verified Member">
                                    <i data-lucide="check" class="w-2.5 h-2.5"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Details -->
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm truncate"><?= htmlspecialchars($u['name'] ?: 'Incomplete Profile') ?></span>
                                <?php if ($u['age']): ?>
                                    <span class="text-xs text-slate-500">• <?= (int)$u['age'] ?> yrs</span>
                                <?php endif; ?>
                                <?php if ($u['gender']): ?>
                                    <span class="text-[11px] font-semibold text-slate-400 capitalize">(<?= htmlspecialchars($u['gender']) ?>)</span>
                                <?php endif; ?>
                            </div>

                            <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2 mt-0.5">
                                <span><?= htmlspecialchars($u['email']) ?></span>
                                <?php if (!empty($u['city'])): ?>
                                    <span>• <i data-lucide="map-pin" class="w-3 h-3 inline text-slate-400"></i> <?= htmlspecialchars($u['city']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="text-[11px] text-slate-400 mt-1">
                                <span>Joined: <?= date('M j, Y', strtotime($u['created_at'])) ?></span>
                                <?php if ($u['last_active_at']): ?>
                                    <span class="ml-2">• Last active: <?= date('M j, g:i a', strtotime($u['last_active_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Badges & Action Controls -->
                    <div class="flex flex-wrap items-center gap-3 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100">
                        <!-- Plan Badge -->
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $u['plan_name'] === 'Free Plan' ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-700 font-bold border border-rose-200' ?>">
                            <?= htmlspecialchars($u['plan_name']) ?>
                        </span>

                        <!-- KYC Badge -->
                        <?php
                        $kycColors = [
                            'verified' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                            'pending' => 'bg-amber-50 text-amber-700 border border-amber-200',
                            'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200',
                            'resubmission' => 'bg-purple-50 text-purple-700 border border-purple-200',
                            'not_submitted' => 'bg-slate-100 text-slate-500',
                        ];
                        ?>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $kycColors[$u['kyc_status']] ?? 'bg-slate-100 text-slate-600' ?>">
                            KYC: <?= ucfirst(str_replace('_', ' ', $u['kyc_status'])) ?>
                        </span>

                        <!-- Account Status Badge -->
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold <?= $u['status'] === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                            <?= strtoupper($u['status']) ?>
                        </span>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-1.5 ml-auto lg:ml-0">
                            <!-- Toggle Ban -->
                            <form method="POST" action="/admin/user/toggle-ban" onsubmit="return confirm('Change ban status for this user?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-colors <?= $u['status'] === 'banned' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-amber-100 hover:bg-amber-200 text-amber-800' ?>">
                                    <?= $u['status'] === 'banned' ? 'Unban' : 'Ban' ?>
                                </button>
                            </form>

                            <!-- Manual KYC Verification Dropdown/Button -->
                            <form method="POST" action="/admin/user/verify-kyc" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="status" value="<?= $u['kyc_status'] === 'verified' ? 'not_submitted' : 'verified' ?>">
                                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition-colors" title="Toggle Manual KYC verification">
                                    <?= $u['kyc_status'] === 'verified' ? 'Revoke KYC' : 'Verify KYC' ?>
                                </button>
                            </form>

                            <!-- Delete User -->
                            <form method="POST" action="/admin/user/delete" onsubmit="return confirm('PERMANENTLY delete user <?= htmlspecialchars($u['email']) ?> and all data? This cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Permanently Delete User">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
