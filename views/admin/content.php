<?php
/**
 * Super Admin Site Content & FAQ Management
 * Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Site Content & Brand Copy</h2>
            <p class="text-xs text-slate-500 mt-0.5">Edit public landing page headlines, support contact, and customer FAQs</p>
        </div>
    </div>

    <!-- 2 Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Brand Copy Form -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="font-serif text-base font-bold text-slate-900">Brand Positioning & Support</h3>
                <p class="text-xs text-slate-400 mt-0.5">Displayed prominently across landing and footer sections</p>
            </div>

            <form method="POST" action="/admin/content/save" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Main Tagline</label>
                    <input type="text" name="site_tagline" value="<?= htmlspecialchars($tagline) ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-medium focus:outline-none focus:border-rose-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Customer Support Email</label>
                    <input type="email" name="contact_email" value="<?= htmlspecialchars($contactEmail) ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-medium focus:outline-none focus:border-rose-500">
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Content</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- FAQ Preview Card -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="font-serif text-base font-bold text-slate-900">Public Landing FAQs</h3>
                <p class="text-xs text-slate-400 mt-0.5">Common questions answered for prospective Indian members</p>
            </div>

            <div class="space-y-3">
                <?php if (empty($faqs)): ?>
                    <!-- Standard Fallback FAQ Showcase -->
                    <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-100 space-y-1">
                        <div class="text-xs font-bold text-slate-900">Is KYC verification mandatory on Milan?</div>
                        <p class="text-[11px] text-slate-600">Yes, to maintain authentic connections, photo and government ID verification is audited by our Super Admin team.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-100 space-y-1">
                        <div class="text-xs font-bold text-slate-900">How do Razorpay payments work?</div>
                        <p class="text-[11px] text-slate-600">We accept all major Indian payment methods: UPI (Google Pay, PhonePe, Paytm), Credit/Debit Cards, and NetBanking.</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-100 space-y-1">
                        <div class="text-xs font-bold text-slate-900">What does a Profile Boost do?</div>
                        <p class="text-[11px] text-slate-600">A Profile Boost highlights your card at the top of member discovery in your city for 24 hours.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($faqs as $faq): ?>
                        <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-100 space-y-1">
                            <div class="text-xs font-bold text-slate-900"><?= htmlspecialchars($faq['q'] ?? '') ?></div>
                            <p class="text-[11px] text-slate-600"><?= htmlspecialchars($faq['a'] ?? '') ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
