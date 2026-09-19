<?php
/**
 * Super Admin Landing Marketing Profiles
 * Showcase Cards for Public Landing Page • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Landing Page Marketing Profiles</h2>
            <p class="text-xs text-slate-500 mt-0.5">Showcase verified member profiles on the public landing page to drive conversion</p>
        </div>
        <button type="button" onclick="openProfileModal()" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Add Showcase Profile</span>
        </button>
    </div>

    <!-- Profiles Cards Grid (Zero Tables) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($profiles)): ?>
            <div class="col-span-full bg-white p-12 rounded-3xl border border-rose-100/80 text-center">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <h3 class="font-serif text-base font-bold text-slate-900">No showcase profiles created</h3>
                <p class="text-xs text-slate-400 mt-1">Add showcase profiles to display to logged-out visitors on the homepage.</p>
            </div>
        <?php else: ?>
            <?php foreach ($profiles as $p): ?>
                <div class="bg-white rounded-3xl border border-rose-100/80 shadow-xs overflow-hidden flex flex-col justify-between hover:border-rose-300 transition-colors">
                    <!-- Photo Cover -->
                    <div class="h-48 bg-slate-100 relative overflow-hidden">
                        <?php if (!empty($p['photo_url'])): ?>
                            <img src="<?= htmlspecialchars($p['photo_url']) ?>" alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-400 font-bold">No Photo</div>
                        <?php endif; ?>

                        <!-- Active Pill -->
                        <div class="absolute top-3 left-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $p['is_active'] ? 'bg-emerald-600 text-white' : 'bg-slate-900/80 text-slate-300' ?>">
                                <?= $p['is_active'] ? 'Live' : 'Hidden' ?>
                            </span>
                        </div>

                        <!-- Badge Pill -->
                        <?php if (!empty($p['badge'])): ?>
                            <div class="absolute top-3 right-3">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/90 backdrop-blur-xs text-rose-700 shadow-xs">
                                    <?= htmlspecialchars($p['badge']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="font-serif text-base font-bold text-slate-900">
                                    <?= htmlspecialchars($p['name']) ?>, <?= (int)$p['age'] ?>
                                </h3>
                                <span class="text-xs text-slate-400 font-semibold">Order: #<?= (int)$p['display_order'] ?></span>
                            </div>

                            <div class="text-xs text-rose-600 font-semibold flex items-center gap-1 mt-0.5">
                                <i data-lucide="map-pin" class="w-3 h-3"></i>
                                <span><?= htmlspecialchars($p['city']) ?></span>
                            </div>

                            <p class="text-xs text-slate-600 mt-2 line-clamp-2">
                                <?= htmlspecialchars($p['short_bio']) ?>
                            </p>

                            <?php if (!empty($p['interests'])): ?>
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <?php foreach (explode(',', $p['interests']) as $tag): ?>
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-medium">
                                            <?= htmlspecialchars(trim($tag)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between mt-3">
                            <button type="button" onclick='openProfileModal(<?= json_encode($p) ?>)' class="text-xs font-bold text-rose-600 hover:underline">
                                Edit Details
                            </button>

                            <form method="POST" action="/admin/landing-profiles/delete" onsubmit="return confirm('Delete this marketing profile?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="text-xs text-slate-400 hover:text-rose-600 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add / Edit Modal -->
<div id="profileModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 id="profileModalTitle" class="font-serif text-lg font-bold text-slate-900">Add Marketing Showcase Profile</h3>
            <button type="button" onclick="closeProfileModal()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="/admin/landing-profiles/save" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" id="modalId" value="0">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Name</label>
                    <input type="text" name="name" id="modalName" required placeholder="e.g. Priya Sharma"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Age</label>
                    <input type="number" name="age" id="modalAge" required min="18" max="99" value="26"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Gender</label>
                    <select name="gender" id="modalGender" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                        <option value="female">Female</option>
                        <option value="male">Male</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">City</label>
                    <input type="text" name="city" id="modalCity" required placeholder="e.g. Mumbai"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Photo URL</label>
                <input type="url" name="photo_url" id="modalPhoto" required placeholder="https://images.unsplash.com/..."
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Short Bio</label>
                <textarea name="short_bio" id="modalBio" rows="2" placeholder="e.g. Architect living in Mumbai. Passionate about filter coffee and contemporary art."
                          class="w-full p-3 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Badge</label>
                    <input type="text" name="badge" id="modalBadge" value="Verified" placeholder="e.g. Verified, Top Match"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Display Order</label>
                    <input type="number" name="display_order" id="modalOrder" value="1" min="0"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Interests (comma separated)</label>
                <input type="text" name="interests" id="modalInterests" placeholder="Coffee, Architecture, Music"
                       class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-rose-500">
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" id="modalActive" value="1" checked class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-xs font-bold text-slate-700">Display on Public Landing Page</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeProfileModal()" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors shadow-xs">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openProfileModal(profile = null) {
        if (profile) {
            document.getElementById('modalId').value = profile.id;
            document.getElementById('profileModalTitle').innerText = 'Edit Showcase Profile';
            document.getElementById('modalName').value = profile.name;
            document.getElementById('modalAge').value = profile.age;
            document.getElementById('modalGender').value = profile.gender;
            document.getElementById('modalCity').value = profile.city;
            document.getElementById('modalPhoto').value = profile.photo_url;
            document.getElementById('modalBio').value = profile.short_bio;
            document.getElementById('modalBadge').value = profile.badge;
            document.getElementById('modalOrder').value = profile.display_order;
            document.getElementById('modalInterests').value = profile.interests || '';
            document.getElementById('modalActive').checked = parseInt(profile.is_active) === 1;
        } else {
            document.getElementById('modalId').value = 0;
            document.getElementById('profileModalTitle').innerText = 'Add Showcase Profile';
            document.getElementById('modalName').value = '';
            document.getElementById('modalAge').value = 25;
            document.getElementById('modalCity').value = 'Mumbai';
            document.getElementById('modalPhoto').value = '';
            document.getElementById('modalBio').value = '';
            document.getElementById('modalBadge').value = 'Verified';
            document.getElementById('modalOrder').value = 1;
            document.getElementById('modalInterests').value = '';
            document.getElementById('modalActive').checked = true;
        }

        document.getElementById('profileModal').classList.remove('hidden');
    }

    function closeProfileModal() {
        document.getElementById('profileModal').classList.add('hidden');
    }
</script>
