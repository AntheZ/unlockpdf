<?php
/**
 * PDF Unlock Tool - Командний інтерфейс для розблокування PDF-файлів
 * 
 * Використання: php cli_unlock.php шлях/до/вхідного.pdf [шлях/до/вихідного.pdf]
 */

// Перевірка, чи запущено з командного рядка
if (php_sapi_name() !== 'cli') {
    echo "Цей скрипт можна запускати тільки з командного рядка.";
    exit(1);
}

// Підключення необхідних файлів
require_once 'includes/functions.php';

// Перевірка аргументів командного рядка
if ($argc < 2) {
    echo "Використання: php " . basename(__FILE__) . " шлях/до/вхідного.pdf [шлях/до/вихідного.pdf]" . PHP_EOL;
    exit(1);
}

// Отримання шляху до вхідного файлу
$inputFile = $argv[1];

// Перевірка існування вхідного файлу
if (!file_exists($inputFile)) {
    echo "Помилка: Вхідний файл не існує: " . $inputFile . PHP_EOL;
    exit(1);
}

// Перевірка, чи є файл PDF-документом
if (!isPdfFile($inputFile)) {
    echo "Помилка: Вхідний файл не є PDF-документом: " . $inputFile . PHP_EOL;
    exit(1);
}

// Визначення шляху до вихідного файлу
if (isset($argv[2])) {
    $outputFile = $argv[2];
} else {
    // Створення директорії для оброблених файлів, якщо вона не існує
    $processedDir = __DIR__ . '/processed';
    if (!is_dir($processedDir)) {
        mkdir($processedDir, 0755, true);
    }
    
    // Генерація імені вихідного файлу
    $outputFile = $processedDir . '/unlocked_' . basename($inputFile);
}

// Створення директорії для логів, якщо вона не існує
$logsDir = __DIR__ . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

// Виведення інформації про файли
echo "Вхідний файл: " . $inputFile . PHP_EOL;
echo "Вихідний файл: " . $outputFile . PHP_EOL;
echo "Початок розблокування..." . PHP_EOL;

// Перевірка наявності інструментів
echo "Перевірка наявності інструментів:" . PHP_EOL;
echo "- Ghostscript: " . (isGhostscriptInstalled() ? "Доступний" : "Недоступний") . PHP_EOL;
echo "- QPDF: " . (isQpdfInstalled() ? "Доступний" : "Недоступний") . PHP_EOL;
echo "- pdftk: " . (isPdftkInstalled() ? "Доступний" : "Недоступний") . PHP_EOL;

// Розблокування PDF-файлу
$startTime = microtime(true);
$success = unlockPdf($inputFile, $outputFile);
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

// Виведення результату
if ($success && file_exists($outputFile)) {
    echo "PDF-файл успішно розблоковано!" . PHP_EOL;
    echo "Час виконання: " . $executionTime . " секунд" . PHP_EOL;
    echo "Розблокований файл збережено: " . $outputFile . PHP_EOL;
    
    // Виведення інформації про файли
    $originalSize = filesize($inputFile);
    $unlockedSize = filesize($outputFile);
    $sizeDiff = $unlockedSize - $originalSize;
    $sizeDiffPercent = round(($sizeDiff / $originalSize) * 100, 2);
    
    echo "Розмір оригінального файлу: " . formatFileSize($originalSize) . PHP_EOL;
    echo "Розмір розблокованого файлу: " . formatFileSize($unlockedSize) . PHP_EOL;
    echo "Різниця: " . formatFileSize($sizeDiff) . " (" . ($sizeDiff >= 0 ? "+" : "") . $sizeDiffPercent . "%)" . PHP_EOL;
    
    exit(0);
} else {
    echo "Не вдалося розблокувати PDF-файл." . PHP_EOL;
    echo "Перевірте лог-файл для отримання додаткової інформації: " . $logsDir . "/app.log" . PHP_EOL;
    exit(1);
} 