<?php
/**
 * Paid Upgrade Page
 * STRICT REQUIREMENT (Rule 11):
 * Must display ONLY:
 * 1. Monthly ₹299
 * 2. 3 Months ₹700
 * Do NOT show Free on this page.
 */
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold uppercase tracking-wider mb-4">
            <i data-lucide="crown" class="w-4 h-4 text-amber-600"></i>
            <span>Milan Premium Experience</span>
        </div>
        <h1 class="font-serif text-3xl sm:text-5xl font-bold text-slate-900 tracking-tight">Upgrade Your Journey</h1>
        <p class="text-slate-600 mt-3 text-base">Enjoy unlimited messaging, profile visitor insights, and top priority visibility.</p>
    </div>

    <!-- ONLY 2 PAID PLANS (Rule 11) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto mb-12">
        <?php foreach ($plans as $plan): ?>
            <?php 
            $isThreeMonth = ($plan['code'] === 'three_months');
            $price = (int)$plan['price_inr'];
            ?>
            <div class="relative bg-white rounded-3xl p-8 border <?= $isThreeMonth ? 'border-brand-500 ring-2 ring-brand-500/20 shadow-2xl' : 'border-rose-100 shadow-lg' ?> flex flex-col justify-between">
                
                <?php if ($isThreeMonth): ?>
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-gradient-to-r from-amber-500 to-brand-600 text-white text-[11px] font-bold uppercase tracking-wider shadow-sm">
                    Most Popular • Save ₹197
                </div>
                <?php endif; ?>

                <div>
                    <h3 class="font-serif text-2xl font-bold text-slate-900"><?= htmlspecialchars($plan['name']) ?></h3>
                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($plan['description']) ?></p>

                    <!-- Price in INR (Rule 11) -->
                    <div class="mt-6 flex items-baseline gap-1">
                        <span class="text-4xl sm:text-5xl font-extrabold text-slate-900">₹<?= $price ?></span>
                        <span class="text-xs font-semibold text-slate-400">/ <?= $isThreeMonth ? '3 months' : 'month' ?></span>
                    </div>

                    <!-- Value breakdown -->
                    <?php if ($isThreeMonth): ?>
                    <p class="text-[11px] font-semibold text-emerald-600 mt-1">
                        Equivalent to ₹233/month. Save ₹197 compared to monthly.
                    </p>
                    <?php endif; ?>

                    <!-- Features Checklist -->
                    <div class="mt-8 space-y-3.5 text-xs text-slate-700">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                            <span class="font-semibold">Unlimited Direct Messaging (Text & Voice)</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                            <span class="font-semibold">See Who Viewed Your Profile</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                            <span>Premium Golden Crown Badge</span>
                        </div>
                        <?php if ($isThreeMonth): ?>
                        <div class="flex items-center gap-2.5 text-brand-700 font-bold">
                            <i data-lucide="zap" class="w-4 h-4 text-amber-500 fill-amber-500 flex-shrink-0"></i>
                            <span>Complimentary Profile Boost Included</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pay Button (Razorpay Real Payment) -->
                <div class="mt-10">
                    <button id="pay-btn-<?= $plan['id'] ?>" onclick="initiateRazorpay(<?= $plan['id'] ?>)" class="w-full py-4 rounded-2xl <?= $isThreeMonth ? 'bg-gradient-to-r from-brand-600 to-rose-600 hover:from-brand-700 hover:to-rose-700 shadow-lg shadow-brand-600/30' : 'bg-slate-900 hover:bg-slate-800' ?> text-white font-bold text-sm transition-all flex items-center justify-center gap-2">
                        <span id="pay-btn-text-<?= $plan['id'] ?>">Subscribe with Razorpay</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Security & Guarantee Footnote -->
    <div class="max-w-lg mx-auto text-center text-xs text-slate-500 space-y-2">
        <p class="flex items-center justify-center gap-2">
            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
            <span>Encrypted 256-bit payment via Razorpay. UPI, Cards, NetBanking supported.</span>
        </p>
    </div>
</div>

<!-- Verification Overlay Modal -->
<div id="payment-verify-modal" class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl border border-slate-100">
        <div class="w-16 h-16 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto mb-4 animate-spin">
            <i data-lucide="loader-2" class="w-8 h-8"></i>
        </div>
        <h3 class="font-serif text-lg font-bold text-slate-900 mb-2">Verifying Payment</h3>
        <p class="text-xs text-slate-500 leading-relaxed">
            Please wait while we confirm your payment securely with Razorpay. Do not refresh or close this window.
        </p>
    </div>
</div>

