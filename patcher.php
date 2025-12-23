<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.html');
    exit;
}
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Patcher - Code Repair Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <style>
        .code-editor {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            tab-size: 4;
        }
        
        .diff-line {
            padding: 2px 8px;
            border-left: 3px solid transparent;
        }
        
        .diff-added {
            background-color: #d4edda;
            border-left-color: #28a745;
        }
        
        .diff-removed {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .diff-unchanged {
            background-color: #f8f9fa;
            border-left-color: #e9ecef;
        }
        
        .file-item:hover {
            background-color: #f3f4f6;
            cursor: pointer;
        }
        
        .protected-badge {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class='bx bx-code-alt text-3xl mr-2'></i>
                        The Patcher
                    </h1>
                    <p class="text-indigo-100 text-sm mt-1">Safe Code Repair Tool</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm">👤 <?php echo htmlspecialchars($adminUsername); ?></span>
                    <a href="admin-enhanced.php" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-12 gap-6">
            <!-- Left Sidebar: File Browser -->
            <div class="col-span-3">
                <div class="bg-white rounded-lg shadow-lg p-4">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class='bx bx-folder text-blue-600 mr-2'></i>
                        Files
                    </h2>
                    
                    <div class="mb-4">
                        <input 
                            type="text" 
                            id="fileSearch" 
                            placeholder="Search files..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm"
                        />
                    </div>

                    <div id="fileList" class="space-y-1 max-h-[600px] overflow-y-auto">
                        <div class="text-center py-8 text-gray-500">
                            <i class='bx bx-loader-alt bx-spin text-3xl'></i>
                            <p class="text-sm mt-2">Loading files...</p>
                        </div>
                    </div>
                </div>

                <!-- Safety Info -->
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg shadow-lg p-4 mt-4 border border-amber-200">
                    <h3 class="font-bold text-amber-900 mb-2 flex items-center">
                        <i class='bx bx-shield text-amber-600 mr-2'></i>
                        Safety Locks
                    </h3>
                    <ul class="text-xs text-amber-800 space-y-1">
                        <li>✓ Whitelisted directories only</li>
                        <li>✓ Config files protected</li>
                        <li>✓ Auto-backup before changes</li>
                        <li>✓ Diff preview required</li>
                        <li>✓ All actions logged</li>
                    </ul>
                </div>
            </div>

            <!-- Main Editor Area -->
            <div class="col-span-9">
                <!-- File Info Bar -->
                <div id="fileInfoBar" class="bg-white rounded-lg shadow-lg p-4 mb-4 hidden">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900" id="currentFileName">No file selected</h2>
                            <p class="text-sm text-gray-500" id="currentFilePath"></p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="viewBackups()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm flex items-center">
                                <i class='bx bx-history mr-2'></i>
                                Backups
                            </button>
                            <button onclick="toggleEditMode()" id="editBtn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm flex items-center">
                                <i class='bx bx-edit mr-2'></i>
                                Edit
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Editor -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div id="editorPlaceholder" class="text-center py-16">
                        <i class='bx bx-file-blank text-6xl text-gray-300'></i>
                        <p class="text-gray-500 mt-4">Select a file from the left to begin</p>
                    </div>

                    <div id="editorContainer" class="hidden">
                        <textarea 
                            id="codeEditor" 
                            class="code-editor w-full h-[500px] p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                            readonly
                        ></textarea>

                        <div class="flex gap-3 mt-4">
                            <button onclick="previewDiff()" id="previewBtn" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold flex items-center disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <i class='bx bx-show mr-2'></i>
                                Preview Changes
                            </button>
                            <button onclick="applyFix()" id="applyBtn" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold flex items-center disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <i class='bx bx-check-circle mr-2'></i>
                                Apply Fix
                            </button>
                            <button onclick="cancelEdit()" id="cancelBtn" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-semibold hidden">
                                <i class='bx bx-x mr-2'></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API = 'api/patcher.php';
        let currentFile = null;
        let originalContent = '';
        let isEditMode = false;
        let allFiles = [];

        // Load files on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadFiles();
            
            // Search functionality
            document.getElementById('fileSearch').addEventListener('input', (e) => {
                filterFiles(e.target.value);
            });
        });

        async function loadFiles() {
            try {
                const res = await fetch(`${API}?action=listFiles`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                allFiles = data.files;
                renderFiles(allFiles);
            } catch (err) {
                Swal.fire('Error', 'Failed to load files: ' + err.message, 'error');
            }
        }

        function renderFiles(files) {
            const container = document.getElementById('fileList');
            
            if (files.length === 0) {
                container.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No files found</p>';
                return;
            }

            let lastDir = '';
            let html = '';
            
            files.forEach(file => {
                if (file.dir !== lastDir) {
                    html += `<div class="text-xs font-bold text-gray-500 mt-3 mb-1 px-2">${file.dir}</div>`;
                    lastDir = file.dir;
                }
                
                const icon = getFileIcon(file.extension);
                html += `
                    <div class="file-item p-2 rounded text-sm flex items-center justify-between" onclick="loadFile('${file.path}')">
                        <div class="flex items-center overflow-hidden">
                            <i class='bx ${icon} text-gray-600 mr-2'></i>
                            <span class="truncate">${file.name}</span>
                        </div>
                        <span class="text-xs text-gray-400">${formatBytes(file.size)}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function filterFiles(search) {
            const filtered = allFiles.filter(f => 
                f.name.toLowerCase().includes(search.toLowerCase()) ||
                f.path.toLowerCase().includes(search.toLowerCase())
            );
            renderFiles(filtered);
        }

        async function loadFile(path) {
            try {
                Swal.fire({
                    title: 'Loading...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch(`${API}?action=readFile&path=${encodeURIComponent(path)}`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                currentFile = data;
                originalContent = data.content;
                
                document.getElementById('codeEditor').value = data.content;
                document.getElementById('currentFileName').textContent = data.filename;
                document.getElementById('currentFilePath').textContent = data.path + ` (${data.lines} lines)`;
                
                document.getElementById('editorPlaceholder').classList.add('hidden');
                document.getElementById('editorContainer').classList.remove('hidden');
                document.getElementById('fileInfoBar').classList.remove('hidden');
                
                isEditMode = false;
                document.getElementById('codeEditor').readOnly = true;
                document.getElementById('previewBtn').disabled = true;
                document.getElementById('applyBtn').disabled = true;
                document.getElementById('cancelBtn').classList.add('hidden');
                
                Swal.close();
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        function toggleEditMode() {
            isEditMode = !isEditMode;
            const editor = document.getElementById('codeEditor');
            const editBtn = document.getElementById('editBtn');
            
            if (isEditMode) {
                editor.readOnly = false;
                editor.classList.add('ring-2', 'ring-amber-400');
                editBtn.innerHTML = '<i class="bx bx-lock-open mr-2"></i> Editing...';
                editBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                editBtn.classList.add('bg-amber-500', 'hover:bg-amber-600');
                document.getElementById('previewBtn').disabled = false;
                document.getElementById('cancelBtn').classList.remove('hidden');
            } else {
                editor.readOnly = true;
                editor.classList.remove('ring-2', 'ring-amber-400');
                editBtn.innerHTML = '<i class="bx bx-edit mr-2"></i> Edit';
                editBtn.classList.remove('bg-amber-500', 'hover:bg-amber-600');
                editBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
                document.getElementById('previewBtn').disabled = true;
                document.getElementById('applyBtn').disabled = true;
                document.getElementById('cancelBtn').classList.add('hidden');
            }
        }

        function cancelEdit() {
            document.getElementById('codeEditor').value = originalContent;
            toggleEditMode();
        }

        async function previewDiff() {
            const newContent = document.getElementById('codeEditor').value;
            
            if (newContent === originalContent) {
                Swal.fire('No Changes', 'The content is identical to the original', 'info');
                return;
            }

            try {
                Swal.fire({
                    title: 'Generating diff...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch(`${API}?action=previewDiff`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        path: currentFile.path,
                        content: newContent
                    })
                });

                const data = await res.json();
                if (data.error) throw new Error(data.error);

                showDiffModal(data.diff, data.stats);
                
                // Enable apply button
                document.getElementById('applyBtn').disabled = false;
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        function showDiffModal(diff, stats) {
            let html = `
                <div class="text-left max-h-[500px] overflow-y-auto">
                    <div class="bg-gray-100 p-3 rounded mb-4 flex justify-around text-sm">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">${stats.added}</div>
                            <div class="text-gray-600">Added</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">${stats.removed}</div>
                            <div class="text-gray-600">Removed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-600">${stats.unchanged}</div>
                            <div class="text-gray-600">Unchanged</div>
                        </div>
                    </div>
                    <div class="border rounded">
            `;

            diff.lines.forEach(line => {
                const classes = {
                    'added': 'diff-added',
                    'removed': 'diff-removed',
                    'unchanged': 'diff-unchanged'
                };
                
                const escapedContent = (line.content || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                html += `
                    <div class="diff-line ${classes[line.type]} font-mono text-xs">
                        <span class="text-gray-400 mr-4">${line.lineNum}</span>
                        <span>${escapedContent || '&nbsp;'}</span>
                    </div>
                `;
            });

            html += `</div></div>`;

            Swal.fire({
                title: '📊 Diff Preview',
                html: html,
                width: '800px',
                confirmButtonText: 'Close',
                confirmButtonColor: '#4f46e5'
            });
        }

        async function applyFix() {
            const result = await Swal.fire({
                title: 'Apply Fix?',
                html: `
                    <p class="text-gray-700 mb-3">This will:</p>
                    <ul class="text-left text-sm text-gray-600">
                        <li>✓ Create a backup with timestamp</li>
                        <li>✓ Overwrite the live file</li>
                        <li>✓ Log the action</li>
                    </ul>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Apply Fix',
                confirmButtonColor: '#16a34a',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            try {
                Swal.fire({
                    title: 'Applying fix...',
                    html: 'Creating backup and writing file...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const newContent = document.getElementById('codeEditor').value;

                const res = await fetch(`${API}?action=applyFix`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        path: currentFile.path,
                        content: newContent
                    })
                });

                const data = await res.json();
                if (data.error) throw new Error(data.error);

                originalContent = newContent;
                isEditMode = false;
                document.getElementById('codeEditor').readOnly = true;
                document.getElementById('applyBtn').disabled = true;
                document.getElementById('previewBtn').disabled = true;
                document.getElementById('cancelBtn').classList.add('hidden');

                Swal.fire({
                    icon: 'success',
                    title: 'Fix Applied!',
                    html: `
                        <p class="text-gray-700">File updated successfully</p>
                        <p class="text-sm text-gray-500 mt-2">Backup: ${data.backup}</p>
                    `,
                    confirmButtonColor: '#16a34a'
                });
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        async function viewBackups() {
            if (!currentFile) return;

            try {
                const res = await fetch(`${API}?action=listBackups&path=${encodeURIComponent(currentFile.path)}`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);

                let html = '<div class="max-h-[400px] overflow-y-auto">';
                
                if (data.count === 0) {
                    html += '<p class="text-gray-500 text-center py-8">No backups found for this file</p>';
                } else {
                    html += '<div class="space-y-2">';
                    data.backups.forEach(backup => {
                        html += `
                            <div class="p-3 bg-gray-50 rounded border text-left">
                                <div class="font-semibold text-sm">${backup.name}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Created: ${backup.created} | Size: ${formatBytes(backup.size)}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                
                html += '</div>';

                Swal.fire({
                    title: '🕒 Backups',
                    html: html,
                    confirmButtonColor: '#4f46e5'
                });
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        function getFileIcon(ext) {
            const icons = {
                'php': 'bxl-php',
                'js': 'bxl-javascript',
                'css': 'bxl-css3',
                'html': 'bxl-html5',
                'json': 'bx-code-curly'
            };
            return icons[ext] || 'bx-file';
        }

        function formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    </script>
</body>
</html>
