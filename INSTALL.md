# Installation Guide for PDF Unlock Tool

This guide provides detailed instructions for installing the PDF Unlock Tool on various hosting environments.

## Quick Installation (New!)

We've added simplified installation scripts to make setup easier:

1. **Automatic Dependency Installation**
   - Run the following command to automatically download and install Composer and all dependencies:
     ```
     php install_dependencies.php
     ```
   - This script will:
     - Download and install Composer locally
     - Install all required dependencies
     - Create necessary directories (uploads, processed, logs)

2. **Check External Tools**
   - Run the following command to check if all required external tools are installed:
     ```
     php check_tools.php
     ```
   - This script will:
     - Check for required PHP extensions
     - Check if Ghostscript, QPDF, and pdftk are installed
     - Provide download links for missing tools
     - Verify if PHP libraries are properly installed

## Shared Hosting Installation

### Prerequisites
- PHP 7.2 or higher
- Access to cPanel, Plesk, or similar control panel
- FTP or SSH access to your hosting account

### Steps

1. **Upload Files**
   - Download the latest release from GitHub
   - Extract the files on your local computer
   - Upload all files to your web hosting account (public_html or www directory)

2. **Set Directory Permissions**
   - Create the following directories if they don't exist:
     ```
     uploads/
     processed/
     logs/
     ```
   - Set permissions to 755 (or 775 if 755 doesn't work):
     ```
     chmod 755 uploads processed logs
     ```

3. **Install Dependencies (if you have SSH access)**
   - Install Composer if not already installed:
     ```
     curl -sS https://getcomposer.org/installer | php
     mv composer.phar composer
     ```
   - Install dependencies:
     ```
     php composer install --no-dev
     ```

4. **Install Dependencies (if you don't have SSH access)**
   - Install Composer on your local machine
   - Run `composer install --no-dev` locally
   - Upload the vendor directory to your hosting account

5. **Test the Installation**
   - Visit your website in a browser
   - Try uploading a PDF file
   - Check the logs directory if you encounter any issues

## VPS or Dedicated Server Installation

### Prerequisites
- Ubuntu/Debian, CentOS, or similar Linux distribution
- Root or sudo access
- PHP 7.2 or higher
- Web server (Apache or Nginx)

### Steps

1. **Install Required Packages**
   
   For Ubuntu/Debian:
   ```
   sudo apt update
   sudo apt install apache2 php php-common php-mbstring php-zip php-gd php-xml php-curl unzip qpdf ghostscript
   ```
   
   For CentOS:
   ```
   sudo yum install httpd php php-common php-mbstring php-zip php-gd php-xml php-curl unzip qpdf ghostscript
   ```

2. **Clone the Repository**
   ```
   cd /var/www/html
   git clone https://github.com/yourusername/unlockpdf.git
   cd unlockpdf
   ```

3. **Set Directory Permissions**
   ```
   mkdir -p uploads processed logs
   chmod 755 uploads processed logs
   chown -R www-data:www-data . # For Ubuntu/Debian
   # OR
   chown -R apache:apache . # For CentOS
   ```

4. **Install Composer and Dependencies**
   ```
   curl -sS https://getcomposer.org/installer | php
   php composer.phar install --no-dev
   ```

5. **Configure Web Server**
   
   For Apache, create a virtual host configuration:
   ```
   sudo nano /etc/apache2/sites-available/unlockpdf.conf
   ```
   
   Add the following:
   ```
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /var/www/html/unlockpdf
       
       <Directory /var/www/html/unlockpdf>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/unlockpdf_error.log
       CustomLog ${APACHE_LOG_DIR}/unlockpdf_access.log combined
   </VirtualHost>
   ```
   
   Enable the site and restart Apache:
   ```
   sudo a2ensite unlockpdf.conf
   sudo systemctl restart apache2
   ```

6. **Test the Installation**
   - Visit your domain in a browser
   - Try uploading a PDF file
   - Check the logs directory if you encounter any issues

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   - Check that the web server user has write permissions to the uploads, processed, and logs directories
   - Try setting permissions to 777 temporarily for testing:
     ```
     chmod -R 777 uploads processed logs
     ```
   - After testing, set more restrictive permissions:
     ```
     chmod -R 755 uploads processed logs
     ```

2. **PDF Unlocking Fails**
   - Check if QPDF or Ghostscript is installed:
     ```
     qpdf --version
     gs --version
     ```
   - Install if missing:
     ```
     sudo apt install qpdf ghostscript # Ubuntu/Debian
     sudo yum install qpdf ghostscript # CentOS
     ```

3. **Composer Installation Fails**
   - Make sure you have the required PHP extensions:
     ```
     sudo apt install php-mbstring php-zip php-xml # Ubuntu/Debian
     sudo yum install php-mbstring php-zip php-xml # CentOS
     ```

4. **File Upload Issues**
   - Check PHP upload limits in php.ini:
     ```
     upload_max_filesize = 20M
     post_max_size = 20M
     ```
   - Restart web server after changes:
     ```
     sudo systemctl restart apache2 # Ubuntu/Debian
     sudo systemctl restart httpd # CentOS
     ```

### Checking Logs

- Application logs: `logs/app.log`
- Web server logs:
  - Apache (Ubuntu/Debian): `/var/log/apache2/error.log`
  - Apache (CentOS): `/var/log/httpd/error_log`
  - Nginx: `/var/log/nginx/error.log`
- PHP logs: Check your php.ini for error_log setting

## Security Recommendations

1. **Set Proper File Permissions**
   - Files should be 644
   - Directories should be 755
   - Sensitive configuration files should be 600

2. **Use HTTPS**
   - Install an SSL certificate (Let's Encrypt is free)
   - Configure your web server to use HTTPS

3. **Limit Upload Size**
   - Set reasonable limits in php.ini:
     ```
     upload_max_filesize = 20M
     post_max_size = 20M
     ```

4. **Regular Updates**
   - Keep the application and all dependencies updated
   - Run `composer update` periodically

5. **Firewall Configuration**
   - Configure a firewall to allow only necessary ports (80, 443)
   - Consider using a Web Application Firewall (WAF) 