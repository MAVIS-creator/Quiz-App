<?php
require __DIR__ . '/db.php';
$pdo = db();
$cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();
$sessions = $pdo->query('SELECT * FROM sessions')->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Admin</title><script src="https://cdn.tailwindcss.com"></script></head>
<body><div class="max-w-5xl mx-auto p-6">
<h1 class="text-2xl font-bold text-gray-800 mb-4">Admin Dashboard</h1>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="bg-white rounded-lg shadow border p-4"><h3 class="font-semibold mb-2">Config</h3>
    <form id="cfgForm">
      <label>Questions <input type="number" id="qcount" value="<?php echo $cfg['question_count'] ?? 40; ?>" min="1" max="100"></label>
      <label>Minutes <input type="number" id="minutes" value="<?php echo $cfg['exam_minutes'] ?? 60; ?>" min="5" max="300"></label>
      <button type="button" id="saveCfg">Save</button>
    </form>
  </div>
  <div class="bg-white rounded-lg shadow border p-4"><h3 class="font-semibold mb-2">Participants</h3>
    <table class="w-full text-sm"><thead><tr class="text-gray-500"><th class="text-left py-2">Name</th><th class="text-left py-2">ID</th><th class="text-left py-2">Progress</th><th class="text-left py-2">Violations</th><th class="text-left py-2">Last Saved</th></tr></thead><tbody>
    <?php foreach($sessions as $s): $prog = 0; $qids = json_decode($s['question_ids_json'] ?? '[]', true) ?: []; $ans = json_decode($s['answers_json'] ?? '[]', true) ?: []; $prog = count($qids)? intval(count($ans)/count($qids)*100):0; ?>
      <tr class="border-t">
        <td class="py-2"><?php echo htmlspecialchars($s['name'] ?? ''); ?></td>
        <td class="py-2"><?php echo htmlspecialchars($s['identifier'] ?? ''); ?></td>
        <td class="py-2"><?php echo $prog; ?>%</td>
        <td class="py-2"><span class="px-2 py-1 rounded text-xs <?php echo ($s['violations']>=3?'bg-red-100 text-red-800':($s['violations']>=1?'bg-yellow-100 text-yellow-800':'bg-green-100 text-green-800')); ?>"><?php echo $s['violations']; ?>/3</span></td>
        <td class="py-2"><?php echo htmlspecialchars($s['last_saved'] ?? '—'); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table>
  </div>
  <div class="bg-white rounded-lg shadow border p-4"><h3 class="font-semibold mb-2">Message Student</h3>
    <form id="msgForm">
      <input type="text" id="receiver" placeholder="Student ID">
      <textarea id="msgText" placeholder="Message"></textarea>
      <button type="button" id="sendMsg">Send</button>
    </form>
  </div>
  <div class="bg-white rounded-lg shadow border p-4"><h3 class="font-semibold mb-2">Latest Snapshot</h3>
    <form id="snapForm">
      <input type="text" id="snapId" placeholder="Student ID">
      <button type="button" id="loadSnap">Load</button>
    </form>
    <div id="snapResult"></div>
  </div>
</div>
</div>
<script>
const API = '/api';

document.getElementById('saveCfg').onclick = async () => {
  const questionCount = Number(document.getElementById('qcount').value);
  const examMinutes = Number(document.getElementById('minutes').value);
  const res = await fetch(API+'/config', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({questionCount, examMinutes})});
  alert(res.ok? 'Saved' : 'Failed');
};

document.getElementById('sendMsg').onclick = async () => {
  const receiver = document.getElementById('receiver').value.trim();
  const text = document.getElementById('msgText').value.trim();
  if(!receiver||!text){ alert('receiver and text required'); return; }
  const res = await fetch(API+'/messages', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({sender:'admin',receiver,text})});
  alert(res.ok? 'Sent' : 'Failed');
};

document.getElementById('loadSnap').onclick = async () => {
  const id = document.getElementById('snapId').value.trim();
  const res = await fetch(API+'/snapshot?identifier='+encodeURIComponent(id));
  const data = await res.json();
  document.getElementById('snapResult').innerHTML = data.image ? `<img src="${data.image}" width="320"> <div class=muted>${data.timestamp}</div>` : 'No snapshot';
};
</script>
</body></html>
