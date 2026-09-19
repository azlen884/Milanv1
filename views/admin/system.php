<?php
/**
 * Super Admin System Health & Diagnostic Center
 * Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">System & Runtime Health</h2>
            <p class="text-xs text-slate-500 mt-0.5">Database connectivity, PDO driver verification, runtime metrics & maintenance controls</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>PHP <?= htmlspecialchars($systemInfo['php_version']) ?> Healthy</span>
            </span>
        </div>
    </div>

    <!-- 4 Cards Environment Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- PDO Status -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="database" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Native PDO Driver</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5">
                    <?= $systemInfo['pdo_loaded'] && $systemInfo['pdo_mysql_loaded'] ? 'Active (pdo_mysql)' : 'Offline' ?>
                </div>
            </div>
        </div>

        <!-- Database Tables -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="layers" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Database Schema</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5"><?= $systemInfo['tables_count'] ?> Active Tables</div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <i data-lucide="cpu" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Memory Utilization</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($systemInfo['memory_usage']) ?></div>
            </div>
        </div>

        <!-- Maintenance Mode State -->
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl <?= $systemInfo['maintenance_mode'] ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' ?> flex items-center justify-center shrink-0">
                <i data-lucide="<?= $systemInfo['maintenance_mode'] ? 'alert-triangle' : 'check-circle' ?>" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Maintenance State</div>
                <div class="text-sm font-bold <?= $systemInfo['maintenance_mode'] ? 'text-rose-600' : 'text-emerald-600' ?> mt-0.5">
                    <?= $systemInfo['maintenance_mode'] ? 'Locked (Maintenance)' : 'Live for Users' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Operations & Maintenance Controls -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Maintenance Mode Switch -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-4 flex flex-col justify-between">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">Emergency Maintenance Mode</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    When active, public visitors and standard members will see an elegant maintenance screen. Only authenticated Super Admins retain full access to manage the platform.
                </p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-700">Current Status:</span>
                    <span class="ml-1 text-xs font-bold <?= $systemInfo['maintenance_mode'] ? 'text-rose-600' : 'text-emerald-600' ?>">
                        <?= $systemInfo['maintenance_mode'] ? 'ACTIVE' : 'OFFLINE (LIVE)' ?>
                    </span>
                </div>

                <form method="POST" action="/admin/system/maintenance" onsubmit="return confirm('Change platform maintenance mode status?');">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold transition-colors shadow-xs <?= $systemInfo['maintenance_mode'] ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-rose-600 hover:bg-rose-700 text-white' ?>">
                        <?= $systemInfo['maintenance_mode'] ? 'Deactivate Maintenance (Go Live)' : 'Activate Maintenance Mode' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Cache & Boosts Pruner -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-4 flex flex-col justify-between">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">System Cleanup & Pruning</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Automatically scan and expire overdue profile boosts, flush expired IP rate-limiting records, and clear transient cache files.
                </p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400">Prunes stale rows in boosts & rate_limits</span>
                <form method="POST" action="/admin/system/cleanup">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-xs transition-colors flex items-center gap-1.5">
                        <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                        <span>Execute System Cleanup</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Database Tables Status (Cards, No HTML Tables) -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div>
            <h3 class="font-serif text-base font-bold text-slate-900">Database Schema Tables</h3>
            <p class="text-xs text-slate-400 mt-0.5">MariaDB 10.11 / InnoDB Engine Status</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 pt-2">
            <?php foreach ($tables as $t): ?>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-mono font-bold text-slate-900"><?= htmlspecialchars($t['Name']) ?></div>
                        <div class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5"><?= htmlspecialchars($t['Engine'] ?? 'InnoDB') ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-slate-700"><?= number_format($t['Rows'] ?? 0) ?> rows</div>
                        <div class="text-[10px] text-slate-400 font-mono"><?= round(($t['Data_length'] ?? 0) / 1024, 1) ?> KB</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
