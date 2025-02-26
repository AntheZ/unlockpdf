<?php
/**
 * PDF Unlock Tool - Налаштування автоматичного очищення
 * 
 * Цей скрипт допомагає налаштувати автоматичне очищення застарілих файлів
 */

// Отримання абсолютного шляху до директорії скрипта
$scriptPath = realpath(__DIR__);
$cleanupScript = $scriptPath . '/cleanup.php';

// Перевірка наявності файлу очищення
if (!file_exists($cleanupScript)) {
    die("Помилка: Файл очищення не знайдено: $cleanupScript\n");
}

// Перевірка прав на виконання
if (!is_executable($cleanupScript) && !chmod($cleanupScript, 0755)) {
    echo "Попередження: Не вдалося встановити права на виконання для файлу очищення.\n";
    echo "Рекомендується вручну встановити права 755 для файлу $cleanupScript\n";
}

// Формування команди cron
$cronCommand = "*/5 * * * * php $cleanupScript > /dev/null 2>&1";

// Виведення інструкцій
echo "PDF Unlock Tool - Налаштування автоматичного очищення\n\n";
echo "Для налаштування автоматичного очищення застарілих файлів, додайте наступний рядок до crontab:\n\n";
echo "$cronCommand\n\n";
echo "Інструкції для налаштування cron:\n";
echo "1. Відкрийте термінал\n";
echo "2. Виконайте команду: crontab -e\n";
echo "3. Додайте вищевказаний рядок в кінець файлу\n";
echo "4. Збережіть файл і вийдіть з редактора\n\n";
echo "Це налаштує автоматичне очищення застарілих файлів кожні 5 хвилин.\n";

// Спроба автоматичного налаштування для Linux/Unix систем
if (PHP_OS !== 'WINNT' && PHP_OS !== 'WIN32') {
    echo "\nБажаєте спробувати автоматичне налаштування? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    
    if (strtolower($line) === 'y') {
        // Створення тимчасового файлу
        $tempFile = tempnam(sys_get_temp_dir(), 'cron');
        exec("crontab -l > $tempFile 2>/dev/null");
        
        // Додавання нашої команди
        file_put_contents($tempFile, file_get_contents($tempFile) . "\n$cronCommand\n");
        
        // Встановлення нового crontab
        exec("crontab $tempFile", $output, $returnVar);
        unlink($tempFile);
        
        if ($returnVar === 0) {
            echo "Cron успішно налаштовано! Застарілі файли будуть автоматично видалятися.\n";
        } else {
            echo "Не вдалося автоматично налаштувати cron. Будь ласка, налаштуйте вручну за інструкціями вище.\n";
        }
    }
    
    fclose($handle);
}

// Для Windows систем
if (PHP_OS === 'WINNT' || PHP_OS === 'WIN32') {
    echo "\nДля Windows систем:\n";
    echo "1. Відкрийте Планувальник завдань (Task Scheduler)\n";
    echo "2. Створіть нове завдання\n";
    echo "3. Налаштуйте запуск програми: php.exe\n";
    echo "4. Додайте аргументи: $cleanupScript\n";
    echo "5. Налаштуйте запуск кожні 5 хвилин\n";
} 