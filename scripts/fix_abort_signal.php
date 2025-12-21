<?php
$file = __DIR__ . '/../quiz_new.php';
$content = file_get_contents($file);
$newContent = str_replace('signal: AbortSignal.timeout(30000)', 'signal: controller.signal', $content);
$newContent = str_replace('                    try {
                        response = await fetch(', '                    try {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 30000);
                        response = await fetch(', $newContent);
$newContent = str_replace('                        data = await response.json();', '                        clearTimeout(timeoutId);
                        data = await response.json();', $newContent);
file_put_contents($file, $newContent);
echo 'Fixed!\n';
