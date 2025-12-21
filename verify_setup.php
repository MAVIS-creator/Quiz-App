<?php
/**
 * Quick verification script to check if enhanced proctoring is working
 */

echo "==========================================\n";
echo "Enhanced Proctoring System Verification\n";
echo "==========================================\n\n";

// Check 1: Composer
echo "1. Checking Composer...\n";
$composerPath = __DIR__ . '/composer.json';
if (file_exists($composerPath)) {
    echo "   ✓ composer.json found\n";
} else {
    echo "   ✗ composer.json NOT found\n";
}

// Check 2: Vendor folder
echo "\n2. Checking Intervention Image installation...\n";
$vendorPath = __DIR__ . '/vendor';
$autoloadPath = $vendorPath . '/autoload.php';
$interventionPath = $vendorPath . '/intervention';

if (file_exists($vendorPath)) {
    echo "   ✓ vendor folder exists\n";
    
    if (file_exists($autoloadPath)) {
        echo "   ✓ autoload.php found\n";
        require $autoloadPath;
        
        if (class_exists('Intervention\Image\ImageManagerStatic')) {
            echo "   ✓ Intervention Image class available\n";
            echo "   ✓ Enhanced image processing ENABLED\n";
        } else {
            echo "   ✗ Intervention Image class NOT available\n";
        }
    } else {
        echo "   ✗ autoload.php NOT found\n";
        echo "   → Run: composer install\n";
    }
} else {
    echo "   ✗ vendor folder NOT found\n";
    echo "   → Run: composer install\n";
}

// Check 3: PHP GD/Imagick
echo "\n3. Checking PHP image extensions...\n";
$hasGD = extension_loaded('gd');
$hasImagick = extension_loaded('imagick');

if ($hasGD) {
    echo "   ✓ GD extension loaded\n";
} else {
    echo "   ✗ GD extension NOT loaded\n";
}

if ($hasImagick) {
    echo "   ✓ Imagick extension loaded\n";
} else {
    echo "   ℹ Imagick extension NOT loaded (optional)\n";
}

if (!$hasGD && !$hasImagick) {
    echo "   ⚠ WARNING: No image extension available!\n";
    echo "   → Intervention Image requires GD or Imagick\n";
}

// Check 4: Uploads directory
echo "\n4. Checking uploads directory...\n";
$uploadsPath = __DIR__ . '/uploads';
if (file_exists($uploadsPath)) {
    echo "   ✓ uploads folder exists\n";
    if (is_writable($uploadsPath)) {
        echo "   ✓ uploads folder is writable\n";
    } else {
        echo "   ✗ uploads folder is NOT writable\n";
        echo "   → Run: chmod 755 uploads\n";
    }
} else {
    echo "   ℹ uploads folder will be created automatically\n";
}

// Check 5: API files
echo "\n5. Checking API files...\n";
$snapshotApi = __DIR__ . '/api/snapshot.php';
if (file_exists($snapshotApi)) {
    echo "   ✓ api/snapshot.php found\n";
    
    // Check if it has Intervention Image code
    $content = file_get_contents($snapshotApi);
    if (strpos($content, 'Intervention\Image') !== false) {
        echo "   ✓ Intervention Image integration detected\n";
    } else {
        echo "   ℹ Basic image processing (no Intervention Image)\n";
    }
} else {
    echo "   ✗ api/snapshot.php NOT found\n";
}

// Check 6: Frontend integration
echo "\n6. Checking frontend integration...\n";
$quizPage = __DIR__ . '/quiz_new.php';
if (file_exists($quizPage)) {
    echo "   ✓ quiz_new.php found\n";
    
    $content = file_get_contents($quizPage);
    if (strpos($content, 'face-api') !== false) {
        echo "   ✓ face-api.js integration detected\n";
    } else {
        echo "   ✗ face-api.js NOT integrated\n";
    }
    
    if (strpos($content, 'faceApiModelsLoaded') !== false) {
        echo "   ✓ Face detection variables found\n";
    } else {
        echo "   ℹ Face detection variables may need updating\n";
    }
} else {
    echo "   ✗ quiz_new.php NOT found\n";
}

// Summary
echo "\n==========================================\n";
echo "Summary\n";
echo "==========================================\n\n";

$interventionReady = file_exists($autoloadPath) && class_exists('Intervention\Image\ImageManagerStatic', false);
$faceApiReady = file_exists($quizPage) && strpos(file_get_contents($quizPage), 'face-api') !== false;

if ($interventionReady && $faceApiReady) {
    echo "✓ SYSTEM READY\n";
    echo "\nBoth frontend and backend enhancements are active:\n";
    echo "  • face-api.js for smart face detection\n";
    echo "  • Intervention Image for watermarks & compression\n";
    echo "\nTest your system:\n";
    echo "  → Open: http://localhost/Quiz-App/test_proctoring.html\n";
} elseif ($faceApiReady && !$interventionReady) {
    echo "⚠ PARTIAL SETUP\n";
    echo "\nFrontend ready, but backend needs setup:\n";
    echo "  ✓ face-api.js integrated\n";
    echo "  ✗ Intervention Image not installed\n";
    echo "\nTo complete setup:\n";
    echo "  → Run: composer install\n";
    echo "  → Or double-click: install.bat (Windows)\n";
} else {
    echo "✗ SETUP INCOMPLETE\n";
    echo "\nPlease complete the installation:\n";
    echo "  1. Run: composer install\n";
    echo "  2. Verify quiz_new.php has face-api.js integration\n";
    echo "  3. Run this script again to verify\n";
}

echo "\n==========================================\n";
