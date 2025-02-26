<?php
/**
 * PDF Unlock Tool - Файл для завантаження розблокованих PDF-файлів
 * 
 * Цей файл забезпечує безпечне завантаження розблокованих PDF-файлів
 */

// Підключення необхідних файлів
require_once 'includes/functions.php';

// Перевірка наявності параметра file
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Помилка: Не вказано файл для завантаження.';
    exit;
}

// Отримання імені файлу
$filename = basename($_GET['file']);

// Перевірка на спроби обходу директорії
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Помилка: Недопустиме ім\'я файлу.';
    exit;
}

// Шлях до файлу
$filePath = __DIR__ . '/processed/' . $filename;

// Перевірка існування файлу
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'Помилка: Файл не знайдено.';
    exit;
}

// Перевірка, чи є файл PDF-документом
if (!isPdfFile($filePath)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Помилка: Файл не є PDF-документом.';
    exit;
}

// Логування завантаження
logMessage("Завантаження розблокованого файлу: " . $filename);

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