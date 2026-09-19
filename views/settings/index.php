<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="font-serif text-3xl font-bold text-slate-900">Account Settings</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your profile, preferences, verification, and privacy</p>
        </div>
        <a href="/logout" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-red-600 font-bold text-xs transition-colors flex items-center gap-1.5">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span>Log Out</span>
        </a>
    </div>

    <!-- Horizontal Tabs -->
    <div class="flex items-center gap-2 border-b border-rose-100 pb-3 mb-8 overflow-x-auto scrollbar-none text-xs font-bold">
        <?php 
        $tabs = [
            'profile' => ['label' => 'Edit Profile', 'icon' => 'user'],
            'photos' => ['label' => 'Photos', 'icon' => 'image'],
            'interests' => ['label' => 'Interests', 'icon' => 'tag'],
            'preferences' => ['label' => 'Preferences', 'icon' => 'sliders'],
            'privacy' => ['label' => 'Privacy', 'icon' => 'eye-off'],
            'kyc' => ['label' => 'Verification', 'icon' => 'badge-check'],
            'blocked' => ['label' => 'Blocked', 'icon' => 'slash'],
            'security' => ['label' => 'Security', 'icon' => 'lock'],
            'account' => ['label' => 'Account', 'icon' => 'trash-2'],
        ];
        ?>
        <?php foreach ($tabs as $key => $t): ?>
        <a href="/settings?tab=<?= $key ?>" class="px-4 py-2.5 rounded-full transition-all flex items-center gap-1.5 whitespace-nowrap <?= ($currentTab === $key) ? 'bg-brand-600 text-white shadow-md shadow-brand-600/20' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' ?>">
            <i data-lucide="<?= $t['icon'] ?>" class="w-3.5 h-3.5"></i>
            <span><?= $t['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- TAB: Edit Profile -->
    <?php if ($currentTab === 'profile'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm">
        <h2 class="font-serif text-xl font-bold text-slate-900 mb-6">Personal Information</h2>
        
        <form action="/settings/profile" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Current City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($user['city']) ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">About Me (Bio)</label>
                <textarea name="bio" rows="4" class="w-full p-4 rounded-xl border border-slate-200 text-sm" placeholder="Share a few details about your lifestyle, interests, and relationship goals..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Occupation</label>
                    <input type="text" name="occupation" value="<?= htmlspecialchars($user['occupation'] ?? '') ?>" placeholder="e.g. Software Architect" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Education</label>
                    <input type="text" name="education" value="<?= htmlspecialchars($user['education'] ?? '') ?>" placeholder="e.g. B.Tech / MBA" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Height (cm)</label>
                    <input type="number" name="height_cm" value="<?= htmlspecialchars($user['height_cm'] ?? '') ?>" placeholder="e.g. 175" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Languages Spoken</label>
                <input type="text" name="languages" value="<?= htmlspecialchars($user['languages'] ?? 'English, Hindi') ?>" placeholder="e.g. English, Hindi, Bengali" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>

            <div class="pt-4">
                <button type="submit" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20">
                    Save Profile Changes
                </button>
            </div>
        </form>
    </div>

    <!-- TAB: Photos -->
    <?php elseif ($currentTab === 'photos'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">Manage Photos</h2>
                <p class="text-xs text-slate-500 mt-0.5">High-quality portrait photos receive 3x more interest</p>
            </div>

            <label id="upload-photo-label" class="px-5 py-2.5 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm cursor-pointer flex items-center gap-1.5 transition-all">
                <i data-lucide="upload" class="w-4 h-4" id="upload-icon"></i>
                <span id="upload-text">Upload Photo</span>
                <input type="file" id="photo-upload-input" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="uploadPhoto(this)">
            </label>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" id="photo-grid">
            <?php foreach ($photos as $ph): ?>
            <div class="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 group">
                <img src="<?= htmlspecialchars($ph['photo_url']) ?>" alt="" class="w-full h-full object-cover">
                
                <?php if ($ph['is_primary']): ?>
                <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-600 text-white shadow-sm">Primary</span>
                <?php else: ?>
                <button onclick="setPrimaryPhoto(<?= $ph['id'] ?>)" class="absolute top-2 left-2 px-2 py-0.5 rounded-full text-[10px] font-bold bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                    Set Primary
                </button>
                <?php endif; ?>

                <button onclick="deletePhoto(<?= $ph['id'] ?>)" class="absolute bottom-2 right-2 p-1.5 rounded-full bg-red-600 text-white opacity-0 group-hover:opacity-100 transition-opacity shadow-sm" title="Delete">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB: Interests -->
    <?php elseif ($currentTab === 'interests'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm">
        <h2 class="font-serif text-xl font-bold text-slate-900 mb-2">Interests & Passions</h2>
        <p class="text-xs text-slate-500 mb-6">Select the hobbies and values that define who you are.</p>

        <form action="/settings/interests" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="flex flex-wrap gap-2.5 mb-8">
                <?php foreach ($allInterests as $in): ?>
                <?php $checked = in_array((int)$in['id'], $userInterestIds, true); ?>
                <label class="cursor-pointer">
                    <input type="checkbox" name="interests[]" value="<?= (int)$in['id'] ?>" <?= $checked ? 'checked' : '' ?> class="hidden peer">
                    <span class="px-4 py-2 rounded-full text-xs font-semibold inline-block transition-all peer-checked:bg-brand-600 peer-checked:text-white bg-slate-100 text-slate-700 hover:bg-slate-200 border border-transparent peer-checked:shadow-sm">
                        <?= htmlspecialchars($in['name']) ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20">
                Update Interests
            </button>
        </form>
    </div>

    <!-- TAB: Preferences -->
    <?php elseif ($currentTab === 'preferences'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm">
        <h2 class="font-serif text-xl font-bold text-slate-900 mb-6">Dating Preferences</h2>

        <form action="/settings/preferences" method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Interested in Meeting</label>
                <div class="grid grid-cols-3 gap-3 max-w-md">
                    <?php 
                    $currGen = $preferences['interested_in_gender'] ?? 'all'; 
                    ?>
                    <label class="cursor-pointer text-center">
                        <input type="radio" name="interested_in_gender" value="female" <?= ($currGen === 'female') ? 'checked' : '' ?> class="hidden peer">
                        <span class="block py-2.5 rounded-xl border border-slate-200 text-xs font-bold peer-checked:bg-brand-50 peer-checked:border-brand-500 peer-checked:text-brand-700">Women</span>
                    </label>
                    <label class="cursor-pointer text-center">
                        <input type="radio" name="interested_in_gender" value="male" <?= ($currGen === 'male') ? 'checked' : '' ?> class="hidden peer">
                        <span class="block py-2.5 rounded-xl border border-slate-200 text-xs font-bold peer-checked:bg-brand-50 peer-checked:border-brand-500 peer-checked:text-brand-700">Men</span>
                    </label>
                    <label class="cursor-pointer text-center">
                        <input type="radio" name="interested_in_gender" value="all" <?= ($currGen === 'all') ? 'checked' : '' ?> class="hidden peer">
                        <span class="block py-2.5 rounded-xl border border-slate-200 text-xs font-bold peer-checked:bg-brand-50 peer-checked:border-brand-500 peer-checked:text-brand-700">Everyone</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 max-w-md">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Minimum Age</label>
                    <input type="number" name="age_min" value="<?= (int)($preferences['age_min'] ?? 18) ?>" min="18" max="70" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Maximum Age</label>
                    <input type="number" name="age_max" value="<?= (int)($preferences['age_max'] ?? 55) ?>" min="18" max="70" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Preferred City (Optional)</label>
                <input type="text" name="city_preference" value="<?= htmlspecialchars($preferences['city_preference'] ?? '') ?>" placeholder="Leave blank for any city" class="max-w-md w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>

            <div class="pt-4">
                <button type="submit" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>

    <!-- TAB: Privacy (Rule 21 & Incognito) -->
    <?php elseif ($currentTab === 'privacy'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm">
        <h2 class="font-serif text-xl font-bold text-slate-900 mb-6">Privacy & Visibility</h2>

        <form action="/settings/privacy" method="POST" class="space-y-6 max-w-xl">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <!-- Profile Visitors Opt-Out (Rule 21) -->
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 flex items-start justify-between gap-4">
                <div>
                    <h4 class="font-bold text-sm text-slate-900">Opt Out of Profile Visitor Lists</h4>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        If you opt out, other members cannot see when you visit their profile. In accordance with mutual privacy rules, your own visitor list will also be paused.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-1">
                    <input type="checkbox" name="opt_out_visitors" value="1" <?= !empty($user['opt_out_visitors']) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                </label>
            </div>

            <!-- Incognito / Pause Mode -->
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 flex items-start justify-between gap-4">
                <div>
                    <h4 class="font-bold text-sm text-slate-900">Incognito Mode (Hide from Discover)</h4>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Hide your profile from Discover results while keeping your existing matches and active conversations.
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 mt-1">
                    <input type="checkbox" name="is_incognito" value="1" <?= !empty($user['is_incognito']) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
                </label>
            </div>

            <button type="submit" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20">
                Save Privacy Settings
            </button>
        </form>
    </div>

    <!-- TAB: KYC Verification (Rule 23) -->
    <?php elseif ($currentTab === 'kyc'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i data-lucide="badge-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h2 class="font-serif text-xl font-bold text-slate-900">Identity Verification (KYC)</h2>
                <span class="text-xs text-slate-500">Earn the trusted green Verified checkmark on your profile</span>
            </div>
        </div>

        <?php if ($user['kyc_status'] === 'verified'): ?>
        <div class="p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center gap-4 mb-6">
            <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-600 flex-shrink-0"></i>
            <div>
                <h4 class="font-bold text-sm">Your Identity is Verified!</h4>
                <p class="text-xs text-emerald-700 mt-0.5">Your profile displays the Verified badge to all members across Discover and Matches.</p>
            </div>
        </div>
        <?php elseif ($user['kyc_status'] === 'pending'): ?>
        <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-4 mb-6">
            <i data-lucide="clock" class="w-8 h-8 text-amber-600 flex-shrink-0"></i>
            <div>
                <h4 class="font-bold text-sm">Verification Under Review</h4>
                <p class="text-xs text-amber-700 mt-0.5">Our compliance team is currently reviewing your uploaded ID and selfie. This usually takes 2-4 hours.</p>
            </div>
        </div>
        <?php else: ?>
            <?php if (!$kycGloballyEnabled): ?>
            <div class="p-6 rounded-2xl bg-slate-100 text-slate-600 text-xs">
                KYC verification is currently paused by administrators.
            </div>
            <?php else: ?>
            <form action="/settings/kyc" method="POST" enctype="multipart/form-data" class="space-y-4 max-w-xl">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Select Government ID Type</label>
                    <select name="doc_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white">
                        <option value="aadhaar">Aadhaar Card</option>
                        <option value="pan">PAN Card</option>
                        <option value="passport">Indian Passport</option>
                        <option value="driving_license">Driving License</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Upload Government ID Document</label>
                    <input type="file" name="doc_file" required accept="image/*,application/pdf" class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-brand-700 hover:file:bg-rose-100">
                    <p class="text-[10px] text-slate-400 mt-1">Accepts JPG, PNG, WEBP, or PDF (Max 8MB). Stored securely and privately.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Upload Live Verification Selfie</label>
                    <input type="file" name="selfie_file" required accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-brand-700 hover:file:bg-rose-100">
                    <p class="text-[10px] text-slate-400 mt-1">A clear photo of your face taken right now to confirm your match with your ID photo.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20">
                        Submit Verification Securely
                    </button>
                </div>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- TAB: Blocked Users (Rule 25) -->
    <?php elseif ($currentTab === 'blocked'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm">
        <h2 class="font-serif text-xl font-bold text-slate-900 mb-2">Blocked Users</h2>
        <p class="text-xs text-slate-500 mb-6">Blocked members cannot see your profile, send interest, or message you.</p>

        <?php if (empty($blockedUsers)): ?>
        <p class="text-xs text-slate-400 py-6 text-center">You have not blocked any members.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($blockedUsers as $b): ?>
            <div class="p-3.5 rounded-2xl bg-slate-50 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="<?= htmlspecialchars($b['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80') ?>" alt="" class="w-10 h-10 rounded-full object-cover">
                    <div>
                        <span class="font-bold text-sm text-slate-900"><?= htmlspecialchars($b['name']) ?></span>
                        <span class="text-xs text-slate-400 block"><?= htmlspecialchars($b['city']) ?></span>
                    </div>
                </div>
                <button onclick="unblockUser(<?= $b['user_id'] ?>)" class="px-4 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-200 text-xs font-semibold">
                    Unblock
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB: Security -->
    <?php elseif ($currentTab === 'security'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-rose-100 shadow-sm max-w-md">
        <h2 class="font-serif text-xl font-bold text-slate-900 mb-6">Change Password</h2>

        <form action="/settings/password" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Current Password</label>
                <input type="password" name="current_password" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">New Password</label>
                <input type="password" name="new_password" minlength="8" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password" minlength="8" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>

            <button type="submit" class="px-6 py-3 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm">
                Update Password
            </button>
        </form>
    </div>

    <!-- TAB: Delete Account -->
    <?php elseif ($currentTab === 'account'): ?>
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-red-100 shadow-sm max-w-lg">
        <h2 class="font-serif text-xl font-bold text-red-600 mb-2">Delete Account Permanently</h2>
        <p class="text-xs text-slate-600 mb-6 leading-relaxed">
            Deleting your account will immediately remove all your personal data, matches, chat history, and active subscriptions. This action cannot be reversed.
        </p>

        <form action="/settings/delete-account" method="POST" onsubmit="return confirm('Are you absolutely certain you want to permanently delete your account?')" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Confirm Password</label>
                <input type="password" name="password" required placeholder="Enter password to confirm" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>

            <button type="submit" class="px-6 py-3 rounded-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-600/20">
                Permanently Delete My Account
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
    async function uploadPhoto(input) {
        if (!input.files || !input.files[0]) return;
        if (input.dataset.uploading === '1') return;

        const file = input.files[0];
        
        // Client-side quick checks
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showToast('Please select a JPG, PNG, or WebP image.', 'error');
            input.value = '';
            return;
        }

        if (file.size > 15 * 1024 * 1024) {
            showToast('Photo size must not exceed 15MB.', 'error');
            input.value = '';
            return;
        }

        input.dataset.uploading = '1';
        const label = document.getElementById('upload-photo-label');
        const text = document.getElementById('upload-text');
        const icon = document.getElementById('upload-icon');
        const originalText = text ? text.textContent : 'Upload Photo';

        if (label) {
            label.classList.add('opacity-70', 'pointer-events-none');
        }
        if (text) {
            text.textContent = 'Uploading...';
        }

        const formData = new FormData();
        formData.append('photo', file);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/photos/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await res.json().catch(() => null);

            if (res.ok && data && data.success) {
                showToast(data.message || 'Photo uploaded successfully.', 'success');
                setTimeout(() => window.location.reload(), 600);
            } else {
                showToast(data?.error || data?.message || 'Failed to upload photo. Please try again.', 'error');
            }
        } catch (e) {
            showToast('Unable to upload photo. Please check your internet connection.', 'error');
        } finally {
            input.value = '';
            delete input.dataset.uploading;
            if (label) {
                label.classList.remove('opacity-70', 'pointer-events-none');
            }
            if (text) {
                text.textContent = originalText;
            }
        }
    }

    async function setPrimaryPhoto(photoId) {
        const formData = new FormData();
        formData.append('photo_id', photoId);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/photos/set-primary', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json().catch(() => null);
            if (res.ok && data && data.success) {
                showToast(data.message || 'Primary photo updated.', 'success');
                setTimeout(() => window.location.reload(), 400);
            } else {
                showToast(data?.error || 'Failed to update primary photo.', 'error');
            }
        } catch (e) {
            showToast('Unable to update primary photo. Please retry.', 'error');
        }
    }

    async function deletePhoto(photoId) {
        if (!confirm('Are you sure you want to delete this photo?')) return;
        const formData = new FormData();
        formData.append('photo_id', photoId);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/photos/delete', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json().catch(() => null);
            if (res.ok && data && data.success) {
                showToast(data.message || 'Photo deleted.', 'success');
                setTimeout(() => window.location.reload(), 400);
            } else {
                showToast(data?.error || 'Failed to delete photo.', 'error');
            }
        } catch (e) {
            showToast('Unable to delete photo. Please retry.', 'error');
        }
    }

    async function unblockUser(userId) {
        const formData = new FormData();
        formData.append('target_id', userId);
        formData.append('csrf_token', getCsrfToken());

        const res = await fetch('/api/profile/unblock', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast('User unblocked.', 'success');
            setTimeout(() => window.location.reload(), 500);
        }
    }
</script>
