<?php
// Reorganize snapshot and audio files into uploads/{identifier}/ folders and update DB paths
require __DIR__ . '/../db.php';

$root = realpath(__DIR__ . '/../uploads');
if (!$root || !is_dir($root)) {
    fwrite(STDERR, "uploads directory not found\n");
    exit(1);
}

$pdo = db();

function ensureDir($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function moveFile($from, $to)
{
    ensureDir(dirname($to));
    if (!is_file($from)) {
        return false;
    }
    return rename($from, $to);
}

$totalSnapshots = 0;
$updatedSnapshots = 0;
$totalAudio = 0;
$updatedAudio = 0;

// Handle snapshots (filename column assumed)
$snapStmt = $pdo->query('SELECT id, identifier, filename FROM snapshots');
$snapRows = $snapStmt->fetchAll();
foreach ($snapRows as $row) {
    $fname = $row['filename'];
    if (!$fname || strpos($fname, '/') !== false) {
        continue; // already nested or empty
    }
    $identifier = $row['identifier'];
    $src = $root . '/' . $fname;
    $dstRel = $identifier . '/' . $fname;
    $dst = $root . '/' . $dstRel;
    $totalSnapshots++;
    if (moveFile($src, $dst)) {
        $upd = $pdo->prepare('UPDATE snapshots SET filename=? WHERE id=?');
        $upd->execute([$dstRel, $row['id']]);
        $updatedSnapshots++;
        echo "Moved snapshot {$fname} -> {$dstRel}\n";
    } else {
        echo "WARNING: cannot move snapshot {$fname}\n";
    }
}

// Handle audio clips if filename column exists
$hasFilename = $pdo->query("SHOW COLUMNS FROM audio_clips LIKE 'filename'")->fetch();
if ($hasFilename) {
    $audioStmt = $pdo->query('SELECT id, identifier, filename FROM audio_clips');
    $audioRows = $audioStmt->fetchAll();
    foreach ($audioRows as $row) {
        $fname = $row['filename'];
        if (!$fname || strpos($fname, '/') !== false) {
            continue; // already nested or empty
        }
        $identifier = $row['identifier'];
        $src = $root . '/' . $fname;
        $dstRel = $identifier . '/' . $fname;
        $dst = $root . '/' . $dstRel;
        $totalAudio++;
        if (moveFile($src, $dst)) {
            $upd = $pdo->prepare('UPDATE audio_clips SET filename=? WHERE id=?');
            $upd->execute([$dstRel, $row['id']]);
            $updatedAudio++;
            echo "Moved audio {$fname} -> {$dstRel}\n";
        } else {
            echo "WARNING: cannot move audio {$fname}\n";
        }
    }
}

echo "\nSummary:\n";
echo "Snapshots moved: {$updatedSnapshots} / {$totalSnapshots}\n";
echo "Audio moved: {$updatedAudio} / {$totalAudio}\n";
