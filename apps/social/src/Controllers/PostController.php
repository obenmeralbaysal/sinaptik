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
}
