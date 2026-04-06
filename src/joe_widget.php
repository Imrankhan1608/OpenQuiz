<?php /* joe_widget.php — inclure juste avant </body> sur chaque page */ ?>
<style>
#joe-btn {
  position: fixed;
  bottom: 88px; right: 24px;
  z-index: 9997;
  width: 54px; height: 54px;
  border-radius: 50%;
  background: #3a7bd5;
  border: 2px solid rgba(255,255,255,0.12);
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 6px 24px rgba(58,123,213,0.45);
  transition: transform 0.2s, box-shadow 0.2s;
  padding: 0; overflow: hidden;
}
#joe-btn:hover { transform: scale(1.08); box-shadow: 0 8px 32px rgba(58,123,213,0.6); }
#joe-btn img { width: 54px; height: 54px; object-fit: cover; border-radius: 50%; }
#joe-btn::after {
  content: '';
  position: absolute; top: 4px; right: 4px;
  width: 10px; height: 10px; border-radius: 50%;
 border: 2px solid #080e14;
  animation: joePulse 2.5s infinite;
}
#joe-btn.no-badge::after { display: none; }

#joe-intro {
  position: fixed;
  bottom: 154px; right: 24px;
  z-index: 9996;
  display: none;
  background: #0d1620;
  border: 1px solid rgba(58,123,213,0.3);
  border-radius: 14px 14px 4px 14px;
  padding: 12px 16px;
  max-width: 220px;
  box-shadow: 0 8px 28px rgba(0,0,0,0.5);
  pointer-events: none;
  animation: joeIntroIn 0.4s cubic-bezier(.34,1.56,.64,1) both;
}
#joe-intro p {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 12px; color: rgba(232,237,242,0.75);
  line-height: 1.6; margin: 0;
}
#joe-intro strong { color: #3a7bd5; }
@keyframes joeIntroIn {
  from { transform: scale(0.85) translateY(10px); opacity: 0; }
  to   { transform: scale(1) translateY(0); opacity: 1; }
}
#joe-chat {
  position: fixed;
  bottom: 164px; right: 24px;
  z-index: 9998;
  width: 340px; max-height: 500px;
  display: flex; flex-direction: column;
  background: #0d1620;
  border: 1px solid rgba(58,123,213,0.28);
  border-radius: 18px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.7);
  overflow: hidden;
  transform: scale(0.88) translateY(16px);
  opacity: 0; pointer-events: none;
  transition: transform 0.28s cubic-bezier(.34,1.3,.64,1), opacity 0.22s ease;
}
#joe-chat.open { transform: scale(1) translateY(0); opacity: 1; pointer-events: auto; }
#joe-chat-header {
  display: flex; align-items: center; gap: 10px;
  padding: 13px 16px;
  background: #111d29;
  border-bottom: 1px solid rgba(58,123,213,0.18);
  flex-shrink: 0;
}
.joe-head-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  overflow: hidden; flex-shrink: 0;
}
.joe-head-avatar img { width: 100%; height: 100%; object-fit: cover; }
.joe-head-info { flex: 1; }
.joe-head-info strong {
  display: block;
  font-family: 'IBM Plex Mono', monospace;
  font-size: 13px; font-weight: 600; color: #e8edf2;
}
.joe-head-info small {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 10px; color: rgba(232,237,242,0.4);
}
.joe-head-info small::before { content: '●'; color: #27ae60; margin-right: 4px; font-size: 8px; }
#joe-close {
  background: none; border: none; cursor: pointer;
  color: rgba(232,237,242,0.3); font-size: 18px;
  line-height: 1; padding: 4px; transition: color 0.2s;
}
#joe-close:hover { color: #e8edf2; }
#joe-messages {
  flex: 1; overflow-y: auto;
  padding: 14px 13px;
  display: flex; flex-direction: column; gap: 9px;
  scroll-behavior: smooth;
}
#joe-messages::-webkit-scrollbar { width: 3px; }
#joe-messages::-webkit-scrollbar-thumb { background: rgba(58,123,213,0.3); border-radius: 2px; }
.joe-msg {
  max-width: 84%;
  padding: 9px 12px; border-radius: 14px;
  font-family: 'IBM Plex Mono', monospace;
  font-size: 12px; line-height: 1.65;
  animation: msgIn 0.2s ease both;
}
@keyframes msgIn {
  from { transform: translateY(5px); opacity: 0; }
  to   { transform: translateY(0); opacity: 1; }
}
.joe-msg.joe {
  background: #111d29;
  border: 1px solid rgba(58,123,213,0.2);
  color: rgba(232,237,242,0.85);
  border-bottom-left-radius: 4px;
  align-self: flex-start;
}
.joe-msg.user {
  background: #3a7bd5; color: #fff;
  border-bottom-right-radius: 4px;
  align-self: flex-end;
}
.joe-typing {
  display: flex; align-items: center; gap: 4px;
  padding: 9px 14px;
  background: #111d29;
  border: 1px solid rgba(58,123,213,0.2);
  border-radius: 14px 14px 4px 14px;
  align-self: flex-start; width: fit-content;
}
.joe-typing span {
  width: 6px; height: 6px; border-radius: 50%;
  background: rgba(58,123,213,0.6);
  animation: typingDot 1.1s infinite ease-in-out;
}
.joe-typing span:nth-child(2) { animation-delay: 0.18s; }
.joe-typing span:nth-child(3) { animation-delay: 0.36s; }
@keyframes typingDot {
  0%,60%,100% { transform: translateY(0); opacity: 0.4; }
  30%          { transform: translateY(-5px); opacity: 1; }
}
#joe-suggestions {
  display: flex; flex-wrap: wrap; gap: 6px;
  padding: 0 13px 10px;
}
.joe-sug {
  font-family: 'IBM Plex Mono', monospace;
  font-size: 10px; color: rgba(58,123,213,0.9);
  border: 1px solid rgba(58,123,213,0.3);
  border-radius: 20px; padding: 4px 10px;
  cursor: pointer; background: transparent;
  transition: background 0.2s; white-space: nowrap;
}
.joe-sug:hover { background: rgba(58,123,213,0.12); }
#joe-input-zone {
  display: flex; gap: 8px; align-items: center;
  padding: 11px 13px;
  border-top: 1px solid rgba(58,123,213,0.14);
  flex-shrink: 0;
}
#joe-input {
  flex: 1; background: #111d29;
  border: 1px solid rgba(58,123,213,0.2);
  border-radius: 10px; padding: 9px 12px;
  font-family: 'IBM Plex Mono', monospace;
  font-size: 12px; color: #e8edf2;
  outline: none; resize: none;
  min-height: 36px; max-height: 100px;
  transition: border-color 0.2s; line-height: 1.5;
}
#joe-input::placeholder { color: rgba(232,237,242,0.22); }
#joe-input:focus { border-color: rgba(58,123,213,0.5); }
#joe-send {
  width: 36px; height: 36px; flex-shrink: 0;
  background: #3a7bd5; border: none; border-radius: 10px;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: background 0.2s, transform 0.15s;
}
#joe-send:hover { background: #2a5fa8; transform: scale(1.05); }
#joe-send:disabled { background: rgba(58,123,213,0.28); cursor: default; transform: none; }
#joe-send svg { width: 15px; height: 15px; fill: #fff; }
@media (max-width: 400px) {
  #joe-chat  { width: calc(100vw - 32px); right: 16px; }
  #joe-btn   { right: 16px; bottom: 80px; }
  #joe-intro { right: 16px; }
}
</style>

