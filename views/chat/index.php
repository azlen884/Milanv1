<?php
// views/chat/index.php
?>
<div class="max-w-6xl mx-auto px-2 sm:px-6 py-4 sm:py-6 h-[calc(100vh-5rem)]">
    
    <div class="bg-white rounded-3xl border border-rose-100 shadow-xl overflow-hidden h-full flex flex-col md:flex-row">
        
        <!-- Left: Conversations List (Hidden on mobile when chat is open) -->
        <div class="w-full md:w-80 lg:w-96 border-r border-slate-100 flex flex-col h-full bg-white <?= $activeConversation ? 'hidden md:flex' : 'flex' ?>">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h1 class="font-serif text-xl font-bold text-slate-900">Messages</h1>
                
                <!-- Daily Limit Indicator for Free Users (Rule 19) -->
                <?php if (!$msgPermission['is_paid']): ?>
                <div class="px-2.5 py-1 rounded-full bg-rose-50 border border-rose-200/60 text-[11px] font-semibold text-brand-700" title="Free accounts receive 4 sent messages daily">
                    <span><?= (int)$msgPermission['used_today'] ?> / 4 daily sent</span>
                </div>
                <?php else: ?>
                <div class="px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200 text-[11px] font-bold text-amber-800 flex items-center gap-1">
                    <i data-lucide="crown" class="w-3 h-3 text-amber-600"></i>
                    <span>Unlimited</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Conversations Scroll Area -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
                <?php if (empty($conversations)): ?>
                <div class="p-8 text-center text-slate-400">
                    <i data-lucide="message-square" class="w-10 h-10 mx-auto mb-2 text-rose-200"></i>
                    <p class="text-xs font-semibold">No conversations yet</p>
                    <p class="text-[11px] text-slate-400 mt-1">Connect with members in Discover to start chatting!</p>
                </div>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                    <?php $cOnline = \App\Helpers\Auth::isOnline($c['last_active_at'] ?? null); ?>
                    <a href="/messages?id=<?= $c['conversation_id'] ?>" class="p-3.5 flex items-center gap-3 hover:bg-rose-50/50 transition-colors <?= ($activeConversation && (int)$activeConversation['id'] === (int)$c['conversation_id']) ? 'bg-rose-50/80 border-l-4 border-brand-600' : '' ?>">
                        <div class="relative flex-shrink-0">
                            <img src="<?= htmlspecialchars($c['partner_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80') ?>" alt="" class="w-12 h-12 rounded-full object-cover ring-1 ring-slate-200">
                            <!-- Online indicator dot on conversation item -->
                            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full ring-2 ring-white <?= $cOnline ? 'bg-emerald-500' : 'bg-slate-300' ?>" title="<?= $cOnline ? 'Online' : 'Offline' ?>"></span>
                            <?php if (($c['unread_count'] ?? 0) > 0): ?>
                            <span class="absolute top-0 right-0 w-3 h-3 bg-brand-600 rounded-full ring-2 ring-white"></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-slate-900 truncate"><?= htmlspecialchars($c['partner_name']) ?></span>
                                <span class="text-[10px] text-slate-400"><?= date('H:i', strtotime($c['last_message_at'])) ?></span>
                            </div>
                            <p class="text-xs text-slate-500 truncate mt-0.5 <?= (($c['unread_count'] ?? 0) > 0) ? 'font-bold text-slate-900' : '' ?>">
                                <?= ($c['last_message_type'] === 'voice') ? '🎤 Voice message' : htmlspecialchars($c['last_message_body'] ?? 'Say hello!') ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Active Chat Area -->
        <div class="flex-1 flex flex-col h-full bg-[#FBF9F9] <?= !$activeConversation ? 'hidden md:flex' : 'flex' ?>">
            <?php if (!$activeConversation): ?>
            <!-- Blank State -->
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center text-slate-400">
                <div class="w-16 h-16 rounded-full bg-rose-50 text-brand-600 flex items-center justify-center mb-4">
                    <i data-lucide="message-circle" class="w-8 h-8"></i>
                </div>
                <h3 class="font-serif text-xl font-bold text-slate-900">Select a Conversation</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-xs">
                    Choose an existing match on the left or explore Discover to connect with someone new.
                </p>
            </div>
            <?php else: ?>
            <!-- Chat Active Header -->
            <div class="p-3.5 px-4 sm:px-6 bg-white border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="/messages" class="md:hidden p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <a href="/profile?id=<?= $activePartner['id'] ?>" class="flex items-center gap-3">
                        <div class="relative">
                            <img src="<?= htmlspecialchars($activePartner['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80') ?>" alt="" class="w-10 h-10 rounded-full object-cover ring-1 ring-slate-200">
                            <!-- Live online presence indicator on active partner avatar -->
                            <?php $partnerIsOnline = \App\Helpers\Auth::isOnline($activePartner['last_active_at'] ?? null); ?>
                            <span id="partner-status-dot" class="absolute bottom-0 right-0 w-3 h-3 rounded-full ring-2 ring-white <?= $partnerIsOnline ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-sm text-slate-900"><?= htmlspecialchars($activePartner['name']) ?></span>
                                <?php if (($activePartner['kyc_status'] ?? '') === 'verified'): ?>
                                <i data-lucide="badge-check" class="w-4 h-4 fill-emerald-500 text-white"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-1.5 text-[11px]">
                                <span id="partner-status-text" class="<?= $partnerIsOnline ? 'text-emerald-600 font-semibold' : 'text-slate-400' ?>"><?= $partnerIsOnline ? 'Online' : 'Offline' ?></span>
                                <span class="text-slate-300">•</span>
                                <span class="text-slate-400"><?= htmlspecialchars($activePartner['city']) ?></span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Part 4: Real Video Call Trigger Button -->
                    <button type="button" onclick="startVideoCall()" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs rounded-xl flex items-center gap-1.5 transition-colors border border-rose-200/60 shadow-xs" title="Start Real Video Call">
                        <i data-lucide="video" class="w-4 h-4 text-rose-600"></i>
                        <span class="hidden sm:inline">Video Call</span>
                    </button>
                    <a href="/profile?id=<?= $activePartner['id'] ?>" class="p-2 text-slate-500 hover:text-slate-800 rounded-xl hover:bg-slate-50" title="View Profile">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

            <!-- Messages Stream Area -->
            <div id="messages-container" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3">
                <?php foreach ($messages as $m): ?>
                    <?php $isMe = ((int)$m['sender_id'] === (int)$currentUser['id']); ?>
                    <div class="flex <?= $isMe ? 'justify-end' : 'justify-start' ?>">
                        <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm <?= $isMe ? 'bg-brand-600 text-white shadow-sm rounded-br-none' : 'bg-white text-slate-800 border border-slate-200/80 shadow-sm rounded-bl-none' ?>">
                            
                            <?php if ($m['type'] === 'voice'): ?>
                            <!-- Real Audio Player -->
                            <div class="flex items-center gap-2 py-1">
                                <audio controls class="h-8 max-w-[220px]">
                                    <source src="<?= htmlspecialchars($m['media_url']) ?>" type="audio/webm">
                                    Your browser does not support audio playback.
                                </audio>
                                <span class="text-[10px] <?= $isMe ? 'text-rose-200' : 'text-slate-400' ?>"><?= (int)$m['duration_seconds'] ?>s</span>
                            </div>
                            <?php else: ?>
                            <p class="whitespace-pre-line leading-relaxed"><?= htmlspecialchars($m['body']) ?></p>
                            <?php endif; ?>

                            <div class="text-[9px] mt-1 text-right <?= $isMe ? 'text-rose-200' : 'text-slate-400' ?>">
                                <?= date('H:i', strtotime($m['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Daily Limit Banner (If reached) -->
            <div id="limit-banner" class="hidden p-3 bg-amber-50 border-t border-amber-200 text-center text-xs text-amber-900">
                <span class="font-bold">Daily Free Limit Reached (4/4 messages used today).</span>
                <a href="/upgrade" class="ml-2 font-bold underline text-brand-700">Upgrade to Premium for Unlimited Messaging</a>
            </div>

            <!-- Part 6: Real-time Typing Indicator -->
            <div id="typing-indicator" class="hidden px-5 py-2 bg-white/95 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-500">
                <div class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-bounce [animation-delay:0.4s]"></span>
                </div>
                <span class="italic"><strong class="font-semibold text-slate-700"><?= htmlspecialchars($activePartner['name']) ?></strong> is typing...</span>
            </div>

            <!-- Quick Emoji Bar -->
            <div class="px-4 py-1.5 bg-white border-t border-slate-100 flex items-center gap-2 overflow-x-auto scrollbar-none">
                <?php foreach (['😊', '❤️', '✨', '🌹', '👍', '🎉', '☕', '🙏'] as $em): ?>
                <button type="button" onclick="insertEmoji('<?= $em ?>')" class="p-1 hover:bg-slate-100 rounded-lg text-base leading-none transition-transform hover:scale-125">
                    <?= $em ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Recording Status Banner -->
            <div id="recording-bar" class="hidden px-4 py-2 bg-rose-50 border-t border-rose-200 flex items-center justify-between text-xs text-brand-700">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 bg-red-600 rounded-full animate-pulse"></span>
                    <span class="font-bold">Recording Voice Message: <span id="record-timer">00:00</span></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="cancelRecording()" class="px-3 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs">
                        Cancel
                    </button>
                    <button type="button" onclick="stopAndSendRecording()" class="px-3 py-1 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs flex items-center gap-1">
                        <i data-lucide="send" class="w-3 h-3"></i> Send
                    </button>
                </div>
            </div>

            <!-- Message Input Form -->
            <form id="chat-input-form" onsubmit="sendText(event)" class="p-3 sm:p-4 bg-white border-t border-slate-100 flex items-center gap-2">
                <input type="hidden" id="conversation-id" value="<?= (int)$activeConversation['id'] ?>">

                <!-- Voice Message Record Trigger (Rule 20) -->
                <button type="button" id="mic-btn" onclick="startRecording()" title="Record Voice Message" class="p-2.5 rounded-full text-slate-500 hover:text-brand-600 hover:bg-rose-50 transition-colors">
                    <i data-lucide="mic" class="w-5 h-5"></i>
                </button>

                <!-- Text Input with typing listener -->
                <input type="text" id="message-text" oninput="handleTypingInput()" placeholder="Type a thoughtful message..." autocomplete="off"
                       class="flex-1 px-4 py-2.5 rounded-full bg-slate-100 border-none text-sm outline-none focus:ring-2 focus:ring-brand-500/30 text-slate-900 placeholder:text-slate-400">

                <!-- Send Button -->
                <button type="submit" id="send-btn" class="p-2.5 rounded-full bg-brand-600 hover:bg-brand-700 text-white shadow-md shadow-brand-600/20 transition-all">
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- PART 4: REAL WEBRTC VIDEO CALL MODALS -->
<!-- ========================================================================= -->

<!-- 1. Outgoing / Active Video Call Modal -->
<div id="video-call-modal" class="fixed inset-0 z-50 bg-slate-950 flex flex-col hidden">
    <!-- Top Bar -->
    <div class="absolute top-0 inset-x-0 z-30 p-4 sm:p-6 flex items-center justify-between bg-gradient-to-b from-black/80 to-transparent">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-rose-600/30 ring-2 ring-white/30 flex items-center justify-center text-white">
                <i data-lucide="video" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base"><?= htmlspecialchars($activePartner['name'] ?? 'Partner') ?></h3>
                <p id="call-status-label" class="text-rose-200 text-xs font-medium">Connecting video call...</p>
            </div>
        </div>
        <div id="call-duration-badge" class="hidden px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white text-xs font-mono font-semibold">
            00:00
        </div>
    </div>

    <!-- Video Stage -->
    <div class="relative flex-1 w-full h-full overflow-hidden bg-slate-950 flex items-center justify-center">
        <!-- Remote Video (Full Screen) -->
        <video id="remote-video" autoplay playsinline class="w-full h-full object-cover"></video>

        <!-- Remote Waiting Avatar Placeholder (Shown while ringing) -->
        <div id="remote-waiting-placeholder" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950 z-10">
            <img src="<?= htmlspecialchars($activePartner['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80') ?>" alt="" class="w-24 h-24 sm:w-32 sm:h-32 rounded-full object-cover ring-4 ring-rose-500/50 shadow-2xl animate-pulse mb-4">
            <h4 class="text-white font-bold text-lg"><?= htmlspecialchars($activePartner['name'] ?? 'Partner') ?></h4>
            <p id="call-calling-subtext" class="text-slate-400 text-xs mt-1">Ringing user device...</p>
        </div>

        <!-- Local Video (Picture-in-Picture) -->
        <video id="local-video" autoplay muted playsinline class="w-28 sm:w-40 h-38 sm:h-52 object-cover rounded-2xl shadow-2xl absolute top-20 sm:top-24 right-4 sm:right-6 z-20 border-2 border-white/60 bg-slate-900"></video>
    </div>

    <!-- Bottom Controls Bar -->
    <div class="absolute bottom-0 inset-x-0 z-30 p-6 flex items-center justify-center gap-4 bg-gradient-to-t from-black/80 to-transparent">
        <!-- Toggle Microphone -->
        <button type="button" id="toggle-mic-btn" onclick="toggleAudio()" class="w-12 h-12 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-md text-white flex items-center justify-center transition-colors">
            <i data-lucide="mic" id="mic-icon" class="w-5 h-5"></i>
        </button>

        <!-- End Call (Red) -->
        <button type="button" onclick="endCall()" class="w-14 h-14 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center shadow-lg shadow-rose-600/40 transition-transform hover:scale-105">
            <i data-lucide="phone-off" class="w-6 h-6"></i>
        </button>

        <!-- Toggle Camera -->
        <button type="button" id="toggle-cam-btn" onclick="toggleVideo()" class="w-12 h-12 rounded-full bg-white/20 hover:bg-white/30 backdrop-blur-md text-white flex items-center justify-center transition-colors">
            <i data-lucide="camera" id="cam-icon" class="w-5 h-5"></i>
        </button>
    </div>
</div>

<!-- 2. Incoming Call Modal -->
<div id="incoming-call-modal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center shadow-2xl border border-rose-100 flex flex-col items-center">
        <div class="relative mb-4">
            <img id="incoming-caller-photo" src="/uploads/profiles/default_female.jpg" alt="" class="w-24 h-24 rounded-full object-cover ring-4 ring-rose-500 shadow-xl">
            <span class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-rose-600 text-white flex items-center justify-center shadow-md animate-bounce">
                <i data-lucide="video" class="w-4 h-4"></i>
            </span>
        </div>
        <h3 id="incoming-caller-name" class="font-bold text-lg text-slate-900">Incoming Video Call</h3>
        <p class="text-xs text-rose-600 font-semibold mt-0.5 animate-pulse">Milan Live Video Calling...</p>

        <div class="flex items-center justify-center gap-6 mt-8 w-full">
            <!-- Decline Button -->
            <button type="button" onclick="declineIncomingCall()" class="flex-1 py-3 px-4 rounded-2xl bg-slate-100 hover:bg-rose-50 text-rose-600 font-bold text-xs flex items-center justify-center gap-2 transition-colors">
                <i data-lucide="phone-off" class="w-4 h-4"></i>
                <span>Decline</span>
            </button>
            <!-- Accept Button -->
            <button type="button" onclick="acceptIncomingCall()" class="flex-1 py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md shadow-emerald-600/30 transition-colors">
                <i data-lucide="video" class="w-4 h-4"></i>
                <span>Accept</span>
            </button>
        </div>
    </div>
</div>

<script>
    const convId = <?= $activeConversation ? (int)$activeConversation['id'] : 0 ?>;
    const activePartnerId = <?= $activePartner ? (int)$activePartner['id'] : 0 ?>;
    let lastMsgId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;
    let mediaRecorder = null;
    let audioChunks = [];
    let recordInterval = null;
    let recordSeconds = 0;

    // Scroll to bottom of message stream
    function scrollToBottom() {
        const c = document.getElementById('messages-container');
        if (c) c.scrollTop = c.scrollHeight;
    }
    scrollToBottom();

    function insertEmoji(em) {
        const inp = document.getElementById('message-text');
        if (inp) {
            inp.value += em;
            inp.focus();
            handleTypingInput();
        }
    }

    // =========================================================================
    // PART 6: TYPING INDICATOR (DEBOUNCED SENDER LOGIC)
    // =========================================================================
    let typingTimeout = null;
    let lastTypingSentAt = 0;

    function handleTypingInput() {
        const now = Date.now();
        if (now - lastTypingSentAt > 2000) {
            sendTypingStatus(1);
            lastTypingSentAt = now;
        }

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            sendTypingStatus(0);
        }, 2500);
    }

    async function sendTypingStatus(isTyping) {
        if (!convId) return;
        const formData = new FormData();
        formData.append('conversation_id', convId);
        formData.append('is_typing', isTyping);
        formData.append('csrf_token', getCsrfToken());
        try {
            fetch('/api/chat/typing', { method: 'POST', body: formData });
        } catch (e) {}
    }

    // Send Text Message
    async function sendText(e) {
        e.preventDefault();
        const inp = document.getElementById('message-text');
        const text = inp.value.trim();
        if (!text || !convId) return;

        // Clear typing indicator immediately
        clearTimeout(typingTimeout);
        sendTypingStatus(0);

        const btn = document.getElementById('send-btn');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('conversation_id', convId);
        formData.append('body', text);
        formData.append('csrf_token', getCsrfToken());

        try {
            const res = await fetch('/api/chat/send-text', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                inp.value = '';
                appendMessage({
                    id: data.message_id,
                    type: 'text',
                    body: text,
                    created_at: new Date().toISOString()
                }, true);
            } else {
                if (data.limit_exceeded) {
                    document.getElementById('limit-banner')?.classList.remove('hidden');
                }
                showToast(data.error || 'Failed to send message.', 'error');
            }
        } catch (err) {
            showToast('Network error while sending.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // Append Message to Stream UI
    function appendMessage(m, isMe) {
        lastMsgId = Math.max(lastMsgId, m.id);
        const container = document.getElementById('messages-container');
        if (!container) return;

        const wrapper = document.createElement('div');
        wrapper.className = `flex ${isMe ? 'justify-end' : 'justify-start'}`;
        
        const nowStr = new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        let content = '';
        if (m.type === 'voice') {
            content = `
                <div class="flex items-center gap-2 py-1">
                    <audio controls class="h-8 max-w-[220px]">
                        <source src="${m.media_url}" type="audio/webm">
                    </audio>
                    <span class="text-[10px] ${isMe ? 'text-rose-200' : 'text-slate-400'}">${m.duration_seconds || 1}s</span>
                </div>
            `;
        } else {
            content = `<p class="whitespace-pre-line leading-relaxed">${escapeHtml(m.body)}</p>`;
        }

        wrapper.innerHTML = `
            <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm ${isMe ? 'bg-brand-600 text-white shadow-sm rounded-br-none' : 'bg-white text-slate-800 border border-slate-200/80 shadow-sm rounded-bl-none'}">
                ${content}
                <div class="text-[9px] mt-1 text-right ${isMe ? 'text-rose-200' : 'text-slate-400'}">
                    ${nowStr}
                </div>
            </div>
        `;
        container.appendChild(wrapper);
        scrollToBottom();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // REAL VOICE MESSAGE RECORDING (Rule 20)
    async function startRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Your browser does not support audio recording.');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.start();
            recordSeconds = 0;
            document.getElementById('recording-bar').classList.remove('hidden');
            document.getElementById('chat-input-form').classList.add('opacity-50', 'pointer-events-none');

            recordInterval = setInterval(() => {
                recordSeconds++;
                const mins = String(Math.floor(recordSeconds / 60)).padStart(2, '0');
                const secs = String(recordSeconds % 60).padStart(2, '0');
                document.getElementById('record-timer').textContent = `${mins}:${secs}`;
                if (recordSeconds >= 60) stopAndSendRecording();
            }, 1000);

        } catch (err) {
            alert('Microphone permission denied or unavailable: ' + err.message);
        }
    }

    function cancelRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(t => t.stop());
        }
        clearInterval(recordInterval);
        document.getElementById('recording-bar').classList.add('hidden');
        document.getElementById('chat-input-form').classList.remove('opacity-50', 'pointer-events-none');
    }

    function stopAndSendRecording() {
        if (!mediaRecorder || mediaRecorder.state === 'inactive') return;

        clearInterval(recordInterval);
        const duration = recordSeconds;

        mediaRecorder.onstop = async () => {
            mediaRecorder.stream.getTracks().forEach(t => t.stop());
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });

            const formData = new FormData();
            formData.append('conversation_id', convId);
            formData.append('duration_seconds', duration);
            formData.append('audio_data', audioBlob, 'voice.webm');
            formData.append('csrf_token', getCsrfToken());

            try {
                const res = await fetch('/api/chat/send-voice', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    appendMessage({
                        id: data.message_id,
                        type: 'voice',
                        media_url: URL.createObjectURL(audioBlob),
                        duration_seconds: duration,
                        created_at: new Date().toISOString()
                    }, true);
                } else {
                    if (data.limit_exceeded) {
                        document.getElementById('limit-banner')?.classList.remove('hidden');
                    }
                    showToast(data.error || 'Voice message limit reached.', 'error');
                }
            } catch (e) {
                showToast('Network error uploading audio.', 'error');
            } finally {
                document.getElementById('recording-bar').classList.add('hidden');
                document.getElementById('chat-input-form').classList.remove('opacity-50', 'pointer-events-none');
            }
        };

        mediaRecorder.stop();
    }

    // =========================================================================
    // PART 4: WEBRTC REAL VIDEO CALLING CLIENT ENGINE
    // =========================================================================
    let peerConnection = null;
    let localStream = null;
    let remoteStream = null;
    let currentCallId = null;
    let callPollInterval = null;
    let callTimerInterval = null;
    let callElapsedSeconds = 0;
    let isMicMuted = false;
    let isCamOff = false;
    let incomingCallData = null;

    const rtcConfig = {
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ]
    };

    // Caller Starts Video Call
    async function startVideoCall() {
        if (!convId || !activePartnerId) return;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Camera and microphone are not supported or blocked in this browser.');
            return;
        }

        try {
            // 1. Acquire local camera and mic
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('local-video').srcObject = localStream;

            // 2. Open Call Modal in Calling state
            document.getElementById('video-call-modal').classList.remove('hidden');
            document.getElementById('remote-waiting-placeholder').classList.remove('hidden');
            document.getElementById('call-status-label').textContent = 'Calling partner...';
            document.getElementById('call-calling-subtext').textContent = 'Waiting for answer...';
            document.getElementById('call-duration-badge').classList.add('hidden');

            // 3. Initialize PeerConnection
            peerConnection = new RTCPeerConnection(rtcConfig);
            remoteStream = new MediaStream();
            document.getElementById('remote-video').srcObject = remoteStream;

            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });

            peerConnection.ontrack = (event) => {
                event.streams[0].getTracks().forEach(track => {
                    remoteStream.addTrack(track);
                });
                document.getElementById('remote-waiting-placeholder').classList.add('hidden');
            };

            const localCandidates = [];
            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    if (currentCallId) {
                        sendIceCandidate(currentCallId, event.candidate);
                    } else {
                        localCandidates.push(event.candidate.toJSON());
                    }
                }
            };

            // 4. Create Offer
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            // 5. Send Offer to Signaling Server
            const formData = new FormData();
            formData.append('conversation_id', convId);
            formData.append('sdp_offer', offer.sdp);
            formData.append('candidates', JSON.stringify(localCandidates));
            formData.append('csrf_token', getCsrfToken());

            const res = await fetch('/api/call/start', { method: 'POST', body: formData });
            const data = await res.json();

            if (!data.success) {
                endCall();
                showToast(data.error || 'User is unavailable.', 'error');
                return;
            }

            currentCallId = data.call_id;

            // 6. Start Polling for Answer
            startCallPolling(currentCallId, true);

        } catch (err) {
            console.error('Call initialization failed:', err);
            endCall();
            alert('Camera / Microphone permission is required to start a video call: ' + err.message);
        }
    }

    // Send an ICE candidate to signaling backend
    async function sendIceCandidate(callId, candidate) {
        const formData = new FormData();
        formData.append('call_id', callId);
        formData.append('candidate', JSON.stringify(candidate.toJSON ? candidate.toJSON() : candidate));
        formData.append('csrf_token', getCsrfToken());
        try {
            fetch('/api/call/candidate', { method: 'POST', body: formData });
        } catch (e) {}
    }

    // Polling Active Call Status (Wait for accept, connect stream, monitor hangup)
    function startCallPolling(callId, isCaller) {
        clearInterval(callPollInterval);
        callPollInterval = setInterval(async () => {
            if (!currentCallId) {
                clearInterval(callPollInterval);
                return;
            }

            try {
                const res = await fetch(`/api/call/check?call_id=${callId}`);
                const data = await res.json();

                if (!data.success || data.status === 'ended' || data.status === 'rejected' || data.status === 'missed') {
                    clearInterval(callPollInterval);
                    const reason = (data.status === 'rejected') ? 'Call was declined.' : (data.status === 'missed' ? 'Call was unanswered.' : 'Call ended.');
                    showToast(reason, 'error');
                    endCall();
                    return;
                }

                if (isCaller && data.status === 'accepted' && peerConnection) {
                    if (peerConnection.signalingState === 'have-local-offer' && data.call.sdp_answer) {
                        const answer = new RTCSessionDescription({ type: 'answer', sdp: data.call.sdp_answer });
                        await peerConnection.setRemoteDescription(answer);

                        // Add receiver candidates
                        if (data.call.receiver_candidates) {
                            try {
                                const cands = JSON.parse(data.call.receiver_candidates);
                                cands.forEach(c => peerConnection.addIceCandidate(new RTCIceCandidate(c)));
                            } catch (e) {}
                        }

                        // Connected!
                        onCallConnected();
                    }
                }
            } catch (e) {}
        }, 1500);
    }

    // When WebRTC connection is officially established
    function onCallConnected() {
        document.getElementById('remote-waiting-placeholder').classList.add('hidden');
        document.getElementById('call-status-label').textContent = 'Connected';
        document.getElementById('call-duration-badge').classList.remove('hidden');

        clearInterval(callTimerInterval);
        callElapsedSeconds = 0;
        callTimerInterval = setInterval(() => {
            callElapsedSeconds++;
            const mins = String(Math.floor(callElapsedSeconds / 60)).padStart(2, '0');
            const secs = String(callElapsedSeconds % 60).padStart(2, '0');
            document.getElementById('call-duration-badge').textContent = `${mins}:${secs}`;
        }, 1000);
    }

    // Handle Incoming Call Notification
    function handleIncomingCall(call) {
        if (currentCallId || document.getElementById('video-call-modal').classList.contains('hidden') === false) {
            return; // Already in a call
        }

        incomingCallData = call;
        document.getElementById('incoming-caller-name').textContent = call.caller_name || 'Incoming Call';
        if (call.caller_photo) {
            document.getElementById('incoming-caller-photo').src = call.caller_photo;
        }
        document.getElementById('incoming-call-modal').classList.remove('hidden');
    }

    // Decline Incoming Call
    async function declineIncomingCall() {
        if (!incomingCallData) return;
        const callId = incomingCallData.id;
        document.getElementById('incoming-call-modal').classList.add('hidden');
        incomingCallData = null;

        const formData = new FormData();
        formData.append('call_id', callId);
        formData.append('action', 'reject');
        formData.append('csrf_token', getCsrfToken());
        try {
            fetch('/api/call/respond', { method: 'POST', body: formData });
        } catch (e) {}
    }

    // Accept Incoming Call
    async function acceptIncomingCall() {
        if (!incomingCallData) return;
        const call = incomingCallData;
        currentCallId = call.id;
        document.getElementById('incoming-call-modal').classList.add('hidden');

        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('local-video').srcObject = localStream;

            document.getElementById('video-call-modal').classList.remove('hidden');
            document.getElementById('remote-waiting-placeholder').classList.add('hidden');
            document.getElementById('call-status-label').textContent = 'Connecting...';

            peerConnection = new RTCPeerConnection(rtcConfig);
            remoteStream = new MediaStream();
            document.getElementById('remote-video').srcObject = remoteStream;

            localStream.getTracks().forEach(t => peerConnection.addTrack(t, localStream));

            peerConnection.ontrack = (event) => {
                event.streams[0].getTracks().forEach(t => remoteStream.addTrack(t));
            };

            const localCandidates = [];
            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    sendIceCandidate(currentCallId, event.candidate);
                }
            };

            // Set remote offer
            await peerConnection.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: call.sdp_offer }));

            // Add caller candidates
            if (call.caller_candidates) {
                try {
                    const cands = JSON.parse(call.caller_candidates);
                    cands.forEach(c => peerConnection.addIceCandidate(new RTCIceCandidate(c)));
                } catch (e) {}
            }

            // Create Answer
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            // Send Answer
            const formData = new FormData();
            formData.append('call_id', currentCallId);
            formData.append('action', 'accept');
            formData.append('sdp_answer', answer.sdp);
            formData.append('candidates', JSON.stringify(localCandidates));
            formData.append('csrf_token', getCsrfToken());

            const res = await fetch('/api/call/respond', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                onCallConnected();
                startCallPolling(currentCallId, false);
            } else {
                endCall();
                showToast(data.error || 'Failed to connect call.', 'error');
            }

        } catch (err) {
            console.error('Accept call error:', err);
            endCall();
            alert('Failed to connect video call: ' + err.message);
        }
    }

    // Toggle Local Audio
    function toggleAudio() {
        if (!localStream) return;
        isMicMuted = !isMicMuted;
        localStream.getAudioTracks().forEach(t => { t.enabled = !isMicMuted; });
        const btn = document.getElementById('toggle-mic-btn');
        btn.classList.toggle('bg-rose-600', isMicMuted);
    }

    // Toggle Local Camera
    function toggleVideo() {
        if (!localStream) return;
        isCamOff = !isCamOff;
        localStream.getVideoTracks().forEach(t => { t.enabled = !isCamOff; });
        const btn = document.getElementById('toggle-cam-btn');
        btn.classList.toggle('bg-rose-600', isCamOff);
    }

    // Cleanly End Video Call
    function endCall() {
        if (currentCallId) {
            const formData = new FormData();
            formData.append('call_id', currentCallId);
            formData.append('csrf_token', getCsrfToken());
            try { fetch('/api/call/end', { method: 'POST', body: formData }); } catch (e) {}
            currentCallId = null;
        }

        clearInterval(callPollInterval);
        clearInterval(callTimerInterval);

        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }

        document.getElementById('local-video').srcObject = null;
        document.getElementById('remote-video').srcObject = null;
        document.getElementById('video-call-modal').classList.add('hidden');
        document.getElementById('incoming-call-modal').classList.add('hidden');
        document.getElementById('call-duration-badge').classList.add('hidden');
    }

    // =========================================================================
    // HEARTBEAT & MESSAGE POLLING LOOP (PRESENCE + TYPING + CALLS + MESSAGES)
    // =========================================================================
    if (convId > 0) {
        setInterval(async () => {
            try {
                const res = await fetch(`/api/chat/poll?id=${convId}&after=${lastMsgId}`);
                const data = await res.json();

                // 1. Deliver new chat messages
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(m => {
                        appendMessage(m, false);
                    });
                }

                // 2. Part 5: Update Online/Offline Presence
                if (data.partner_online !== undefined) {
                    const dot = document.getElementById('partner-status-dot');
                    const text = document.getElementById('partner-status-text');
                    if (data.partner_online) {
                        dot.className = 'absolute bottom-0 right-0 w-3 h-3 rounded-full ring-2 ring-white bg-emerald-500';
                        text.className = 'text-emerald-600 font-semibold';
                        text.textContent = 'Online';
                    } else {
                        dot.className = 'absolute bottom-0 right-0 w-3 h-3 rounded-full ring-2 ring-white bg-slate-300';
                        text.className = 'text-slate-400';
                        text.textContent = 'Offline';
                    }
                }

                // 3. Part 6: Update Partner Typing Indicator
                const typingBox = document.getElementById('typing-indicator');
                if (typingBox) {
                    if (data.is_typing) {
                        typingBox.classList.remove('hidden');
                    } else {
                        typingBox.classList.add('hidden');
                    }
                }

                // 4. Part 4: Check for incoming calls
                if (!currentCallId) {
                    const callCheckRes = await fetch(`/api/call/check?conversation_id=${convId}`);
                    const callCheck = await callCheckRes.json();
                    if (callCheck.incoming && callCheck.call) {
                        handleIncomingCall(callCheck.call);
                    }
                }

            } catch (e) {}
        }, 2200);
    }
</script>
