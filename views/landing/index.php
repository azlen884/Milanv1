<?php
/**
 * Public Landing Page
 * STRICT ADHERENCE:
 * - NO pricing information (No ₹0, ₹299, ₹700, plans or "4 messages" mentioned anywhere on public landing page)
 * - Uses ONLY landing_marketing_profiles for fictional marketing cards
 * - High-end modern Indian aesthetic: Rose + White, elegant typography
 */
?>

<!-- Hero Section -->
<section class="relative overflow-hidden pt-12 pb-20 md:pt-20 md:pb-28">
    <!-- Subtle Ambient Glow -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] bg-rose-200/40 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="absolute top-1/3 right-10 w-[350px] h-[350px] bg-amber-100/40 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 text-center">
        <!-- Trust Pill -->
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-rose-50 border border-rose-200 text-brand-700 text-xs font-bold uppercase tracking-wider mb-6 shadow-sm">
            <i data-lucide="shield-check" class="w-4 h-4 text-brand-600"></i>
            <span>India's Verified Relationship Platform</span>
        </div>

        <!-- Main Headline -->
        <h1 class="font-serif text-4xl sm:text-6xl md:text-7xl font-bold tracking-tight text-slate-900 leading-[1.15] max-w-4xl mx-auto">
            Find Someone Who Understands <span class="bg-gradient-to-r from-brand-600 via-rose-500 to-amber-600 bg-clip-text text-transparent">Your World</span>
        </h1>

        <!-- Subheadline -->
        <p class="mt-6 text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto font-normal leading-relaxed">
            Designed for genuine Indians seeking meaningful love, compatibility, and real companionship. Real profiles, government-verified trust, and zero noise.
        </p>

        <!-- CTA Buttons -->
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto">
            <a href="/register" class="w-full sm:w-auto px-8 py-4 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-bold text-base shadow-lg shadow-brand-600/30 hover:shadow-brand-600/40 transition-all flex items-center justify-center gap-2.5">
                <span>Create Your Profile</span>
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </a>
            <a href="/login" class="w-full sm:w-auto px-8 py-4 rounded-full bg-white hover:bg-slate-50 text-slate-700 font-bold text-base border border-slate-200 shadow-sm transition-all flex items-center justify-center">
                <span>Sign In</span>
            </a>
        </div>

        <!-- Trust Badges -->
        <div class="mt-12 flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-xs font-semibold text-slate-500">
            <div class="flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4 text-emerald-600 stroke-[3]"></i>
                <span>Strict 18+ Community</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4 text-emerald-600 stroke-[3]"></i>
                <span>Optional KYC Verification</span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4 text-emerald-600 stroke-[3]"></i>
                <span>Encrypted & Private</span>
            </div>
        </div>
    </div>
</section>

