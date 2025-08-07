<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

$title = $content = $category_id = "";
$title_err = $content_err = $category_err = $image_err = "";
$success_message = "";

// Get categories for dropdown
$categories = [];
$sql = "SELECT id, name FROM categories ORDER BY name";
if($result = mysqli_query($conn, $sql)){
    while($row = mysqli_fetch_assoc($result)){
        $categories[] = $row;
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title.";
    } else{
        $title = trim($_POST["title"]);
    }
    
    // Validate content
    if(empty(trim($_POST["content"]))){
        $content_err = "Please enter your question.";
    } else{
        $content = trim($_POST["content"]);
    }
    
    // Validate category
    if(empty($_POST["category_id"])){
        $category_err = "Please select a category.";
    } else{
        $category_id = $_POST["category_id"];
    }
    
    // Handle image upload
    $image_path = null;
    if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if(!in_array($_FILES["image"]["type"], $allowed_types)){
            $image_err = "Only JPG, PNG and GIF images are allowed.";
        } elseif($_FILES["image"]["size"] > $max_size){
            $image_err = "Image size should not exceed 5MB.";
        } else{
            $upload_dir = "uploads/questions/";
            if(!file_exists($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $file_name = uniqid() . "." . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)){
                $image_path = $target_path;
            } else{
                $image_err = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // Check input errors before inserting in database
    if(empty($title_err) && empty($content_err) && empty($category_err) && empty($image_err)){
        $sql = "INSERT INTO questions (user_id, category_id, title, content, image_path) VALUES (?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "iisss", $param_user_id, $param_category_id, $param_title, $param_content, $param_image_path);
            
            $param_user_id = $_SESSION["id"];
            $param_category_id = $category_id;
            $param_title = $title;
            $param_content = $content;
            $param_image_path = $image_path;
            
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Your question has been posted successfully!";
                // Clear form data
                $title = $content = $category_id = "";
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}

$page_title = "Ask a Question";
ob_start();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
          

        <?php if(!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
        <?php endif; ?>
        
            <!-- <div class="card mb-4 border-info">
                <div class="card-body py-4">
                    <h5 class="card-title text-info mb-2">Writing a good question</h5>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-1">The community is here to help you with specific coding, algorithm, or language problems.</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-1">✓ Summarize the problem</li>
                                <li class="mb-1">✓ Describe what you've tried</li>
                                <li class="mb-0">✓ Show some code</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-1">Avoid asking...</h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-1">✗ Questions that are too broad</li>
                                <li class="mb-1">✗ Questions that are opinion-based</li>
                                <li class="mb-0">✗ Questions that are unclear</li>
            </ul>
        </div>
                    </div>
                </div>
            </div> -->
        
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="card mb-4">
                    <div class="card-body">
                        <label class="form-label fw-bold">Title</label>
                        <div class="form-text mb-2">Be specific and imagine you're asking a question to another person</div>
                        <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>" placeholder="e.g. How to implement a responsive navigation bar?" required>
                        <span class="invalid-feedback"><?php echo $title_err; ?></span>

                        <label class="form-label fw-bold mt-3">Category</label>
                        <div class="form-text mb-2">Choose the category that best fits your question</div>
                        <select name="category_id" class="form-select <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>" required>
                            <option value="">Select a category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $category_err; ?></span>

                        <label class="form-label fw-bold mt-3">Body</label>
                        <div class="form-text mb-2">Include all the information someone would need to answer your question</div>
                        <textarea name="content" class="form-control <?php echo (!empty($content_err)) ? 'is-invalid' : ''; ?>" rows="10" placeholder="Describe your problem in detail. What have you tried? What are you trying to achieve?" required></textarea>
                        <span class="invalid-feedback"><?php echo $content_err; ?></span>

                        <label class="form-label fw-bold mt-3">
                            <i class="fas fa-paperclip me-2"></i>Attachments
                        </label>
                        <div class="form-text mb-2">Add files to help explain your question (optional)</div>
                        <div class="upload-area p-4 border rounded-3 text-center mb-2" id="dropZone">
                            <input type="file" name="image" id="fileInput" class="d-none" accept="image/*">
                            <div class="upload-icon mb-2">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                            </div>
                            <h6 class="mb-1">
                                <i class="fas fa-arrow-down me-1 text-primary"></i>
                                Drag & Drop your files here
                            </h6>
                            <p class="text-muted small mb-2">or</p>
                            <div class="supported-formats mt-3">
                                <div class="row g-2 justify-content-center">
                                    <div class="col-auto">
                                        <div class="file-type-option p-2 border rounded-3 text-center" style="width: 80px; cursor: pointer;" onclick="triggerFileInput('image/*')">
                                            <i class="fas fa-file-image fa-2x text-primary mb-1"></i>
                                            <div class="small">Image</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="file-type-option p-2 border rounded-3 text-center" style="width: 80px; cursor: pointer;" onclick="triggerFileInput('.pdf')">
                                            <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                                            <div class="small">PDF</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="file-type-option p-2 border rounded-3 text-center" style="width: 80px; cursor: pointer;" onclick="triggerFileInput('.doc,.docx')">
                                            <i class="fas fa-file-word fa-2x text-primary mb-1"></i>
                                            <div class="small">Word</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="file-type-option p-2 border rounded-3 text-center" style="width: 80px; cursor: pointer;" onclick="triggerFileInput('.txt')">
                                            <i class="fas fa-file-alt fa-2x text-secondary mb-1"></i>
                                            <div class="small">Text</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="file-type-option p-2 border rounded-3 text-center" style="width: 80px; cursor: pointer;" onclick="triggerFileInput('.zip')">
                                            <i class="fas fa-file-archive fa-2x text-warning mb-1"></i>
                                            <div class="small">ZIP</div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="file-type-option p-2 border rounded-3 text-center" style="width: 80px; cursor: pointer;" onclick="showCodeEditor()">
                                            <i class="fas fa-code fa-2x text-success mb-1"></i>
                                            <div class="small">Code</div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted small mt-2 mb-0">
                                    <i class="fas fa-info-circle me-1"></i>Max file size: 5MB
                                </p>
                            </div>
                        </div>
                        <div id="codeEditorArea" class="d-none">
                            <div class="card">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <select id="languageSelect" class="form-select form-select-sm me-2" style="width: auto;">
                                            <option value="javascript">JavaScript</option>
                                            <option value="typescript">TypeScript</option>
                                            <option value="php">PHP</option>
                                            <option value="python">Python</option>
                                            <option value="java">Java</option>
                                            <option value="kotlin">Kotlin</option>
                                            <option value="swift">Swift</option>
                                            <option value="dart">Flutter/Dart</option>
                                            <option value="cpp">C++</option>
                                            <option value="csharp">C#</option>
                                            <option value="go">Go</option>
                                            <option value="rust">Rust</option>
                                            <option value="ruby">Ruby</option>
                                            <option value="html">HTML</option>
                                            <option value="css">CSS</option>
                                            <option value="scss">SCSS</option>
                                            <option value="sql">SQL</option>
                                            <option value="yaml">YAML</option>
                                            <option value="json">JSON</option>
                                            <option value="markdown">Markdown</option>
                                        </select>
                                        <span class="text-muted small" id="editorFileName">untitled.js</span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="copyEditorCode()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleEditor()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <textarea id="codeEditor"></textarea>
                                </div>
                            </div>
                        </div>
                        <div id="previewArea" class="d-none">
                            <div class="selected-file p-2 border rounded-3 d-flex align-items-center">
                                <div class="file-icon me-2">
                                    <i class="fas fa-file fa-2x text-primary"></i>
                                </div>
                                <div class="file-info flex-grow-1">
                                    <p class="mb-0 small" id="fileName">
                                        <span></span>
                                    </p>
                                    <p class="mb-0 text-muted small" id="fileSize">
                                        <i class="fas fa-weight me-1"></i>
                                        <span></span>
                                    </p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div id="codePreview" class="mt-2 d-none">
                                <div class="card">
                                    <div class="card-header py-1 d-flex justify-content-between align-items-center">
                                        <span class="small" id="codeFileName"></span>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyCode()">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <pre class="mb-0"><code id="codeContent"></code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="invalid-feedback"><?php echo $image_err; ?></span>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Post Question
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Prism.js for syntax highlighting -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-java.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-cpp.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-csharp.min.js"></script>

<!-- Add CodeMirror CSS and JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/dart/dart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/ruby/ruby.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/go/go.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/rust/rust.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/yaml/yaml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/markdown/markdown.min.js"></script>

<script>
// Add Bootstrap form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// File upload handling
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const previewArea = document.getElementById('previewArea');
const fileName = document.getElementById('fileName').querySelector('span');
const fileSize = document.getElementById('fileSize').querySelector('span');
const fileIcon = previewArea.querySelector('.file-icon i');
const codePreview = document.getElementById('codePreview');
const codeFileName = document.getElementById('codeFileName');
const codeContent = document.getElementById('codeContent');

function triggerFileInput(acceptType) {
    fileInput.accept = acceptType;
    fileInput.click();
}

function copyCode() {
    const code = codeContent.textContent;
    navigator.clipboard.writeText(code).then(() => {
        alert('Code copied to clipboard!');
    });
}

// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
});

// Highlight drop zone when item is dragged over it
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

// Handle dropped files
dropZone.addEventListener('drop', handleDrop, false);
fileInput.addEventListener('change', handleFiles, false);

function preventDefaults (e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight(e) {
    dropZone.classList.add('bg-light');
}

function unhighlight(e) {
    dropZone.classList.remove('bg-light');
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles({ target: { files: files } });
}

function handleFiles(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('File size should not exceed 5MB');
            return;
        }
        
        // Set appropriate icon based on file type
        const fileType = file.type.split('/')[0];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        fileIcon.className = 'fas fa-2x';
        if (fileType === 'image') {
            fileIcon.className += ' fa-file-image text-primary';
            codePreview.classList.add('d-none');
        } else if (fileExtension === 'pdf') {
            fileIcon.className += ' fa-file-pdf text-danger';
            codePreview.classList.add('d-none');
        } else if (['doc', 'docx'].includes(fileExtension)) {
            fileIcon.className += ' fa-file-word text-primary';
            codePreview.classList.add('d-none');
        } else if (fileExtension === 'txt') {
            fileIcon.className += ' fa-file-alt text-secondary';
            codePreview.classList.add('d-none');
        } else if (fileExtension === 'zip') {
            fileIcon.className += ' fa-file-archive text-warning';
            codePreview.classList.add('d-none');
        } else if (['php', 'js', 'html', 'css', 'py', 'java', 'cpp', 'cs'].includes(fileExtension)) {
            fileIcon.className += ' fa-code text-success';
            // Handle code file preview
            const reader = new FileReader();
            reader.onload = function(e) {
                codeFileName.textContent = file.name;
                codeContent.textContent = e.target.result;
                codeContent.className = `language-${fileExtension}`;
                Prism.highlightElement(codeContent);
                codePreview.classList.remove('d-none');
            }
            reader.readAsText(file);
        } else {
            fileIcon.className += ' fa-file text-primary';
            codePreview.classList.add('d-none');
        }
        
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        previewArea.classList.remove('d-none');
        dropZone.classList.add('d-none');
    }
}

function removeFile() {
    fileInput.value = '';
    previewArea.classList.add('d-none');
    dropZone.classList.remove('d-none');
    codePreview.classList.add('d-none');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

let codeEditor;
let currentLanguage = 'javascript';

// Initialize CodeMirror
document.addEventListener('DOMContentLoaded', function() {
    codeEditor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
        mode: 'javascript',
        theme: 'monokai',
        lineNumbers: true,
        autoCloseBrackets: true,
        matchBrackets: true,
        indentUnit: 4,
        tabSize: 4,
        lineWrapping: true,
        extraKeys: {"Ctrl-Space": "autocomplete"},
        height: '300px'
    });
});

