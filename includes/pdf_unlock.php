<?php
/**
 * PDF Unlock Tool - Власна бібліотека для розблокування PDF-файлів
 * 
 * Цей файл містить функції для розблокування PDF-файлів різними методами
 * без залежності від сторонніх бібліотек.
 */

/**
 * Перевірка наявності Ghostscript
 * 
 * @return bool Чи встановлено Ghostscript
 */
function isGhostscriptInstalled() {
    logMessage("Checking if Ghostscript is installed...");
    
    // Перевірка для Linux/Mac
    $command = 'gs --version 2>&1';
    logMessage("Trying command: " . $command);
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        logMessage("Ghostscript found: " . implode(", ", $output));
        return true;
    }
    
    // Перевірка для Windows (64-bit)
    $command = 'gswin64c --version 2>&1';
    logMessage("Trying command: " . $command);
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        logMessage("Ghostscript found (64-bit Windows): " . implode(", ", $output));
        return true;
    }
    
    // Перевірка для Windows (32-bit)
    $command = 'gswin32c --version 2>&1';
    logMessage("Trying command: " . $command);
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        logMessage("Ghostscript found (32-bit Windows): " . implode(", ", $output));
        return true;
    }
    
    logMessage("Ghostscript not found");
    return false;
}

/**
 * Перевірка наявності QPDF
 * 
 * @return bool Чи встановлено QPDF
 */
function isQpdfInstalled() {
    $command = 'qpdf --version 2>&1';
    exec($command, $output, $returnVar);
    
    return ($returnVar === 0);
}

/**
 * Перевірка наявності pdftk
 * 
 * @return bool Чи встановлено pdftk
 */
function isPdftkInstalled() {
    $command = 'pdftk --version 2>&1';
    exec($command, $output, $returnVar);
    
    return ($returnVar === 0);
}

/**
 * Розблокування PDF-файлу за допомогою Ghostscript з розширеними параметрами
 * 
 * @param string $inputFile Шлях до вхідного файлу
 * @param string $outputFile Шлях до вихідного файлу
 * @return bool Чи вдалося розблокувати файл
 */
function unlockPdfWithEnhancedGhostscript($inputFile, $outputFile) {
    logMessage("Method 1: Attempting to unlock with Ghostscript (enhanced parameters)");
    
    if (!isGhostscriptInstalled()) {
        logMessage("Ghostscript is not installed, skipping this method");
        return false;
    }
    
    // Визначення команди Ghostscript в залежності від ОС
    $gsCommand = 'gs';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        if (commandExists('gswin64c')) {
            $gsCommand = 'gswin64c';
        } elseif (commandExists('gswin32c')) {
            $gsCommand = 'gswin32c';
        }
    }
    
    // Екранування шляхів до файлів
    $escapedInputFile = escapeshellarg($inputFile);
    $escapedOutputFile = escapeshellarg($outputFile);
    
    // Формування команди Ghostscript з розширеними параметрами
    $command = "$gsCommand -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 " .
               "-dPDFSETTINGS=/default -dCompressFonts=true -dDetectDuplicateImages=true " .
               "-dAutoRotatePages=/None -dPrinted=false -dCannotEmbedFontPolicy=/Warning " .
               "-c \"<</AllowPrint true /AllowCopy true /AllowChange true /AllowAnnots true " .
               "/AllowFillIn true /AllowScreenReaders true /AllowAssembly true " .
               "/AllowDegradedPrinting true /OwnerPassword () /UserPassword () " .
               "/EncryptMetadata false>> setpdfparams\" -f $escapedInputFile " .
               "-sOutputFile=$escapedOutputFile 2>&1";
    
    logMessage("Виконання команди Ghostscript з розширеними параметрами: " . $command);
    
    // Виконання команди
    exec($command, $output, $returnVar);
    
    // Перевірка результату
    if ($returnVar === 0 && file_exists($outputFile) && filesize($outputFile) > 0) {
        logMessage("PDF успішно розблоковано за допомогою Ghostscript з розширеними параметрами");
        return true;
    } else {
        $outputStr = implode("\n", $output);
        logMessage("Не вдалося розблокувати PDF за допомогою Ghostscript з розширеними параметрами. Код повернення: $returnVar, Вивід: $outputStr");
        return false;
    }
}

