<?php
/**
 * Super Admin Global Settings
 * Pure Responsive Cards & Toggles • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
$siteName = $settings['site_name'] ?? 'Milan Dating';
$siteTagline = $settings['site_tagline'] ?? 'Meaningful Connections for Modern Indians';
$contactEmail = $settings['contact_email'] ?? 'support@milandating.in';
$maintenance = ($settings['maintenance_mode'] ?? '0') === '1';
$kycGlobal = ($settings['kyc_globally_enabled'] ?? '1') === '1';
$freeMsgLimit = $settings['free_daily_messages_limit'] ?? '4';
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Platform Settings</h2>
            <p class="text-xs text-slate-500 mt-0.5">Control global platform constants, maintenance state, and default user policies</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= $maintenance ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                <span class="w-2 h-2 rounded-full <?= $maintenance ? 'bg-rose-500' : 'bg-emerald-500' ?>"></span>
                <span><?= $maintenance ? 'Maintenance Mode ACTIVE' : 'Platform Online & Live' ?></span>
            </span>
        </div>
    </div>

    <!-- Settings Form Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form (2 Cols) -->
        <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="font-serif text-base font-bold text-slate-900">General Platform Configuration</h3>
                <p class="text-xs text-slate-400 mt-0.5">Core site identity and operational flags</p>
            </div>

            <form method="POST" action="/admin/settings/update" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Platform Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($siteName) ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-medium focus:outline-none focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Support Contact Email</label>
                        <input type="email" name="contact_email" value="<?= htmlspecialchars($contactEmail) ?>" required
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-medium focus:outline-none focus:border-rose-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Brand Tagline</label>
                    <input type="text" name="site_tagline" value="<?= htmlspecialchars($siteTagline) ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-medium focus:outline-none focus:border-rose-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Free Daily Messaging Quota</label>
                        <input type="number" name="free_daily_messages_limit" value="<?= (int)$freeMsgLimit ?>" required min="1" max="100"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-bold focus:outline-none focus:border-rose-500">
                        <p class="text-[11px] text-slate-400 mt-1">Number of free messages per day before prompting upgrade</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Platform Maintenance Mode</label>
                        <select name="maintenance_mode" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-medium focus:outline-none focus:border-rose-500">
                            <option value="0" <?= !$maintenance ? 'selected' : '' ?>>0 - Live / Normal Operation</option>
                            <option value="1" <?= $maintenance ? 'selected' : '' ?>>1 - Maintenance Mode (Admin only)</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Locks out non-admin members when enabled</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Platform Settings</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Super Admin Security Audit Card (1 Col) -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold shrink-0">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-serif text-sm font-bold text-slate-900">Security Architecture</h3>
                        <p class="text-xs text-slate-400">Strictly single Super Admin</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    This platform operates under a consolidated Super Admin governance model. Multi-tier staff, moderator, or editor roles have been permanently retired from both the database schema and application controllers.
                </p>

                <div class="pt-2 border-t border-slate-100">
                    <a href="/admin/audit-logs" class="text-xs font-bold text-rose-600 hover:underline flex items-center gap-1">
                        <span>View Security Audit Logs</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
