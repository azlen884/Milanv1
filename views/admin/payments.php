<?php
/**
 * Super Admin Payment Transactions
 * Pure Responsive Cards & List Rows • Zero HTML Tables
 */
?>
<div class="space-y-6">
    <!-- Header & Search Controls -->
    <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">Payment Transactions</h2>
                <p class="text-xs text-slate-500 mt-0.5">Processed via Razorpay gateway • All transactions settled in INR (₹)</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="px-4 py-2 rounded-2xl bg-emerald-50 border border-emerald-200/80 text-emerald-800">
                    <span class="text-[10px] uppercase font-bold tracking-wider block">Total Captured</span>
                    <span class="text-base font-bold font-serif">₹<?= number_format($totalCapturedAmount) ?></span>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="/admin/payments" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3 pt-2 border-t border-slate-100">
            <!-- Search -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search Order ID, Payment ID, Email..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-500 focus:bg-white transition-all">
            </div>

            <!-- Status Filter -->
            <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition-all">
                <option value="">All Payment Statuses</option>
                <option value="captured" <?= $statusFilter === 'captured' ? 'selected' : '' ?>>Captured (Successful)</option>
                <option value="created" <?= $statusFilter === 'created' ? 'selected' : '' ?>>Created (Pending)</option>
                <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>

            <!-- Type Filter -->
            <select name="type" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500 focus:bg-white transition-all">
                <option value="">All Product Types</option>
                <option value="subscription" <?= $typeFilter === 'subscription' ? 'selected' : '' ?>>Subscription Plans</option>
                <option value="boost" <?= $typeFilter === 'boost' ? 'selected' : '' ?>>Profile Boosts</option>
            </select>

            <!-- Filter Button Group -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-1.5">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <?php if ($search !== '' || $statusFilter !== '' || $typeFilter !== ''): ?>
                    <a href="/admin/payments" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Payments List (Card Rows, No Tables) -->
    <div class="space-y-3">
        <?php if (empty($transactions)): ?>
            <div class="bg-white p-12 rounded-3xl border border-rose-100/80 text-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="receipt" class="w-6 h-6"></i>
                </div>
                <h3 class="font-serif text-base font-bold text-slate-900">No payment transactions found</h3>
                <p class="text-xs text-slate-400 mt-1">Transactions captured via Razorpay will be logged here in real time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($transactions as $pay): ?>
                <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs hover:border-rose-300 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Left: User & Product info -->
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-12 h-12 rounded-2xl <?= $pay['payment_type'] === 'boost' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-rose-50 text-rose-600 border border-rose-200' ?> flex items-center justify-center shrink-0">
                            <i data-lucide="<?= $pay['payment_type'] === 'boost' ? 'zap' : 'crown' ?>" class="w-6 h-6"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm truncate"><?= htmlspecialchars($pay['user_name'] ?: $pay['user_email']) ?></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $pay['payment_type'] === 'boost' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800' ?>">
                                    <?= htmlspecialchars($pay['payment_type']) ?>
                                </span>
                            </div>
                            <div class="text-xs text-slate-500 truncate mt-0.5"><?= htmlspecialchars($pay['user_email']) ?></div>
                            <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap items-center gap-2 font-mono">
                                <span>Order: <?= htmlspecialchars($pay['razorpay_order_id']) ?></span>
                                <?php if (!empty($pay['razorpay_payment_id'])): ?>
                                    <span>• Pay ID: <?= htmlspecialchars($pay['razorpay_payment_id']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Amount & Status Badge -->
                    <div class="flex items-center justify-between md:justify-end gap-6 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                        <div class="text-right">
                            <div class="text-lg font-bold font-serif text-slate-900">₹<?= number_format($pay['amount_paisa'] / 100, 2) ?></div>
                            <div class="text-[10px] text-slate-400 font-semibold uppercase"><?= htmlspecialchars($pay['currency']) ?></div>
                        </div>

                        <div class="text-right">
                            <?php
                            $statusBadge = [
                                'captured' => 'bg-emerald-100 text-emerald-800',
                                'created' => 'bg-amber-100 text-amber-800',
                                'failed' => 'bg-rose-100 text-rose-800',
                            ];
                            ?>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $statusBadge[$pay['status']] ?? 'bg-slate-100 text-slate-700' ?>">
                                <?= htmlspecialchars($pay['status']) ?>
                            </span>
                            <div class="text-[10px] text-slate-400 mt-1"><?= date('M j, Y • g:i a', strtotime($pay['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
