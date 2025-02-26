<?php
/**
 * PDF Unlock Tool - Файл для завантаження розблокованих PDF-файлів
 * 
 * Цей файл забезпечує безпечне завантаження розблокованих PDF-файлів
 */

// Підключення необхідних файлів
require_once 'includes/functions.php';

// Перевірка наявності параметра file або id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Підтримка старого формату URL з параметром id
    $fileId = $_GET['id'];
    $userId = isset($_GET['user']) ? $_GET['user'] : getUserId();
    
    // Пошук файлу за ID
    $processedDir = __DIR__ . '/processed/' . $userId;
    $filename = null;
    
    if (is_dir($processedDir)) {
        foreach (new DirectoryIterator($processedDir) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'pdf') {
                continue;
            }
            
            $currentFileId = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
            if ($currentFileId === $fileId) {
                $filename = $fileInfo->getFilename();
                break;
            }
        }
    }
    
    if ($filename === null) {
        header('HTTP/1.1 404 Not Found');
        echo 'Помилка: Файл не знайдено.';
        exit;
    }
} elseif (isset($_GET['file']) && !empty($_GET['file'])) {
    // Новий формат URL з параметром file
    $filename = basename($_GET['file']);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo 'Помилка: Не вказано файл для завантаження.';
    exit;
}

// Перевірка на спроби обходу директорії
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Помилка: Недопустиме ім\'я файлу.';
    exit;
}

// Отримання ID користувача
$userId = isset($_GET['user']) ? $_GET['user'] : getUserId();

// Перевірка ID користувача
if (empty($userId) || !preg_match('/^[a-f0-9]+$/', $userId)) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Помилка: Недійсний ID користувача.';
    exit;
}

// Шлях до файлу
$filePath = __DIR__ . '/processed/' . $userId . '/' . $filename;

// Перевірка існування файлу
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'Помилка: Файл не знайдено.';
    exit;
}

// Перевірка, чи не закінчився термін дії файлу
$fileId = pathinfo($filename, PATHINFO_FILENAME);
$metadataFile = __DIR__ . '/processed/' . $userId . '/' . $fileId . '.meta';

if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);
    
    if (isset($metadata['expiry_time']) && $metadata['expiry_time'] < time()) {
        header('HTTP/1.1 410 Gone');
        echo 'Помилка: Термін дії файлу закінчився. Файл був автоматично видалений.';
        
        // Видалення файлу та метаданих
        @unlink($filePath);
        @unlink($metadataFile);
        exit;
    }
}

// Перевірка, чи є файл PDF-документом
if (!isPdfFile($filePath)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Помилка: Файл не є PDF-документом.';
    exit;
}

// Логування завантаження
logMessage("Завантаження розблокованого файлу: " . $filename . " користувачем: " . $userId);

// Встановлення заголовків для завантаження
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Відправка файлу
readfile($filePath);
exit; 