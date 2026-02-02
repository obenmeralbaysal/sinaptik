<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Routine;
use App\Services\AIService;

class RoutineController extends Controller
{
    public function create()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $accounts = \App\Models\Account::allActiveForUser($user['id']);

        $this->render('routines/create', [
            'title' => 'Create Automation Routine',
            'active_page' => 'routines_create',
            'accounts' => $accounts
        ]);
    }

    public function generate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /routines/create');
            exit;
        }

        $criteria = [
            'language' => $_POST['language'],
            'mode' => $_POST['mode'],
            'persona' => $_POST['persona'],
            'topic' => $_POST['topic'],
            'account_id' => $_POST['account_id']
        ];
        
        $count = (int)$_POST['days'];
        // Limit max generation to avoid timeouts
        $count = min($count, 30); 

        $aiService = new AIService();
        $generatedPosts = $aiService->generateBatchPosts($criteria, $count);
        
        // If AI returned fewer posts than requested (error or token limit), pad it
        while (count($generatedPosts) < $count) {
            $generatedPosts[] = "Could not generate post. Please regenerate.";
        }

        $this->render('routines/preview', [
            'title' => 'Review Routine Plan',
            'active_page' => 'routines_create',
            'posts' => $generatedPosts,
            'input' => $_POST
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /routines/create');
            exit;
        }

        $user = User::findById($_SESSION['user_id']);
        
        // 1. Save Routine
        $promptSettings = json_encode([
            'language' => $_POST['language'],
            'mode' => $_POST['mode'],
            'persona' => $_POST['persona'],
            'topic' => $_POST['topic']
        ]);
        
        $scheduleSettings = json_encode([
            'days' => $_POST['days'],
            'timezone' => $_POST['timezone'],
            'time_start' => $_POST['time_start'],
            'time_end' => $_POST['time_end'],
            'is_random_time' => isset($_POST['is_random_time']),
            'account_id' => $_POST['account_id']
        ]);

        $routineId = Routine::create([
            'user_id' => $user['id'],
            'platform' => 'x',
            'name' => 'Routine: ' . substr($_POST['topic'], 0, 30) . '...',
            'prompt_settings' => $promptSettings,
            'schedule_settings' => $scheduleSettings,
            'status' => 'active'
        ]);

        // 2. Schedule Posts
        // Calculate dates
        $startDate = new \DateTime('now', new \DateTimeZone($_POST['timezone']));
        $startDate->modify('+1 day'); // Start tomorrow

        $postsContent = $_POST['posts']; // Array of edited content
        $accountId = (int)$_POST['account_id'];
        
        foreach ($postsContent as $index => $content) {
            $postDate = clone $startDate;
            $postDate->modify("+$index days");
            
            // Calculate time
            if (isset($_POST['is_random_time'])) {
                $start = strtotime($_POST['time_start']);
                $end = strtotime($_POST['time_end']);
                $timestamp = mt_rand($start, $end);
                $timeStr = date('H:i:s', $timestamp);
            } else {
                $timeStr = $_POST['time_start'] . ':00';
            }
            
            // Set time on the date object
            list($h, $m, $s) = explode(':', $timeStr);
            $postDate->setTime((int)$h, (int)$m, (int)$s);
            
            // Convert to UTC for storage usually, but let's stick to user timezone logic if app supports it.
            // Assuming system stores UTC, we convert:
            $postDate->setTimezone(new \DateTimeZone('UTC'));
            
            // Create pending post
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("INSERT INTO posts (account_id, routine_id, content, scheduled_at, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $accountId,
                $routineId,
                $content,
                $postDate->format('Y-m-d H:i:s')
            ]);
        }

        header('Location: /dashboard'); // Or routines index
    }

    public function regenerateSingle()
    {
        // Simple JSON response for AJAX
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $criteria = [
            'language' => $input['language'],
            'mode' => $input['mode'],
            'persona' => $input['persona'],
            'topic' => $input['topic']
        ];
        
        $aiService = new AIService();
        // Generate batch of 1
        $posts = $aiService->generateBatchPosts($criteria, 1);
        
        echo json_encode(['content' => $posts[0] ?? 'Error generating post']);
        exit;
    }
}