/**
 * Розблокування PDF-файлу за допомогою стандартного Ghostscript
 * 
 * @param string $inputFile Шлях до вхідного файлу
 * @param string $outputFile Шлях до вихідного файлу
 * @return bool Чи вдалося розблокувати файл
 */
function unlockPdfWithGhostscript($inputFile, $outputFile) {
    logMessage("Method 2: Attempting to unlock with standard Ghostscript");
    
    if (!isGhostscriptInstalled()) {
        logMessage("Ghostscript is not installed, skipping this method");
        return false;
    }
    
    // Визначення команди Ghostscript в залежності від ОС
    $gsCommand = 'gs';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        if (commandExists('gswin64c')) {
            $gsCommand = 'gswin64c';
        } elseif (commandExists('gswin32c')) {
            $gsCommand = 'gswin32c';
        }
    }
    
    // Екранування шляхів до файлів
    $escapedInputFile = escapeshellarg($inputFile);
    $escapedOutputFile = escapeshellarg($outputFile);
    
    // Формування стандартної команди Ghostscript
    $command = "$gsCommand -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$escapedOutputFile -c .setpdfwrite -f $escapedInputFile 2>&1";
    
    logMessage("Виконання стандартної команди Ghostscript: " . $command);
    
    // Виконання команди
    exec($command, $output, $returnVar);
    
    // Перевірка результату
    if ($returnVar === 0 && file_exists($outputFile) && filesize($outputFile) > 0) {
        logMessage("PDF успішно розблоковано за допомогою стандартного Ghostscript");
        return true;
    } else {
        $outputStr = implode("\n", $output);
        logMessage("Не вдалося розблокувати PDF за допомогою стандартного Ghostscript. Код повернення: $returnVar, Вивід: $outputStr");
        return false;
    }
}

/**
 * Розблокування PDF за допомогою QPDF
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithQpdf($inputFile, $outputFile) {
    // Перевірка наявності QPDF
    if (!isQpdfInstalled()) {
        logMessage("QPDF не встановлено");
        return false;
    }
    
    // Конструювання команди QPDF
    $command = sprintf(
        'qpdf --decrypt "%s" "%s" 2>&1',
        escapeshellarg($inputFile),
        escapeshellarg($outputFile)
    );
    
    // Логування команди
    logMessage("Виконання команди QPDF: " . $command);
    
    // Виконання команди
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Перевірка успішності виконання
    if ($returnCode === 0 && file_exists($outputFile)) {
        logMessage("PDF успішно розблоковано за допомогою QPDF");
        return true;
    } else {
        logMessage("Не вдалося розблокувати PDF за допомогою QPDF. Код повернення: " . $returnCode . ", Вивід: " . implode("\n", $output));
        return false;
    }
}

/**
 * Розблокування PDF за допомогою pdftk
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithPdftk($inputFile, $outputFile) {
    // Перевірка наявності pdftk
    if (!isPdftkInstalled()) {
        logMessage("pdftk не встановлено");
        return false;
    }
    
    // Конструювання команди pdftk
    $command = sprintf(
        'pdftk "%s" output "%s" allow all 2>&1',
        escapeshellarg($inputFile),
        escapeshellarg($outputFile)
    );
    
    // Логування команди
    logMessage("Виконання команди pdftk: " . $command);
    
    // Виконання команди
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Перевірка успішності виконання
    if ($returnCode === 0 && file_exists($outputFile)) {
        logMessage("PDF успішно розблоковано за допомогою pdftk");
        return true;
    } else {
        logMessage("Не вдалося розблокувати PDF за допомогою pdftk. Код повернення: " . $returnCode . ", Вивід: " . implode("\n", $output));
        return false;
    }
}

/**
 * Розблокування PDF-файлу за допомогою PHP
 * 
 * @param string $inputFile Шлях до вхідного файлу
 * @param string $outputFile Шлях до вихідного файлу
 * @return bool Чи вдалося розблокувати файл
 */
