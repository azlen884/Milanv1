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
                    <a href="/messages?id=<?= $c['conversation_id'] ?>" class="p-3.5 flex items-center gap-3 hover:bg-rose-50/50 transition-colors <?= ($activeConversation && (int)$activeConversation['id'] === (int)$c['conversation_id']) ? 'bg-rose-50/80 border-l-4 border-brand-600' : '' ?>">
                        <div class="relative flex-shrink-0">
                            <img src="<?= htmlspecialchars($c['partner_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80') ?>" alt="" class="w-12 h-12 rounded-full object-cover ring-1 ring-slate-200">
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
                        <img src="<?= htmlspecialchars($activePartner['primary_photo'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80') ?>" alt="" class="w-10 h-10 rounded-full object-cover ring-1 ring-slate-200">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-sm text-slate-900"><?= htmlspecialchars($activePartner['name']) ?></span>
                                <?php if (($activePartner['kyc_status'] ?? '') === 'verified'): ?>
                                <i data-lucide="badge-check" class="w-4 h-4 fill-emerald-500 text-white"></i>
                                <?php endif; ?>
                            </div>
                            <span class="text-[11px] text-slate-400"><?= htmlspecialchars($activePartner['city']) ?></span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-2">
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

                <!-- Text Input -->
                <input type="text" id="message-text" placeholder="Type a thoughtful message..." autocomplete="off"
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

<script>
    const convId = <?= $activeConversation ? (int)$activeConversation['id'] : 0 ?>;
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
        }
    }

    // Send Text Message
    async function sendText(e) {
        e.preventDefault();
        const inp = document.getElementById('message-text');
        const text = inp.value.trim();
        if (!text || !convId) return;

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

    // Polling for incoming messages
    if (convId > 0) {
        setInterval(async () => {
            try {
                const res = await fetch(`/api/chat/poll?id=${convId}&after=${lastMsgId}`);
                const data = await res.json();
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(m => {
                        appendMessage(m, false);
                    });
                }
            } catch (e) {}
        }, 3000);
    }
</script>
