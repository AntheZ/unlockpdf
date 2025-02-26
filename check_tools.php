<?php
/**
 * PDF Unlock Tool - Перевірка наявності необхідних інструментів
 * 
 * Цей файл перевіряє наявність інструментів, необхідних для розблокування PDF-файлів
 */

// Підключення необхідних файлів
require_once 'includes/functions.php';

// Перевірка, чи запущено з командного рядка
$isCli = php_sapi_name() === 'cli';

// Функція для виведення повідомлень
function printMessage($message, $type = 'info') {
    global $isCli;
    
    if ($isCli) {
        $prefix = '';
        switch ($type) {
            case 'success':
                $prefix = "\033[32m[✓]\033[0m ";
                break;
            case 'error':
                $prefix = "\033[31m[✗]\033[0m ";
                break;
            case 'warning':
                $prefix = "\033[33m[!]\033[0m ";
                break;
            default:
                $prefix = "\033[34m[i]\033[0m ";
        }
        echo $prefix . $message . PHP_EOL;
    } else {
        $class = '';
        switch ($type) {
            case 'success':
                $class = 'success';
                break;
            case 'error':
                $class = 'error';
                break;
            case 'warning':
                $class = 'warning';
                break;
            default:
                $class = 'info';
        }
        echo '<div class="message ' . $class . '">' . $message . '</div>';
    }
}

// Перевірка версії PHP
$phpVersion = phpversion();
$requiredPhpVersion = '7.2.0';
$phpVersionCheck = version_compare($phpVersion, $requiredPhpVersion, '>=');

// Перевірка наявності необхідних розширень PHP
$requiredExtensions = ['gd', 'mbstring', 'fileinfo'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

// Перевірка наявності інструментів
$tools = [
    'ghostscript' => isGhostscriptInstalled(),
    'qpdf' => isQpdfInstalled(),
    'pdftk' => isPdftkInstalled()
];

// Перевірка прав доступу до директорій
$directories = [
    'uploads' => __DIR__ . '/uploads',
    'processed' => __DIR__ . '/processed',
    'logs' => __DIR__ . '/logs'
];

$directoryIssues = [];

foreach ($directories as $name => $path) {
    if (!is_dir($path)) {
        $directoryIssues[$name] = 'не існує';
    } elseif (!is_writable($path)) {
        $directoryIssues[$name] = 'немає прав на запис';
    }
}

// Виведення результатів
if (!$isCli) {
    echo '<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Unlock Tool - Перевірка інструментів</title>
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
        .message {
            padding: 10px;
            margin-bottom: 10px;
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
        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h2 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PDF Unlock Tool - Перевірка інструментів</h1>';
}

// Виведення заголовка для CLI
if ($isCli) {
    echo "=== PDF Unlock Tool - Перевірка інструментів ===" . PHP_EOL . PHP_EOL;
}

// Виведення інформації про PHP
if (!$isCli) echo '<div class="section"><h2>PHP</h2>';

if ($phpVersionCheck) {
    printMessage("PHP версія: $phpVersion (відповідає вимогам)", 'success');
} else {
    printMessage("PHP версія: $phpVersion (потрібно $requiredPhpVersion або вище)", 'error');
}

if (empty($missingExtensions)) {
    printMessage("Усі необхідні розширення PHP встановлені", 'success');
} else {
    printMessage("Відсутні розширення PHP: " . implode(', ', $missingExtensions), 'error');
}

if (!$isCli) echo '</div>';

// Виведення інформації про інструменти
if (!$isCli) echo '<div class="section"><h2>Інструменти</h2>';

$hasTools = false;
foreach ($tools as $tool => $installed) {
    if ($installed) {
        $hasTools = true;
        printMessage("$tool: встановлено", 'success');
    } else {
        printMessage("$tool: не встановлено", 'warning');
    }
}

if (!$hasTools) {
    printMessage("Жоден з інструментів не встановлено. PDF Unlock Tool буде використовувати власні методи розблокування.", 'warning');
} else {
    printMessage("PDF Unlock Tool буде використовувати доступні інструменти для розблокування PDF-файлів.", 'info');
}

if (!$isCli) echo '</div>';

// Виведення інформації про директорії
if (!$isCli) echo '<div class="section"><h2>Директорії</h2>';

if (empty($directoryIssues)) {
    printMessage("Усі необхідні директорії існують і мають правильні права доступу", 'success');
} else {
    foreach ($directoryIssues as $name => $issue) {
        printMessage("Директорія '$name': $issue", 'error');
    }
    
    printMessage("Виконайте наступну команду для створення директорій:", 'info');
    $command = "mkdir -p " . implode(' ', array_values($directories));
    printMessage($command, 'info');
    
    printMessage("Виконайте наступну команду для встановлення прав доступу:", 'info');
    $command = "chmod 755 " . implode(' ', array_values($directories));
    printMessage($command, 'info');
}

if (!$isCli) echo '</div>';

// Виведення загального результату
if (!$isCli) echo '<div class="section"><h2>Загальний результат</h2>';

$hasErrors = !$phpVersionCheck || !empty($missingExtensions) || !empty($directoryIssues);

if ($hasErrors) {
    printMessage("Виявлено проблеми, які потрібно вирішити перед використанням PDF Unlock Tool", 'error');
} else {
    printMessage("PDF Unlock Tool готовий до використання", 'success');
}

if (!$isCli) {
    echo '<a href="index.php" class="back-link">Повернутися на головну сторінку</a>';
    echo '</div></div></body></html>';
} 