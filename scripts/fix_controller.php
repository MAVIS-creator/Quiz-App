<?php
$file = __DIR__ . '/../quiz_new.php';
$content = file_get_contents($file);

// First, add the controller declaration before the try block
$search1 = '                    // Retry logic for network resilience
                    while (retries > 0) {
                        try {
                            response = await fetch(';

$replace1 = '                    // Retry logic for network resilience
                    while (retries > 0) {
                        try {
                            const controller = new AbortController();
                            const timeoutId = setTimeout(() => controller.abort(), 30000);
                            
                            response = await fetch(';

$content = str_replace($search1, $replace1, $content);

file_put_contents($file, $content);
echo "Fixed! Added AbortController declaration.\n";
