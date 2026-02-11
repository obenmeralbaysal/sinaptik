<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();
$sql = "DELETE FROM posts WHERE status = 'pending' AND (content LIKE '%Unable to generate response%' OR content LIKE 'Error%')";
$stmt = $db->prepare($sql);
$stmt->execute();
$count = $stmt->rowCount();

echo "Deleted $count invalid pending posts.\n";