function unlockPdfWithPhp($inputFile, $outputFile) {
    logMessage("Method 5: Attempting to unlock with PHP (custom implementation)");
    logMessage("Спроба розблокування PDF за допомогою PHP: " . $inputFile);
    
    // Перевірка існування вхідного файлу
    if (!file_exists($inputFile)) {
        logMessage("Вхідний файл не існує: " . $inputFile);
        return false;
    }
    
    // Читання вмісту PDF-файлу
    $content = file_get_contents($inputFile);
    if ($content === false) {
        logMessage("Не вдалося прочитати вхідний файл: " . $inputFile);
        return false;
    }
    
    // Збереження оригінального розміру для порівняння
    $originalSize = strlen($content);
    logMessage("Розмір оригінального файлу: " . $originalSize . " байт");
    
    // Видалення шифрування
    $patterns = [
        // Видалення словника шифрування
        '/\/Encrypt\s+\d+\s+\d+\s+R/i' => '',
        '/\/Encrypt\s*<<.*?>>/s' => '',
        
        // Видалення посилань на шифрування
        '/\/Encrypt\s*\[\s*.*?\s*\]/s' => '',
        
        // Видалення прав доступу
        '/\/P\s+(-?\d+)/i' => '/P 4294967295',
        
        // Видалення прапорців шифрування
        '/\/EncryptMetadata\s+(true|false)/i' => '/EncryptMetadata false',
        '/\/Encrypted\s+(true|false)/i' => '/Encrypted false',
        
        // Видалення паролів
        '/\/O\s*\(.*?\)/s' => '/O ()',
        '/\/U\s*\(.*?\)/s' => '/U ()',
        '/\/OE\s*\(.*?\)/s' => '/OE ()',
        '/\/UE\s*\(.*?\)/s' => '/UE ()',
        '/\/Perms\s*\(.*?\)/s' => '/Perms ()',
        
        // Видалення ключів шифрування
        '/\/CF\s*<<.*?>>/s' => '',
        '/\/StmF\s*\/.*?/i' => '',
        '/\/StrF\s*\/.*?/i' => '',
        '/\/EFF\s*\/.*?/i' => '',
        
        // Видалення методу шифрування
        '/\/Filter\s*\/Standard/i' => '',
        '/\/SubFilter\s*\/.*?/i' => '',
        '/\/V\s+\d+/i' => '',
        '/\/Length\s+\d+/i' => '',
        '/\/R\s+\d+/i' => '',
        
        // Додаткові шаблони для видалення обмежень
        '/\/Metadata\s+\d+\s+\d+\s+R/i' => '',
    ];
    
    // Застосування шаблонів для видалення шифрування
    $modifiedContent = $content;
    $changesCount = 0;
    
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $modifiedContent, -1, $count);
        if ($count > 0) {
            $changesCount += $count;
            $modifiedContent = $newContent;
            logMessage("Застосовано шаблон: " . $pattern . " (замінено " . $count . " входжень)");
        }
    }
    
    // Додавання дозволів на всі операції
    if (strpos($modifiedContent, '/Permissions') === false) {
        $modifiedContent = preg_replace(
            '/(\/Type\s*\/Catalog\s*)/i',
            '$1/Permissions << /TrueValue true >> ',
            $modifiedContent,
            1,
            $count
        );
        
        if ($count > 0) {
            $changesCount++;
            logMessage("Додано дозволи на всі операції");
        }
    }
    
    // Перевірка, чи були внесені зміни
    if ($changesCount === 0) {
        logMessage("Не знайдено шифрування або обмежень для видалення");
    } else {
        logMessage("Видалено " . $changesCount . " елементів шифрування або обмежень");
    }
    
    // Запис модифікованого вмісту у вихідний файл
    $result = file_put_contents($outputFile, $modifiedContent);
    
    if ($result === false) {
        logMessage("Не вдалося записати у вихідний файл: " . $outputFile);
        return false;
    }
    
    // Перевірка розміру вихідного файлу
    $newSize = filesize($outputFile);
    logMessage("Розмір розблокованого файлу: " . $newSize . " байт");
    
    // Перевірка, чи є вихідний файл дійсним PDF
    $handle = fopen($outputFile, 'rb');
    if (!$handle) {
        logMessage("Не вдалося відкрити вихідний файл для перевірки: " . $outputFile);
        return false;
    }
    
    $header = fread($handle, 4);
    fclose($handle);
    
    if (substr($header, 0, 4) !== '%PDF') {
        logMessage("Вихідний файл не є дійсним PDF (неправильний заголовок)");
        return false;
    }
    
    logMessage("PDF успішно розблоковано за допомогою PHP");
    logMessage("Successfully unlocked PDF with PHP (custom implementation)");
    return true;
}

