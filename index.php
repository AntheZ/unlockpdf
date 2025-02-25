<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Unlock Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>PDF Unlock Tool</h1>
            <p>Remove protection from PDF files to enable copying and editing</p>
        </header>

        <main>
            <div class="upload-section">
                <form id="pdf-upload-form" enctype="multipart/form-data">
                    <div class="upload-methods">
                        <div class="upload-method">
                            <h3>Upload from your device</h3>
                            <div class="file-input-container">
                                <input type="file" name="pdf_file" id="pdf-file" accept=".pdf">
                                <label for="pdf-file">Choose PDF file</label>
                            </div>
                        </div>
                        
                        <div class="upload-method">
                            <h3>Upload from URL</h3>
                            <div class="url-input-container">
                                <input type="url" name="pdf_url" id="pdf-url" placeholder="Enter PDF URL">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" id="submit-btn">Unlock PDF</button>
                </form>
            </div>

            <div id="progress-container" class="hidden">
                <h3>Processing your PDF...</h3>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p id="progress-status">0%</p>
            </div>

            <div id="results-container" class="hidden">
                <h3>Your unlocked PDF is ready!</h3>
                <div id="file-list">
                    <!-- Unlocked files will be listed here -->
                </div>
            </div>
        </main>

        <footer>
            <p>&copy; <?php echo date('Y'); ?> PDF Unlock Tool. All files are automatically deleted after 10 minutes.</p>
        </footer>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html> 