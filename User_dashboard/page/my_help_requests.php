<?php
if (session_status() === PHP_SESSION_NONE) { session_name('user_session'); session_start(); }
require '../config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($_POST['message']) || !empty($_FILES['media']['name']))) {
    $msg = trim($_POST['message'] ?? '');
    $mediaFile = null;

    if (!empty($_FILES['media']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov','pdf'];
        $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['media']['size'] <= 10 * 1024 * 1024) {
            $fname = time() . '_' . $userId . '.' . $ext;
            $dest  = __DIR__ . '/../uploads/help_media/' . $fname;
            if (move_uploaded_file($_FILES['media']['tmp_name'], $dest)) {
                $mediaFile = $fname;
            }
        }
    }

    if ($msg !== '' || $mediaFile) {
        $pdo->prepare("INSERT INTO help_requests (user_id, subject, message, media_file, status, sender, created_at) VALUES (?, 'Chat Support', ?, ?, 'Pending', 'user', NOW())")
            ->execute([$userId, $msg ?: '', $mediaFile]);
    }
    header("Location: my_help_requests.php"); exit;
}

$stmt = $pdo->prepare("SELECT * FROM help_requests WHERE user_id = ? ORDER BY created_at ASC");
$stmt->execute([$userId]);
$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msgMap = [];
foreach ($chats as $c) $msgMap[$c['id']] = $c;

$uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$uname = $uStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help & Support - Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../../favicon.ico">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    :root {
      --sidebar: 250px;
      --green: #25d366;
      --dark-green: #075e54;
      --light-green: #dcf8c6;
      --bg-chat: #efeae2;
      --white: #fff;
      --radius: 12px;
    }
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    html, body { height:100%; background:var(--bg-chat); }

    /* Layout */
    .chat-page { margin-left:var(--sidebar); height:100vh; display:flex; flex-direction:column; overflow:hidden; }
    .embed .chat-page { margin-left:0; }

    /* Header */
    .chat-header {
      background:var(--dark-green);
      padding:12px 20px;
      display:flex; align-items:center; gap:14px;
      flex-shrink:0;
      box-shadow:0 2px 8px rgba(0,0,0,0.2);
    }
    .hd-avatar {
      width:42px; height:42px; border-radius:50%;
      background:rgba(255,255,255,0.15);
      display:flex; align-items:center; justify-content:center;
      color:#fff; flex-shrink:0;
    }
    .hd-info h3 { font-size:0.95rem; font-weight:700; color:#fff; }
    .hd-info p  { font-size:0.72rem; color:rgba(255,255,255,0.75); display:flex; align-items:center; gap:5px; margin-top:2px; }
    .online-dot { width:7px; height:7px; background:var(--green); border-radius:50%; display:inline-block; animation:pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

    /* Chat body */
    .chat-body {
      flex:1; overflow-y:auto; padding:16px 20px;
      display:flex; flex-direction:column; gap:6px;
      background:var(--bg-chat);
      background-image:url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23000' fill-opacity='0.03'%3E%3Cpath d='M0 0h40v40H0zm40 40h40v40H40z'/%3E%3C/g%3E%3C/svg%3E");
    }
    .chat-body::-webkit-scrollbar { width:5px; }
    .chat-body::-webkit-scrollbar-thumb { background:#ccc; border-radius:10px; }

    /* Date divider */
    .date-divider { text-align:center; margin:10px 0; }
    .date-divider span { background:rgba(255,255,255,0.85); color:#667781; font-size:0.72rem; padding:4px 12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,0.1); }

    /* Message rows */
    .msg-row { display:flex; align-items:flex-end; gap:6px; max-width:75%; }
    .msg-row.user  { align-self:flex-end; flex-direction:row-reverse; }
    .msg-row.admin { align-self:flex-start; }

    .av { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0; margin-bottom:2px; }
    .av.u { background:linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; }
    .av.a { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }

    /* Bubble */
    .bubble {
      padding:8px 12px 6px;
      border-radius:var(--radius);
      font-size:0.85rem; line-height:1.55;
      position:relative; word-break:break-word;
      box-shadow:0 1px 2px rgba(0,0,0,0.12);
    }
    .bubble.user  { background:var(--light-green); color:#1e293b; border-bottom-right-radius:3px; }
    .bubble.admin { background:var(--white); color:#1e293b; border-bottom-left-radius:3px; }

    /* Reply quote inside bubble */
    .quote-box {
      background:rgba(0,0,0,0.06); border-left:3px solid var(--dark-green);
      border-radius:6px; padding:5px 10px; margin-bottom:7px;
      font-size:0.75rem; color:#555; cursor:pointer;
    }
    .bubble.user .quote-box { border-color:rgba(255,255,255,0.6); background:rgba(0,0,0,0.08); }
    .quote-box .q-name { font-weight:700; color:var(--dark-green); font-size:0.72rem; margin-bottom:2px; }
    .bubble.user .quote-box .q-name { color:#4f46e5; }
    .quote-box .q-text { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

    /* Time + tick */
    .bubble-foot { display:flex; align-items:center; justify-content:flex-end; gap:3px; margin-top:4px; }
    .bubble-time { font-size:0.65rem; color:#94a3b8; }
    .bubble.user .bubble-time { color:#6b9e6b; }
    .tick { font-size:12px; color:#6b9e6b; }

    /* Status pill */
    .s-pill { font-size:0.62rem; font-weight:700; padding:2px 7px; border-radius:10px; margin-left:4px; }
    .s-pill.pending    { background:#fef9c3; color:#92400e; }
    .s-pill.resolved   { background:#dcfce7; color:#166534; }
    .s-pill.in-progress { background:#dbeafe; color:#1e40af; }

    /* Typing dots */
    .typing-row { align-self:flex-start; display:flex; align-items:center; gap:6px; padding:4px 0; }
    .typing-bubble { background:#fff; border-radius:12px; padding:10px 14px; box-shadow:0 1px 2px rgba(0,0,0,0.1); display:flex; gap:4px; align-items:center; }
    .typing-bubble span { width:7px; height:7px; background:#94a3b8; border-radius:50%; animation:typing 1.2s infinite; }
    .typing-bubble span:nth-child(2) { animation-delay:.2s; }
    .typing-bubble span:nth-child(3) { animation-delay:.4s; }
    @keyframes typing { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-5px)} }

    /* Empty */
    .empty-state { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; color:#94a3b8; }
    .empty-state .material-icons-round { font-size:56px; opacity:0.4; }
    .empty-state p { font-size:0.88rem; }

    /* Footer */
    .chat-footer { background:#f0f0f0; padding:10px 14px; flex-shrink:0; display:flex; flex-direction:column; gap:6px; }

    /* Reply bar */
    .reply-bar { display:none; background:#fff; border-left:4px solid var(--dark-green); border-radius:8px; padding:8px 12px; font-size:0.78rem; color:#555; }
    .reply-bar.show { display:flex; align-items:center; gap:10px; }
    .rb-content { flex:1; min-width:0; }
    .rb-name { font-weight:700; color:var(--dark-green); font-size:0.72rem; }
    .rb-text { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:0.78rem; color:#667781; }
    .rb-close { cursor:pointer; color:#94a3b8; flex-shrink:0; }

    /* Input row */
    .input-row { display:flex; align-items:flex-end; gap:8px; }
    .input-row textarea {
      flex:1; border:none; border-radius:22px;
      padding:10px 16px; font-size:0.88rem;
      font-family:'Poppins',sans-serif; resize:none;
      outline:none; max-height:120px; line-height:1.5;
      background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.08);
    }
    .send-btn {
      width:46px; height:46px; border-radius:50%;
      background:var(--dark-green); border:none; color:#fff;
      cursor:pointer; display:flex; align-items:center; justify-content:center;
      flex-shrink:0; box-shadow:0 2px 6px rgba(0,0,0,0.2);
      transition:background 0.2s;
    }
    .send-btn:hover { background:#128c7e; }

    /* Mobile */
    @media(max-width:768px) {
      .chat-page { margin-left:0; height:100dvh; }
      .msg-row { max-width:88%; }
      .chat-footer { position:sticky; bottom:0; z-index:10; }
    }
  </style>
</head>
<body<?php if(isset($_GET['embed'])) echo ' class="embed"'; ?>>
<?php if (!isset($_GET['embed'])): include('../sidebar.php'); include('../mobile_header.php'); endif; ?>

<div class="chat-page">

  <!-- Header -->
  <div class="chat-header">
    <div class="hd-avatar"><span class="material-icons-round">support_agent</span></div>
    <div class="hd-info">
      <h3>Goldpay Support</h3>
      <p><span class="online-dot"></span> Online · Replies within 24 hours</p>
    </div>
  </div>

  <!-- Messages -->
  <div class="chat-body" id="chatBody">

    <?php if (empty($chats)): ?>
      <div class="empty-state">
        <span class="material-icons-round">chat_bubble_outline</span>
        <p>No messages yet. Say hello! 👋</p>
      </div>
    <?php else: ?>

      <div class="date-divider"><span>Today</span></div>

      <!-- Welcome bubble -->
      <div class="msg-row admin">
        <div class="av a"><span class="material-icons-round" style="font-size:13px">support_agent</span></div>
        <div class="bubble admin">
          👋 Hello <strong><?= htmlspecialchars($uname) ?></strong>! Welcome to Goldpay Support. How can we help you?
          <div class="bubble-foot"><span class="bubble-time">Support</span></div>
        </div>
      </div>

      <?php foreach ($chats as $chat):
        $isAdmin = ($chat['sender'] === 'admin');
        $msgText = $isAdmin ? ($chat['admin_reply'] ?? '') : $chat['message'];
        $replyToMsg = (!empty($chat['reply_to_id']) && isset($msgMap[$chat['reply_to_id']])) ? $msgMap[$chat['reply_to_id']] : null;
        $rowClass = $isAdmin ? 'admin' : 'user';
        $time = date('h:i A', strtotime($chat['created_at']));
        $statusClass = strtolower(str_replace(' ', '-', $chat['status'] ?? 'pending'));
      ?>
      <div class="msg-row <?= $rowClass ?>" id="msg-<?= $chat['id'] ?>">
        <div class="av <?= $isAdmin ? 'a' : 'u' ?>">
          <span class="material-icons-round" style="font-size:13px"><?= $isAdmin ? 'support_agent' : 'person' ?></span>
        </div>
        <div class="bubble <?= $rowClass ?>"
             data-id="<?= $chat['id'] ?>"
             data-text="<?= htmlspecialchars(mb_substr($msgText,0,60), ENT_QUOTES) ?>"
             data-name="<?= $isAdmin ? 'Support' : htmlspecialchars($uname, ENT_QUOTES) ?>">

          <?php if ($replyToMsg): ?>
            <?php $rText = $replyToMsg['sender']==='admin' ? ($replyToMsg['admin_reply']??'') : $replyToMsg['message']; ?>
            <div class="quote-box" onclick="scrollToMsg(<?= $replyToMsg['id'] ?>)">
              <div class="q-name"><?= $replyToMsg['sender']==='admin' ? 'Support' : htmlspecialchars($uname) ?></div>
              <div class="q-text"><?= htmlspecialchars(mb_substr($rText,0,60)) ?></div>
            </div>
          <?php endif; ?>

          <?= nl2br(htmlspecialchars($msgText)) ?>

          <?php if (!empty($chat['media_file'])): ?>
            <?php $mf = $chat['media_file']; $mext = strtolower(pathinfo($mf, PATHINFO_EXTENSION)); $murl = '../../User_dashboard/uploads/help_media/' . $mf; ?>
            <?php if (in_array($mext, ['jpg','jpeg','png','gif','webp'])): ?>
              <div style="margin-top:6px;">
                <a href="<?= $murl ?>" target="_blank">
                  <img src="<?= $murl ?>" style="max-width:220px;max-height:200px;border-radius:8px;display:block;">
                </a>
              </div>
            <?php elseif (in_array($mext, ['mp4','mov'])): ?>
              <video src="<?= $murl ?>" controls style="max-width:220px;border-radius:8px;margin-top:6px;display:block;"></video>
            <?php elseif ($mext === 'pdf'): ?>
              <a href="<?= $murl ?>" target="_blank" style="display:inline-flex;align-items:center;gap:6px;margin-top:6px;background:rgba(0,0,0,0.07);padding:6px 12px;border-radius:8px;font-size:0.78rem;color:#1e293b;text-decoration:none;">
                <span class="material-icons-round" style="font-size:16px;">picture_as_pdf</span> View PDF
              </a>
            <?php endif; ?>
          <?php endif; ?>

          <div class="bubble-foot">
            <span class="bubble-time"><?= $time ?></span>
            <span class="material-icons-round tick" style="font-size:13px">done_all</span>
          </div>
        </div>
      </div>

      <?php if (!$isAdmin && empty($chat['admin_reply']) && $chat === end($chats)): ?>
      <div class="typing-row">
        <div class="av a"><span class="material-icons-round" style="font-size:13px">support_agent</span></div>
        <div class="typing-bubble"><span></span><span></span><span></span></div>
      </div>
      <?php endif; ?>

      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Footer -->
  <div class="chat-footer">
    <div class="reply-bar" id="replyBar">
      <div class="rb-content">
        <div class="rb-name" id="rbName"></div>
        <div class="rb-text" id="rbText"></div>
      </div>
      <span class="material-icons-round rb-close" onclick="clearReply()">close</span>
    </div>
    <form method="POST" id="chatForm" enctype="multipart/form-data">
      <input type="hidden" name="reply_to_id" id="replyToId" value="">
      <!-- Media preview -->
      <div id="mediaPreview" style="display:none;padding:6px 0;">
        <div style="display:inline-flex;align-items:center;gap:8px;background:#fff;border-radius:8px;padding:6px 12px;font-size:0.78rem;color:#374151;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
          <span class="material-icons-round" style="font-size:16px;color:#6366f1;">attach_file</span>
          <span id="mediaFileName"></span>
          <span onclick="clearMedia()" style="cursor:pointer;color:#94a3b8;font-size:16px;">&times;</span>
        </div>
      </div>
      <div class="input-row">
        <input type="file" name="media" id="mediaInput" accept="image/*,video/*,.pdf" style="display:none;" onchange="previewMedia(this)">
        <button type="button" onclick="document.getElementById('mediaInput').click()" style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <span class="material-icons-round" style="font-size:20px;color:#64748b;">attach_file</span>
        </button>
        <textarea name="message" id="msgInput" placeholder="Type a message..." rows="1"
          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('chatForm').submit();}"></textarea>
        <button type="submit" class="send-btn"><span class="material-icons-round">send</span></button>
      </div>
    </form>
  </div>

</div>

<script>
  // Scroll to bottom
  const cb = document.getElementById('chatBody');
  if (cb) cb.scrollTop = cb.scrollHeight;

  // Auto resize textarea
  const ta = document.getElementById('msgInput');
  if (ta) ta.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
  });

  function previewMedia(input) {
    if (input.files && input.files[0]) {
      document.getElementById('mediaFileName').textContent = input.files[0].name;
      document.getElementById('mediaPreview').style.display = 'block';
    }
  }
  function clearMedia() {
    document.getElementById('mediaInput').value = '';
    document.getElementById('mediaPreview').style.display = 'none';
    document.getElementById('mediaFileName').textContent = '';
  }

  // Long press / right click to reply on bubble
  document.querySelectorAll('.bubble').forEach(b => {
    b.addEventListener('contextmenu', e => {
      e.preventDefault();
      setReply(b.dataset.id, b.dataset.text, b.dataset.name);
    });
    // Touch long press
    let timer;
    b.addEventListener('touchstart', () => { timer = setTimeout(() => setReply(b.dataset.id, b.dataset.text, b.dataset.name), 500); });
    b.addEventListener('touchend', () => clearTimeout(timer));
  });

  function setReply(id, text, name) {
    document.getElementById('replyToId').value = id;
    document.getElementById('rbName').textContent = name;
    document.getElementById('rbText').textContent = text + (text.length >= 60 ? '...' : '');
    document.getElementById('replyBar').classList.add('show');
    document.getElementById('msgInput').focus();
    // Flash original
    const orig = document.getElementById('msg-' + id);
    if (orig) { orig.style.opacity='0.5'; setTimeout(()=>orig.style.opacity='1',1000); }
  }

  function clearReply() {
    document.getElementById('replyToId').value = '';
    document.getElementById('replyBar').classList.remove('show');
  }

  function scrollToMsg(id) {
    const el = document.getElementById('msg-' + id);
    if (el) { el.scrollIntoView({behavior:'smooth',block:'center'}); el.style.opacity='0.4'; setTimeout(()=>el.style.opacity='1',1000); }
  }

  // ── Real-time polling ──
  let lastId = <?= !empty($chats) ? end($chats)['id'] : 0 ?>;
  const uname = <?= json_encode($uname) ?>;

  function appendMsg(chat) {
    const isAdmin = chat.sender === 'admin';
    const msgText = isAdmin ? (chat.admin_reply || '') : chat.message;
    const time = new Date(chat.created_at).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    const rowClass = isAdmin ? 'admin' : 'user';
    const avClass  = isAdmin ? 'a' : 'u';
    const icon     = isAdmin ? 'support_agent' : 'person';

    // Remove typing indicator if admin replied
    if (isAdmin) {
      const typing = document.querySelector('.typing-row');
      if (typing) typing.remove();
    }

    const div = document.createElement('div');
    div.className = 'msg-row ' + rowClass;
    div.id = 'msg-' + chat.id;
    div.innerHTML = `
      <div class="av ${avClass}"><span class="material-icons-round" style="font-size:13px">${icon}</span></div>
      <div class="bubble ${rowClass}" data-id="${chat.id}" data-text="${msgText.substring(0,60).replace(/"/g,'&quot;')}" data-name="${isAdmin ? 'Support' : uname}">
        ${msgText.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}
        <div class="bubble-foot">
          <span class="bubble-time">${time}</span>
          ${isAdmin ? '<span class="material-icons-round tick" style="font-size:13px">done_all</span>' : ''}
        </div>
      </div>`;

    // Re-attach reply listeners
    const bubble = div.querySelector('.bubble');
    bubble.addEventListener('contextmenu', e => { e.preventDefault(); setReply(bubble.dataset.id, bubble.dataset.text, bubble.dataset.name); });
    let timer;
    bubble.addEventListener('touchstart', () => { timer = setTimeout(() => setReply(bubble.dataset.id, bubble.dataset.text, bubble.dataset.name), 500); });
    bubble.addEventListener('touchend', () => clearTimeout(timer));

    cb.appendChild(div);
    cb.scrollTop = cb.scrollHeight;
    lastId = chat.id;
  }

  function pollMessages() {
    fetch('get_new_messages.php?after_id=' + lastId)
      .then(r => r.json())
      .then(msgs => { msgs.forEach(appendMsg); })
      .catch(() => {});
  }

  setInterval(pollMessages, 4000);
</script>
</body>
</html>
