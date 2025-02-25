document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pdf-upload-form');
    const fileInput = document.getElementById('pdf-file');
    const urlInput = document.getElementById('pdf-url');
    const submitBtn = document.getElementById('submit-btn');
    const progressContainer = document.getElementById('progress-container');
    const progressFill = document.querySelector('.progress-fill');
    const progressStatus = document.getElementById('progress-status');
    const resultsContainer = document.getElementById('results-container');
    const fileList = document.getElementById('file-list');

    // Check for existing files on page load
    checkExistingFiles();

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate input
        const file = fileInput.files[0];
        const url = urlInput.value.trim();
        
        if (!file && !url) {
            alert('Please select a PDF file or enter a URL');
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        if (file) {
            formData.append('pdf_file', file);
        } else {
            formData.append('pdf_url', url);
        }
        
        // Show progress bar
        form.classList.add('hidden');
        progressContainer.classList.remove('hidden');
        
        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'process.php', true);
        
        // Track upload progress
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                updateProgress(percentComplete);
            }
        };
        
        // Handle response
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Show success message and file
                        progressContainer.classList.add('hidden');
                        resultsContainer.classList.remove('hidden');
                        displayFile(response.file);
                    } else {
                        // Show error message
                        alert('Error: ' + response.message);
                        resetForm();
                    }
                } catch (e) {
                    alert('An error occurred while processing the response');
                    resetForm();
                }
            } else {
                alert('An error occurred while processing your request');
                resetForm();
            }
        };
        
        // Handle errors
        xhr.onerror = function() {
            alert('An error occurred while processing your request');
            resetForm();
        };
        
        // Send the request
        xhr.send(formData);
    });
    
    // Update progress bar
    function updateProgress(percent) {
        progressFill.style.width = percent + '%';
        progressStatus.textContent = percent + '%';
    }
    
    // Reset form
    function resetForm() {
        form.reset();
        form.classList.remove('hidden');
        progressContainer.classList.add('hidden');
        progressFill.style.width = '0%';
        progressStatus.textContent = '0%';
    }
    
    // Display file in results
    function displayFile(fileData) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.id = 'file-' + fileData.id;
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        
        const fileName = document.createElement('div');
        fileName.className = 'file-name';
        fileName.textContent = fileData.name;
        
        const fileTimer = document.createElement('div');
        fileTimer.className = 'file-timer';
        fileTimer.textContent = 'Available for: 10:00';
        fileTimer.dataset.expiry = fileData.expiry;
        
        const downloadBtn = document.createElement('a');
        downloadBtn.className = 'download-btn';
        downloadBtn.href = fileData.download_url;
        downloadBtn.textContent = 'Download';
        downloadBtn.download = fileData.name;
        
        fileInfo.appendChild(fileName);
        fileInfo.appendChild(fileTimer);
        fileItem.appendChild(fileInfo);
        fileItem.appendChild(downloadBtn);
        
        fileList.appendChild(fileItem);
        
        // Start timer
        startTimer(fileTimer, fileData.id);
    }
    
    // Start countdown timer
    function startTimer(timerElement, fileId) {
        const expiryTime = parseInt(timerElement.dataset.expiry);
        
        const timerInterval = setInterval(function() {
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiryTime - now;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                const fileItem = document.getElementById('file-' + fileId);
                if (fileItem) {
                    fileItem.remove();
                }
                
                // If no files left, show form again
                if (fileList.children.length === 0) {
                    resultsContainer.classList.add('hidden');
                    form.classList.remove('hidden');
                }
            } else {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = 'Available for: ' + 
                    minutes.toString().padStart(2, '0') + ':' + 
                    seconds.toString().padStart(2, '0');
            }
        }, 1000);
    }
    
    // Check for existing files
    function checkExistingFiles() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', 'check_files.php', true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response.files.length > 0) {
                        resultsContainer.classList.remove('hidden');
                        form.classList.add('hidden');
                        
                        response.files.forEach(function(file) {
                            displayFile(file);
                        });
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            }
        };
        
        xhr.send();
    }
}); 