<script>
    let isProcessingPayment = false;

    function ensureRazorpayScript() {
        return new Promise((resolve, reject) => {
            if (typeof Razorpay !== 'undefined') {
                return resolve(true);
            }
            const script = document.createElement('script');
            script.src = 'https://checkout.razorpay.com/v1/checkout.js';
            script.onload = () => resolve(true);
            script.onerror = () => reject(new Error('Failed to load Razorpay Checkout script.'));
            document.head.appendChild(script);
        });
    }

    async function initiateRazorpay(planId) {
        if (isProcessingPayment) return;

        const btn = document.getElementById(`pay-btn-${planId}`);
        const btnText = document.getElementById(`pay-btn-text-${planId}`);
        const originalText = btnText ? btnText.textContent : 'Subscribe with Razorpay';

        isProcessingPayment = true;
        if (btn) btn.classList.add('opacity-75', 'pointer-events-none');
        if (btnText) btnText.textContent = 'Connecting to Gateway...';

        try {
            await ensureRazorpayScript();

            const formData = new FormData();
            formData.append('plan_id', planId);
            formData.append('type', 'subscription');
            formData.append('csrf_token', getCsrfToken());

            const res = await fetch('/api/subscription/create-order', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            let data = null;
            try {
                data = await res.json();
            } catch (jsonErr) {
                data = null;
            }

            if (!res.ok || !data || !data.success) {
                showToast(data?.error || 'Failed to create payment order. Please try again.', 'error');
                resetButton(planId, originalText);
                return;
            }

            // Real Razorpay Checkout Options
            const options = {
                key: data.key_id,
                amount: data.order.amount,
                currency: 'INR',
                name: 'Milan Dating',
                description: data.plan.name,
                order_id: data.order.id,
                prefill: {
                    name: data.user.name,
                    email: data.user.email
                },
                theme: {
                    color: '#E11D48'
                },
                handler: async function (response) {
                    await verifyPayment(response, planId, 'subscription', originalText);
                },
                modal: {
                    ondismiss: function() {
                        resetButton(planId, originalText);
                        showToast('Payment window closed.', 'error');
                    }
                }
            };

            const rzp1 = new Razorpay(options);
            rzp1.on('payment.failed', function (resp) {
                resetButton(planId, originalText);
                const desc = resp.error?.description || 'Payment was declined by your bank or gateway.';
                showToast(desc, 'error');

                // Asynchronously mark failure in backend
                const failForm = new FormData();
                failForm.append('razorpay_order_id', resp.error?.metadata?.order_id || data.order.id || '');
                failForm.append('razorpay_payment_id', resp.error?.metadata?.payment_id || '');
                failForm.append('reason', desc);
                failForm.append('csrf_token', getCsrfToken());
                fetch('/api/subscription/record-failure', {
                    method: 'POST',
                    body: failForm,
                    headers: { 'X-CSRF-TOKEN': getCsrfToken() }
                }).catch(() => {});
            });
            rzp1.open();

        } catch (err) {
            resetButton(planId, originalText);
            showToast('Unable to connect to payment gateway: ' + (err.message || 'Please check your connection.'), 'error');
        }
    }

    function resetButton(planId, originalText) {
        isProcessingPayment = false;
        const btn = document.getElementById(`pay-btn-${planId}`);
        const btnText = document.getElementById(`pay-btn-text-${planId}`);
        if (btn) btn.classList.remove('opacity-75', 'pointer-events-none');
        if (btnText) btnText.textContent = originalText;
    }

    async function verifyPayment(rzpResponse, planId, type, originalBtnText) {
        const modal = document.getElementById('payment-verify-modal');
        if (modal) modal.classList.remove('hidden');

        const verifyForm = new FormData();
        verifyForm.append('razorpay_order_id', rzpResponse.razorpay_order_id || '');
        verifyForm.append('razorpay_payment_id', rzpResponse.razorpay_payment_id || '');
        verifyForm.append('razorpay_signature', rzpResponse.razorpay_signature || '');
        verifyForm.append('plan_id', planId);
        verifyForm.append('type', type);
        verifyForm.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/subscription/verify-payment', {
                method: 'POST',
                body: verifyForm,
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            let data = null;
            try {
                data = await res.json();
            } catch (jsonErr) {
                data = null;
            }

            if (res.ok && data && data.success) {
                showToast(data.message || 'Payment verified! Welcome to Premium.', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || '/subscription';
                }, 800);
            } else {
                if (modal) modal.classList.add('hidden');
                resetButton(planId, originalBtnText);
                showToast(data?.error || 'Payment verification failed. Please contact support.', 'error');
            }
        } catch (e) {
            if (modal) modal.classList.add('hidden');
            resetButton(planId, originalBtnText);
            showToast('Network error verifying payment. If amount was deducted, your perks will activate shortly.', 'error');
        }
    }
</script>
