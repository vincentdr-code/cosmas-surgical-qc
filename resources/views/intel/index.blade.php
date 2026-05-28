<x-app-layout>
    <x-slot name="header">
        <span style="font-family:'JetBrains Mono',monospace; font-size:11px; font-weight:700; color:#8A9BAE; letter-spacing:0.12em; text-transform:uppercase;">
            [ DAMIAN · QC INTELLIGENCE ] &nbsp;·&nbsp; <span style="font-weight:400; color:rgba(138,155,174,0.5);">Named after · Patron Saint of Medicine &amp; Surgery · twins with St. Cosmas</span>
        </span>
        <a href="{{ route('dashboard') }}"
           style="font-family:'JetBrains Mono',monospace; font-size:11px; color:#8A9BAE; text-decoration:none; letter-spacing:0.08em;">
            ← DASHBOARD
        </a>
    </x-slot>

    <style>
        .damian-chat-wrap {
            max-width: 860px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        /* ── Metadata strip ──────────────────────────────────────── */
        .damian-meta-strip {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1px;
            background: rgba(201,150,62,0.1);
        }
        .damian-meta-cell {
            background: #060F1E;
            padding: 10px 16px;
            text-align: center;
        }
        .damian-meta-cell .val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.06em;
            margin-bottom: 2px;
        }
        .damian-meta-cell .lbl {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #8A9BAE;
        }

        /* ── Chat shell ──────────────────────────────────────────── */
        .damian-shell {
            background: #060F1E;
            border: 1px solid rgba(201,150,62,0.2);
            display: flex;
            flex-direction: column;
            height: 580px;
        }

        /* Shell header */
        .damian-shell-header {
            padding: 10px 20px;
            border-bottom: 1px solid rgba(201,150,62,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .damian-shell-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            font-weight: 700;
            color: #C9963E;
            letter-spacing: 0.12em;
        }
        .damian-shell-sub {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            color: rgba(138,155,174,0.5);
            letter-spacing: 0.08em;
        }

        /* Messages area */
        .damian-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px 20px 10px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            scroll-behavior: smooth;
        }
        .damian-messages::-webkit-scrollbar { width: 4px; }
        .damian-messages::-webkit-scrollbar-track { background: transparent; }
        .damian-messages::-webkit-scrollbar-thumb { background: rgba(201,150,62,0.2); border-radius: 2px; }

        /* Suggested questions (shown when chat is empty) */
        .damian-suggestions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 30px 0 10px;
        }
        .damian-suggestions-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: rgba(138,155,174,0.4);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .damian-suggestions-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            max-width: 640px;
        }
        .damian-suggestion-chip {
            background: rgba(201,150,62,0.06);
            border: 1px solid rgba(201,150,62,0.2);
            color: #8A9BAE;
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            letter-spacing: 0.07em;
            padding: 6px 12px;
            cursor: pointer;
            text-transform: uppercase;
            transition: border-color 0.15s, color 0.15s;
        }
        .damian-suggestion-chip:hover {
            border-color: rgba(201,150,62,0.5);
            color: #C9963E;
        }

        /* Message bubbles */
        .msg-row {
            display: flex;
            gap: 10px;
            max-width: 88%;
        }
        .msg-row.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .msg-row.damian {
            align-self: flex-start;
        }

        /* Avatar */
        .msg-avatar {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .msg-avatar.user {
            background: rgba(201,150,62,0.15);
            border: 1px solid rgba(201,150,62,0.3);
            color: #C9963E;
        }
        .msg-avatar.damian {
            background: rgba(74,124,158,0.15);
            border: 1px solid rgba(74,124,158,0.3);
            color: #4A7C9E;
        }

        /* Bubble */
        .msg-bubble {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .msg-content {
            padding: 12px 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            line-height: 1.75;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .msg-row.user .msg-content {
            background: rgba(201,150,62,0.1);
            border: 1px solid rgba(201,150,62,0.25);
            color: #E8EDF2;
            border-radius: 2px 0 2px 2px;
        }
        .msg-row.damian .msg-content {
            background: rgba(11,31,58,0.8);
            border: 1px solid rgba(74,124,158,0.25);
            color: #C8D8E8;
            border-radius: 0 2px 2px 2px;
        }
        .msg-meta {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8px;
            letter-spacing: 0.07em;
            color: rgba(138,155,174,0.4);
            text-transform: uppercase;
        }
        .msg-row.user .msg-meta { text-align: right; }

        /* Typing indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 12px 16px;
            background: rgba(11,31,58,0.8);
            border: 1px solid rgba(74,124,158,0.25);
            width: fit-content;
        }
        .typing-dot {
            width: 5px;
            height: 5px;
            background: #4A7C9E;
            border-radius: 50%;
            animation: typingPulse 1.2s ease-in-out infinite;
        }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingPulse {
            0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }
            30% { opacity: 1; transform: scale(1); }
        }

        /* ── Input bar ───────────────────────────────────────────── */
        .damian-input-bar {
            border-top: 1px solid rgba(201,150,62,0.15);
            padding: 12px 16px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-shrink: 0;
            background: rgba(6,15,30,0.95);
        }
        #questionInput {
            flex: 1;
            background: rgba(11,31,58,0.6);
            border: 1px solid rgba(74,124,158,0.25);
            color: #E8EDF2;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            line-height: 1.5;
            padding: 9px 14px;
            resize: none;
            outline: none;
            min-height: 40px;
            max-height: 120px;
            overflow-y: auto;
            transition: border-color 0.15s;
        }
        #questionInput::placeholder { color: rgba(138,155,174,0.35); }
        #questionInput:focus { border-color: rgba(201,150,62,0.4); }
        #sendBtn {
            background: #C9963E;
            color: #060F1E;
            border: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            padding: 9px 18px;
            cursor: pointer;
            white-space: nowrap;
            text-transform: uppercase;
            height: 40px;
            flex-shrink: 0;
            transition: opacity 0.15s;
        }
        #sendBtn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .input-hint {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8px;
            color: rgba(138,155,174,0.3);
            letter-spacing: 0.07em;
            flex-shrink: 0;
            align-self: flex-end;
            padding-bottom: 11px;
        }
    </style>

    <div class="damian-chat-wrap">

        {{-- ── Metadata strip ────────────────────────────────────── --}}
        <div class="damian-meta-strip">
            <div class="damian-meta-cell">
                <div class="val" style="color:#C9963E;">NL → SQL</div>
                <div class="lbl">Claude Translates</div>
            </div>
            <div class="damian-meta-cell">
                <div class="val" style="color:#2ECC71;">READ-ONLY</div>
                <div class="lbl">No Data Modified</div>
            </div>
            <div class="damian-meta-cell">
                <div class="val" style="color:#4A7C9E;">21 CFR</div>
                <div class="lbl">Audit Logged</div>
            </div>
        </div>

        {{-- ── Chat shell ─────────────────────────────────────────── --}}
        <div class="damian-shell">

            {{-- Shell header --}}
            <div class="damian-shell-header">
                <span class="damian-shell-title">[ DAMIAN · QC INTELLIGENCE ]</span>
                <span class="damian-shell-sub">NAMED AFTER · PATRON SAINT OF MEDICINE &amp; SURGERY · TWINS WITH ST. COSMAS</span>
            </div>

            {{-- Messages --}}
            <div class="damian-messages" id="chatMessages">
                {{-- Suggested questions (hidden once chat starts) --}}
                <div class="damian-suggestions" id="suggestionPanel">
                    <div class="damian-suggestions-title">Ask anything about your inspection data</div>
                    <div class="damian-suggestions-grid">
                        @foreach([
                            "Is our defect rate getting worse this week?",
                            "Which instrument class has the highest failure rate?",
                            "Was there a defect spike this week vs normal?",
                            "Is the AI model reliable enough to trust?",
                            "What's our regulatory exposure for an FDA audit?",
                        ] as $q)
                        <button class="damian-suggestion-chip" onclick="sendSuggestion(this.dataset.q)" data-q="{{ $q }}">
                            {{ $q }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Input bar --}}
            <div class="damian-input-bar">
                <textarea
                    id="questionInput"
                    rows="1"
                    placeholder="Ask DAMIAN about your inspection data…"
                ></textarea>
                <span class="input-hint">↵ SEND</span>
                <button id="sendBtn" onclick="sendMessage()">ASK →</button>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF  = '{{ csrf_token() }}';
    const ROUTE = '{{ route("intel.query") }}';
    let chatHistory = []; // [{role, content, time}]
    let isThinking  = false;

    // ── Suggestion chips ───────────────────────────────────────────
    function sendSuggestion(q) {
        document.getElementById('questionInput').value = q;
        sendMessage();
    }

    // ── Auto-resize textarea ───────────────────────────────────────
    const input = document.getElementById('questionInput');
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    // ── Main send function ─────────────────────────────────────────
    async function sendMessage() {
        const q = input.value.trim();
        if (!q || isThinking) return;

        // Hide suggestions after first message
        const suggPanel = document.getElementById('suggestionPanel');
        if (suggPanel) suggPanel.style.display = 'none';

        // Append user message
        appendMessage('user', q);
        input.value = '';
        input.style.height = 'auto';

        // Show typing indicator
        const typingId = showTyping();
        isThinking = true;
        document.getElementById('sendBtn').disabled = true;

        const start = Date.now();
        try {
            const res = await fetch(ROUTE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({
                    question: q,
                    history: chatHistory.slice(-10).map(h => ({
                        role:    h.role === 'damian' ? 'assistant' : 'user',
                        content: h.content,
                    })),
                }),
            });
            const data = await res.json();
            const elapsed = ((Date.now() - start) / 1000).toFixed(1);
            removeTyping(typingId);
            const answer = data.answer || data.error || 'No response received.';
            appendMessage('damian', answer, elapsed + 's · READ-ONLY · NO DATA MODIFIED');
        } catch (err) {
            removeTyping(typingId);
            appendMessage('damian', '[ CONNECTION ERROR: ' + err.message + ' ]', null);
        }

        isThinking = false;
        document.getElementById('sendBtn').disabled = false;
        input.focus();
    }

    // ── Append a chat bubble ───────────────────────────────────────
    function appendMessage(role, content, meta) {
        const container = document.getElementById('chatMessages');

        const row = document.createElement('div');
        row.className = 'msg-row ' + role;

        const avatar = document.createElement('div');
        avatar.className = 'msg-avatar ' + role;
        avatar.textContent = role === 'user' ? 'YOU' : 'DM';

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';

        const msgContent = document.createElement('div');
        msgContent.className = 'msg-content';
        msgContent.textContent = content; // safe text (no innerHTML)

        bubble.appendChild(msgContent);

        if (meta) {
            const msgMeta = document.createElement('div');
            msgMeta.className = 'msg-meta';
            msgMeta.textContent = meta;
            bubble.appendChild(msgMeta);
        }

        if (role === 'user') {
            row.appendChild(bubble);
            row.appendChild(avatar);
        } else {
            row.appendChild(avatar);
            row.appendChild(bubble);
        }

        container.appendChild(row);
        scrollToBottom();

        chatHistory.push({ role, content, meta });
    }

    // ── Typing indicator ───────────────────────────────────────────
    function showTyping() {
        const container = document.getElementById('chatMessages');
        const id = 'typing-' + Date.now();

        const row = document.createElement('div');
        row.className = 'msg-row damian';
        row.id = id;

        const avatar = document.createElement('div');
        avatar.className = 'msg-avatar damian';
        avatar.textContent = 'DM';

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';

        const indicator = document.createElement('div');
        indicator.className = 'typing-indicator';
        indicator.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';

        bubble.appendChild(indicator);
        row.appendChild(avatar);
        row.appendChild(bubble);
        container.appendChild(row);
        scrollToBottom();
        return id;
    }

    function removeTyping(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    // ── Scroll helpers ─────────────────────────────────────────────
    function scrollToBottom() {
        const container = document.getElementById('chatMessages');
        container.scrollTop = container.scrollHeight;
    }

    // Focus input on load
    window.addEventListener('load', () => input.focus());
    </script>
    @endpush
</x-app-layout>
