<?php
/**
 * Script to add Group 2 students from tutor list
 * Run from command line: php add_group2_students.php
 */

require __DIR__ . '/../db.php';
$pdo = db();

$students = [
    ['name' => 'Gabriel Anuoluwapo Isreal', 'matric' => '2025003277', 'phone' => '9072476961'],
    ['name' => 'Oyewusi Oladayo Isaac', 'matric' => '2025005038', 'phone' => '9116112915'],
    ['name' => 'Onyemauzechi Chukwuebuka', 'matric' => '2025004321', 'phone' => '9165343485'],
    ['name' => 'Ayoola Franklyn Olusegun', 'matric' => '2025005237', 'phone' => '9038673125'],
    ['name' => 'Bakare Farouk Adebola', 'matric' => '2025003001', 'phone' => '9913 6252 925'],
    ['name' => 'Aiuko zainab kehinde', 'matric' => '2025004440', 'phone' => '0814 7009 713'],
    ['name' => 'Olonade Samuel Mayomikun', 'matric' => '2025004389', 'phone' => '7035236547'],
    ['name' => 'Aderemi Babatunde Mustapha', 'matric' => '2025004841', 'phone' => '9063171896'],
    ['name' => 'Abdulsalam Abdulwahab femi', 'matric' => '2025000671', 'phone' => '7061816910'],
    ['name' => 'Odelabi John Oluwagboogo', 'matric' => '2025002331', 'phone' => '7046594294'],
    ['name' => 'Adeyi Daniel Olumide', 'matric' => '2025000879', 'phone' => '7034755802'],
    ['name' => 'Ogunlola Muhammad Olatunde', 'matric' => '2025003272', 'phone' => '8089188642'],
    // Use phone as fallback identifier when matric is missing
    ['name' => 'Oladipo David', 'matric' => null, 'phone' => '7033535484'],
    ['name' => 'ojo Emmanuel oluwastemi', 'matric' => '2025004011', 'phone' => '8088732400'],
];

$pdo->beginTransaction();
$inserted = 0;
$skipped = 0;

try {
    foreach ($students as $student) {
        // Allow either matric or phone as identifier
        $identifier = $student['matric'] ?: $student['phone'];
        if (!$identifier) {
            echo "Skipping: {$student['name']} (no matric or phone)\n";
            $skipped++;
            continue;
        }

        // Check if already exists
        $check = $pdo->prepare('SELECT id FROM students WHERE identifier = ? AND group_id = 2');
        $check->execute([$identifier]);
        
        if ($check->fetch()) {
            echo "Already exists: {$student['name']} ({$student['matric']})\n";
            $skipped++;
            continue;
        }

        // Insert student
        $stmt = $pdo->prepare('INSERT INTO students (name, identifier, phone, group_id) VALUES (?, ?, ?, 2)');
        $stmt->execute([$student['name'], $identifier, $student['phone']]);
        $inserted++;
        echo "✓ Added: {$student['name']} ({$identifier})\n";
    }

    $pdo->commit();
    echo "\n=== SUMMARY ===\n";
    echo "Inserted: $inserted\n";
    echo "Skipped: $skipped\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: {$e->getMessage()}\n";
    exit(1);
}
?>
