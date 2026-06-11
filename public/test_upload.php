<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['test'] ?? null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $dest = __DIR__ . '/uploads/' . $file['name'];
        move_uploaded_file($file['tmp_name'], $dest);
        echo "OK: " . $file['name'] . " -> " . $dest . " size: " . $file['size'];
    } elseif ($file) {
        echo "ERROR: " . $file['error'];
    } else {
        echo "ERROR: no file";
    }
} else {
    echo '<form method="post" enctype="multipart/form-data"><input type="file" name="test"><button>Upload</button></form>';
}
