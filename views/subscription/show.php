<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    
    <div class="mb-8">
        <h1 class="font-serif text-3xl font-bold text-slate-900">My Subscription & Perks</h1>
        <p class="text-sm text-slate-500 mt-1">Manage your membership, message limits, and profile boost</p>
    </div>

    <!-- Active Plan Status Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-md mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b border-slate-100">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Current Membership</span>
                <div class="flex items-center gap-3 mt-1">
                    <h2 class="font-serif text-3xl font-bold text-slate-900 capitalize"><?= htmlspecialchars($subscription['name']) ?></h2>
                    <?php if ($subscription['code'] !== 'free'): ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Active Premium
                    </span>
                    <?php else: ?>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                        Standard Free
                    </span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <?php if ($subscription['code'] !== 'free'): ?>
                    Valid until <?= date('F j, Y', strtotime($subscription['expires_at'])) ?>
                    <?php else: ?>
                    Free membership with 4 daily sent messages.
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($subscription['code'] === 'free'): ?>
            <a href="/upgrade" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition-all text-center">
                Upgrade to Unlimited
            </a>
            <?php endif; ?>
        </div>

        <!-- Messaging Quota -->
        <div class="pt-6">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="font-bold text-slate-700">Today's Sent Messages Quota</span>
                <span class="font-semibold text-slate-500">
                    <?php if ($dailyPerm['is_paid']): ?>
                    <span class="text-emerald-600 font-bold">Unlimited Active</span>
                    <?php else: ?>
                    <?= (int)$dailyPerm['used_today'] ?> / <?= (int)$dailyPerm['limit'] ?> used
                    <?php endif; ?>
                </span>
            </div>
            
            <?php if (!$dailyPerm['is_paid']): ?>
            <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                <?php $pct = min(100, ((int)$dailyPerm['used_today'] / 4) * 100); ?>
                <div class="h-full bg-brand-600 rounded-full transition-all" style="width: <?= $pct ?>%"></div>
            </div>
            <p class="text-[11px] text-slate-400 mt-2">Free accounts reset automatically at midnight IST.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- PROFILE BOOST WIDGET (Rule 22) -->
    <div class="bg-gradient-to-br from-amber-50 to-rose-50 rounded-3xl p-6 sm:p-8 border border-amber-200/70 shadow-sm mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm">
                        <i data-lucide="zap" class="w-4 h-4 fill-current"></i>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-slate-900">Profile Boost</h3>
                </div>
                <p class="text-xs text-slate-600 mt-2 max-w-md leading-relaxed">
                    Feature your profile at the top of Discover for 24 hours. Boost improves legitimate discovery ranking only — zero fake likes or simulated activity.
                </p>

                <?php if ($activeBoost): ?>
                <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-200/70 text-amber-900 text-xs font-bold">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span>Boost is ACTIVE until <?= date('M j, g:i A', strtotime($activeBoost['expires_at'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Boost Button Logic -->
            <div class="flex-shrink-0">
                <?php if ($activeBoost): ?>
                <span class="px-5 py-2.5 rounded-full bg-amber-400 text-white font-bold text-xs shadow-sm">Currently Boosted</span>
                <?php elseif ($boostInfo['allowed'] && $boostInfo['is_included']): ?>
                <button onclick="activateIncludedBoost()" class="px-6 py-3 rounded-full bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-md shadow-amber-500/30 transition-all flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <span>Activate Included Boost</span>
                </button>
                <?php elseif ($boostInfo['allowed']): ?>
                <button onclick="purchaseBoost(<?= (int)$boostInfo['price_inr'] ?>)" class="px-6 py-3 rounded-full bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-md shadow-amber-600/30 transition-all flex items-center gap-2">
                    <i data-lucide="zap" class="w-4 h-4 fill-current"></i>
                    <span>Boost for ₹<?= (int)$boostInfo['price_inr'] ?></span>
                </button>
                <?php else: ?>
                <a href="/upgrade" class="px-6 py-3 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition-all flex items-center gap-1.5">
                    <span>Upgrade to Boost</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Billing & Payment Records (NO HTML TABLES! Responsive Cards, Rule 42) -->
    <div>
        <h3 class="font-serif text-xl font-bold text-slate-900 mb-4">Payment History</h3>

        <?php if (empty($payments)): ?>
        <div class="p-8 rounded-2xl bg-white border border-rose-100 text-center text-slate-400 text-xs">
            No payment records found.
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($payments as $pay): ?>
            <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900 uppercase">
                            <?= ($pay['payment_type'] === 'boost') ? 'Profile Boost (24h)' : 'Premium Subscription' ?>
                        </div>
                        <span class="text-[11px] text-slate-400">Order: <?= htmlspecialchars($pay['razorpay_order_id']) ?> • <?= date('M j, Y', strtotime($pay['created_at'])) ?></span>
                    </div>
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-4">
                    <span class="font-bold text-slate-900 text-sm">₹<?= (int)($pay['amount_paisa'] / 100) ?></span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= ($pay['status'] === 'captured') ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                        <?= htmlspecialchars($pay['status']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    async function activateIncludedBoost() {
        const formData = new FormData();
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/subscription/activate-boost', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                showToast('Boost activated! Enjoy 24 hours of top visibility.', 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(data.error || 'Failed to activate boost.', 'error');
            }
        } catch (e) {
            showToast('Network error.', 'error');
        }
    }

    async function purchaseBoost(priceInr) {
        const formData = new FormData();
        formData.append('type', 'boost');
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/subscription/create-order', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (!data.success) {
                showToast(data.error || 'Could not initiate boost payment.', 'error');
                return;
            }

            const options = {
                key: data.key_id,
                amount: data.order.amount,
                currency: 'INR',
                name: 'Milan Dating',
                description: 'Profile Boost (24h)',
                order_id: data.order.id,
                prefill: {
                    name: data.user.name,
                    email: data.user.email
                },
                theme: { color: '#F59E0B' },
                handler: async function (response) {
                    await verifyBoostPayment(response);
                },
                modal: {
                    ondismiss: function() {
                        showToast('Boost payment was cancelled.', 'error');
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (resp) {
                showToast(resp.error?.description || 'Payment failed. Please try again.', 'error');
            });
            rzp.open();

        } catch (err) {
            showToast('Network error.', 'error');
        }
    }

    async function verifyBoostPayment(rzpResponse) {
        const verifyForm = new FormData();
        verifyForm.append('razorpay_order_id', rzpResponse.razorpay_order_id);
        verifyForm.append('razorpay_payment_id', rzpResponse.razorpay_payment_id);
        verifyForm.append('razorpay_signature', rzpResponse.razorpay_signature);
        verifyForm.append('type', 'boost');
        verifyForm.append('csrf_token', getCsrfToken());

        const res = await fetch('/api/subscription/verify-payment', {
            method: 'POST',
            body: verifyForm
        });
        const data = await res.json();
        if (data.success) {
            showToast('Boost activated successfully!', 'success');
            setTimeout(() => window.location.reload(), 1200);
        } else {
            showToast(data.error || 'Payment verification failed.', 'error');
        }
    }
</script>