<button id="joe-btn" aria-label="Parler avec Joe">
  <img src="assests/joe.jpg" alt="Joe">
</button>

<div id="joe-intro">
  <p>Psst… t'as une question ?<br><strong>Joe</strong> est là. Enfin, presque utile. 😏</p>
</div>

<div id="joe-chat" role="dialog" aria-label="Chat avec Joe">
  <div id="joe-chat-header">
    <div class="joe-head-avatar"><img src="assests/joe.jpg" alt="Joe"></div>
    <div class="joe-head-info">
      <strong>Joe</strong>
      <small>En ligne — probablement</small>
    </div>
    <button id="joe-close" aria-label="Fermer">✕</button>
  </div>
  <div id="joe-messages"></div>
  <div id="joe-suggestions">
    <button class="joe-sug">Comment jouer ?</button>
    <button class="joe-sug">C'est gratuit ?</button>
    <button class="joe-sug">Comment s'inscrire ?</button>
    <button class="joe-sug">Les thèmes dispo ?</button>
  </div>
  <div id="joe-input-zone">
    <textarea id="joe-input" placeholder="Pose ta question à Joe…" rows="1"></textarea>
    <button id="joe-send" disabled aria-label="Envoyer">
      <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    </button>
  </div>
</div>

<script>
(function() {
  var btn       = document.getElementById('joe-btn');
  var chat      = document.getElementById('joe-chat');
  var closeBtn  = document.getElementById('joe-close');
  var input     = document.getElementById('joe-input');
  var sendBtn   = document.getElementById('joe-send');
  var msgs      = document.getElementById('joe-messages');
  var intro     = document.getElementById('joe-intro');
  var sugs      = document.querySelectorAll('.joe-sug');
  var isOpen    = false;
  var isTyping  = false;
  var firstOpen = true;
  var history   = [];
  var sessionId = Math.random().toString(36).slice(2,10) + Date.now().toString(36);

  /* chemin absolu vers joe_chat.php depuis la racine du site */
  var CHAT_ENDPOINT = 'joe_chat.php';

  /* bulle intro : apparaît 4s après chargement, disparaît à 10s */
  setTimeout(function() {
    if (!isOpen) intro.style.display = 'block';
  }, 4000);
  setTimeout(function() {
    if (!isOpen) {
      intro.style.transition = 'opacity 0.5s';
      intro.style.opacity = '0';
      setTimeout(function() { intro.style.display = 'none'; }, 500);
    }
  }, 10000);

  function openChat() {
    isOpen = true;
    chat.classList.add('open');
    btn.classList.add('no-badge');
    intro.style.display = 'none';
    if (firstOpen) {
      firstOpen = false;
      setTimeout(function() {
        addMsg('joe', 'Tiens, un visiteur. 👀 Bienvenue sur OpenQuiz — la seule plateforme de quiz qui rend les gens intelligents. Enfin, presque. T\'avais quoi comme question ?');
      }, 320);
    }
    setTimeout(function() { input.focus(); }, 280);
  }

  function closeChat() {
    isOpen = false;
    chat.classList.remove('open');
  }

  btn.addEventListener('click', function() { isOpen ? closeChat() : openChat(); });
  closeBtn.addEventListener('click', closeChat);

  function addMsg(who, text) {
    var d = document.createElement('div');
    d.className = 'joe-msg ' + who;
    d.textContent = text;
    msgs.appendChild(d);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function showTyping() {
    var t = document.createElement('div');
    t.className = 'joe-typing'; t.id = 'joe-typing-el';
    t.innerHTML = '<span></span><span></span><span></span>';
    msgs.appendChild(t);
    msgs.scrollTop = msgs.scrollHeight;
  }
  function hideTyping() {
    var t = document.getElementById('joe-typing-el');
    if (t) t.remove();
  }

  function send(text) {
    text = text.trim();
    if (!text || isTyping) return;
    addMsg('user', text);
    history.push({ role: 'user', content: text });
    input.value = '';
    input.style.height = 'auto';
    sendBtn.disabled = true;
    isTyping = true;
    document.getElementById('joe-suggestions').style.display = 'none';
    showTyping();

    fetch(CHAT_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message:    text,
        history:    history.slice(-10, -1),
        session_id: sessionId
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      hideTyping();
      var reply = data.error || data.reply || '…';
      addMsg('joe', reply);
      if (!data.error) {
        history.push({ role: 'assistant', content: reply });
        if (data.session_id) sessionId = data.session_id;
      }
    })
    .catch(function() {
      hideTyping();
      addMsg('joe', 'Connexion perdue. Joe boude. Réessaie dans un instant.');
    })
    .finally(function() {
      isTyping = false;
      sendBtn.disabled = input.value.trim().length === 0;
    });
  }

  input.addEventListener('input', function() {
    sendBtn.disabled = this.value.trim().length === 0;
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
  });
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); if (!sendBtn.disabled) send(input.value); }
  });
  sendBtn.addEventListener('click', function() { send(input.value); });

  sugs.forEach(function(s) {
    s.addEventListener('click', function() {
      if (!isOpen) openChat();
      setTimeout(function() { send(s.textContent); }, 200);
    });
  });
})();
</script>