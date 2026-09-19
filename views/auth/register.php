<?php
// Maximum allowed date of birth (must be 18+ years ago)
$maxDob = date('Y-m-d', strtotime('-18 years'));
?>

<div class="max-w-xl mx-auto px-4 py-10">
    <div class="bg-white rounded-3xl p-8 sm:p-10 border border-rose-100 shadow-xl shadow-rose-100/50">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="user-plus" class="w-6 h-6"></i>
            </div>
            <h1 class="font-serif text-2xl font-bold text-slate-900">Create Your Milan Profile</h1>
            <p class="text-sm text-slate-500 mt-1">Begin meeting genuine, verified singles across India</p>
        </div>

        <form action="/register" method="POST" class="space-y-4" onsubmit="return validateAge()">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Your Full Name</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </span>
                    <input type="text" id="name" name="name" required placeholder="e.g. Ananya Sen"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all">
                </div>
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </span>
                    <input type="email" id="email" name="email" required placeholder="ananya@example.com"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Create Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </span>
                    <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all">
                </div>
            </div>

            <!-- Gender and City (Two columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="gender" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">I am a</label>
                    <select id="gender" name="gender" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all bg-white">
                        <option value="">Select Gender</option>
                        <option value="female">Woman</option>
                        <option value="male">Man</option>
                        <option value="other">Other / Non-Binary</option>
                    </select>
                </div>

                <div>
                    <label for="city" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Current City</label>
                    <select id="city" name="city" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all bg-white">
                        <option value="">Select City</option>
                        <?php foreach ($cities as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Date of Birth (STRICT 18+ ENFORCEMENT) -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="dob" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Date of Birth</label>
                    <span class="text-[11px] font-semibold text-brand-600">Must be 18+</span>
                </div>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                    </span>
                    <input type="date" id="dob" name="dob" max="<?= $maxDob ?>" required
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 text-sm outline-none transition-all">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">We respect age criteria strictly. Only members 18 years or older are admitted.</p>
            </div>

            <!-- Terms agreement -->
            <div class="pt-2 text-xs text-slate-500">
                By clicking "Complete Registration", you agree to our Terms of Service, Privacy Policy, and confirm that you are at least 18 years of age.
            </div>

            <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-600/25 transition-all duration-200">
                Complete Registration
            </button>
        </form>

        <div class="text-center mt-6 text-xs text-slate-500">
            Already have an account? 
            <a href="/login" class="font-bold text-brand-600 hover:underline">Sign In</a>
        </div>
    </div>
</div>

<script>
    function validateAge() {
        const dobInput = document.getElementById('dob').value;
        if (!dobInput) return false;

        const dob = new Date(dobInput);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
        }

        if (age < 18) {
            alert('You must be at least 18 years old to register on Milan Dating.');
            return false;
        }
        return true;
    }
</script>
