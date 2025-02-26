<?php
/**
 * Скрипт для перевірки зовнішніх інструментів, які використовуються для розблокування PDF
 */

// Функція для виведення повідомлень
function output($message) {
    echo $message . PHP_EOL;
    flush();
}

// Функція для перевірки наявності команди
function commandExists($command) {
    $whereCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    $cmd = sprintf('%s %s 2>&1', $whereCommand, escapeshellarg($command));
    exec($cmd, $output, $return_var);
    return $return_var === 0;
}

// Функція для перевірки версії Ghostscript
function checkGhostscript() {
    // Спочатку спробуємо команду для Linux/Mac
    $command = 'gs --version 2>&1';
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    // Спробуємо команди для Windows як запасний варіант
    $command = 'gswin64c --version 2>&1';
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    $command = 'gswin32c --version 2>&1';
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    return false;
}

// Функція для перевірки версії QPDF
function checkQPDF() {
    $command = 'qpdf --version 2>&1';
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        // Витягуємо версію з виводу
        foreach ($output as $line) {
            if (strpos($line, 'qpdf version') !== false) {
                return trim(str_replace('qpdf version', '', $line));
            }
        }
        return 'встановлено (версія невідома)';
    }
    
    return false;
}

// Функція для перевірки версії pdftk
function checkPdftk() {
    $command = 'pdftk --version 2>&1';
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        // Витягуємо версію з виводу
        foreach ($output as $line) {
            if (strpos($line, 'pdftk') !== false && strpos($line, 'version') !== false) {
                return trim($line);
            }
        }
        return 'встановлено (версія невідома)';
    }
    
    return false;
}

// Перевірка PHP-розширень
output('Перевірка PHP-розширень...');
$required_extensions = ['json', 'fileinfo', 'zip', 'openssl', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        output("✓ Розширення {$ext} встановлено");
    } else {
        output("✗ Розширення {$ext} відсутнє");
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    output('');
    output('Увага: Деякі необхідні PHP-розширення відсутні.');
    output('Можуть виникнути проблеми з роботою PDF Unlock Tool.');
}

// Перевірка зовнішніх інструментів
output('');
output('Перевірка зовнішніх інструментів...');

// Перевірка Ghostscript
$gs_version = checkGhostscript();
if ($gs_version !== false) {
    output("✓ Ghostscript встановлено (версія {$gs_version})");
} else {
    output("✗ Ghostscript не встановлено або відсутній у PATH");
    output("  Завантажити: https://www.ghostscript.com/releases/gsdnld.html");
}

// Перевірка QPDF
$qpdf_version = checkQPDF();
if ($qpdf_version !== false) {
    output("✓ QPDF встановлено (версія {$qpdf_version})");
} else {
    output("✗ QPDF не встановлено або відсутній у PATH");
    output("  Завантажити: https://github.com/qpdf/qpdf/releases");
}

// Перевірка pdftk
$pdftk_version = checkPdftk();
if ($pdftk_version !== false) {
    output("✓ pdftk встановлено ({$pdftk_version})");
} else {
    output("✗ pdftk не встановлено або відсутній у PATH");
    output("  Завантажити: https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/");
}

// Перевірка FPDI (PHP-бібліотека)
output('');
output('Перевірка PHP-бібліотек...');
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('\\setasign\\Fpdi\\Fpdi')) {
        output("✓ Бібліотека FPDI встановлена");
    } else {
        output("✗ Бібліотека FPDI не встановлена");
        output("  Виконайте: php composer require setasign/fpdi-tcpdf");
    }
} else {
    output("✗ Залежності Composer не встановлені");
    output("  Виконайте: php composer update");
}

// Підсумок
output('');
output('Підсумок:');
$tools_installed = 0;
$tools_total = 3; // Ghostscript, QPDF, pdftk

if ($gs_version !== false) $tools_installed++;
if ($qpdf_version !== false) $tools_installed++;
if ($pdftk_version !== false) $tools_installed++;

output("Встановлено {$tools_installed} з {$tools_total} зовнішніх інструментів.");

if ($tools_installed === 0) {
    output('');
    output('Увага: Жоден зовнішній інструмент не встановлено!');
    output('PDF Unlock Tool використовуватиме запасні методи, які можуть працювати не для всіх PDF-файлів.');
    output('Наполегливо рекомендується встановити хоча б один із зовнішніх інструментів.');
} elseif ($tools_installed < $tools_total) {
    output('');
    output('Рекомендація: Встановіть усі зовнішні інструменти для найкращих результатів.');
    output('Різні інструменти краще працюють для різних типів захисту PDF.');
}

output('');
output('Для інструкцій з встановлення див. INSTALL.md');
output(''); 