/**
 * Просте копіювання PDF-файлу як останній варіант
 * 
 * @param string $inputFile Шлях до вхідного файлу
 * @param string $outputFile Шлях до вихідного файлу
 * @return bool Чи вдалося скопіювати файл
 */
function simplePdfCopy($inputFile, $outputFile) {
    logMessage("Method 8: Attempting simple file copy as last resort");
    
    // Перевірка існування вхідного файлу
    if (!file_exists($inputFile)) {
        logMessage("Вхідний файл не існує: " . $inputFile);
        return false;
    }
    
    // Перевірка, чи є вхідний файл дійсним PDF
    $handle = fopen($inputFile, 'rb');
    if (!$handle) {
        logMessage("Не вдалося відкрити вхідний файл для перевірки: " . $inputFile);
        return false;
    }
    
    $header = fread($handle, 4);
    fclose($handle);
    
    if (substr($header, 0, 4) !== '%PDF') {
        logMessage("Вхідний файл не є дійсним PDF (неправильний заголовок)");
        return false;
    }
    
    // Копіювання файлу
    $result = copy($inputFile, $outputFile);
    
    if ($result) {
        logMessage("Файл успішно скопійовано: " . $inputFile . " -> " . $outputFile);
        
        // Перевірка розміру вихідного файлу
        $inputSize = filesize($inputFile);
        $outputSize = filesize($outputFile);
        
        logMessage("Розмір вхідного файлу: " . $inputSize . " байт");
        logMessage("Розмір вихідного файлу: " . $outputSize . " байт");
        
        if ($outputSize === 0) {
            logMessage("Помилка: вихідний файл має нульовий розмір");
            return false;
        }
        
        if ($inputSize !== $outputSize) {
            logMessage("Попередження: розмір вхідного та вихідного файлів відрізняється");
        }
        
        return true;
    } else {
        $error = error_get_last();
        logMessage("Не вдалося скопіювати файл: " . ($error ? $error['message'] : 'Невідома помилка'));
        return false;
    }
}

/**
 * Розблокування PDF-файлу шляхом модифікації метаданих
 * 
 * @param string $inputFile Шлях до вхідного файлу
 * @param string $outputFile Шлях до вихідного файлу
 * @return bool Чи вдалося розблокувати файл
 */
