<?php
// views/admin/bots.php
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Admin Controlled Bots</h1>
            <p class="text-sm text-slate-500 mt-1">Manage automated companion profiles, predefined message sequences, and timed delivery to free users.</p>
        </div>
        <button onclick="document.getElementById('create-bot-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Create New Bot</span>
        </button>
    </div>

    <!-- Paid Subscriber Guarantee Banner -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/80 rounded-2xl p-4 flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-xs">
            <i data-lucide="shield-check" class="w-5 h-5"></i>
        </div>
        <div>
            <h4 class="text-sm font-bold text-emerald-900">Automatic Subscription Stop Guard Enabled</h4>
            <p class="text-xs text-emerald-700 mt-0.5 leading-relaxed">
                When a user upgrades to any paid subscription (Monthly or 3 Months), the system strictly and permanently halts all automated bot messages for that user. Bot messages are only delivered to free tier accounts.
            </p>
        </div>
    </div>

    <!-- Bots List -->
    <?php if (empty($bots)): ?>
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="bot" class="w-8 h-8"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">No Admin Bots Created Yet</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-6">Create realistic bot profiles with predefined messages to greet new users and demonstrate the chat experience.</p>
            <button onclick="document.getElementById('create-bot-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Create Your First Bot</span>
            </button>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($bots as $bot): ?>
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col">
                    <!-- Bot Header Card -->
                    <div class="p-5 border-b border-slate-100 flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <img src="<?= htmlspecialchars($bot['avatar_url']) ?>" alt="<?= htmlspecialchars($bot['name']) ?>" class="w-14 h-14 rounded-2xl object-cover ring-2 ring-slate-100 shrink-0" onerror="this.src='/uploads/profiles/default_female.jpg'">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-900"><?= htmlspecialchars($bot['name']) ?></h3>
                                    <span class="text-xs text-slate-500"><?= (int)$bot['age'] ?> • <?= ucfirst(htmlspecialchars($bot['gender'])) ?></span>
                                    <?php if ($bot['is_active']): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Paused</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span><?= htmlspecialchars($bot['city']) ?></span>
                                    <span>•</span>
                                    <span><?= count($bot['messages']) ?> predefined messages</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <!-- Toggle Active -->
                            <form action="/admin/bots/toggle" method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="bot_id" value="<?= $bot['id'] ?>">
                                <button type="submit" class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition-colors" title="<?= $bot['is_active'] ? 'Pause Bot' : 'Activate Bot' ?>">
                                    <i data-lucide="<?= $bot['is_active'] ? 'pause' : 'play' ?>" class="w-4 h-4 <?= $bot['is_active'] ? 'text-amber-600' : 'text-emerald-600' ?>"></i>
                                </button>
                            </form>
                            <!-- Delete Bot -->
                            <form action="/admin/bots/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this bot and its conversations?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="bot_id" value="<?= $bot['id'] ?>">
                                <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Delete Bot">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Bio -->
                    <div class="px-5 py-3 bg-slate-50/50 border-b border-slate-100 text-xs text-slate-600 italic">
                        "<?= htmlspecialchars($bot['bio']) ?>"
                    </div>

                    <!-- Predefined Message Sequence -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Message Sequence</h4>
                                <span class="text-[11px] text-slate-400">Delivered sequentially by delay</span>
                            </div>

                            <div class="space-y-2 mb-4">
                                <?php if (empty($bot['messages'])): ?>
                                    <p class="text-xs text-slate-400 italic py-2">No messages in sequence yet. Add one below.</p>
                                <?php else: ?>
                                    <?php foreach ($bot['messages'] as $idx => $msg): ?>
                                        <div class="flex items-start justify-between gap-3 p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs">
                                            <div class="flex items-start gap-2.5">
                                                <span class="w-5 h-5 rounded-lg bg-rose-100 text-rose-700 font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5"><?= $idx + 1 ?></span>
                                                <div>
                                                    <p class="text-slate-800 font-medium leading-relaxed"><?= htmlspecialchars($msg['message_text']) ?></p>
                                                    <span class="text-[10px] font-semibold text-slate-400 mt-1 inline-block">
                                                        Delay: <?= (int)$msg['delay_minutes'] ?> min after registration
                                                    </span>
                                                </div>
                                            </div>
                                            <form action="/admin/bots/message/delete" method="POST" class="shrink-0" onsubmit="return confirm('Delete this message?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                                <button type="submit" class="text-slate-400 hover:text-rose-600 p-1">
                                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Add Message Form -->
                        <form action="/admin/bots/message/add" method="POST" class="pt-3 border-t border-slate-100">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="bot_id" value="<?= $bot['id'] ?>">
                            <div class="flex items-center gap-2">
                                <input type="number" name="delay_minutes" min="0" max="1440" value="5" placeholder="Min" class="w-20 px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none" title="Delay in minutes after user registers">
                                <input type="text" name="message_text" required placeholder="Type predefined reply message..." class="flex-1 px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                                <button type="submit" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl shrink-0 transition-colors">
                                    Add
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Create Bot Modal -->
<div id="create-bot-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="bot" class="w-5 h-5 text-rose-600"></i>
                <span>Create New Admin Bot</span>
            </h3>
            <button onclick="document.getElementById('create-bot-modal').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div id="create-bot-error" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium"></div>

        <form id="create-bot-form" action="/admin/bots/create" method="POST" enctype="multipart/form-data" class="space-y-4 pt-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Bot Name *</label>
                <input type="text" name="name" id="bot-name-input" required placeholder="e.g. Priya Sharma" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Age *</label>
                    <input type="number" name="age" min="18" max="70" value="24" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Gender *</label>
                    <select name="gender" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        <option value="female" selected>Female</option>
                        <option value="male">Male</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">City / Location *</label>
                <input type="text" name="city" required value="Mumbai" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Profile Bio *</label>
                <textarea name="bio" rows="2" required class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">Looking to make real connections and meet genuine people!</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Avatar / Profile Photo</label>
                <input type="file" name="photo" id="bot-photo-input" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                <p class="text-[11px] text-slate-400 mt-1">Optional. Defaults to a standard high-quality avatar if left empty.</p>
            </div>

            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Predefined Message</label>
                <input type="text" name="initial_message" value="Hey there! How is your day going? 😊" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Delay (Minutes after registration)</label>
                <input type="number" name="initial_delay_minutes" min="0" max="1440" value="0" class="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:outline-none">
                <p class="text-[11px] text-slate-400 mt-0.5">Set to 0 to send immediately upon user registration.</p>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-bot-modal').classList.add('hidden')" class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" id="create-bot-submit-btn" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors flex items-center gap-2">
                    <span>Create Bot</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('create-bot-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const errBox = document.getElementById('create-bot-error');
        const submitBtn = document.getElementById('create-bot-submit-btn');
        const nameInput = document.getElementById('bot-name-input');

        if (errBox) {
            errBox.classList.add('hidden');
            errBox.textContent = '';
        }

        if (!nameInput || !nameInput.value.trim()) {
            if (errBox) {
                errBox.textContent = 'Bot Name is required.';
                errBox.classList.remove('hidden');
            }
            return;
        }

        const origBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Creating Bot...</span>';

        try {
            const formData = new FormData(form);
            formData.append('is_ajax', '1');

            const res = await fetch('/admin/bots/create', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await res.json().catch(() => null);

            if (res.ok && data && data.success) {
                window.location.reload();
            } else {
                const errMsg = data?.error || data?.message || ('Server error (HTTP ' + res.status + ')');
                if (errBox) {
                    errBox.textContent = errMsg;
                    errBox.classList.remove('hidden');
                } else {
                    alert('Error: ' + errMsg);
                }
            }
        } catch (err) {
            console.error('Bot creation request failed:', err);
            if (errBox) {
                errBox.textContent = 'Network error or connection failed. Please try again.';
                errBox.classList.remove('hidden');
            } else {
                alert('Connection error. Please try again.');
            }
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
    });
});
</script>
