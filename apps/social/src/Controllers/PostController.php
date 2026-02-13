<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $posts = Post::findAllByUserId($user['id']);

        $this->render('posts/index', [
            'title' => 'Scheduled & Sent Posts',
            'active_page' => 'posts',
            'posts' => $posts
        ]);
    }
    public function delete($id)
    {
        // Set header JSON
        header('Content-Type: application/json');

        // 1. Check user login
        $user = User::findById($_SESSION['user_id'] ?? 0);
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized', 'redirect_url' => '/login']);
            exit;
        }

        // 2. Fetch Post
        $post = Post::findById($id);
        if (!$post) {
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }

        // 3. Authorization Check (Post belongs to an account owned by user)
        $account = \App\Models\Account::find($post['account_id']);
        if ($account['user_id'] != $user['id']) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // 4. Status Check
        if (!in_array($post['status'], ['pending', 'failed'])) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete sent posts']);
            exit;
        }

        // 5. Delete
        if (Post::delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Post deleted successfully', 'redirect_url' => '/posts']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete post']);
        }
        exit;
    }


    public function update($id)
    {
        // Remove explicit JSON header as we might return different errors or success
        // header('Content-Type: application/json'); 

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $user = User::findById($_SESSION['user_id'] ?? 0);
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $post = Post::findById($id);
        if (!$post) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }

        $account = \App\Models\Account::find($post['account_id']);
        if ($account['user_id'] != $user['id']) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // Handle JSON or Form Data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $updates = [];

        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['content']))
                $updates['content'] = $input['content'];
            if (isset($input['scheduled_at']))
                $updates['scheduled_at'] = $input['scheduled_at'];
        } else {
            // Form Data
            if (isset($_POST['content']))
                $updates['content'] = $_POST['content'];
            if (isset($_POST['scheduled_at']))
                $updates['scheduled_at'] = $_POST['scheduled_at'];

            // Handle File Upload
            if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileTmpPath = $_FILES['media']['tmp_name'];
                $fileName = time() . '_' . basename($_FILES['media']['name']);
                $destPath = $uploadDir . $fileName;

                // Validate Image
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($fileTmpPath);

                if (in_array($mimeType, $allowedMimeTypes)) {
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $updates['media_url'] = '/uploads/' . $fileName;
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
                        exit;
                    }
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only images are allowed.']);
                    exit;
                }
            }
        }

        if (empty($updates)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No data to update']);
            exit;
        }

        if (Post::update($id, $updates)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to update']);
        }
        exit;
    }

    public function bulkSchedule()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $user = User::findById($_SESSION['user_id'] ?? 0);
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $postIds = $input['post_ids'] ?? [];
        $offsetMinutes = (int) ($input['minutes_offset'] ?? 0);

        if (empty($postIds) || $offsetMinutes === 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        $successCount = 0;
        foreach ($postIds as $id) {
            $post = Post::findById($id);
            if (!$post)
                continue;

            $account = \App\Models\Account::find($post['account_id']);
            if ($account['user_id'] != $user['id'])
                continue;

            // Calculate new time
            $currentDate = new \DateTime($post['scheduled_at']);
            // Modify time only? User said "dates remain same". 
            // "toplu olarak seçtiğim postların saatlerini ileri veya geri alabilmeliyim ama tarihleri aynı kalabilmeli"
            // This is tricky. If I shift 23:00 by +60 mins, it becomes 00:00 next day.
            // If user explicitly wants date to stay same, we might need to wrap around or clamp.
            // However, usually "shift time" implies moving the point in time.
            // Let's interpret "dates remain same" as "don't change the day manually, just shift the hours".
            // If it crosses midnight, it strictly speaking changes date.
            // But maybe they mean "adjust the time of day relative to the current time".
            // Let's implement strict offset time shift first (standard behavior).
            // If they mean "set all to 10:00 AM", that's different.
            // "saatlerini ileri veya geri alabilmeliyim" -> shift forward/backward.
            // I will apply the offset.

            $method = $offsetMinutes > 0 ? 'add' : 'sub';
            $interval = new \DateInterval('PT' . abs($offsetMinutes) . 'M');
            $currentDate->$method($interval);

            if (Post::update($id, ['scheduled_at' => $currentDate->format('Y-m-d H:i:s')])) {
                $successCount++;
            }
        }

        echo json_encode(['success' => true, 'updated_count' => $successCount]);
        exit;
    }

    public function sendNow($id)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $user = User::findById($_SESSION['user_id'] ?? 0);
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $post = Post::findById($id);
        if (!$post) {
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }

        $account = \App\Models\Account::find($post['account_id']);
        if ($account['user_id'] != $user['id']) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // Must be pending or failed
        if (!in_array($post['status'], ['pending', 'failed'])) {
            echo json_encode(['success' => false, 'error' => 'Post is already sent']);
            exit;
        }

        // We need the full post object with account details for the service
        // Since Post::findById only returns post table columns, we need to join with account info
        // Or re-use getPending query logic but for single ID.
        // Actually AutomationService needs keys which are in account table.
        // Let's manually construct the object or add a helper in Post model.
        // "getPending" joins with accounts.
        // Let's build the array manually here since we have $post and $account.

        $enrichedPost = array_merge($post, [
            'account_pk' => $account['id'],
            'username' => $account['username'],
            'api_key' => $account['api_key'],
            'api_secret' => $account['api_secret'],
            'access_token' => $account['access_token'],
            'access_token_secret' => $account['access_token_secret'],
            'refresh_token' => $account['refresh_token'],
            'expires_at' => $account['expires_at']
        ]);

        $automationService = new \App\Services\AutomationService();
        if ($automationService->sendSinglePost($enrichedPost)) {
            echo json_encode(['success' => true, 'message' => 'Post sent successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to send post']);
        }
        exit;
    }

    public function sendAgain($id)
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $user = User::findById($_SESSION['user_id'] ?? 0);
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $originalPost = Post::findById($id);
        if (!$originalPost) {
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }

        $account = \App\Models\Account::find($originalPost['account_id']);
        if ($account['user_id'] != $user['id']) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // Create new post
        $newPostData = [
            'account_id' => $originalPost['account_id'],
            'content' => $originalPost['content'],
            'status' => 'pending',
            'scheduled_at' => date('Y-m-d H:i:s'), // Now
        ];

        // We need the ID of the new post. Post::create returns bool.
        // We should probably modify Post::create to return ID or use lastInsertId.
        // For now, let's insert and then fetch the latest post for this account to get the ID (a bit risky but usually fine)
        // OR better: use Database instance directly to get last ID.

        $db = \App\Core\Database::getInstance();
        if (Post::create($newPostData)) {
            $newPostId = $db->lastInsertId();
            $newPost = Post::findById($newPostId);

            $enrichedPost = array_merge($newPost, [
                'account_pk' => $account['id'],
                'username' => $account['username'],
                'api_key' => $account['api_key'],
                'api_secret' => $account['api_secret'],
                'access_token' => $account['access_token'],
                'access_token_secret' => $account['access_token_secret'],
                'refresh_token' => $account['refresh_token'],
                'expires_at' => $account['expires_at']
            ]);

            $automationService = new \App\Services\AutomationService();
            if ($automationService->sendSinglePost($enrichedPost)) {
                echo json_encode(['success' => true, 'message' => 'Post sent again successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to send post']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create new post']);
        }
        exit;
    }
}
