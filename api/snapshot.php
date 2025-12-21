<?php
require __DIR__ . '/../db.php';

// Check if Intervention Image is available
$useIntervention = file_exists(__DIR__ . '/../vendor/autoload.php');
if ($useIntervention) {
    require __DIR__ . '/../vendor/autoload.php';
    use Intervention\Image\ImageManagerStatic as Image;
}

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $id = $_GET['identifier'] ?? null;
        if (!$id) json_out(['error' => 'identifier required'], 400);
        $type = strtolower($_GET['type'] ?? 'preview'); // preview | violation
        $limit = max(1, intval($_GET['limit'] ?? 1));

        // Filter by filename prefix - files are now stored as {identifier}/{prefix}{identifier}_...
        $prefix = $type === 'violation' ? 'snapshotv_' : 'snapshot_';
        $sql = 'SELECT filename, timestamp FROM snapshots WHERE identifier=? AND filename LIKE ? ORDER BY timestamp DESC LIMIT ' . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $id . '/' . $prefix . '%']);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            if ($row && $row['filename']) {
                $path = ltrim($row['filename'], '/');
                $row['url'] = '/Quiz-App/uploads/' . $path;
            }
        }

        if ($limit === 1) {
            $row = $rows[0] ?? null;
            json_out($row ?: ['filename' => null, 'timestamp' => null, 'url' => null]);
        } else {
            json_out(['items' => $rows]);
        }
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) json_out(['error' => 'Invalid payload'], 400);
        $id = $data['identifier'] ?? null;
        $image = $data['image'] ?? null;
        $type = strtolower($data['type'] ?? 'preview'); // preview | violation
        $faceCount = isset($data['faceCount']) ? (int)$data['faceCount'] : null;
        
        if (!$id || !$image) json_out(['error' => 'identifier and image required'], 400);

        // Create uploads/{identifier} directory if it doesn't exist
        $uploadsDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $idDir = $uploadsDir . '/' . $id;
        if (!is_dir($idDir)) {
            mkdir($idDir, 0755, true);
        }

        // Convert data URL to file
        if (preg_match('/^data:image\/(\w+);base64,(.*)$/', $image, $m)) {
            $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
            $prefix = $type === 'violation' ? 'snapshotv_' : 'snapshot_';
            $baseName = $prefix . $id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $filename = $id . '/' . $baseName;
            $filepath = $uploadsDir . '/' . $filename;
            
            // Use Intervention Image if available for better processing
            if ($useIntervention) {
                try {
                    $img = Image::make($image);
                    
                    // Resize to standard width (save disk space)
                    $img->resize(640, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    // Add watermark with timestamp and student ID
                    $timestamp = date('Y-m-d H:i:s');
                    $watermarkText = "{$id} | {$timestamp}";
                    if ($faceCount !== null) {
                        $watermarkText .= " | Faces: {$faceCount}";
                    }
                    
                    // Draw semi-transparent background for watermark
                    $textWidth = strlen($watermarkText) * 9;
                    $img->rectangle(5, $img->height() - 30, $textWidth + 10, $img->height() - 5, function ($draw) {
                        $draw->background('rgba(0, 0, 0, 0.7)');
                    });
                    
                    // Add text watermark (will use default GD font if custom font not available)
                    try {
                        // Try with custom font first
                        $fontPath = __DIR__ . '/../assets/arial.ttf';
                        if (file_exists($fontPath)) {
                            $img->text($watermarkText, 10, $img->height() - 10, function($font) use ($fontPath) {
                                $font->file($fontPath);
                                $font->size(14);
                                $font->color('#ffffff');
                                $font->align('left');
                                $font->valign('bottom');
                            });
                        } else {
                            // Fallback to GD built-in font
                            $img->text($watermarkText, 10, $img->height() - 15, function($font) {
                                $font->size(3); // GD font size
                                $font->color('#ffffff');
                                $font->align('left');
                                $font->valign('bottom');
                            });
                        }
                    } catch (Exception $fontError) {
                        // Silent fail on font errors
                        error_log("Watermark font error: " . $fontError->getMessage());
                    }
                    
                    // Save with compression
                    $img->save($filepath, 80); // 80% quality
                    
                } catch (Exception $e) {
                    // Fallback to basic save if Intervention fails
                    $rawData = base64_decode($m[2]);
                    if (file_put_contents($filepath, $rawData) === false) {
                        json_out(['error' => 'Failed to save file'], 500);
                    }
                }
            } else {
                // Basic save without Intervention Image
                $rawData = base64_decode($m[2]);
                if (file_put_contents($filepath, $rawData) === false) {
                    json_out(['error' => 'Failed to save file'], 500);
                }
            }
            
            // Save to database
            $pdo->prepare('INSERT INTO snapshots(identifier,filename) VALUES (?,?)')->execute([$id, $filename]);
            json_out([
                'ok' => true, 
                'type' => $type, 
                'filename' => $filename, 
                'url' => '/Quiz-App/uploads/' . $filename,
                'processed_with' => $useIntervention ? 'intervention' : 'basic'
            ]);
        } else {
            json_out(['error' => 'Invalid image format'], 400);
        }
    }

    json_out(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    json_out(['error' => $e->getMessage()], 500);
}
