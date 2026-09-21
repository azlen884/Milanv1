<?php
/**
 * Super Admin Razorpay Gateway Management
 * Secure API Credentials • Webhook Endpoint • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'milandating.in';
$webhookUrl = "{$protocol}://{$host}/api/razorpay/webhook";
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Razorpay Gateway Management</h2>
            <p class="text-xs text-slate-500 mt-0.5">Configure live & test API keys, webhook handlers, and monitor Indian payment processing</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold <?= $enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                <span class="w-2 h-2 rounded-full <?= $enabled ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                <span>Gateway <?= $enabled ? 'Enabled' : 'Disabled' ?></span>
            </span>
            <span class="px-3.5 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wider">
                Mode: <?= htmlspecialchars($mode) ?>
            </span>
        </div>
    </div>

    <!-- Main Config & Webhook Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Gateway Configuration Form (2 Cols) -->
        <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="font-serif text-base font-bold text-slate-900">API Credentials & Operational Mode</h3>
                <p class="text-xs text-slate-400 mt-0.5">Credentials are securely encrypted in the database.</p>
            </div>

            <form method="POST" action="/admin/razorpay/update" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Enabled / Disabled Toggle -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Gateway Status</label>
                        <select name="razorpay_enabled" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                            <option value="1" <?= $enabled ? 'selected' : '' ?>>Enabled (Accept Payments)</option>
                            <option value="0" <?= !$enabled ? 'selected' : '' ?>>Disabled (Maintenance)</option>
                        </select>
                    </div>

                    <!-- Mode Toggle -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Environment Mode</label>
                        <select name="razorpay_mode" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                            <option value="test" <?= $mode === 'test' ? 'selected' : '' ?>>Test Mode (rzp_test_...)</option>
                            <option value="live" <?= $mode === 'live' ? 'selected' : '' ?>>Live Mode (rzp_live_...)</option>
                        </select>
                    </div>
                </div>

                <!-- Key ID -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Razorpay Key ID</label>
                    <input type="text" name="razorpay_key_id" value="<?= htmlspecialchars($keyId) ?>" required placeholder="rzp_test_... or rzp_live_..."
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-mono focus:outline-none focus:border-rose-500">
                </div>

                <!-- Key Secret -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Razorpay Key Secret</label>
                        <?php if ($hasSecret): ?>
                            <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                <span>Secret is Configured</span>
                            </span>
                        <?php else: ?>
                            <span class="text-[11px] font-bold text-amber-600">Secret Missing</span>
                        <?php endif; ?>
                    </div>
                    <input type="password" name="razorpay_key_secret" placeholder="<?= $hasSecret ? '•••••••••••••••• (Leave blank to keep existing)' : 'Enter Razorpay Key Secret' ?>"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-mono focus:outline-none focus:border-rose-500">
                    <p class="text-[11px] text-slate-400 mt-1">For security, existing secret is never displayed in HTML. Leave blank unless updating.</p>
                </div>

                <!-- Webhook Secret -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Webhook Signing Secret</label>
                        <?php if ($hasWebhookSecret): ?>
                            <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                <span>Configured</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <input type="password" name="razorpay_webhook_secret" placeholder="Webhook Secret configured in Razorpay Dashboard"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 font-mono focus:outline-none focus:border-rose-500">
                    <p class="text-[11px] text-slate-400 mt-1">Used to verify HMAC SHA256 signatures of webhook events.</p>
                </div>

                <div id="testConnResult" class="hidden p-3 rounded-xl text-xs font-medium"></div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <button type="button" id="testConnBtn" onclick="testRazorpayConnection()" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-slate-500"></i>
                        <span id="testConnText">Test API Connection</span>
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Razorpay Configuration</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Webhook Info & Instructions (1 Col) -->
        <div class="space-y-6">
            <!-- Webhook URL Box -->
            <div class="bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
                <div>
                    <h3 class="font-serif text-base font-bold text-slate-900">Webhook Endpoint</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Configure this in your Razorpay Dashboard &rarr; Webhooks</p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Webhook URL</label>
                    <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-xl border border-slate-200">
                        <input type="text" id="webhookUrlInput" readonly value="<?= htmlspecialchars($webhookUrl) ?>"
                               class="w-full bg-transparent text-xs font-mono text-slate-800 focus:outline-none select-all">
                        <button type="button" onclick="copyWebhookUrl()" class="p-1.5 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs" title="Copy URL">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>

                <div class="space-y-2 pt-2 border-t border-slate-100 text-xs text-slate-600">
                    <span class="font-bold text-slate-800 block">Recommended Webhook Events:</span>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-mono">payment.captured</span>
                        <span class="px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-mono">payment.failed</span>
                        <span class="px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-mono">order.paid</span>
                    </div>
                </div>
            </div>

            <!-- Currency & Region Notice -->
            <div class="bg-gradient-to-br from-rose-50 to-pink-50/50 p-6 rounded-3xl border border-rose-100 shadow-xs space-y-2">
                <div class="flex items-center gap-2 text-rose-700 font-bold text-xs">
                    <i data-lucide="indian-rupee" class="w-4 h-4"></i>
                    <span>Settlement in Indian Rupees</span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Milan Dating is strictly denominated in INR (₹). Payments are securely authorized and captured in paise (₹1 = 100 paise) via UPI, Cards, NetBanking, and Wallets.
                </p>
            </div>
        </div>
    </div>

    <!-- Recent Gateway Logs (Cards, No Tables) -->
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h3 class="font-serif text-base font-bold text-slate-900">Recent Gateway Activity</h3>
                <p class="text-xs text-slate-400 mt-0.5">Live events recorded via Razorpay checkout and callbacks</p>
            </div>
            <a href="/admin/payments" class="text-xs font-bold text-rose-600 hover:underline">Full Payment History &rarr;</a>
        </div>

        <div class="space-y-3">
            <?php if (empty($recentLogs)): ?>
                <div class="py-8 text-center text-xs text-slate-400">No payment events logged yet.</div>
            <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl <?= $log['status'] === 'captured' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?> flex items-center justify-center font-bold">
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-900">
                                    <span><?= htmlspecialchars($log['user_email']) ?></span>
                                    <span class="ml-2 font-mono text-[11px] text-slate-400">Order: <?= htmlspecialchars($log['razorpay_order_id']) ?></span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    <span>Type: <?= ucfirst($log['payment_type']) ?></span>
                                    <span class="ml-2">• <?= date('M j, Y • g:i a', strtotime($log['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 shrink-0">
                            <span class="text-sm font-bold font-serif text-slate-900">₹<?= number_format($log['amount_paisa'] / 100) ?></span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $log['status'] === 'captured' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' ?>">
                                <?= htmlspecialchars($log['status']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function copyWebhookUrl() {
        const input = document.getElementById('webhookUrlInput');
        input.select();
        navigator.clipboard.writeText(input.value);
        if (typeof showToast === 'function') {
            showToast('Webhook endpoint URL copied to clipboard.', 'info');
        } else {
            alert('Webhook endpoint URL copied to clipboard.');
        }
    }

    async function testRazorpayConnection() {
        const btn = document.getElementById('testConnBtn');
        const text = document.getElementById('testConnText');
        const resultDiv = document.getElementById('testConnResult');

        btn.disabled = true;
        btn.classList.add('opacity-75');
        text.textContent = 'Testing Connection...';
        resultDiv.classList.add('hidden');
        resultDiv.className = 'hidden p-3 rounded-xl text-xs font-medium';

        try {
            const formData = new FormData();
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = csrfMeta ? csrfMeta.getAttribute('content') : '<?= \App\Helpers\Session::getCsrfToken() ?>';
            formData.append('csrf_token', csrf);

            const res = await fetch('/admin/razorpay/test', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await res.json();
            resultDiv.classList.remove('hidden');

            if (data.success) {
                resultDiv.className = 'p-3 rounded-xl text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-2';
                resultDiv.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i> <span>' + (data.message || 'Connection successful! API credentials valid.') + '</span>';
            } else {
                resultDiv.className = 'p-3 rounded-xl text-xs font-medium bg-rose-50 text-rose-800 border border-rose-200 flex items-center gap-2';
                resultDiv.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i> <span>' + (data.error || 'Connection failed.') + '</span>';
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (err) {
            resultDiv.classList.remove('hidden');
            resultDiv.className = 'p-3 rounded-xl text-xs font-medium bg-rose-50 text-rose-800 border border-rose-200 flex items-center gap-2';
            resultDiv.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i> <span>Network error while testing connection.</span>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-75');
            text.textContent = 'Test API Connection';
        }
    }
</script>
