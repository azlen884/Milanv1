<?php
/**
 * Super Admin Discovery & Dating Settings
 * Algorithm Parameters & Interests Manager • Zero HTML Tables
 */
$csrfToken = \App\Helpers\Session::csrfToken();
?>
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-rose-100/80 shadow-xs">
        <div>
            <h2 class="font-serif text-xl font-bold text-slate-900">Discovery & Matching Parameters</h2>
            <p class="text-xs text-slate-500 mt-0.5">Control geolocation thresholds, age gating, and profile matchmaking interests</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                <?= count($interests) ?> Interest Tags Active
            </span>
        </div>
    </div>

    <!-- 2 Column Settings Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Matching Parameters Form -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="font-serif text-base font-bold text-slate-900">Algorithm Matching Rules</h3>
                <p class="text-xs text-slate-400 mt-0.5">Global defaults applied to discovery search</p>
            </div>

            <form method="POST" action="/admin/discovery/update" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Minimum Age Requirement</label>
                    <input type="number" name="min_age_requirement" value="<?= (int)$minAge ?>" required min="18" max="99"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                    <p class="text-[11px] text-slate-400 mt-1">Strictly enforced across registration (minimum 18 in India)</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Default Discovery Radius (KM)</label>
                    <input type="number" name="max_search_distance_km" value="<?= (int)$maxDistance ?>" required min="5" max="1000"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                    <p class="text-[11px] text-slate-400 mt-1">Radius for matching nearby members across Indian cities</p>
                </div>

                <div class="pt-2">
                    <label class="flex items-center gap-3 p-4 rounded-2xl bg-rose-50/40 border border-rose-100 cursor-pointer">
                        <input type="checkbox" name="require_photo_for_discovery" value="1" <?= $requirePhoto ? 'checked' : '' ?> class="rounded text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">Require Profile Photo for Discovery</span>
                            <span class="text-[11px] text-slate-500">Hide profiles from search if they haven't uploaded an avatar/photo yet</span>
                        </div>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Discovery Rules</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Interests & Tags Manager -->
        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-rose-100/80 shadow-xs space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="font-serif text-base font-bold text-slate-900">Profile Interest Tags</h3>
                <p class="text-xs text-slate-400 mt-0.5">Used for compatibility matching and user bios</p>
            </div>

            <!-- Add New Tag Form -->
            <form method="POST" action="/admin/interests/save" class="flex gap-2">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="text" name="name" required placeholder="New Interest Tag (e.g. Filter Coffee, Hiking)..."
                       class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-500">
                <select name="category" class="px-3 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-rose-500">
                    <option value="Lifestyle">Lifestyle</option>
                    <option value="Food & Drink">Food & Drink</option>
                    <option value="Entertainment">Entertainment</option>
                    <option value="Music">Music</option>
                    <option value="Travel">Travel</option>
                </select>
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-colors shrink-0">
                    Add Tag
                </button>
            </form>

            <!-- Tags List (Pills) -->
            <div class="flex flex-wrap gap-2 pt-2 max-h-80 overflow-y-auto pr-1">
                <?php foreach ($interests as $tag): ?>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 border border-slate-200/80 text-xs font-semibold text-slate-700 transition-colors">
                        <span><?= htmlspecialchars($tag['name']) ?></span>
                        <span class="text-[10px] text-slate-400">(<?= htmlspecialchars($tag['category']) ?>)</span>
                        <form method="POST" action="/admin/interests/delete" class="inline ml-1" onsubmit="return confirm('Delete interest <?= htmlspecialchars($tag['name']) ?>?');">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="interest_id" value="<?= $tag['id'] ?>">
                            <button type="submit" class="text-slate-400 hover:text-rose-600 transition-colors">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
