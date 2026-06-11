<?php
$pdo = new PDO('sqlite:C:\Users\Pau\Desktop\Empreses\voracms-demo\var\voracms.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== ENTRIES ===\n";
$stmt = $pdo->query("SELECT id, content_type_id, status, locale, created_at FROM entries ORDER BY id");
foreach ($stmt as $row) {
    echo $row['id'] . ' | CT: ' . $row['content_type_id'] . ' | ' . $row['status'] . ' | ' . $row['locale'] . ' | ' . $row['created_at'] . "\n";
}

echo "\n=== FIELD VALUES WITH __upload__ ===\n";
$stmt = $pdo->query("SELECT entry_id, value, field_definition_id FROM field_values WHERE value='__upload__'");
foreach ($stmt as $row) {
    echo 'Entry: ' . $row['entry_id'] . ' | FD: ' . $row['field_definition_id'] . ' | Value: ' . $row['value'] . "\n";
}

echo "\n=== MEDIA TABLE ===\n";
$stmt = $pdo->query("SELECT id, filename, path, file_size FROM media ORDER BY id");
foreach ($stmt as $row) {
    echo 'ID: ' . $row['id'] . ' | File: ' . $row['filename'] . ' | Path: ' . $row['path'] . ' | Size: ' . $row['file_size'] . "\n";
}

echo "\n=== UPLOADS DIR ===\n";
$files = scandir('C:\Users\Pau\Desktop\Empreses\voracms-demo\public\uploads');
foreach ($files as $f) {
    if ($f !== '.' && $f !== '..') {
        echo '  ' . $f . "\n";
    }
}
