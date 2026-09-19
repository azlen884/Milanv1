<?php
/**
 * Super Admin Subscription Management
 * Strictly Three Tiers: Free, Monthly, 3 Months • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Subscription Plans</h2>
            <p class="text-xs text-slate-500 mt-0.5">Strictly 3 Tiers: Free Tier, 1 Month Premium, and 3 Months Premium</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <?= count($plans) ?> Standard Tiers Active
            </span>
        </div>
    </div>

    <!-- The 3 Subscription Tiers Cards (Grid Layout) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($plans as $p): ?>
            <?php
            $isFree = ($p['code'] === 'free' || (int)$p['price_inr'] === 0);
            $subscriberCount = $subCounts[$p['id']] ?? 0;
            ?>
            <div class="bg-white rounded-3xl border border-rose-100/80 shadow-xs p-6 flex flex-col justify-between hover:border-rose-300 transition-colors relative overflow-hidden">
                <?php if ($p['code'] === 'monthly'): ?>
                    <div class="absolute top-0 right-0 bg-rose-600 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-bl-xl shadow-xs">
                        Most Popular
                    </div>
                <?php elseif ($p['code'] === 'three_months'): ?>
                    <div class="absolute top-0 right-0 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 rounded-bl-xl shadow-xs">
                        Best Value
                    </div>
                <?php endif; ?>

                <div>
                    <!-- Card Top -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-2xl <?= $isFree ? 'bg-slate-100 text-slate-600' : 'bg-rose-50 text-rose-600' ?> flex items-center justify-center font-bold">
                            <i data-lucide="<?= $isFree ? 'shield' : 'crown' ?>" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-serif text-lg font-bold text-slate-900"><?= htmlspecialchars($p['name']) ?></h3>
                            <div class="text-[11px] text-slate-400 font-medium">Code: <?= htmlspecialchars($p['code']) ?> &bull; <?= (int)$p['duration_days'] ?> Days</div>
                        </div>
                    </div>

                    <!-- Price Display -->
                    <div class="my-4 pb-4 border-b border-rose-50">
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold font-serif text-slate-900">₹<?= number_format($p['price_inr']) ?></span>
                            <span class="text-xs text-slate-400 font-semibold">/ <?= (int)$p['duration_days'] ?> days</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2"><?= htmlspecialchars($p['description']) ?></p>
                    </div>

                    <!-- Feature List Badges -->
                    <div class="space-y-2.5 my-4 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Daily Messaging:</span>
                            <span class="font-bold text-slate-800">
                                <?= $p['daily_messages_limit'] == 0 ? 'Unlimited' : (int)$p['daily_messages_limit'] . ' messages/day' ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Profile Visitors View:</span>
                            <span class="font-bold <?= $p['can_view_visitors'] ? 'text-emerald-600' : 'text-slate-400' ?>">
                                <?= $p['can_view_visitors'] ? 'Enabled' : 'Locked' ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Profile Boost Included:</span>
                            <span class="font-bold <?= $p['includes_boost'] ? 'text-amber-600' : 'text-slate-400' ?>">
                                <?= $p['includes_boost'] ? 'Yes, Included' : 'No' ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Plan Status:</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $p['is_active'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                <?= $p['is_active'] ? 'Active' : 'Disabled' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Subscriber Stat & Edit Trigger -->
                <div class="pt-4 border-t border-rose-50 flex items-center justify-between mt-4">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Active Members</div>
                        <div class="text-lg font-bold font-serif text-slate-900"><?= number_format($subscriberCount) ?></div>
                    </div>
                    <button type="button" onclick="openPlanModal(<?= htmlspecialchars(json_encode($p)) ?>)"
                            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-colors flex items-center gap-1.5 shadow-xs">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        <span>Configure</span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Plan Edit Modal -->
<div id="planModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="planModalTitle" class="font-serif text-lg font-bold text-slate-900">Configure Subscription Tier</h3>
            <button type="button" onclick="closePlanModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/subscriptions/update" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="plan_id" id="modalPlanId" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Price (₹ INR)</label>
                <input type="number" name="price_inr" id="modalPrice" required min="0"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                <p class="text-[11px] text-slate-400 mt-1">Set to 0 for Free tier</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Daily Messages Limit</label>
                <input type="number" name="daily_messages_limit" id="modalDailyLimit" required min="0"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                <p class="text-[11px] text-slate-400 mt-1">Set to 0 for Unlimited messaging</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Plan Description Copy</label>
                <textarea name="description" id="modalDesc" rows="2"
                          class="w-full p-3 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" name="can_view_visitors" id="modalVisitors" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-xs font-bold text-slate-700">Can View Visitors</span>
                </label>

                <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" name="includes_boost" id="modalBoost" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-xs font-bold text-slate-700">Includes Free Boost</span>
                </label>

                <label class="col-span-2 flex items-center gap-2 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" name="is_active" id="modalActive" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-xs font-bold text-slate-700">Plan is Active & Available for Purchase</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closePlanModal()" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors shadow-xs">Save Plan Configuration</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPlanModal(plan) {
        document.getElementById('modalPlanId').value = plan.id;
        document.getElementById('planModalTitle').innerText = 'Configure ' + plan.name;
        document.getElementById('modalPrice').value = plan.price_inr;
        document.getElementById('modalDailyLimit').value = plan.daily_messages_limit;
        document.getElementById('modalDesc').value = plan.description;
        document.getElementById('modalVisitors').checked = parseInt(plan.can_view_visitors) === 1;
        document.getElementById('modalBoost').checked = parseInt(plan.includes_boost) === 1;
        document.getElementById('modalActive').checked = parseInt(plan.is_active) === 1;

        document.getElementById('planModal').classList.remove('hidden');
    }

    function closePlanModal() {
        document.getElementById('planModal').classList.add('hidden');
    }
</script>
