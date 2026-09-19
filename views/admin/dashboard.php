<?php
/**
 * Super Admin High-Level Dashboard
 * Pure Responsive Cards & List Rows • Zero HTML Tables
 */
?>
<div class="space-y-8">
    <!-- Welcome Header & Quick Status Strip -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-600 to-pink-500 text-white flex items-center justify-center shadow-md shadow-rose-500/20 shrink-0">
                <i data-lucide="crown" class="w-6 h-6"></i>
            </div>
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">Welcome, Super Administrator</h2>
                <p class="text-xs text-slate-500 mt-0.5">Milan Dating Platform Live Operations &bull; <?= date('l, F j, Y') ?></p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Gateway Status Pill -->
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $systemStatus['razorpay_enabled'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                <span class="w-2 h-2 rounded-full <?= $systemStatus['razorpay_enabled'] ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                <span>Razorpay: <?= ucfirst($systemStatus['razorpay_mode']) ?></span>
            </span>
            <!-- Super Admin Badge -->
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/80">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-rose-600"></i>
                <span>Super Admin</span>
            </span>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Users Card -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex flex-col justify-between hover:border-rose-200 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Members</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="users" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-bold text-slate-900 font-serif"><?= number_format($metrics['total_users']) ?></div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-emerald-600 font-semibold"><?= number_format($metrics['active_today']) ?> active</span>
                    <span>in last 24 hours</span>
                </div>
            </div>
        </div>

        <!-- Total Revenue Card -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex flex-col justify-between hover:border-rose-200 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Platform Revenue</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="indian-rupee" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-bold text-slate-900 font-serif">₹<?= number_format($revenue['all_time']) ?></div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-emerald-600 font-semibold">₹<?= number_format($revenue['this_month']) ?></span>
                    <span>this month</span>
                </div>
            </div>
        </div>

        <!-- Paid Subscribers Card -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex flex-col justify-between hover:border-rose-200 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Paid Plans</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="crown" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-bold text-slate-900 font-serif"><?= number_format($metrics['paid_subscribers']) ?></div>
                <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                    <span>Monthly & 3-Month Premium</span>
                </div>
            </div>
        </div>

        <!-- Pending KYC Card -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex flex-col justify-between hover:border-rose-200 transition-colors">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">KYC Verifications</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="badge-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mt-4">
                <div class="text-2xl font-bold text-slate-900 font-serif"><?= number_format($metrics['verified_kyc']) ?></div>
                <div class="text-[11px] mt-1 flex items-center gap-1">
                    <?php if ($metrics['pending_kyc'] > 0): ?>
                        <a href="/admin/kyc" class="text-rose-600 font-bold hover:underline"><?= $metrics['pending_kyc'] ?> pending review &rarr;</a>
                    <?php else: ?>
                        <span class="text-slate-400">All submissions reviewed</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Metric Badges & Quick Action Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Active Boosts Monitor -->
        <div class="bg-gradient-to-br from-amber-500/10 to-orange-500/5 p-5 rounded-2xl border border-amber-200/80 flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-amber-800">Profile Boosts</div>
                <div class="text-xl font-bold text-slate-900 font-serif mt-1"><?= $metrics['active_boosts'] ?> Active Boosts</div>
                <a href="/admin/boost" class="text-xs text-amber-700 font-semibold hover:underline mt-1 inline-block">Manage boost engine &rarr;</a>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-xs">
                <i data-lucide="zap" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Open Safety Reports -->
        <div class="bg-gradient-to-br from-rose-500/10 to-pink-500/5 p-5 rounded-2xl border border-rose-200/80 flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-rose-800">Safety Reports</div>
                <div class="text-xl font-bold text-slate-900 font-serif mt-1"><?= $metrics['open_reports'] ?> Open Incidents</div>
                <a href="/admin/reports" class="text-xs text-rose-700 font-semibold hover:underline mt-1 inline-block">Review report queue &rarr;</a>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center shadow-xs">
                <i data-lucide="flag" class="w-5 h-5"></i>
            </div>
        </div>

        <!-- Razorpay Gateway Config Quick Link -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 p-5 rounded-2xl text-white flex items-center justify-between shadow-xs">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Payment Gateway</div>
                <div class="text-xl font-bold font-serif mt-1">Razorpay Live</div>
                <a href="/admin/razorpay" class="text-xs text-brand-400 font-semibold hover:underline mt-1 inline-block">Gateway settings &rarr;</a>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-800 text-brand-400 flex items-center justify-center border border-slate-700">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- Recent Activity Split Grid (Cards & List Rows, NO HTML Tables) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Users (Card List) -->
        <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-base font-bold text-slate-900">Recent Registrations</h3>
                    <p class="text-xs text-slate-400">Latest members registered on Milan</p>
                </div>
                <a href="/admin/users" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline">View All &rarr;</a>
            </div>

            <div class="divide-y divide-rose-50">
                <?php if (empty($recentUsers)): ?>
                    <div class="py-8 text-center text-xs text-slate-400">No recent users registered yet.</div>
                <?php else: ?>
                    <?php foreach ($recentUsers as $u): ?>
                        <div class="py-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                                    <?php if (!empty($u['primary_photo'])): ?>
                                        <img src="<?= htmlspecialchars($u['primary_photo']) ?>" alt="" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-slate-400 font-bold text-xs">
                                            <?= strtoupper(substr($u['name'] ?? $u['email'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-900 truncate">
                                        <?= htmlspecialchars($u['name'] ?: 'Incomplete Profile') ?>
                                    </div>
                                    <div class="text-xs text-slate-500 truncate"><?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $u['plan_name'] === 'Free Plan' ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-700 font-bold' ?>">
                                    <?= htmlspecialchars($u['plan_name']) ?>
                                </span>
                                <div class="text-[10px] text-slate-400 mt-1"><?= date('M j, g:i a', strtotime($u['created_at'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Transactions (Card List) -->
        <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-base font-bold text-slate-900">Recent Transactions</h3>
                    <p class="text-xs text-slate-400">Captured via Razorpay integration</p>
                </div>
                <a href="/admin/payments" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline">View All &rarr;</a>
            </div>

            <div class="divide-y divide-rose-50">
                <?php if (empty($recentPayments)): ?>
                    <div class="py-8 text-center text-xs text-slate-400">No payment transactions recorded yet.</div>
                <?php else: ?>
                    <?php foreach ($recentPayments as $pay): ?>
                        <div class="py-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl <?= $pay['status'] === 'captured' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' ?> flex items-center justify-center shrink-0">
                                    <i data-lucide="<?= $pay['payment_type'] === 'boost' ? 'zap' : 'crown' ?>" class="w-5 h-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-900 truncate">
                                        <?= htmlspecialchars($pay['user_name'] ?: $pay['user_email']) ?>
                                    </div>
                                    <div class="text-xs text-slate-400 truncate">
                                        Order: <?= htmlspecialchars($pay['razorpay_order_id']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-slate-900 font-serif">₹<?= number_format($pay['amount_paisa'] / 100) ?></div>
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $pay['status'] === 'captured' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= htmlspecialchars($pay['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