function unlockPdfWithMetadataModification($inputFile, $outputFile) {
    logMessage("Method 6: Attempting to unlock with metadata modification");
    
    // Перевірка існування вхідного файлу
    if (!file_exists($inputFile)) {
        logMessage("Вхідний файл не існує: " . $inputFile);
        return false;
    }
    
    // Читання вмісту PDF-файлу
    $content = file_get_contents($inputFile);
    if ($content === false) {
        logMessage("Не вдалося прочитати вхідний файл: " . $inputFile);
        return false;
    }
    
    // Збереження оригінального розміру для порівняння
    $originalSize = strlen($content);
    logMessage("Розмір оригінального файлу: " . $originalSize . " байт");
    
    // Модифікація метаданих
    $modifiedContent = $content;
    $changesCount = 0;
    
    // 1. Видалення метаданих XMP
    $xmpPattern = '/<\?xpacket\s+begin.*?<\?xpacket\s+end.*?>/s';
    $modifiedContent = preg_replace($xmpPattern, '', $modifiedContent, -1, $count);
    if ($count > 0) {
        $changesCount += $count;
        logMessage("Видалено XMP метадані: " . $count . " входжень");
    }
    
    // 2. Видалення посилань на метадані
    $metadataPattern = '/\/Metadata\s+\d+\s+\d+\s+R/i';
    $modifiedContent = preg_replace($metadataPattern, '', $modifiedContent, -1, $count);
    if ($count > 0) {
        $changesCount += $count;
        logMessage("Видалено посилання на метадані: " . $count . " входжень");
    }
    
    // 3. Модифікація інформації про документ
    $infoPatterns = [
        // Видалення інформації про захист
        '/\/Encrypted\s+(true|false)/i' => '/Encrypted false',
        
        // Модифікація прав доступу
        '/\/P\s+(-?\d+)/i' => '/P 4294967295',
        
        // Видалення інформації про власника
        '/\/Producer\s*\(.*?\)/s' => '/Producer (PDF Unlock Tool)',
        '/\/Creator\s*\(.*?\)/s' => '/Creator (PDF Unlock Tool)',
        
        // Видалення інформації про шифрування
        '/\/Filter\s*\/Standard/i' => '',
        '/\/V\s+\d+/i' => '',
        '/\/R\s+\d+/i' => '',
        '/\/O\s*\(.*?\)/s' => '',
        '/\/U\s*\(.*?\)/s' => '',
    ];
    
    foreach ($infoPatterns as $pattern => $replacement) {
        $modifiedContent = preg_replace($pattern, $replacement, $modifiedContent, -1, $count);
        if ($count > 0) {
            $changesCount += $count;
            logMessage("Модифіковано інформацію про документ: " . $pattern . " (замінено " . $count . " входжень)");
        }
    }
    
    // 4. Додавання нових метаданих
    if (strpos($modifiedContent, '/Type /Catalog') !== false) {
        $catalogPattern = '/(\/Type\s*\/Catalog)/i';
        $catalogReplacement = '$1 /Permissions << /TrueValue true >>';
        $modifiedContent = preg_replace($catalogPattern, $catalogReplacement, $modifiedContent, 1, $count);
        if ($count > 0) {
            $changesCount += $count;
            logMessage("Додано дозволи до каталогу документа");
        }
    }
    
    // Перевірка, чи були внесені зміни
    if ($changesCount === 0) {
        logMessage("Не знайдено метаданих для модифікації");
    } else {
        logMessage("Модифіковано " . $changesCount . " елементів метаданих");
    }
    
    // Запис модифікованого вмісту у вихідний файл
    $result = file_put_contents($outputFile, $modifiedContent);
    
    if ($result === false) {
        logMessage("Не вдалося записати у вихідний файл: " . $outputFile);
        return false;
    }
    
    // Перевірка розміру вихідного файлу
    $newSize = filesize($outputFile);
    logMessage("Розмір розблокованого файлу: " . $newSize . " байт");
    
    // Перевірка, чи є вихідний файл дійсним PDF
    $handle = fopen($outputFile, 'rb');
    if (!$handle) {
        logMessage("Не вдалося відкрити вихідний файл для перевірки: " . $outputFile);
        return false;
    }
    
    $header = fread($handle, 4);
    fclose($handle);
    
    if (substr($header, 0, 4) !== '%PDF') {
        logMessage("Вихідний файл не є дійсним PDF (неправильний заголовок)");
        return false;
    }
    
    logMessage("PDF успішно розблоковано шляхом модифікації метаданих");
    return true;
}

