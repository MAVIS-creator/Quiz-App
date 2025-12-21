<?php
/**
 * API: Calculate student accuracy and performance metrics
 * Returns detailed statistics about student's quiz performance
 */

require_once '../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = db();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get all students or specific student
        $identifier = $_GET['identifier'] ?? null;
        
        $query = 'SELECT s.*, 
                         (SELECT COUNT(*) FROM violations WHERE identifier = s.identifier) as violation_count
                  FROM sessions s';
        
        if ($identifier) {
            $query .= ' WHERE s.identifier = ?';
            $stmt = $pdo->prepare($query);
            $stmt->execute([$identifier]);
        } else {
            $stmt = $pdo->query($query);
        }
        
        $students = $stmt->fetchAll();
        $results = [];
        
        foreach ($students as $student) {
            if (!$student['submitted']) {
                $results[] = [
                    'identifier' => $student['identifier'],
                    'name' => $student['name'],
                    'status' => $student['status'],
                    'accuracy' => 0,
                    'score' => 0,
                    'total_questions' => 0,
                    'avg_time_per_question' => 0,
                    'violations' => $student['violation_count'],
                    'submitted' => false
                ];
                continue;
            }
            
            $answers = json_decode($student['answers_json'], true) ?? [];
            $timings = json_decode($student['timings_json'], true) ?? [];
            $questionIds = json_decode($student['question_ids_json'], true) ?? [];
            
            // Get correct answers
            $correctCount = 0;
            $totalQuestions = count($questionIds);
            
            foreach ($questionIds as $qid) {
                $qStmt = $pdo->prepare('SELECT option_a, option_b, option_c, option_d, answer FROM questions WHERE id = ?');
                $qStmt->execute([$qid]);
                $qRow = $qStmt->fetch();
                if (!$qRow) continue;

                $optA = trim($qRow['option_a'] ?? '');
                $optB = trim($qRow['option_b'] ?? '');
                $optC = trim($qRow['option_c'] ?? '');
                $optD = trim($qRow['option_d'] ?? '');
                $correctField = trim($qRow['answer'] ?? '');

                // Determine correct letter from stored answer
                $correctLetter = null;
                $correctLower = strtolower($correctField);
                if (in_array($correctLower, ['a','b','c','d'], true)) {
                    $correctLetter = strtoupper($correctLower);
                } else {
                    // Match by option text
                    if (strcasecmp($correctField, $optA) === 0) $correctLetter = 'A';
                    elseif (strcasecmp($correctField, $optB) === 0) $correctLetter = 'B';
                    elseif (strcasecmp($correctField, $optC) === 0) $correctLetter = 'C';
                    elseif (strcasecmp($correctField, $optD) === 0) $correctLetter = 'D';
                }

                $studentAnswer = $answers[$qid] ?? '';
                $studentLower = strtolower(trim($studentAnswer));

                // Compare robustly: letters or full text
                $isCorrect = false;
                if (in_array($studentLower, ['a','b','c','d'], true)) {
                    $isCorrect = ($correctLetter && $studentLower === strtolower($correctLetter));
                } else {
                    // Compare text
                    if ($correctLetter === 'A') $isCorrect = (strcasecmp($studentAnswer, $optA) === 0);
                    elseif ($correctLetter === 'B') $isCorrect = (strcasecmp($studentAnswer, $optB) === 0);
                    elseif ($correctLetter === 'C') $isCorrect = (strcasecmp($studentAnswer, $optC) === 0);
                    elseif ($correctLetter === 'D') $isCorrect = (strcasecmp($studentAnswer, $optD) === 0);
                    else $isCorrect = (strcasecmp($studentAnswer, $correctField) === 0);
                }

                if ($isCorrect) {
                    $correctCount++;
                }
            }
            
            // Calculate metrics
            $accuracy = $totalQuestions > 0 ? ($correctCount / $totalQuestions) * 100 : 0;
            $avgTime = $totalQuestions > 0 ? array_sum($timings) / $totalQuestions : 0;
            
            // Update session with calculated metrics
            $updateStmt = $pdo->prepare('UPDATE sessions 
                                         SET accuracy_score = ?, avg_time_per_question = ? 
                                         WHERE identifier = ?');
            $updateStmt->execute([$accuracy, $avgTime, $student['identifier']]);
            
            $results[] = [
                'identifier' => $student['identifier'],
                'name' => $student['name'],
                'status' => $student['status'],
                'accuracy' => round($accuracy, 2),
                'score' => $correctCount,
                'total_questions' => $totalQuestions,
                'avg_time_per_question' => round($avgTime, 2),
                'violations' => $student['violation_count'],
                'time_adjustment' => $student['time_adjustment_seconds'],
                'point_deduction' => $student['point_deduction'],
                'submitted' => true
            ];
        }
        
        if ($identifier) {
            json_out($results[0] ?? ['error' => 'Student not found']);
        } else {
            json_out(['students' => $results]);
        }
    }
    
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
