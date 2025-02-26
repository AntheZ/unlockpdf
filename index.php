<?php
/**
 * PDF Unlock Tool - Головний файл веб-інтерфейсу
 * 
 * Цей файл забезпечує веб-інтерфейс для розблокування PDF-файлів
 */

// Підключення необхідних файлів
require_once 'includes/functions.php';

// Ініціалізація змінних
$message = '';
$messageType = '';
$fileInfo = null;

// Отримання ID користувача
$userId = getUserId();

// Створення директорій користувача
createUserDirectories($userId);

// Перевірка наявності директорій
$uploadsDir = __DIR__ . '/uploads/' . $userId;
$processedDir = __DIR__ . '/processed/' . $userId;
$logsDir = __DIR__ . '/logs';

// Створення директорій, якщо вони не існують
foreach ([$uploadsDir, $processedDir, $logsDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Обробка завантаження файлу або URL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Визначення джерела PDF (файл або URL)
    $pdfSource = isset($_POST['pdf_source']) ? $_POST['pdf_source'] : 'file';
    
    if ($pdfSource === 'file' && isset($_FILES['pdf_file'])) {
        // Обробка завантаження файлу
        $uploadedFile = $_FILES['pdf_file'];
        
        // Перевірка наявності помилок при завантаженні
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $message = 'Помилка при завантаженні файлу: ';
            switch ($uploadedFile['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message .= 'Файл перевищує допустимий розмір.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message .= 'Файл був завантажений частково.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message .= 'Файл не був завантажений.';
                    break;
                default:
                    $message .= 'Невідома помилка.';
            }
            $messageType = 'error';
        } else {
            // Перевірка типу файлу
            $fileType = $uploadedFile['type'];
            $allowedTypes = ['application/pdf'];
            
            if (!in_array($fileType, $allowedTypes) && !isPdfFile($uploadedFile['tmp_name'])) {
                $message = 'Помилка: Дозволено завантажувати тільки PDF-файли.';
                $messageType = 'error';
            } else {
                // Генерація унікального імені файлу
                $filename = generateUniqueFilename($uploadsDir);
                $uploadedFilePath = $uploadsDir . '/' . $filename;
                
                // Переміщення завантаженого файлу
                if (move_uploaded_file($uploadedFile['tmp_name'], $uploadedFilePath)) {
                    // Шлях до розблокованого файлу
                    $unlockedFilename = 'unlocked_' . $filename;
                    $unlockedFilePath = $processedDir . '/' . $unlockedFilename;
                    
                    // Розблокування PDF-файлу
                    logMessage("Початок розблокування файлу: " . $filename);
                    $success = unlockPdf($uploadedFilePath, $unlockedFilePath);
                    
                    if ($success && file_exists($unlockedFilePath)) {
                        // Отримання інформації про файл
                        $fileInfo = [
                            'original' => [
                                'name' => $uploadedFile['name'],
                                'size' => formatFileSize($uploadedFile['size']),
                                'path' => $uploadedFilePath,
                                'is_protected' => isPdfProtected($uploadedFilePath)
                            ],
                            'unlocked' => [
                                'name' => $unlockedFilename,
                                'size' => formatFileSize(filesize($unlockedFilePath)),
                                'path' => $unlockedFilePath,
                                'download_url' => 'download.php?file=' . urlencode($unlockedFilename) . '&user=' . urlencode($userId)
                            ]
                        ];
                        
                        // Збереження метаданих файлу
                        $fileId = pathinfo($unlockedFilename, PATHINFO_FILENAME);
                        saveFileMetadata($fileId, $userId, [
                            'original_name' => $uploadedFile['name'],
                            'unlocked_time' => time(),
                            'expiry_time' => time() + FILE_EXPIRY_TIME
                        ]);
                        
                        $message = 'PDF-файл успішно розблоковано!';
                        $messageType = 'success';
                        logMessage("Файл успішно розблоковано: " . $filename);
                    } else {
                        $message = 'Не вдалося розблокувати PDF-файл. Спробуйте інший файл.';
                        $messageType = 'error';
                        logMessage("Не вдалося розблокувати файл: " . $filename);
                        
                        // Видалення завантаженого файлу у разі невдачі
                        if (file_exists($uploadedFilePath)) {
                            unlink($uploadedFilePath);
                        }
                    }
                } else {
                    $message = 'Помилка при збереженні файлу.';
                    $messageType = 'error';
                    logMessage("Помилка при збереженні файлу: " . $uploadedFile['name']);
                }
            }
        }
    } elseif ($pdfSource === 'url' && isset($_POST['pdf_url']) && !empty($_POST['pdf_url'])) {
        // Обробка URL
        $pdfUrl = $_POST['pdf_url'];
        
        // Перевірка URL
        if (!filter_var($pdfUrl, FILTER_VALIDATE_URL)) {
            $message = 'Помилка: Невірний формат URL.';
            $messageType = 'error';
        } else {
            // Генерація унікального імені файлу
            $filename = generateUniqueFilename($uploadsDir);
            $downloadedFilePath = $uploadsDir . '/' . $filename;
            
            // Завантаження файлу з URL
            logMessage("Спроба завантаження файлу з URL: " . $pdfUrl);
            if (downloadFile($pdfUrl, $downloadedFilePath)) {
                // Перевірка, чи є завантажений файл PDF-документом
                if (!isPdfFile($downloadedFilePath)) {
                    $message = 'Помилка: Завантажений файл не є PDF-документом.';
                    $messageType = 'error';
                    logMessage("Завантажений файл не є PDF-документом: " . $pdfUrl);
                    
                    // Видалення завантаженого файлу
                    if (file_exists($downloadedFilePath)) {
                        unlink($downloadedFilePath);
                    }
                } else {
                    // Шлях до розблокованого файлу
                    $unlockedFilename = 'unlocked_' . $filename;
                    $unlockedFilePath = $processedDir . '/' . $unlockedFilename;
                    
                    // Розблокування PDF-файлу
                    logMessage("Початок розблокування файлу з URL: " . $filename);
                    $success = unlockPdf($downloadedFilePath, $unlockedFilePath);
                    
                    if ($success && file_exists($unlockedFilePath)) {
                        // Отримання інформації про файл
                        $originalName = basename(parse_url($pdfUrl, PHP_URL_PATH));
                        if (empty($originalName)) {
                            $originalName = 'document_from_url.pdf';
                        }
                        
                        $fileInfo = [
                            'original' => [
                                'name' => $originalName,
                                'size' => formatFileSize(filesize($downloadedFilePath)),
                                'path' => $downloadedFilePath,
                                'is_protected' => isPdfProtected($downloadedFilePath)
                            ],
                            'unlocked' => [
                                'name' => $unlockedFilename,
                                'size' => formatFileSize(filesize($unlockedFilePath)),
                                'path' => $unlockedFilePath,
                                'download_url' => 'download.php?file=' . urlencode($unlockedFilename) . '&user=' . urlencode($userId)
                            ]
                        ];
                        
                        // Збереження метаданих файлу
                        $fileId = pathinfo($unlockedFilename, PATHINFO_FILENAME);
                        saveFileMetadata($fileId, $userId, [
                            'original_name' => $originalName,
                            'unlocked_time' => time(),
                            'expiry_time' => time() + FILE_EXPIRY_TIME
                        ]);
                        
                        $message = 'PDF-файл успішно розблоковано!';
                        $messageType = 'success';
                        logMessage("Файл з URL успішно розблоковано: " . $filename);
                    } else {
                        $message = 'Не вдалося розблокувати PDF-файл. Спробуйте інший URL.';
                        $messageType = 'error';
                        logMessage("Не вдалося розблокувати файл з URL: " . $filename);
                        
                        // Видалення завантаженого файлу у разі невдачі
                        if (file_exists($downloadedFilePath)) {
                            unlink($downloadedFilePath);
                        }
                    }
                }
            } else {
                $message = 'Помилка при завантаженні файлу з URL.';
                $messageType = 'error';
                logMessage("Помилка при завантаженні файлу з URL: " . $pdfUrl);
            }
        }
    } else {
        $message = 'Помилка: Не вказано джерело PDF-файлу.';
        $messageType = 'error';
    }
}

// Отримання списку файлів користувача
$userFiles = getUserFiles($userId);

// Перевірка наявності інструментів
$tools = [
    'ghostscript' => isGhostscriptInstalled(),
    'qpdf' => isQpdfInstalled(),
    'pdftk' => isPdftkInstalled()
];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Unlock Tool - Розблокування PDF-файлів</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .upload-form {
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"], input[type="text"], input[type="url"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .source-selector {
            display: flex;
            margin-bottom: 15px;
        }
        .source-option {
            margin-right: 20px;
            cursor: pointer;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #2980b9;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .file-info {
            background-color: #e9f7fe;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .file-info h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .download-link {
            display: inline-block;
            background-color: #27ae60;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        .download-link:hover {
            background-color: #219653;
        }
        .tools-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .tools-info h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .tool-status {
            margin-bottom: 5px;
        }
        .tool-status .status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .tool-status .status.available {
            background-color: #27ae60;
        }
        .tool-status .status.unavailable {
            background-color: #e74c3c;
        }
        .user-files {
            margin-top: 30px;
            padding: 15px;
            background-color: #f0f7ff;
            border-radius: 4px;
        }
        .user-files h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .file-list {
            list-style: none;
            padding: 0;
        }
        .file-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-name {
            flex-grow: 1;
        }
        .file-expiry {
            margin: 0 15px;
            color: #e74c3c;
            font-size: 14px;
        }
        footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PDF Unlock Tool</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-form">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="source-selector">
                    <label class="source-option">
                        <input type="radio" name="pdf_source" value="file" checked onchange="toggleSourceForm()"> Завантажити файл з комп'ютера
                    </label>
                    <label class="source-option">
                        <input type="radio" name="pdf_source" value="url" onchange="toggleSourceForm()"> Вказати URL до PDF-файлу
                    </label>
                </div>
                
                <div id="file-upload-form" class="form-group">
                    <label for="pdf_file">Виберіть PDF-файл для розблокування:</label>
                    <input type="file" name="pdf_file" id="pdf_file" accept=".pdf">
                </div>
                
                <div id="url-upload-form" class="form-group" style="display: none;">
                    <label for="pdf_url">Вкажіть URL до PDF-файлу:</label>
                    <input type="url" name="pdf_url" id="pdf_url" placeholder="https://example.com/document.pdf">
                </div>
                
                <button type="submit">Розблокувати PDF</button>
            </form>
        </div>
        
        <?php if ($fileInfo): ?>
            <div class="file-info">
                <h3>Інформація про файл</h3>
                <p><strong>Оригінальний файл:</strong> <?php echo htmlspecialchars($fileInfo['original']['name']); ?> (<?php echo $fileInfo['original']['size']; ?>)</p>
                <p><strong>Захищений:</strong> <?php echo $fileInfo['original']['is_protected'] ? 'Так' : 'Ні'; ?></p>
                <p><strong>Розблокований файл:</strong> <?php echo htmlspecialchars($fileInfo['unlocked']['name']); ?> (<?php echo $fileInfo['unlocked']['size']; ?>)</p>
                <a href="<?php echo htmlspecialchars($fileInfo['unlocked']['download_url']); ?>" class="download-link">Завантажити розблокований PDF</a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($userFiles)): ?>
            <div class="user-files">
                <h3>Ваші розблоковані файли</h3>
                <p>Файли автоматично видаляються через 10 хвилин після розблокування.</p>
                <ul class="file-list">
                    <?php foreach ($userFiles as $file): ?>
                        <li class="file-item">
                            <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                            <div class="file-expiry" data-expiry="<?php echo $file['expiry']; ?>">
                                Залишилось: <span class="countdown"></span>
                            </div>
                            <a href="<?php echo htmlspecialchars($file['download_url']); ?>" class="download-link">Завантажити</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="tools-info">
            <h3>Статус інструментів</h3>
            <div class="tool-status">
                <span class="status <?php echo $tools['ghostscript'] ? 'available' : 'unavailable'; ?>"></span>
                <strong>Ghostscript:</strong> <?php echo $tools['ghostscript'] ? 'Доступний' : 'Недоступний'; ?>
            </div>
            <div class="tool-status">
                <span class="status <?php echo $tools['qpdf'] ? 'available' : 'unavailable'; ?>"></span>
                <strong>QPDF:</strong> <?php echo $tools['qpdf'] ? 'Доступний' : 'Недоступний'; ?>
            </div>
            <div class="tool-status">
                <span class="status <?php echo $tools['pdftk'] ? 'available' : 'unavailable'; ?>"></span>
                <strong>pdftk:</strong> <?php echo $tools['pdftk'] ? 'Доступний' : 'Недоступний'; ?>
            </div>
            <p><small>Примітка: Навіть якщо деякі інструменти недоступні, PDF Unlock Tool все одно спробує розблокувати ваш файл за допомогою доступних методів.</small></p>
        </div>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> PDF Unlock Tool. Всі права захищено.</p>
        </footer>
    </div>
    
    <script>
        // Функція для перемикання форм завантаження
        function toggleSourceForm() {
            const fileForm = document.getElementById('file-upload-form');
            const urlForm = document.getElementById('url-upload-form');
            const pdfSource = document.querySelector('input[name="pdf_source"]:checked').value;
            
            if (pdfSource === 'file') {
                fileForm.style.display = 'block';
                urlForm.style.display = 'none';
                document.getElementById('pdf_file').setAttribute('required', '');
                document.getElementById('pdf_url').removeAttribute('required');
            } else {
                fileForm.style.display = 'none';
                urlForm.style.display = 'block';
                document.getElementById('pdf_file').removeAttribute('required');
                document.getElementById('pdf_url').setAttribute('required', '');
            }
        }
        
        // Функція для оновлення таймерів зворотного відліку
        function updateCountdowns() {
            const now = Math.floor(Date.now() / 1000);
            const expiryElements = document.querySelectorAll('.file-expiry');
            
            expiryElements.forEach(element => {
                const expiry = parseInt(element.getAttribute('data-expiry'));
                const remaining = expiry - now;
                
                if (remaining <= 0) {
                    element.parentElement.style.display = 'none';
                    return;
                }
                
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                
                const countdownElement = element.querySelector('.countdown');
                countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (remaining < 60) {
                    element.style.color = '#e74c3c';
                } else if (remaining < 300) {
                    element.style.color = '#f39c12';
                }
            });
        }
        
        // Оновлення таймерів кожну секунду
        setInterval(updateCountdowns, 1000);
        
        // Ініціалізація при завантаженні сторінки
        document.addEventListener('DOMContentLoaded', function() {
            toggleSourceForm();
            updateCountdowns();
        });
    </script>
</body>
</html> 