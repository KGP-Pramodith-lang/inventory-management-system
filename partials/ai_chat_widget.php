<?php
// AI chat widget (embedded in dashboard pages)
?>
<div id="chatAiWidget" class="chat-widget chat-widget--hidden" aria-hidden="true">
  <div class="chat-widget__header">
    <div class="d-flex align-items-center gap-2 fw-semibold">
      <i class="bi bi-robot"></i>
      <span>AI Chat</span>
    </div>
    <button type="button" class="btn btn-sm btn-light" id="chatAiClose" aria-label="Close chat">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <div class="chat-widget__messages" id="chatAiMessages" aria-live="polite"></div>

  <form class="chat-widget__form" id="chatAiForm" autocomplete="off">
    <input
      type="text"
      class="form-control form-control-sm"
      id="chatAiInput"
      placeholder="Type a message..."
      maxlength="1000"
      required
    />
    <button type="submit" class="btn btn-primary btn-sm" id="chatAiSend">Send</button>
  </form>
</div>

<script>
(function () {
  const toggleBtn = document.getElementById('chatAiToggle');
  const widget = document.getElementById('chatAiWidget');
  const closeBtn = document.getElementById('chatAiClose');
  const form = document.getElementById('chatAiForm');
  const input = document.getElementById('chatAiInput');
  const sendBtn = document.getElementById('chatAiSend');
  const messagesEl = document.getElementById('chatAiMessages');

  if (!toggleBtn || !widget || !closeBtn || !form || !input || !sendBtn || !messagesEl) return;

  function setOpen(isOpen) {
    widget.classList.toggle('chat-widget--hidden', !isOpen);
    widget.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    if (isOpen) {
      if (messagesEl.childElementCount === 0) {
        addMsg('assistant', 'Hi! How can I help?');
      }
      setTimeout(() => input.focus(), 0);
      scrollToBottom();
    }
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function addMsg(role, text) {
    const row = document.createElement('div');
    row.className = role === 'user' ? 'chat-msg chat-msg--user' : 'chat-msg chat-msg--assistant';

    const bubble = document.createElement('div');
    bubble.className = 'chat-msg__bubble';
    bubble.innerHTML = escapeHtml(text);

    row.appendChild(bubble);
    messagesEl.appendChild(row);
  }

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  async function sendMessage(message) {
    const res = await fetch('logics/ai_chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message })
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      const err = data && data.error ? data.error : 'Something went wrong.';
      throw new Error(err);
    }

    return (data && data.reply) ? String(data.reply) : '';
  }

  toggleBtn.addEventListener('click', function (e) {
    e.preventDefault();
    const isHidden = widget.classList.contains('chat-widget--hidden');
    setOpen(isHidden);
  });

  closeBtn.addEventListener('click', function () {
    setOpen(false);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      setOpen(false);
    }
  });

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const message = input.value.trim();
    if (!message) return;

    addMsg('user', message);
    input.value = '';

    input.disabled = true;
    sendBtn.disabled = true;

    const thinkingText = 'Thinking...';
    addMsg('assistant', thinkingText);
    scrollToBottom();

    try {
      const reply = await sendMessage(message);
      const lastBubble = messagesEl.querySelector('.chat-msg--assistant:last-child .chat-msg__bubble');
      if (lastBubble) lastBubble.textContent = reply || '…';
    } catch (err) {
      const lastBubble = messagesEl.querySelector('.chat-msg--assistant:last-child .chat-msg__bubble');
      if (lastBubble) lastBubble.textContent = err && err.message ? err.message : 'Failed to get reply.';
    } finally {
      input.disabled = false;
      sendBtn.disabled = false;
      input.focus();
      scrollToBottom();
    }
  });
})();
</script>
