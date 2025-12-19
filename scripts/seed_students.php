<?php
require __DIR__ . '/../db.php';

$students = [
    ['matric' => '2025000831', 'name' => 'SANNI Olayinka', 'phone' => '8084343242', 'group_id' => 1],
    ['matric' => '2025002070', 'name' => 'Shobodun Faridat Tolulope', 'phone' => '9128823922', 'group_id' => 1],
    ['matric' => '2025000776', 'name' => 'DIPEOLU AMAL TITILOPE', 'phone' => '8063966934', 'group_id' => 1],
    ['matric' => 'NIL',        'name' => 'Jamiu Abdullahi Olalekan', 'phone' => '7073247811', 'group_id' => 1],
    ['matric' => '2025003523', 'name' => 'LIGALI OLUWASEGUN OLUMAYOWA', 'phone' => '8126479848', 'group_id' => 1],
    ['matric' => '2025002782', 'name' => 'Adekanye seyi semilore', 'phone' => '9115660920', 'group_id' => 1],
    ['matric' => '2025007581', 'name' => 'Adepetu Peter taiwo', 'phone' => '8077923006', 'group_id' => 1],
    ['matric' => '2025001994', 'name' => 'Taofeeq uthman Timilehin', 'phone' => '8122069891', 'group_id' => 1],
    ['matric' => '2025007041', 'name' => 'Oluwafemi Daniel Iyiola', 'phone' => '8128711370', 'group_id' => 1],
    ['matric' => '2025003519', 'name' => 'Alagbe Michael Kehinde', 'phone' => '8128972860', 'group_id' => 1],
    ['matric' => '2025006425', 'name' => 'Ojeabi-Champion Praise Erinayo', 'phone' => '9069380243', 'group_id' => 1],
    ['matric' => '2025003870', 'name' => 'Obiye Isaac Osareemen', 'phone' => '9114220817', 'group_id' => 1],
    ['matric' => '2025003210', 'name' => 'ADEMOLA BOLUWATIFE JEREMIAH', 'phone' => '8025073532', 'group_id' => 1],
    ['matric' => '2025002074', 'name' => 'Olatunji Testimony Israel', 'phone' => '9015037316', 'group_id' => 1],
    ['matric' => 'TEST001',    'name' => 'Test Student', 'phone' => '1234567890', 'group_id' => 1],
    // Group 2 sample/test student
    ['matric' => 'G2TEST001',  'name' => 'Group2 Test Student', 'phone' => '0000000002', 'group_id' => 2],
];

try {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO students (identifier, name, phone, group_id) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), group_id = VALUES(group_id)');
    $count = 0;
    foreach ($students as $s) {
        $stmt->execute([$s['matric'], $s['name'], $s['phone'], $s['group_id']]);
        $count++;
    }
    echo "Seeded $count students (Group 1 + Group 2 test).\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit(1);
}
