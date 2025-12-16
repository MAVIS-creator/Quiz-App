<?php
require __DIR__ . '/db.php';
$pdo = db();
$cfg = $pdo->query('SELECT exam_minutes, question_count FROM config WHERE id=1')->fetch();
$examMin = $cfg['exam_minutes'] ?? 60;
$count = $cfg['question_count'] ?? 40;
$qs = $pdo->query('SELECT * FROM questions ORDER BY RANDOM() LIMIT ' . intval($count))->fetchAll();

?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Quiz</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
<div class="max-w-4xl mx-auto p-6">
  <h1 class="text-2xl font-bold text-gray-800 mb-4">HTML & CSS Quiz</h1>
  <form id="quizForm" class="space-y-4">
    <input type="hidden" id="identifier" value="<?php echo htmlspecialchars($_GET['id'] ?? 'student-' . rand(1000,9999)); ?>">
    <input type="hidden" id="name" value="<?php echo htmlspecialchars($_GET['name'] ?? 'Student'); ?>">
    <div id="timer" class="text-gray-700">Time: <span id="timeLeft" class="font-semibold"><?php echo $examMin*60; ?></span>s</div>
    <?php foreach ($qs as $idx => $q): ?>
      <div class="bg-white rounded-lg shadow border p-4">
        <div class="text-sm text-gray-500 mb-2">Category: <?php echo htmlspecialchars($q['category']); ?></div>
        <div class="text-lg font-semibold mb-3"><?php echo htmlspecialchars($q['prompt']); ?></div>
        <?php $opts = [$q['option_a'],$q['option_b'],$q['option_c'],$q['option_d']]; shuffle($opts); foreach ($opts as $opt): ?>
          <label class="block py-2"><input class="mr-2" type="radio" name="q<?php echo $q['id']; ?>" value="<?php echo htmlspecialchars($opt); ?>"> <?php echo htmlspecialchars($opt); ?></label>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <button type="button" id="submitBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Submit Quiz</button>
  </form>
  <div id="snapshotStatus" class="text-sm text-gray-500"></div>
</div>

<script>
const API = '/api';
const id = document.getElementById('identifier').value;
const name = document.getElementById('name').value;
let violations = 0;
let questionIds = Array.from(document.querySelectorAll('input[type=radio]'))
  .map(i => i.name.replace('q','')).filter((v,i,a)=>a.indexOf(v)===i);
let timings = [];
let questionStart = Date.now();

// Save session periodically
async function saveSession(submitted=false) {
  const answers = {};
  questionIds.forEach(qid => {
    const sel = document.querySelector('input[name="q'+qid+'"]:checked');
    if (sel) answers[qid] = sel.value;
  });
  const payload = { identifier: id, name, submitted: submitted?1:0, answers, questionTimings: timings, questionIds, violations, examMinutes: <?php echo $examMin; ?> };
  await fetch(API+'/sessions', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
}
setInterval(saveSession, 5000);

// Tab visibility (less strict): count violation only if hidden >5s
let hideTimer = null; let wasHidden = false;
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    wasHidden = true;
    hideTimer = setTimeout(async () => {
      if (wasHidden && document.hidden) {
        violations = Math.min(3, violations+1);
        await fetch(API+'/violations', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({identifier:id,type:'tab-switch',severity:1,message:'Stayed out >5s'})});
        saveSession();
        alert('Warning: tab switch violation '+violations+'/3');
        if (violations>=3) { alert('Quiz terminated'); submitQuiz(true); }
      }
    }, 5000);
  } else { wasHidden=false; if (hideTimer) { clearTimeout(hideTimer); hideTimer=null; } }
});

// Camera snapshot every 2s (best-effort)
(async function initCamera(){
  try {
    const stream = await navigator.mediaDevices.getUserMedia({video:true,audio:false});
    const video = document.createElement('video'); video.srcObject=stream; await video.play();
    setInterval(async () => {
      const canvas = document.createElement('canvas'); canvas.width=320; canvas.height=240;
      const ctx = canvas.getContext('2d'); ctx.drawImage(video,0,0,320,240);
      const dataUrl = canvas.toDataURL('image/jpeg',0.4);
      await fetch(API+'/snapshot', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({identifier:id,image:dataUrl})});
      document.getElementById('snapshotStatus').textContent = 'Snapshot sent';
    }, 2000);
  } catch(e) {
    document.getElementById('snapshotStatus').textContent = 'Camera unavailable';
  }
})();

// Track time per question
Array.from(document.querySelectorAll('input[type=radio]')).forEach(el => {
  el.addEventListener('change', () => {
    const qid = el.name.replace('q','');
    const timeSpent = (Date.now()-questionStart)/1000;
    timings = timings.filter(t => t.questionId != qid);
    timings.push({questionId: Number(qid), timeSpent, timestamp: new Date().toISOString()});
    questionStart = Date.now();
  });
});

function submitQuiz(forced=false){
  showResultModal();
  saveSession(true).then(()=>{ alert('Submitted!'); window.location = '/'; });
}

document.getElementById('submitBtn').addEventListener('click', () => submitQuiz(false));

function showResultModal(){
  const answers = {};
  questionIds.forEach(qid => {
    const sel = document.querySelector('input[name="q'+qid+'"]:checked');
    if (sel) answers[qid] = sel.value;
  });
  let total = questionIds.length; let correct=0;
  // Fetch correct answers from DOM not available; simple summary only
  const html = `<div class=\"fixed inset-0 bg-black/50 flex items-center justify-center\">
    <div class=\"bg-white rounded-lg shadow w-full max-w-md p-6\">
      <h2 class=\"text-xl font-bold mb-3\">Quiz Summary</h2>
      <div class=\"space-y-1 text-gray-700\">
        <p><span class=\"font-semibold\">Name:</span> ${name}</p>
        <p><span class=\"font-semibold\">Answered:</span> ${Object.keys(answers).length}/${total}</p>
        <p><span class=\"font-semibold\">Violations:</span> ${violations}/3</p>
      </div>
      <div class=\"mt-4 flex justify-end\"><button class=\"px-4 py-2 bg-blue-600 text-white rounded\" onclick=\"this.closest('[class~=fixed]').remove()\">Close</button></div>
    </div>
  </div>`;
  const div = document.createElement('div'); div.innerHTML = html; document.body.appendChild(div.firstChild);
}
</script>
</body>
</html>
