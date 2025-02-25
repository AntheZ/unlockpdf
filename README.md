# PDF Unlock Tool

A simple web application that allows users to unlock protected PDF documents, enabling text copying and editing.

## Features

- Upload PDF files from your device
- Provide a URL to a PDF file online
- Automatic removal of copy protection from PDF files
- Progress bar showing the unlocking process
- Files are automatically deleted after 10 minutes
- User-specific file management using cookies

## Requirements

- PHP 7.2 or higher
- Web server (Apache, Nginx, etc.)
- Optional but recommended: QPDF or Ghostscript for better PDF unlocking

## Installation

1. Clone or download this repository to your web server directory
2. Make sure the following directories are writable by the web server:
   - `uploads/`
   - `processed/`
3. If they don't exist, create them with proper permissions:
   ```
   mkdir uploads processed
   chmod 755 uploads processed
   ```
4. For better PDF unlocking capabilities, install QPDF or Ghostscript:
   - On Debian/Ubuntu: `sudo apt-get install qpdf ghostscript`
   - On CentOS/RHEL: `sudo yum install qpdf ghostscript`
   - On Windows: Download and install from their official websites

## Usage

1. Open the application in your web browser
2. Choose a PDF file from your device or enter a URL to a PDF file
3. Click "Unlock PDF" to start the process
4. Wait for the unlocking process to complete
5. Download the unlocked PDF file
6. The file will be available for 10 minutes before being automatically deleted

## Security Considerations

- The application uses cookies to identify users and manage their files
- All uploaded and processed files are automatically deleted after 10 minutes
- File IDs are randomly generated to prevent unauthorized access
- Input validation is implemented to prevent security issues

## License

This project is licensed under the MIT License - see the LICENSE file for details.