/**
 * Розблокування PDF-файлу шляхом видалення обмежень
 * 
 * @param string $inputFile Шлях до вхідного файлу
 * @param string $outputFile Шлях до вихідного файлу
 * @return bool Чи вдалося розблокувати файл
 */
function unlockPdfWithRestrictionRemoval($inputFile, $outputFile) {
    logMessage("Method 7: Attempting to unlock with restriction removal");
    
    // Перевірка існування вхідного файлу
    if (!file_exists($inputFile)) {
        logMessage("Вхідний файл не існує: " . $inputFile);
        return false;
    }
    
    // Читання вмісту PDF-файлу
    $content = file_get_contents($inputFile);
    if ($content === false) {
        logMessage("Не вдалося прочитати вхідний файл: " . $inputFile);
        return false;
    }
    
    // Збереження оригінального розміру для порівняння
    $originalSize = strlen($content);
    logMessage("Розмір оригінального файлу: " . $originalSize . " байт");
    
    // Видалення обмежень
    $modifiedContent = $content;
    $changesCount = 0;
    
    // 1. Знаходження та модифікація словника прав доступу
    $permissionsPatterns = [
        // Встановлення всіх прав доступу
        '/\/P\s+(-?\d+)/i' => '/P 4294967295',
        
        // Видалення прапорців шифрування
        '/\/Encrypt\s+\d+\s+\d+\s+R/i' => '',
        '/\/Encrypt\s*<<.*?>>/s' => '',
        
        // Видалення обмежень на друк
        '/\/PrintRestricted\s+(true|false)/i' => '/PrintRestricted false',
        '/\/Print\s+(true|false)/i' => '/Print true',
        
        // Видалення обмежень на копіювання
        '/\/CopyRestricted\s+(true|false)/i' => '/CopyRestricted false',
        '/\/Copy\s+(true|false)/i' => '/Copy true',
        
        // Видалення обмежень на редагування
        '/\/ModifyRestricted\s+(true|false)/i' => '/ModifyRestricted false',
        '/\/Modify\s+(true|false)/i' => '/Modify true',
        
        // Видалення обмежень на анотації
        '/\/AnnotRestricted\s+(true|false)/i' => '/AnnotRestricted false',
        '/\/Annot\s+(true|false)/i' => '/Annot true',
        
        // Видалення обмежень на форми
        '/\/FormRestricted\s+(true|false)/i' => '/FormRestricted false',
        '/\/Form\s+(true|false)/i' => '/Form true',
        
        // Видалення обмежень на доступність
        '/\/AccessibilityRestricted\s+(true|false)/i' => '/AccessibilityRestricted false',
        '/\/Accessibility\s+(true|false)/i' => '/Accessibility true',
        
        // Видалення обмежень на збірку
        '/\/AssemblyRestricted\s+(true|false)/i' => '/AssemblyRestricted false',
        '/\/Assembly\s+(true|false)/i' => '/Assembly true',
    ];
    
    foreach ($permissionsPatterns as $pattern => $replacement) {
        $modifiedContent = preg_replace($pattern, $replacement, $modifiedContent, -1, $count);
        if ($count > 0) {
            $changesCount += $count;
            logMessage("Видалено обмеження: " . $pattern . " (замінено " . $count . " входжень)");
        }
    }
    
    // 2. Додавання дозволів до каталогу документа
    if (strpos($modifiedContent, '/Type /Catalog') !== false) {
        $catalogPattern = '/(\/Type\s*\/Catalog)/i';
        $catalogReplacement = '$1 /Permissions << /TrueValue true >>';
        $modifiedContent = preg_replace($catalogPattern, $catalogReplacement, $modifiedContent, 1, $count);
        if ($count > 0) {
            $changesCount += $count;
            logMessage("Додано дозволи до каталогу документа");
        }
    }
    
    // 3. Модифікація трейлера для видалення посилань на шифрування
    $trailerPattern = '/(\/Encrypt\s+\d+\s+\d+\s+R\s*)/i';
    $modifiedContent = preg_replace($trailerPattern, '', $modifiedContent, -1, $count);
    if ($count > 0) {
        $changesCount += $count;
        logMessage("Видалено посилання на шифрування в трейлері: " . $count . " входжень");
    }
    
    // 4. Додавання нового об'єкта з дозволами в кінець документа
    if ($changesCount > 0) {
        $newObj = "\n" . (time() % 1000) . " 0 obj\n<< /Type /Permissions /AllowAll true >>\nendobj\n";
        $modifiedContent = preg_replace('/(%%EOF)/i', $newObj . "\n$1", $modifiedContent, 1, $count);
        if ($count > 0) {
            $changesCount += $count;
            logMessage("Додано новий об'єкт з дозволами");
        }
    }
    
    // Перевірка, чи були внесені зміни
    if ($changesCount === 0) {
        logMessage("Не знайдено обмежень для видалення");
    } else {
        logMessage("Видалено " . $changesCount . " обмежень");
    }
    
    // Запис модифікованого вмісту у вихідний файл
    $result = file_put_contents($outputFile, $modifiedContent);
    
    if ($result === false) {
        logMessage("Не вдалося записати у вихідний файл: " . $outputFile);
        return false;
    }
    
    // Перевірка розміру вихідного файлу
    $newSize = filesize($outputFile);
    logMessage("Розмір розблокованого файлу: " . $newSize . " байт");
    
    // Перевірка, чи є вихідний файл дійсним PDF
    $handle = fopen($outputFile, 'rb');
    if (!$handle) {
        logMessage("Не вдалося відкрити вихідний файл для перевірки: " . $outputFile);
        return false;
    }
    
    $header = fread($handle, 4);
    fclose($handle);
    
    if (substr($header, 0, 4) !== '%PDF') {
        logMessage("Вихідний файл не є дійсним PDF (неправильний заголовок)");
        return false;
    }
    
    logMessage("PDF успішно розблоковано шляхом видалення обмежень");
    return true;
}

