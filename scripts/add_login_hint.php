<?php
$file = __DIR__ . '/login.php';
$content = file_get_contents($file);

// Find the existing hint text and replace it with a more detailed troubleshooting hint
$oldHint = '<p class="text-xs text-gray-500 mt-1">You can use either your matric number or phone number</p>';

$newHint = '<p class="text-xs text-gray-500 mt-1">You can use either your matric number or phone number</p>
                    <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-xs text-blue-800 font-semibold mb-1 flex items-center">
                            <i class=\'bx bx-info-circle mr-1\'></i> Troubleshooting Tips:
                        </p>
                        <ul class="text-xs text-blue-700 space-y-1 ml-4">
                            <li>• Enter matric number <strong>without spaces</strong> (e.g., 2025003519)</li>
                            <li>• If using phone, try <strong>without leading 0</strong> (e.g., 8012345678)</li>
                            <li>• Make sure you\'re registered in the system</li>
                        </ul>
                    </div>';

$content = str_replace($oldHint, $newHint, $content);

file_put_contents($file, $content);
echo "Added troubleshooting hint to login page!\n";
