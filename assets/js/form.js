/**
 * SDO CTS - Form JavaScript
 * Handles form validation, file uploads, and signature functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    initRadioOptions();
    initFileUpload();
    initSignaturePad();
    initFormValidation();
});

/**
 * Initialize radio button styling and "Others" field toggle
 */
function initRadioOptions() {
    const radioOptions = document.querySelectorAll('.radio-option');
    const otherWrapper = document.getElementById('otherReferredWrapper');
    const otherInput = document.querySelector('input[name="referred_to_other"]');
    
    radioOptions.forEach(option => {
        const input = option.querySelector('input[type="radio"]');
        
        input.addEventListener('change', function() {
            // Update selected state
            radioOptions.forEach(opt => opt.classList.remove('selected'));
            if (this.checked) {
                option.classList.add('selected');
            }
            
            // Toggle "Others" input
            if (this.value === 'Others') {
                otherWrapper.classList.add('visible');
                otherInput.required = true;
                otherInput.focus();
            } else {
                otherWrapper.classList.remove('visible');
                otherInput.required = false;
                otherInput.value = '';
            }
        });
    });
}

/**
 * Initialize file upload with drag & drop
 */
function initFileUpload() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    
    if (!dropZone || !fileInput) return;
    
    let selectedFiles = [];
    const maxFileSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    
    // Click to upload
    dropZone.addEventListener('click', () => fileInput.click());
    
    // Drag & drop handlers
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
    
    // File input change
    fileInput.addEventListener('change', () => {
        handleFiles(fileInput.files);
    });
    
    function handleFiles(files) {
        Array.from(files).forEach(file => {
            // Validate file type
            if (!allowedTypes.includes(file.type)) {
                alert(`Invalid file type: ${file.name}. Only PDF, JPG, and PNG files are allowed.`);
                return;
            }
            
            // Validate file size
            if (file.size > maxFileSize) {
                alert(`File too large: ${file.name}. Maximum size is 10MB.`);
                return;
            }
            
            // Check for duplicates
            if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                return;
            }
            
            selectedFiles.push(file);
        });
        
        updateFileList();
        updateFileInput();
    }
    
    function updateFileList() {
        fileList.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            const icon = file.type === 'application/pdf' ? '📄' : '🖼️';
            const size = formatFileSize(file.size);
            
            fileItem.innerHTML = `
                <span class="file-name">${icon} ${file.name} <small>(${size})</small></span>
                <span class="remove-file" data-index="${index}">✕ Remove</span>
            `;
            
            fileList.appendChild(fileItem);
        });
        
        // Add remove handlers
        document.querySelectorAll('.remove-file').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                selectedFiles.splice(index, 1);
                updateFileList();
                updateFileInput();
            });
        });
    }
    
    function updateFileInput() {
        // Create a new DataTransfer to update the file input
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

/**
 * Initialize signature pad
 */
function initSignaturePad() {
    const tabs = document.querySelectorAll('.signature-tab');
    const typedDiv = document.getElementById('typedSignature');
    const digitalDiv = document.getElementById('digitalSignature');
    const signatureType = document.getElementById('signatureType');
    const signatureData = document.getElementById('signatureData');
    const canvas = document.getElementById('signaturePad');
    const clearBtn = document.getElementById('clearSignature');
    
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;
    
    // Set canvas size
    function resizeCanvas() {
        const wrapper = canvas.parentElement;
        canvas.width = wrapper.offsetWidth;
        canvas.height = 150;
        ctx.strokeStyle = '#1a1a2e';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }
    
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    
    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const type = this.dataset.type;
            signatureType.value = type;
            
            if (type === 'typed') {
                typedDiv.classList.add('active');
                digitalDiv.classList.remove('active');
            } else {
                typedDiv.classList.remove('active');
                digitalDiv.classList.add('active');
                resizeCanvas();
            }
        });
    });
    
    // Drawing functions
    function startDrawing(e) {
        isDrawing = true;
        const pos = getPosition(e);
        lastX = pos.x;
        lastY = pos.y;
    }
    
    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        
        const pos = getPosition(e);
        
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        
        lastX = pos.x;
        lastY = pos.y;
        
        // Update signature data
        signatureData.value = canvas.toDataURL('image/png');
    }
    
    function stopDrawing() {
        isDrawing = false;
    }
    
    function getPosition(e) {
        const rect = canvas.getBoundingClientRect();
        let x, y;
        
        if (e.touches) {
            x = e.touches[0].clientX - rect.left;
            y = e.touches[0].clientY - rect.top;
        } else {
            x = e.clientX - rect.left;
            y = e.clientY - rect.top;
        }
        
        return { x, y };
    }
    
    // Mouse events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Touch events
    canvas.addEventListener('touchstart', startDrawing);
    canvas.addEventListener('touchmove', draw);
    canvas.addEventListener('touchend', stopDrawing);
    
    // Clear button
    clearBtn.addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        signatureData.value = '';
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const form = document.getElementById('complaintForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const errors = [];
        
        // Clear previous errors
        document.querySelectorAll('.form-control.error').forEach(el => {
            el.classList.remove('error');
        });
        document.querySelectorAll('.error-message').forEach(el => {
            el.remove();
        });
        
        // Validate required fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                showError(field, 'This field is required');
            }
        });
        
        // Validate email
        const emailField = document.getElementById('complainant_email');
        if (emailField && emailField.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value)) {
                isValid = false;
                emailField.classList.add('error');
                showError(emailField, 'Please enter a valid email address');
            }
        }
        
        // Validate contact number
        const contactField = document.getElementById('complainant_contact');
        if (contactField && contactField.value) {
            const phoneRegex = /^[0-9]{10,11}$/;
            if (!phoneRegex.test(contactField.value.replace(/\D/g, ''))) {
                isValid = false;
                contactField.classList.add('error');
                showError(contactField, 'Please enter a valid 10-11 digit phone number');
            }
        }
        
        // Validate certification checkbox
        const certCheckbox = form.querySelector('input[name="certification_agreed"]');
        if (certCheckbox && !certCheckbox.checked) {
            isValid = false;
            showError(certCheckbox.parentElement.parentElement, 'You must agree to the certification');
        }
        
        // Validate signature
        const signatureType = document.getElementById('signatureType').value;
        if (signatureType === 'typed') {
            const typedSig = form.querySelector('input[name="typed_signature"]');
            if (!typedSig || !typedSig.value.trim()) {
                isValid = false;
                typedSig.classList.add('error');
                showError(typedSig, 'Please type your signature');
            }
        } else {
            const signatureData = document.getElementById('signatureData').value;
            if (!signatureData) {
                isValid = false;
                showError(document.getElementById('digitalSignature'), 'Please draw your signature');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = document.querySelector('.form-control.error, .error-message');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    // Remove error on input
    form.querySelectorAll('.form-control').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('error');
            const errorMsg = this.parentElement.querySelector('.error-message');
            if (errorMsg) errorMsg.remove();
        });
    });
}

function showError(element, message) {
    const existingError = element.parentElement.querySelector('.error-message');
    if (existingError) return;
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    if (element.parentElement) {
        element.parentElement.appendChild(errorDiv);
    }
}

/**
 * Reset form
 */
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('complaintForm').reset();
        
        // Clear file list
        const fileList = document.getElementById('fileList');
        if (fileList) fileList.innerHTML = '';
        
        // Clear signature
        const canvas = document.getElementById('signaturePad');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
        document.getElementById('signatureData').value = '';
        
        // Clear radio selections
        document.querySelectorAll('.radio-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Hide "Others" input
        document.getElementById('otherReferredWrapper').classList.remove('visible');
        
        // Clear errors
        document.querySelectorAll('.form-control.error').forEach(el => {
            el.classList.remove('error');
        });
        document.querySelectorAll('.error-message').forEach(el => {
            el.remove();
        });
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

