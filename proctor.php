<?php
require __DIR__ . '/db.php';
$pdo = db();
$violCounts = $pdo->query('SELECT identifier, COUNT(*) as count FROM violations GROUP BY identifier')->fetchAll();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Proctor</title><script src="https://cdn.tailwindcss.com"></script></head>
<body><div class="max-w-5xl mx-auto p-6">
<h1 class="text-2xl font-bold text-gray-800 mb-4">Proctor Dashboard</h1>
<div class="bg-white rounded-lg shadow border p-4"><h3 class="font-semibold mb-2">Violations</h3>
<table class="w-full text-sm"><thead><tr class="text-gray-500"><th class="text-left py-2">Student</th><th class="text-left py-2">Count</th></tr></thead><tbody>
<?php foreach($violCounts as $v): ?>
<tr><td><?php echo htmlspecialchars($v['identifier']); ?></td><td><?php echo intval($v['count']); ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
<div class="bg-white rounded-lg shadow border p-4"><h3 class="font-semibold mb-2">Live Snapshot</h3>
  <form id="snapForm">
    <input type="text" id="snapId" placeholder="Student ID">
    <button type="button" id="loadSnap">Load</button>
  </form>
  <div id="snapResult"></div>
</div>
</div>
<script>
const API = '/api';
document.getElementById('loadSnap').onclick = async () => {
  const id = document.getElementById('snapId').value.trim();
  const res = await fetch(API+'/snapshot?identifier='+encodeURIComponent(id));
  const data = await res.json();
  document.getElementById('snapResult').innerHTML = data.image ? `<img src="${data.image}" width="320"> <div class=muted>${data.timestamp}</div>` : 'No snapshot';
};
setInterval(() => { document.getElementById('loadSnap').click(); }, 2000);
</script>
</body></html>
