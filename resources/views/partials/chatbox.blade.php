{{-- Shared Heritage Shop Assistant
     Include with: @include('partials.chatbox')
     Chat history is kept in memory only and is cleared on refresh.
     Requires animate.css on the page. --}}

<style>
    .chat-fab{
        position:fixed;right:24px;bottom:24px;z-index:1000;
        width:60px;height:60px;border-radius:50%;border:none;cursor:pointer;
        background:linear-gradient(135deg,var(--red-dark,#691616),var(--red,#8c1f1f));
        color:#fff;font-size:1.6rem;
        box-shadow:0 14px 30px rgba(105,22,22,.35);
        display:flex;align-items:center;justify-content:center;
        transition:.2s;
    }
    .chat-fab:hover{
        transform:translateY(-3px) scale(1.05);
        box-shadow:0 18px 36px rgba(105,22,22,.42);
    }
    .chat-fab-badge{
        position:absolute;top:-4px;right:-4px;width:16px;height:16px;
        border-radius:50%;background:var(--gold,#c98b16);border:2px solid #fff;
    }

    .chat-panel{
        position:fixed;right:24px;bottom:96px;z-index:1000;
        width:340px;max-width:calc(100vw - 32px);
        height:460px;max-height:calc(100vh - 140px);
        background:#fff;border-radius:20px;overflow:hidden;
        box-shadow:0 24px 60px rgba(69,34,18,.28);
        border:1px solid rgba(140,31,31,.12);
        display:none;flex-direction:column;
    }
    .chat-panel.open{display:flex}

    .chat-header{
        background:linear-gradient(135deg,var(--red-dark,#691616),var(--red,#8c1f1f));
        color:#fff;padding:14px 16px;
        display:flex;align-items:center;justify-content:space-between;
    }
    .chat-header-title{
        display:flex;align-items:center;gap:8px;
        font-weight:800;font-size:.98rem;
    }
    .chat-close{
        background:transparent;border:none;color:#fff;
        font-size:1.1rem;cursor:pointer;opacity:.85;
    }

    .chat-messages{
        flex:1;overflow-y:auto;padding:14px;
        background:var(--cream,#f7efe4);
        display:flex;flex-direction:column;gap:10px;
    }
    .chat-bubble{
        max-width:82%;padding:9px 13px;border-radius:14px;
        font-size:.88rem;line-height:1.45;word-wrap:break-word;
    }
    .chat-bubble.user{
        align-self:flex-end;background:var(--red-dark,#691616);
        color:#fff;border-bottom-right-radius:4px;
    }
    .chat-bubble.bot{
        align-self:flex-start;background:#fff;
        color:var(--text,#2f241d);
        border:1px solid rgba(140,31,31,.1);
        border-bottom-left-radius:4px;
    }
    .chat-bubble.typing{
        display:flex;gap:4px;align-items:center;padding:11px 14px;
    }
    .chat-bubble.typing span{
        width:6px;height:6px;border-radius:50%;
        background:var(--muted,#6f5845);
        animation:chat-typing 1.1s infinite ease-in-out;
    }
    .chat-bubble.typing span:nth-child(2){animation-delay:.15s}
    .chat-bubble.typing span:nth-child(3){animation-delay:.3s}

    @keyframes chat-typing{
        0%,60%,100%{opacity:.3;transform:translateY(0)}
        30%{opacity:1;transform:translateY(-3px)}
    }

    .chat-input-row{
        display:flex;gap:8px;padding:12px;
        border-top:1px solid rgba(140,31,31,.08);
        background:#fff;
    }
    .chat-input-row input{
        flex:1;border:1px solid rgba(140,31,31,.18);
        border-radius:999px;padding:10px 14px;
        font-size:.88rem;outline:none;
        color:var(--text,#2f241d);
    }
    .chat-input-row input:focus{
        border-color:var(--red,#8c1f1f);
    }
    .chat-send{
        width:38px;height:38px;border-radius:50%;
        border:none;cursor:pointer;flex-shrink:0;
        background:var(--red-dark,#691616);color:#fff;
        display:flex;align-items:center;justify-content:center;
    }
    .chat-send:disabled{opacity:.5;cursor:not-allowed}

    @media(max-width:480px){
        .chat-panel{
            right:16px;bottom:88px;width:calc(100vw - 32px);
        }
        .chat-fab{right:16px;bottom:16px}
    }
</style>

<button id="chatFab"
        class="chat-fab animate__animated animate__fadeIn"
        aria-label="Ask about heritage shops"
        aria-expanded="false">
    💬
    <span class="chat-fab-badge"></span>
</button>

<div id="chatPanel"
     class="chat-panel"
     role="dialog"
     aria-label="Heritage shop assistant">

    <div class="chat-header">
        <div class="chat-header-title">🏮 Heritage Shop Assistant</div>
        <button id="chatClose"
                class="chat-close"
                aria-label="Close chat">✕</button>
    </div>

    <div id="chatMessages" class="chat-messages"></div>

    <div class="chat-input-row">
        <input id="chatInput"
               type="text"
               placeholder="Ask about a shop, e.g. cendol in Melaka..."
               maxlength="500"
               autocomplete="off">

        <button id="chatSend"
                class="chat-send"
                aria-label="Send message">➤</button>
    </div>
</div>

<script>
(function () {
    const CHAT_CONFIG = {
        chatUrl: '{{ route('chat.respond') }}',
        csrfToken: '{{ csrf_token() }}'
    };

    const fab = document.getElementById('chatFab');
    const panel = document.getElementById('chatPanel');
    const closeBtn = document.getElementById('chatClose');
    const messages = document.getElementById('chatMessages');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');

    let history = [];
    let sending = false;
    let greeted = false;

    function scrollBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function addBubble(text, role) {
        const bubble = document.createElement('div');
        bubble.className =
            'chat-bubble ' +
            (role === 'user' ? 'user' : 'bot') +
            ' animate__animated animate__fadeInUp animate__faster';

        bubble.textContent = text;
        messages.appendChild(bubble);
        scrollBottom();
        return bubble;
    }

    function addTyping() {
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble bot typing';
        bubble.innerHTML = '<span></span><span></span><span></span>';
        messages.appendChild(bubble);
        scrollBottom();
        return bubble;
    }

    function openPanel() {
        panel.classList.add('open');
        fab.setAttribute('aria-expanded', 'true');

        if (!greeted) {
            greeted = true;
            addBubble(
                'Hi! Ask me about any heritage shop — its category, state, or story.',
                'bot'
            );
        }

        input.focus();
    }

    function closePanel() {
        panel.classList.remove('open');
        fab.setAttribute('aria-expanded', 'false');
    }

    fab.addEventListener('click', function () {
        panel.classList.contains('open') ? closePanel() : openPanel();
    });

    closeBtn.addEventListener('click', closePanel);

    async function sendMessage() {
        const text = input.value.trim();

        if (!text || sending) return;

        sending = true;
        sendBtn.disabled = true;

        addBubble(text, 'user');
        input.value = '';

        const typing = addTyping();

        try {
            const response = await fetch(CHAT_CONFIG.chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CHAT_CONFIG.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: text,
                    history: history
                })
            });

            const data = await response.json();

            typing.remove();

            const reply = data?.reply ||
                'Sorry, something went wrong. Please try again.';

            addBubble(reply, 'bot');

            history.push(
                { role: 'user', content: text },
                { role: 'assistant', content: reply }
            );

            if (history.length > 12) {
                history = history.slice(-12);
            }

        } catch (error) {
            typing.remove();
            addBubble(
                "Sorry, I couldn't connect right now. Please try again.",
                'bot'
            );
        } finally {
            sending = false;
            sendBtn.disabled = false;
        }
    }

    sendBtn.addEventListener('click', sendMessage);

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
})();
</script>