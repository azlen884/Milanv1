<?php
/**
 * Super Admin Security Audit Logs
 * Pure Responsive Cards • Zero HTML Tables
 */
?>
<div class="space-y-6">
    <!-- Header & Filter -->
    <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">Security Audit Trail</h2>
                <p class="text-xs text-slate-500 mt-0.5">Immutable record of Super Administrator actions, KYC audits, and gateway updates</p>
            </div>
            <div class="text-xs font-bold text-slate-400">
                <span><?= count($logs) ?> Recorded Action(s)</span>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="/admin/audit-logs" class="flex gap-2 pt-2 border-t border-slate-100">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" name="action" value="<?= htmlspecialchars($actionFilter) ?>" placeholder="Filter by action keyword (e.g. login, kyc, plan, razorpay)..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-500 focus:bg-white transition-all">
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors">
                Filter Logs
            </button>
            <?php if ($actionFilter !== ''): ?>
                <a href="/admin/audit-logs" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-colors">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Audit Logs List (Card Rows, No Tables) -->
    <div class="space-y-3">
        <?php if (empty($logs)): ?>
            <div class="bg-white p-12 rounded-3xl border border-rose-100/80 text-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="history" class="w-6 h-6"></i>
                </div>
                <h3 class="font-serif text-base font-bold text-slate-900">No audit records found</h3>
                <p class="text-xs text-slate-400 mt-1">Actions performed by the Super Admin will be logged here automatically.</p>
            </div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-rose-100/80 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-rose-300 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                            <i data-lucide="shield" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                                <?php if (!empty($log['entity_type'])): ?>
                                    <span class="text-xs font-semibold text-slate-500">
                                        <?= htmlspecialchars($log['entity_type']) ?> #<?= (int)$log['entity_id'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-slate-800 mt-1"><?= htmlspecialchars($log['details']) ?></div>
                        </div>
                    </div>

                    <div class="text-right shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 text-[11px] text-slate-400">
                        <div class="font-bold text-slate-600">Admin: <?= htmlspecialchars($log['admin_username'] ?? 'Super Admin') ?></div>
                        <div><?= date('M j, Y • g:i:s a', strtotime($log['created_at'])) ?></div>
                        <?php if (!empty($log['ip_address'])): ?>
                            <div class="font-mono text-[10px] text-slate-400">IP: <?= htmlspecialchars($log['ip_address']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