<!-- Featured Presentation Profiles (From landing_marketing_profiles ONLY) -->
<section class="py-12 bg-white border-y border-rose-100/60 overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600">Authentic Members</span>
                <h2 class="font-serif text-3xl font-bold text-slate-900 mt-1">People with Stories Like Yours</h2>
            </div>
            <p class="text-slate-500 text-sm mt-2 md:mt-0 max-w-sm">
                From Bengaluru to Mumbai, discover accomplished, culturally grounded individuals looking for meaningful commitment.
            </p>
        </div>

        <!-- Horizontal Scroll / Grid of Portrait Marketing Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($marketingProfiles as $profile): ?>
            <div class="group relative rounded-3xl overflow-hidden bg-slate-900 shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <!-- Portrait Image -->
                <div class="aspect-[3/4] w-full overflow-hidden">
                    <img src="<?= htmlspecialchars($profile['photo_url']) ?>" alt="<?= htmlspecialchars($profile['name']) ?>" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500">
                </div>

                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-transparent flex flex-col justify-end p-5 text-white">
                    <!-- Verification Badge -->
                    <div class="flex items-center justify-between mb-2">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/90 text-white backdrop-blur-sm">
                            <i data-lucide="badge-check" class="w-3.5 h-3.5 fill-current"></i>
                            <span><?= htmlspecialchars($profile['badge']) ?></span>
                        </span>
                        <span class="text-xs text-slate-200/90 font-medium flex items-center gap-1">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-rose-400"></i>
                            <?= htmlspecialchars($profile['city']) ?>
                        </span>
                    </div>

                    <!-- Name & Age -->
                    <h3 class="text-xl font-bold font-serif tracking-tight">
                        <?= htmlspecialchars($profile['name']) ?>, <?= (int)$profile['age'] ?>
                    </h3>

                    <!-- Bio -->
                    <p class="text-xs text-slate-300 mt-1 line-clamp-2 leading-relaxed">
                        <?= htmlspecialchars($profile['short_bio']) ?>
                    </p>

                    <!-- Interest Chips -->
                    <div class="flex flex-wrap gap-1.5 mt-3">
                        <?php 
                        $tagList = explode(',', $profile['interests']);
                        foreach (array_slice($tagList, 0, 3) as $tag): 
                        ?>
                        <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-white/20 backdrop-blur-sm text-slate-100">
                            <?= htmlspecialchars(trim($tag)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-20 max-w-6xl mx-auto px-4 sm:px-6">
    <div class="text-center max-w-2xl mx-auto mb-16">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600">Thoughtful Architecture</span>
        <h2 class="font-serif text-3xl sm:text-4xl font-bold text-slate-900 mt-2">How Milan Works</h2>
        <p class="text-slate-600 mt-3 text-base">Simple, respectful, and focused on compatibility.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Step 1 -->
        <div class="p-8 rounded-3xl bg-white border border-rose-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center text-brand-600 mb-6">
                <i data-lucide="user-plus" class="w-7 h-7"></i>
            </div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Step 01</span>
            <h3 class="text-xl font-bold text-slate-900 mt-1 mb-3">Create Your Verified Profile</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Add your passions, languages, city, and authentic photos. Complete identity verification to earn your trusted Verified badge.
            </p>
        </div>

        <!-- Step 2 -->
        <div class="p-8 rounded-3xl bg-white border border-rose-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center text-brand-600 mb-6">
                <i data-lucide="sliders" class="w-7 h-7"></i>
            </div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Step 02</span>
            <h3 class="text-xl font-bold text-slate-900 mt-1 mb-3">Explore With Precision</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Filter by city, interests, lifestyle, and shared values. No endless superficial swiping — browse genuine, complete profiles.
            </p>
        </div>

        <!-- Step 3 -->
        <div class="p-8 rounded-3xl bg-white border border-rose-100 shadow-sm hover:shadow-md transition-shadow">
            <div class="w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center text-brand-600 mb-6">
                <i data-lucide="message-square-heart" class="w-7 h-7"></i>
            </div>
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Step 03</span>
            <h3 class="text-xl font-bold text-slate-900 mt-1 mb-3">Connect On Mutual Interest</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
                Send interests to profiles you appreciate. When the interest is mutual, a match is made. Enjoy seamless, real-time messaging.
            </p>
        </div>
    </div>
</section>

<!-- Safety & Verification Section -->
<section class="py-20 bg-rose-50/50 border-t border-rose-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-brand-600">Your Safety First</span>
                <h2 class="font-serif text-3xl sm:text-4xl font-bold text-slate-900 mt-2 mb-6">
                    A Respectful Space Built For Real Intentions
                </h2>
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-emerald-600 flex-shrink-0 mt-0.5">
                            <i data-lucide="badge-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">Government ID & Selfie Verification</h4>
                            <p class="text-slate-600 text-sm mt-1 leading-relaxed">
                                Our dedicated team reviews verification submissions to maintain high profile authenticity and weed out impersonators.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-brand-600 flex-shrink-0 mt-0.5">
                            <i data-lucide="eye-off" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">Full Privacy & Incognito Controls</h4>
                            <p class="text-slate-600 text-sm mt-1 leading-relaxed">
                                Control your visibility at any time. Opt out of profile visitors tracking or pause your profile while retaining your conversations.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-rose-600 flex-shrink-0 mt-0.5">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900">Instant Block & Report Features</h4>
                            <p class="text-slate-600 text-sm mt-1 leading-relaxed">
                                Maintain complete peace of mind. Any profile behaving disrespectfully can be blocked and reported for immediate review.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visual Safety Card -->
            <div class="p-8 sm:p-10 rounded-3xl bg-white border border-rose-100 shadow-xl relative overflow-hidden">
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100">
                    <div class="w-14 h-14 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 font-bold">
                        <i data-lucide="check-circle-2" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-bold text-emerald-600 tracking-wider">Milan Verified Member</span>
                        <h4 class="text-lg font-bold text-slate-900">Identity-Checked Badge</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Protected with high-grade privacy protocols</p>
                    </div>
                </div>
                <div class="space-y-3 text-sm text-slate-600">
                    <p class="flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-500 stroke-[3]"></i>
                        <span>No exact GPS location or street address ever revealed</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-500 stroke-[3]"></i>
                        <span>End-to-end HTTPS encrypted communication</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-500 stroke-[3]"></i>
                        <span>Zero fake bots or simulated activity</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Frequently Asked Questions -->
<section class="py-20 max-w-4xl mx-auto px-4 sm:px-6">
    <div class="text-center mb-12">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-600">Common Questions</span>
        <h2 class="font-serif text-3xl font-bold text-slate-900 mt-2">Frequently Asked Questions</h2>
    </div>

    <div class="space-y-4">
        <div class="p-6 rounded-2xl bg-white border border-rose-100 shadow-sm">
            <h4 class="font-bold text-slate-900 text-base">Who is Milan Dating designed for?</h4>
            <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                Milan is created for Indian singles aged 18 and older who are looking for serious, meaningful relationships and long-term companionship.
            </p>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-rose-100 shadow-sm">
            <h4 class="font-bold text-slate-900 text-base">How does profile verification work?</h4>
            <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                You can upload a government-issued photo ID along with a real-time verification selfie. Once approved by our team, your profile displays the green Verified badge.
            </p>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-rose-100 shadow-sm">
            <h4 class="font-bold text-slate-900 text-base">Is my phone number or exact address shown to others?</h4>
            <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                Never. Only your chosen display name, age, selected city, and shared profile information are visible to other members. Your contact details and verification files are stored privately.
            </p>
        </div>

        <div class="p-6 rounded-2xl bg-white border border-rose-100 shadow-sm">
            <h4 class="font-bold text-slate-900 text-base">How do matches happen on Milan?</h4>
            <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                When two people express interest in each other's profiles, a mutual match is formed, allowing you to start conversations immediately.
            </p>
        </div>
    </div>
</section>

<!-- Final Pre-Footer Call to Action -->
<section class="py-16 bg-gradient-to-tr from-brand-700 via-rose-600 to-amber-600 text-white text-center">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <h2 class="font-serif text-3xl sm:text-5xl font-bold tracking-tight">Ready to Meet Someone Real?</h2>
        <p class="text-rose-100 mt-4 text-base sm:text-lg max-w-xl mx-auto">
            Join thousands of verified Indian professionals and singles creating genuine connections today.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="/register" class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-brand-700 font-bold text-base hover:bg-rose-50 shadow-lg transition-all">
                Create Your Profile
            </a>
            <a href="/login" class="w-full sm:w-auto px-8 py-4 rounded-full bg-black/20 hover:bg-black/30 text-white font-bold text-base border border-white/30 backdrop-blur-sm transition-all">
                Sign In
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-white border-t border-rose-100/80 py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-brand-600 text-white flex items-center justify-center">
                <i data-lucide="heart" class="w-4 h-4 fill-current"></i>
            </div>
            <span class="font-serif text-xl font-bold text-slate-900">Milan Dating</span>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-6 text-xs font-semibold text-slate-500">
            <a href="/login" class="hover:text-brand-600 transition-colors">Sign In</a>
            <a href="/register" class="hover:text-brand-600 transition-colors">Join Now</a>
            <span>•</span>
            <span class="text-slate-400">Strictly 18+ Only</span>
            <span>•</span>
            <span class="text-slate-400">Made for India</span>
        </div>

        <p class="text-xs text-slate-400 text-center md:text-right">
            &copy; <?= date('Y') ?> Milan Dating. All rights reserved.
        </p>
    </div>
</footer>
