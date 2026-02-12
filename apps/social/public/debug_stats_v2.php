require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Models/Post.php';
require_once __DIR__ . '/../src/Models/Reply.php';

use App\Core\Database;

try {
$db = Database::getInstance();

// Check Posts created today
echo "--- POSTS CRATED TODAY ---\n";
$stmt = $db->query("SELECT id, created_at, status, account_id FROM posts WHERE DATE(created_at) = CURDATE()");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($posts);

// Check Replies created today
echo "\n--- REPLIES CREATED TODAY ---\n";
$stmt = $db->query("SELECT id, created_at, status, original_post_id FROM replies WHERE DATE(created_at) = CURDATE()");
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($replies);

echo "\n--- SERVER TIME ---\n";
echo date('Y-m-d H:i:s') . "\n";

echo "\n--- DB TIME ---\n";
$stmt = $db->query("SELECT NOW()");
echo $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
echo "Error: " . $e->getMessage() . "\n";
}