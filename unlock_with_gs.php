<?php
/**
 * Скрипт командного рядка для розблокування PDF-файлів
 * 
 * Використання: php unlock_with_gs.php input.pdf [output.pdf]
 */

// Підключення необхідних файлів
require_once 'includes/functions.php';
require_once 'includes/pdf_unlock.php';

// Перевірка наявності директорії для логів
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
}

// Відображення інструкцій, якщо не вказано аргументи
if ($argc < 2) {
    echo "Використання: php " . basename(__FILE__) . " вхідний.pdf [вихідний.pdf]\n";
    echo "Якщо вихідний.pdf не вказано, буде використано вхідний_unlocked.pdf\n";
    exit(1);
}

// Отримання вхідного файлу
$inputFile = $argv[1];

// Перевірка існування вхідного файлу
if (!file_exists($inputFile)) {
    echo "Помилка: Вхідний файл '$inputFile' не існує\n";
    exit(1);
}

// Визначення імені вихідного файлу
$outputFile = isset($argv[2]) ? $argv[2] : pathinfo($inputFile, PATHINFO_DIRNAME) . '/' . 
              pathinfo($inputFile, PATHINFO_FILENAME) . '_unlocked.' . 
              pathinfo($inputFile, PATHINFO_EXTENSION);

// Відображення інформації
echo "Розблокування PDF-файлу...\n";
echo "Вхідний файл: $inputFile\n";
echo "Вихідний файл: $outputFile\n";

// Спроба різних методів розблокування PDF
echo "Спроба різних методів розблокування PDF...\n";

// Метод 1: Розширений Ghostscript
echo "Метод 1: Спроба Ghostscript з розширеними параметрами...\n";
if (unlockPdfWithEnhancedGhostscript($inputFile, $outputFile)) {
    echo "Успіх! PDF розблоковано за допомогою Ghostscript з розширеними параметрами.\n";
    exit(0);
}

// Метод 2: TCPDF
echo "Метод 2: Спроба TCPDF та FPDI...\n";
if (unlockPdfWithTCPDF($inputFile, $outputFile)) {
    echo "Успіх! PDF розблоковано за допомогою TCPDF та FPDI.\n";
    exit(0);
}

// Метод 3: Стандартний Ghostscript
echo "Метод 3: Спроба стандартного Ghostscript...\n";
if (unlockPdfWithGhostscript($inputFile, $outputFile)) {
    echo "Успіх! PDF розблоковано за допомогою стандартного Ghostscript.\n";
    exit(0);
}

// Метод 4: pdftk
echo "Метод 4: Перевірка наявності pdftk...\n";
if (isPdftkInstalled()) {
    echo "pdftk знайдено, спроба розблокування...\n";
    if (unlockPdfWithPdftk($inputFile, $outputFile)) {
        echo "Успіх! PDF розблоковано за допомогою pdftk.\n";
        exit(0);
    } else {
        echo "Не вдалося розблокувати за допомогою pdftk.\n";
    }
} else {
    echo "pdftk не встановлено, пропускаємо цей метод.\n";
}

// Метод 5: QPDF
echo "Метод 5: Перевірка наявності QPDF...\n";
if (isQpdfInstalled()) {
    echo "QPDF знайдено, спроба розблокування...\n";
    if (unlockPdfWithQpdf($inputFile, $outputFile)) {
        echo "Успіх! PDF розблоковано за допомогою QPDF.\n";
        exit(0);
    } else {
        echo "Не вдалося розблокувати за допомогою QPDF.\n";
    }
} else {
    echo "QPDF не встановлено, пропускаємо цей метод.\n";
}

// Метод 6: FPDI
echo "Метод 6: Перевірка наявності FPDI...\n";
if (class_exists('\\setasign\\Fpdi\\Fpdi')) {
    echo "FPDI знайдено, спроба розблокування...\n";
    if (unlockPdfWithFpdi($inputFile, $outputFile)) {
        echo "Успіх! PDF розблоковано за допомогою FPDI.\n";
        exit(0);
    } else {
        echo "Не вдалося розблокувати за допомогою FPDI.\n";
    }
} else {
    echo "FPDI недоступно, пропускаємо цей метод.\n";
}

// Метод 7: Просте копіювання
echo "Метод 7: Усі методи не вдалися, спроба простого копіювання як останній варіант...\n";
if (copy($inputFile, $outputFile)) {
    echo "PDF скопійовано (метод простого копіювання). Примітка: Це може не видалити всі обмеження.\n";
    exit(0);
} else {
    echo "Не вдалося скопіювати PDF-файл.\n";
}

echo "Усі методи розблокування не вдалися.\n";
exit(1); 