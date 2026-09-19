<?php
/**
 * Super Admin Safety & Moderation Reports
 * Pure Responsive Cards • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-6">
    <!-- Header & Filter Tabs -->
    <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">Safety & Moderation Reports</h2>
                <p class="text-xs text-slate-500 mt-0.5">Review user-submitted safety grievances, harassment reports, and bad actors</p>
            </div>
            <div class="text-xs font-bold text-slate-400">
                <span><?= count($reports) ?> Incident(s) Listed</span>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
            <?php
            $tabs = [
                'open' => 'Open Incidents',
                'resolved' => 'Resolved',
                'dismissed' => 'Dismissed',
                'all' => 'All Reports',
            ];
            ?>
            <?php foreach ($tabs as $key => $label): ?>
                <a href="/admin/reports?status=<?= $key ?>"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-colors <?= $statusFilter === $key ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Reports Cards List (No Tables) -->
    <div class="space-y-4">
        <?php if (empty($reports)): ?>
            <div class="bg-white p-12 rounded-3xl border border-rose-100/80 text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="shield-check" class="w-6 h-6"></i>
                </div>
                <h3 class="font-serif text-base font-bold text-slate-900">No reports found</h3>
                <p class="text-xs text-slate-400 mt-1">There are no reports matching status "<?= htmlspecialchars($statusFilter) ?>".</p>
            </div>
        <?php else: ?>
            <?php foreach ($reports as $r): ?>
                <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4 hover:border-rose-300 transition-colors">
                    <!-- Top Info: Incident Meta & Status Badge -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Incident #<?= $r['id'] ?> &bull; <?= htmlspecialchars(str_replace('_', ' ', $r['reason'])) ?></span>
                                <div class="text-[11px] text-slate-400">Logged on <?= date('M j, Y • g:i a', strtotime($r['created_at'])) ?></div>
                            </div>
                        </div>

                        <div>
                            <?php
                            $statusPill = [
                                'open' => 'bg-rose-100 text-rose-800',
                                'resolved' => 'bg-emerald-100 text-emerald-800',
                                'dismissed' => 'bg-slate-100 text-slate-600',
                            ];
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $statusPill[$r['status']] ?? 'bg-slate-100 text-slate-700' ?>">
                                <?= htmlspecialchars($r['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Split Details: Offender vs Reporter -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                        <!-- Offender Card -->
                        <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-100 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-rose-700">Reported Offender</div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($r['reported_name'] ?: 'No Name') ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars($r['reported_email']) ?></div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $r['reported_status'] === 'banned' ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-700' ?>">
                                    <?= $r['reported_status'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Reporter Card -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Complainant / Reporter</div>
                            <div>
                                <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($r['reporter_name'] ?: 'No Name') ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['reporter_email']) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Statement / Description -->
                    <?php if (!empty($r['description'])): ?>
                        <div class="p-3.5 rounded-2xl bg-slate-50 text-xs text-slate-700 border border-slate-100">
                            <span class="font-bold text-slate-900 block mb-1">Complainant's Statement:</span>
                            <p class="italic text-slate-600">"<?= nl2br(htmlspecialchars($r['description'])) ?>"</p>
                        </div>
                    <?php endif; ?>

                    <!-- Action Controls for Open Reports -->
                    <?php if ($r['status'] === 'open'): ?>
                        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2">
                            <!-- Ban Offender & Resolve -->
                            <form method="POST" action="/admin/reports/resolve" onsubmit="return confirm('Immediately ban the offender and mark this incident resolved?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="ban">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5">
                                    <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                    <span>Ban Offender & Resolve</span>
                                </button>
                            </form>

                            <!-- Resolve Without Ban -->
                            <form method="POST" action="/admin/reports/resolve">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="resolve">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Mark Resolved</span>
                                </button>
                            </form>

                            <!-- Dismiss -->
                            <form method="POST" action="/admin/report/dismiss">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors flex items-center gap-1.5">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    <span>Dismiss Report</span>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
