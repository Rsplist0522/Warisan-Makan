{{-- Smart Heritage Assistant UI --}}
<style>
    .chat-fab{position:fixed;right:24px;bottom:24px;z-index:1000;width:60px;height:60px;border-radius:50%;border:none;cursor:pointer;background:linear-gradient(135deg,#691616,#8c1f1f);color:#fff;font-size:1.6rem;box-shadow:0 14px 30px rgba(105,22,22,.35);display:flex;align-items:center;justify-content:center;transition:.2s;outline:none}
    .chat-fab:hover{transform:translateY(-3px) scale(1.05);box-shadow:0 18px 36px rgba(105,22,22,.42)}
    
    .chat-panel{position:fixed;right:24px;bottom:96px;z-index:1000;width:360px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 140px);background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 24px 60px rgba(69,34,18,.28);border:1px solid rgba(140,31,31,.12);display:none;flex-direction:column}
    .chat-panel.open{display:flex}
    
    .chat-header{background:linear-gradient(135deg,#691616,#8c1f1f);color:#fff;padding:16px;display:flex;align-items:center;justify-content:space-between}
    .chat-header-title{font-weight:800;font-size:1rem;display:flex;align-items:center;gap:8px}
    
    .chat-messages{flex:1;overflow-y:auto;padding:16px;background:#fcf7ef;display:flex;flex-direction:column;gap:12px}
    .chat-bubble{max-width:85%;padding:10px 14px;border-radius:18px;font-size:.9rem;line-height:1.5;word-wrap:break-word}
    .chat-bubble.user{align-self:flex-end;background:#691616;color:#fff;border-bottom-right-radius:4px}
    .chat-bubble.bot{align-self:flex-start;background:#fff;color:#2f241d;border:1px solid rgba(140,31,31,.1);border-bottom-left-radius:4px}
    
    /* Link Styling */
    .chat-bubble a{color:#8c1f1f;font-weight:800;text-decoration:underline;cursor:pointer}
    .chat-bubble a:hover{color:#c98b16}

    .quick-actions{padding:10px 16px;background:#fff;border-top:1px solid rgba(140,31,31,.05);display:flex;gap:8px;overflow-x:auto;scrollbar-width:none}
    .quick-actions::-webkit-scrollbar{display:none}
    .action-btn{padding:6px 14px;background:#fdf6ea;border:1px solid rgba(201,139,22,.3);border-radius:999px;font-size:.78rem;font-weight:700;color:#8c1f1f;white-space:nowrap;cursor:pointer;transition:.2s}
    .action-btn:hover{background:#c98b16;color:#fff}

    .chat-input-row{display:flex;gap:8px;padding:12px;border-top:1px solid rgba(140,31,31,.08);background:#fff}
    .chat-input-row input{flex:1;border:1px solid rgba(140,31,31,.18);border-radius:999px;padding:10px 16px;font-size:.9rem;outline:none;color:#2f241d}
    .chat-send{width:40px;height:40px;border-radius:50%;border:none;cursor:pointer;background:#691616;color:#fff;display:flex;align-items:center;justify-content:center;transition:.2s}
    .chat-send:disabled{opacity:0.5;cursor:not-allowed}

    .typing-dots{display:flex;gap:4px;padding:10px 14px}
    .typing-dots span{width:6px;height:6px;background:#6f5845;border-radius:50%;animation:typing 1.1s infinite ease-in-out}
    .typing-dots span:nth-child(2){animation-delay:0.2s}
    .typing-dots span:nth-child(3){animation-delay:0.4s}
    @keyframes typing{0%,60%,100%{opacity:0.3;transform:translateY(0)}30%{opacity:1;transform:translateY(-4px)}}
</style>

<button id="chatFab" class="chat-fab animate__animated animate__fadeIn">💬</button>

<div id="chatPanel" class="chat-panel">
    <div class="chat-header">
        <div class="chat-header-title">🏮 Heritage Assistant</div>
        <button id="chatClose" style="background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer">✕</button>
    </div>
    
    <div id="chatMessages" class="chat-messages"></div>
    
    <div class="quick-actions">
        <div class="action-btn" onclick="askQuick('What is this system?')">What is this?</div>
        <div class="action-btn" onclick="askQuick('How to use Blind Box?')">Blind Box?</div>
        <div class="action-btn" onclick="askQuick('How to submit a shop?')">Submit Shop</div>
        <div class="action-btn" onclick="askQuick('Where are food trails?')">Food Trails</div>
    </div>

    <div class="chat-input-row">
        <input id="chatInput" type="text" placeholder="Ask me about heritage food..." maxlength="500">
        <button id="chatSend">➤</button>
    </div>
</div>

<script>
(function () {
    const fab = document.getElementById('chatFab');
    const panel = document.getElementById('chatPanel');
    const messages = document.getElementById('chatMessages');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    
    let history = [];
    let greeted = false;
    let sending = false;

    window.askQuick = function(text) {
        if (sending) return;
        input.value = text;
        sendMessage();
    };

    function addBubble(text, role) {
        const b = document.createElement('div');
        b.className = `chat-bubble ${role} animate__animated animate__fadeInUp animate__faster`;
        
        // --- FIXED LINK CONVERSION ---
        // Converts [Text](/url) into a clickable HTML link
        let html = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
        // Converts **Bold** into HTML
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        b.innerHTML = html;
        messages.appendChild(b);
        messages.scrollTop = messages.scrollHeight;
        return b;
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text || sending) return;

        sending = true;
        sendBtn.disabled = true;
        addBubble(text, 'user');
        input.value = '';
        
        const typing = document.createElement('div');
        typing.className = 'chat-bubble bot typing-dots';
        typing.innerHTML = '<span></span><span></span><span></span>';
        messages.appendChild(typing);
        messages.scrollTop = messages.scrollHeight;

        try {
            const res = await fetch('{{ route('chat.respond') }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: text, history: history })
            });
            
            const data = await res.json();
            typing.remove();
            
            const reply = data.reply || "Sorry, I couldn't process that.";
            addBubble(reply, 'bot');
            
            history.push({role:'user', content:text}, {role:'assistant', content:reply});
            if(history.length > 8) history = history.slice(-8);
        } catch (e) {
            typing.remove();
            addBubble("Sorry, I couldn't connect right now.", 'bot');
        } finally {
            sending = false;
            sendBtn.disabled = false;
        }
    }

    fab.onclick = () => {
        panel.classList.toggle('open');
        if (!greeted) {
            greeted = true;
            addBubble("Selamat Datang! I am your WarisanMakan Assistant. How can I help you today?", 'bot');
        }
    };
    
    document.getElementById('chatClose').onclick = () => panel.classList.remove('open');
    sendBtn.onclick = sendMessage;
    input.onkeydown = (e) => { if(e.key==='Enter') sendMessage(); };
})();
</script>
