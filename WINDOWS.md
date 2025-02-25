# Windows Installation Guide for PDF Unlock Tool

This guide provides detailed instructions for installing the PDF Unlock Tool on Windows systems.

## Prerequisites

- PHP 7.2 or higher (recommended: PHP 8.0+)
- Web server (XAMPP, WAMP, or similar)
- External tools for PDF unlocking (optional but recommended)

## Installation Steps

### 1. Install a Web Server Package

Choose one of the following options:

- **XAMPP**: [Download XAMPP](https://www.apachefriends.org/index.html)
- **WAMP**: [Download WAMP](https://www.wampserver.com/en/)
- **Laragon**: [Download Laragon](https://laragon.org/download/)

Follow the installation instructions for your chosen package.

### 2. Clone or Download the PDF Unlock Tool

- Download the latest release from GitHub
- Extract the files to your web server's document root:
  - XAMPP: `C:\xampp\htdocs\unlockpdf`
  - WAMP: `C:\wamp\www\unlockpdf`
  - Laragon: `C:\laragon\www\unlockpdf`

### 3. Install Dependencies Using the Automated Script

1. Open Command Prompt or PowerShell
2. Navigate to your project directory:
   ```
   cd C:\xampp\htdocs\unlockpdf
   ```
   (Adjust the path according to your web server)
3. Run the automated installation script:
   ```
   php install_dependencies.php
   ```

### 4. Install External Tools (Recommended)

For best results, install the following external tools:

#### Ghostscript

1. Download Ghostscript from [the official website](https://www.ghostscript.com/releases/gsdnld.html)
2. Run the installer and follow the instructions
3. **Important**: Make sure to check the option to add Ghostscript to your PATH during installation

#### QPDF

1. Download QPDF from [GitHub releases](https://github.com/qpdf/qpdf/releases)
2. Extract the ZIP file to a location of your choice (e.g., `C:\Program Files\qpdf`)
3. Add the bin directory to your PATH:
   - Right-click on "This PC" or "My Computer" and select "Properties"
   - Click on "Advanced system settings"
   - Click on "Environment Variables"
   - Under "System variables", find the "Path" variable, select it and click "Edit"
   - Click "New" and add the path to the QPDF bin directory (e.g., `C:\Program Files\qpdf\bin`)
   - Click "OK" on all dialogs to save the changes

#### pdftk

1. Download pdftk from [the official website](https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/)
2. Run the installer and follow the instructions
3. The installer should automatically add pdftk to your PATH

### 5. Verify Installation

1. Run the check tools script to verify that everything is installed correctly:
   ```
   php check_tools.php
   ```
2. Start your web server if it's not already running
3. Open a web browser and navigate to:
   - XAMPP: `http://localhost/unlockpdf`
   - WAMP: `http://localhost/unlockpdf`
   - Laragon: `http://unlockpdf.test` (if you've configured Laragon's automatic virtual hosts)

## Troubleshooting

### External Tools Not Found

If the `check_tools.php` script reports that external tools are not found, even though you've installed them:

1. Make sure you've added the tools to your PATH as described above
2. Restart your command prompt or PowerShell to refresh the PATH
3. Restart your computer if necessary

### PHP Extensions Missing

If you're missing required PHP extensions:

1. Open your php.ini file:
   - XAMPP: `C:\xampp\php\php.ini`
   - WAMP: `C:\wamp\bin\php\php{version}\php.ini`
   - Laragon: `C:\laragon\bin\php\php{version}\php.ini`
2. Uncomment (remove the semicolon) from the beginning of the following lines:
   ```
   ;extension=fileinfo
   ;extension=mbstring
   ;extension=openssl
   ;extension=zip
   ```
3. Save the file and restart your web server

### Permission Issues

If you encounter permission issues:

1. Make sure your web server has write permissions to the following directories:
   - `uploads/`
   - `processed/`
   - `logs/`
2. If running from a command prompt, make sure you're running as an administrator

## Testing the Installation

1. Upload a password-protected PDF file through the web interface
2. Check if the unlocking process works
3. If you encounter issues, check the logs in the `logs/` directory

## Additional Resources

- For more detailed installation instructions, see the main [INSTALL.md](INSTALL.md) file
- For troubleshooting common issues, see the [Troubleshooting](#troubleshooting) section above
- For support, please open an issue on GitHub 