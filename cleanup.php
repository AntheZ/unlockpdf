<?php
/**
 * PDF Unlock Tool - Скрипт очищення застарілих файлів
 * 
 * Цей скрипт видаляє файли, термін дії яких закінчився
 * Рекомендується запускати через cron кожні 5-10 хвилин
 * Приклад налаштування cron: */5 * * * * php /path/to/cleanup.php
 */

// Підключення необхідних файлів
require_once __DIR__ . '/includes/functions.php';

// Логування початку процесу очищення
logMessage("Початок процесу очищення застарілих файлів");

// Виклик функції очищення
cleanupExpiredFiles();

// Логування завершення процесу очищення
logMessage("Процес очищення застарілих файлів завершено");

/**
 * Функція для запуску з командного рядка
 */
if (php_sapi_name() === 'cli') {
    echo "PDF Unlock Tool - Очищення застарілих файлів\n";
    echo "Процес очищення завершено. Перевірте логи для деталей.\n";
} 