function showCodeEditor() {
    document.getElementById('dropZone').classList.add('d-none');
    document.getElementById('codeEditorArea').classList.remove('d-none');
    document.getElementById('previewArea').classList.add('d-none');
    codeEditor.refresh();
}

function toggleEditor() {
    document.getElementById('dropZone').classList.remove('d-none');
    document.getElementById('codeEditorArea').classList.add('d-none');
    document.getElementById('previewArea').classList.add('d-none');
}

function copyEditorCode() {
    const code = codeEditor.getValue();
    navigator.clipboard.writeText(code).then(() => {
        alert('Code copied to clipboard!');
    });
}

// Handle language change
document.getElementById('languageSelect').addEventListener('change', function(e) {
    currentLanguage = e.target.value;
    const fileName = `untitled.${getFileExtension(currentLanguage)}`;
    document.getElementById('editorFileName').textContent = fileName;
    
    let mode;
    switch(currentLanguage) {
        case 'javascript':
            mode = 'javascript';
            break;
        case 'typescript':
            mode = 'text/typescript';
            break;
        case 'php':
            mode = 'php';
            break;
        case 'python':
            mode = 'python';
            break;
        case 'java':
        case 'kotlin':
            mode = 'text/x-java';
            break;
        case 'swift':
            mode = 'text/x-swift';
            break;
        case 'dart':
            mode = 'dart';
            break;
        case 'cpp':
            mode = 'text/x-c++src';
            break;
        case 'csharp':
            mode = 'text/x-csharp';
            break;
        case 'go':
            mode = 'go';
            break;
        case 'rust':
            mode = 'rust';
            break;
        case 'ruby':
            mode = 'ruby';
            break;
        case 'html':
            mode = 'text/html';
            break;
        case 'css':
            mode = 'css';
            break;
        case 'scss':
            mode = 'text/x-scss';
            break;
        case 'sql':
            mode = 'text/x-sql';
            break;
        case 'yaml':
            mode = 'yaml';
            break;
        case 'json':
            mode = 'application/json';
            break;
        case 'markdown':
            mode = 'markdown';
            break;
        default:
            mode = 'javascript';
    }
    
    codeEditor.setOption('mode', mode);
});

function getFileExtension(language) {
    switch(language) {
        case 'javascript': return 'js';
        case 'typescript': return 'ts';
        case 'php': return 'php';
        case 'python': return 'py';
        case 'java': return 'java';
        case 'kotlin': return 'kt';
        case 'swift': return 'swift';
        case 'dart': return 'dart';
        case 'cpp': return 'cpp';
        case 'csharp': return 'cs';
        case 'go': return 'go';
        case 'rust': return 'rs';
        case 'ruby': return 'rb';
        case 'html': return 'html';
        case 'css': return 'css';
        case 'scss': return 'scss';
        case 'sql': return 'sql';
        case 'yaml': return 'yml';
        case 'json': return 'json';
        case 'markdown': return 'md';
        default: return 'txt';
    }
}
</script>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?> 