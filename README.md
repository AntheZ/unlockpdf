# PDF Unlock Tool

Інструмент для розблокування захищених PDF-файлів з обмеженнями на друк, копіювання та редагування.

## Можливості

- Розблокування PDF-файлів з обмеженнями на друк, копіювання та редагування
- Підтримка різних методів розблокування:
  - Ghostscript з розширеними параметрами (основний метод)
  - TCPDF та FPDI
  - Стандартний Ghostscript
  - pdftk (якщо встановлено)
  - QPDF (якщо встановлено)
  - FPDI (як PHP-бібліотека)
- Веб-інтерфейс для завантаження та розблокування PDF-файлів
- Командний рядок для швидкого розблокування
- Детальне логування процесу розблокування
- Автоматичне видалення файлів через 10 хвилин
- Управління файлами користувача за допомогою cookies

## Вимоги

- PHP 7.2 або вище
- Веб-сервер (Apache, Nginx тощо)
- Composer для встановлення залежностей
- Ghostscript (рекомендовано)
- Розширення PHP: fileinfo, zip, gd

## Встановлення

Детальні інструкції з встановлення доступні в файлі [INSTALL.md](INSTALL.md).

Швидке встановлення:

```bash
# Клонувати репозиторій
git clone https://github.com/yourusername/unlockpdf.git
cd unlockpdf

# Встановити залежності
php composer update

# Створити необхідні директорії
mkdir -p uploads processed logs
chmod 755 uploads processed logs

# Перевірити наявність необхідних інструментів
php check_tools.php
```

## Використання

### Веб-інтерфейс

1. Відкрийте сайт у браузері
2. Завантажте PDF-файл з пристрою або вкажіть URL до PDF-файлу
3. Натисніть кнопку "Розблокувати"
4. Дочекайтеся завершення процесу розблокування
5. Завантажте розблокований файл
6. Файл буде доступний протягом 10 хвилин перед автоматичним видаленням

### Командний рядок

```bash
php unlock_with_gs.php шлях/до/файлу.pdf [шлях/до/вихідного/файлу.pdf]
```

Якщо вихідний файл не вказано, буде створено файл з суфіксом "_unlocked".

## Усунення несправностей

Якщо виникають проблеми з розблокуванням PDF-файлів:

1. Перевірте логи в директорії `logs/`
2. Переконайтеся, що всі директорії мають правильні дозволи на запис
3. Переконайтеся, що Ghostscript встановлено та доступний
4. Якщо використовуєте Composer, переконайтеся, що всі залежності встановлено правильно
5. Деякі сильно захищені PDF-файли можуть не піддаватися розблокуванню цим інструментом

## Міркування щодо безпеки

- Додаток використовує cookies для ідентифікації користувачів та управління їхніми файлами
- Всі завантажені та оброблені файли автоматично видаляються через 10 хвилин
- ID файлів генеруються випадковим чином для запобігання несанкціонованому доступу
- Реалізовано валідацію вхідних даних для запобігання проблемам безпеки

## Ліцензія

Цей проект розповсюджується під ліцензією MIT. Детальніше дивіться у файлі LICENSE.
