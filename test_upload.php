<?php
session_start();

// Test upload directory
$uploadsDir = __DIR__ . '/uploads';
echo "Uploads directory: " . $uploadsDir . "<br>";
echo "Directory exists: " . (is_dir($uploadsDir) ? "Yes" : "No") . "<br>";
echo "Directory writable: " . (is_writable($uploadsDir) ? "Yes" : "No") . "<br>";
echo "Directory permissions: " . substr(sprintf('%o', fileperms($uploadsDir)), -4) . "<br><br>";

// Test PHP upload settings
echo "PHP Upload Settings:<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? "Enabled" : "Disabled") . "<br><br>";

// Test file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['testFile'])) {
    $file = $_FILES['testFile'];
    echo "File upload test:<br>";
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . "<br>";
    echo "File type: " . $file['type'] . "<br>";
    echo "File error: " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = 'test_' . time() . '.txt';
        $uploadPath = $uploadsDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo "Upload successful: " . $filename . "<br>";
            chmod($uploadPath, 0644);
        } else {
            echo "Upload failed!<br>";
            $error = error_get_last();
            if ($error) {
                echo "Error: " . $error['message'] . "<br>";
            }
        }
    } else {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        echo "Upload error: " . ($errors[$file['error']] ?? 'Unknown error') . "<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
</head>
<body>
    <h1>Upload Test</h1>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="testFile" required>
        <button type="submit">Test Upload</button>
    </form>
</body>
</html>
