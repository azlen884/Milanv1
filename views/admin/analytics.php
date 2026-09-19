<?php
/**
 * Platform Analytics & Growth Insights
 * Zero HTML Tables
 */
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Platform Analytics</h2>
            <p class="text-xs text-slate-500 mt-0.5">Demographics, subscriber conversion, and engagement velocity</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">Real-Time Data</span>
        </div>
    </div>

    <!-- Engagement Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                <i data-lucide="heart" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Mutual Matches</div>
                <div class="text-2xl font-bold text-slate-900 font-serif mt-0.5"><?= number_format($totalMatches) ?></div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i data-lucide="message-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Exchanged Messages</div>
                <div class="text-2xl font-bold text-slate-900 font-serif mt-0.5"><?= number_format($totalMessages) ?></div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-rose-100/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="zap" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Boosts Purchased</div>
                <div class="text-2xl font-bold text-slate-900 font-serif mt-0.5"><?= number_format($totalBoostsPurchased) ?></div>
            </div>
        </div>
    </div>

    <!-- Analytics Breakdown Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Gender Demographics -->
        <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">Gender Demographics</h3>
                <p class="text-xs text-slate-400">Distribution across active member profiles</p>
            </div>

            <?php
            $totalGenderProfiles = array_sum(array_column($genderStats, 'count')) ?: 1;
            ?>

            <div class="space-y-4">
                <?php foreach ($genderStats as $g): ?>
                    <?php 
                    $pct = round(($g['count'] / $totalGenderProfiles) * 100, 1);
                    $color = $g['gender'] === 'female' ? 'bg-pink-500' : ($g['gender'] === 'male' ? 'bg-blue-500' : 'bg-purple-500');
                    ?>
                    <div>
                        <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                            <span class="capitalize text-slate-700"><?= htmlspecialchars($g['gender']) ?></span>
                            <span class="text-slate-500"><?= number_format($g['count']) ?> members (<?= $pct ?>%)</span>
                        </div>
                        <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full <?= $color ?> rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Subscription Tier Distribution -->
        <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">Subscription Tier Distribution</h3>
                <p class="text-xs text-slate-400">Subscribers by package tier (Free, Monthly, 3 Months)</p>
            </div>

            <div class="space-y-4">
                <?php foreach ($planDistribution as $plan): ?>
                    <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white text-rose-600 border border-rose-200 flex items-center justify-center font-bold">
                                <i data-lucide="crown" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($plan['name']) ?></div>
                                <div class="text-xs text-slate-500">Tier Code: <?= htmlspecialchars($plan['code']) ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-bold font-serif text-rose-600"><?= number_format($plan['subscriber_count']) ?></div>
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Active Members</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top Active Cities (Full Width) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">Top Member Hubs (Cities)</h3>
                <p class="text-xs text-slate-400">Geographic density of users across India</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                <?php if (empty($topCities)): ?>
                    <div class="col-span-4 py-8 text-center text-xs text-slate-400">No geographic city data yet.</div>
                <?php else: ?>
                    <?php foreach ($topCities as $city): ?>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col justify-between">
                            <div class="flex items-center gap-2 text-rose-600 text-xs font-bold">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                <span class="truncate"><?= htmlspecialchars($city['city']) ?></span>
                            </div>
                            <div class="mt-3">
                                <div class="text-xl font-bold text-slate-900 font-serif"><?= number_format($city['count']) ?></div>
                                <div class="text-[10px] text-slate-400 font-semibold">Registered Profiles</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
