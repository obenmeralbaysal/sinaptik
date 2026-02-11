<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
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
        // 1. Check user login
        $user = User::findById($_SESSION['user_id'] ?? 0);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        // 2. Fetch Post
        $post = Post::findById($id);
        if (!$post) {
            // Flash message: Post not found
            header('Location: /posts?error=Post not found');
            exit;
        }

        // 3. Authorization Check (Post belongs to an account owned by user)
        $account = \App\Models\Account::find($post['account_id']);
        if ($account['user_id'] != $user['id']) {
            // Flash message: Unauthorized
            header('Location: /posts?error=Unauthorized');
            exit;
        }

        // 4. Status Check
        if (!in_array($post['status'], ['pending', 'failed'])) {
            header('Location: /posts?error=Cannot delete sent posts');
            exit;
        }

        // 5. Delete
        if (Post::delete($id)) {
            header('Location: /posts?success=Post deleted successfully');
        } else {
            header('Location: /posts?error=Failed to delete post');
        }
        exit;
    }
}
