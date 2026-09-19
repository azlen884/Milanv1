<?php
/**
 * Super Admin Profile Boost Management
 * Price, Duration, Active Boosts Monitor • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Profile Boost Engine</h2>
            <p class="text-xs text-slate-500 mt-0.5">Control spotlight positioning, boost pricing (₹ INR), duration, and monitor active boosts</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold <?= $enabled ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600' ?>">
                <span class="w-2 h-2 rounded-full <?= $enabled ? 'bg-amber-500' : 'bg-slate-400' ?>"></span>
                <span>Boost Engine <?= $enabled ? 'Active' : 'Disabled' ?></span>
            </span>
            <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                <?= count($activeBoosts) ?> Currently Boosted
            </span>
        </div>
    </div>

    <!-- Engine Config Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Boost Parameters Form (2 Cols) -->
        <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="font-serif text-base font-bold text-slate-900">Engine Parameters & Pricing</h3>
                <p class="text-xs text-slate-400 mt-0.5">Adjust duration, standalone purchase price, and user concurrency</p>
            </div>

            <form method="POST" action="/admin/boost/update" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Global Toggle -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Global Boost Switch</label>
                        <select name="boost_globally_enabled" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                            <option value="1" <?= $enabled ? 'selected' : '' ?>>Enabled (Allow Boosts)</option>
                            <option value="0" <?= !$enabled ? 'selected' : '' ?>>Disabled (Pause All)</option>
                        </select>
                    </div>

                    <!-- Price in INR -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Boost Price (₹ INR)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-serif font-bold text-xs pointer-events-none">₹</span>
                            <input type="number" name="boost_price_inr" value="<?= (int)$price ?>" required min="1"
                                   class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-bold focus:outline-none focus:border-rose-500">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Default: ₹19 per 24-hour spotlight</p>
                    </div>

                    <!-- Duration Hours -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Duration (Hours)</label>
                        <input type="number" name="boost_duration_hours" value="<?= (int)$duration ?>" required min="1" max="168"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-bold focus:outline-none focus:border-rose-500">
                        <p class="text-[11px] text-slate-400 mt-1">Default: 24 hours</p>
                    </div>

                    <!-- Max Active Boosts per User -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Max Active Per Member</label>
                        <input type="number" name="max_active_boosts_per_user" value="<?= (int)$maxActive ?>" required min="1" max="10"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-bold focus:outline-none focus:border-rose-500">
                        <p class="text-[11px] text-slate-400 mt-1">Prevents over-stacking</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Boost Configuration</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Subscription Tier Rules Card (1 Col) -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">Tier Entitlement Rules</h3>
                <p class="text-xs text-slate-400 mt-0.5">Enforced across the dating matching algorithm</p>
            </div>

            <div class="space-y-3 pt-2">
                <!-- Free Plan -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                        <span class="text-slate-800">Free Tier</span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px]">Blocked</span>
                    </div>
                    <p class="text-[11px] text-slate-500">Free members cannot purchase or activate profile boosts.</p>
                </div>

                <!-- Monthly Plan -->
                <div class="p-3.5 rounded-2xl bg-rose-50/50 border border-rose-100">
                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                        <span class="text-rose-900">Monthly Tier (₹299)</span>
                        <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px]">Can Purchase</span>
                    </div>
                    <p class="text-[11px] text-rose-700">Monthly subscribers can buy standalone boosts at ₹<?= (int)$price ?> each.</p>
                </div>

                <!-- 3-Month Plan -->
                <div class="p-3.5 rounded-2xl bg-amber-50/50 border border-amber-200/80">
                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                        <span class="text-amber-900">3-Month Tier (₹700)</span>
                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px]">Included Free</span>
                    </div>
                    <p class="text-[11px] text-amber-800">Includes recurring free spotlight boosts as part of premium membership.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Boosts Live Monitor (Cards, No Tables) -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">Currently Active Boosts</h3>
                <p class="text-xs text-slate-400 mt-0.5">Profiles currently elevated to top discovery spotlight</p>
            </div>
            <span class="text-xs font-bold text-slate-400"><?= count($activeBoosts) ?> Active</span>
        </div>

        <div class="space-y-3">
            <?php if (empty($activeBoosts)): ?>
                <div class="py-8 text-center text-xs text-slate-400">No active profile boosts right now.</div>
            <?php else: ?>
                <?php foreach ($activeBoosts as $b): ?>
                    <div class="p-4 rounded-2xl bg-amber-50/40 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-amber-100 overflow-hidden shrink-0 border border-amber-300">
                                <?php if (!empty($b['primary_photo'])): ?>
                                    <img src="<?= htmlspecialchars($b['primary_photo']) ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center font-bold text-amber-800 text-xs">
                                        <?= strtoupper(substr($b['name'] ?? $b['email'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <span><?= htmlspecialchars($b['name'] ?: $b['email']) ?></span>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                                        <i data-lucide="zap" class="w-3 h-3"></i>
                                        <span>Spotlight Active</span>
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    <span>User: <?= htmlspecialchars($b['email']) ?></span>
                                    <?php if (!empty($b['city'])): ?>
                                        <span class="ml-1">• <?= htmlspecialchars($b['city']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <div class="text-xs font-bold text-slate-700">Expires: <?= date('M j, Y • g:i a', strtotime($b['expires_at'])) ?></div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Started: <?= date('M j, g:i a', strtotime($b['started_at'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
