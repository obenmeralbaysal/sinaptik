<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Routine;
use App\Services\AIService;

class RoutineController extends Controller
{
    public function index()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $routines = Routine::findAllByUser($user['id']);

        $this->render('routines/index', [
            'title' => 'My Routines',
            'active_page' => 'routines_index',
            'routines' => $routines
        ]);
    }

    public function create()
    {
        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $accounts = \App\Models\Account::allActiveForUser($user['id'], 'x');

        $aiService = new AIService();
        $models = [];
        try {
            $models = $aiService->getAvailableModels();
        } catch (\Exception $e) {
            // If API key is missing or invalid, we might get an error here.
            // We can suppress it for now or let the view handle empty models.
            // $_SESSION['error'] = $e->getMessage();
        }

        $this->render('routines/create', [
            'title' => 'Create Automation Routine',
            'active_page' => 'routines_create',
            'accounts' => $accounts,
            'ai_models' => $models
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
            'model' => $_POST['model'] ?? 'gemini-1.5-flash',
            'account_id' => $_POST['account_id']
        ];

        $count = (int) $_POST['days'];
        // Limit max generation to avoid timeouts
        $count = min($count, 30);

        try {
            $aiService = new AIService();
            $generatedPosts = $aiService->generateBatchPosts($criteria, $count);
        } catch (\Exception $e) {
            // Handle missing API key or other errors
            $_SESSION['error'] = $e->getMessage();
            header('Location: /routines/create');
            exit;
        }

        // If AI returned fewer posts than requested (error or token limit), pad it
        while (count($generatedPosts) < $count) {
            $generatedPosts[] = "Could not generate post. Please regenerate.";
        }

        // Store in session for preview
        $_SESSION['routine_preview'] = [
            'posts' => $generatedPosts,
            'input' => $_POST
        ];

        // Return JSON redirect for AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->json([
                'success' => true,
                'redirect_url' => '/routines/preview'
            ]);
        }

        // Fallback
        header('Location: /routines/preview');
        exit;
    }

    public function preview()
    {
        if (empty($_SESSION['routine_preview'])) {
            header('Location: /routines/create');
            exit;
        }

        $data = $_SESSION['routine_preview'];

        $this->render('routines/preview', [
            'title' => 'Review Routine Plan',
            'active_page' => 'routines_create',
            'posts' => $data['posts'],
            'input' => $data['input']
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
            'topic' => $_POST['topic'],
            'model' => $_POST['model'] ?? 'gemini-1.5-flash'
        ]);

        $scheduleSettings = json_encode([
            'days' => $_POST['days'],
            'timezone' => $_POST['timezone'],
            'time_start' => $_POST['time_start'],
            'time_end' => $_POST['time_end'],
            'is_random_time' => isset($_POST['is_random_time']),
            'account_id' => $_POST['account_id']
        ]);

        $account = \App\Models\Account::find((int) $_POST['account_id']);
        $platform = $account['platform'] ?? 'x';

        $routineId = Routine::create([
            'user_id' => $user['id'],
            'platform' => $platform,
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
        $accountId = (int) $_POST['account_id'];

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
            $postDate->setTime((int) $h, (int) $m, (int) $s);

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

        // Return JSON success
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->json([
                'success' => true,
                'message' => 'Routine created and scheduled successfully!',
                'redirect_url' => '/dashboard'
            ]);
        }

        header('Location: /dashboard');
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
            'topic' => $input['topic'],
            'model' => $input['model'] ?? 'gemini-1.5-flash'
        ];

        try {
            $aiService = new AIService();
            // Generate batch of 1
            $posts = $aiService->generateBatchPosts($criteria, 1);
            echo json_encode(['content' => $posts[0] ?? 'Error generating post']);
        } catch (\Exception $e) {
            echo json_encode(['content' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