/**
 * Перевірка наявності команди в системі
 * 
 * @param string $command Назва команди
 * @return bool Чи існує команда
 */
function commandExists($command) {
    // Додавання логування для відстеження
    logMessage("Checking if command exists: " . $command);
    
    // Визначення ОС
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
    if ($isWindows) {
        // Для Windows використовуємо where
        $cmd = 'where ' . escapeshellarg($command) . ' 2>nul';
        logMessage("Windows command check using: " . $cmd);
        exec($cmd, $output, $return_var);
    } else {
        // Для Linux/Mac використовуємо which
        $cmd = 'which ' . escapeshellarg($command) . ' 2>/dev/null';
        logMessage("Unix command check using: " . $cmd);
        exec($cmd, $output, $return_var);
    }
    
    // Додаткова перевірка для Ghostscript на Windows
    if ($isWindows && $return_var !== 0 && ($command === 'gs' || $command === 'gswin32c' || $command === 'gswin64c')) {
        // Перевірка через реєстр
        $regCmd = 'reg query "HKLM\\SOFTWARE\\GPL Ghostscript" /s 2>nul';
        logMessage("Checking Ghostscript in registry: " . $regCmd);
        exec($regCmd, $regOutput, $regReturn);
        
        if ($regReturn === 0 && !empty($regOutput)) {
            logMessage("Ghostscript found in registry");
            return true;
        }
    }
    
    // Логування результату
    $result = ($return_var === 0);
    logMessage("Command check result for " . $command . ": " . 
               ($result ? "exists" : "not found") . 
               " (return code: " . $return_var . ", output: " . implode(", ", $output) . ")");
    
    return $result;
} 