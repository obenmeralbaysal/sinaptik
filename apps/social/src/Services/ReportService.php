<?php

namespace App\Services;

use App\Core\Database;
use App\Services\MailService;
use App\Core\Logger;

class ReportService
{
    /**
     * Send periodic reports to users based on their preference
     * 
     * @param string $frequency 'daily', 'weekly', 'monthly'
     */
    public function sendReports(string $frequency)
    {
        $db = Database::getInstance();

        // Find active users with the specified report frequency and a paid plan (plan_id IS NOT NULL)
        // Adjust the query based on exact business logic. Assuming 'users.plan_id' implies a paid plan if not null?
        // Or should we join with plans table to check price > 0?
        // For now, let's assume any plan_id means they are on a plan that supports reports if they selected a frequency.

        $stmt = $db->prepare("
            SELECT u.*, p.name as plan_name 
            FROM users u
            LEFT JOIN plans p ON u.plan_id = p.id
            WHERE u.report_frequency = :frequency 
            AND u.plan_id IS NOT NULL
        ");

        $stmt->execute(['frequency' => $frequency]);
        $users = $stmt->fetchAll();

        Logger::log("Found " . count($users) . " users for {$frequency} reports.");

        foreach ($users as $user) {
            $this->generateAndSendReport($user, $frequency);
        }
    }

    private function generateAndSendReport(array $user, string $frequency)
    {
        $stats = $this->getUserStats($user['id'], $frequency);

        $subject = ucfirst($frequency) . " Activity Report - Sinapp Social";

        $body = "<h1>Hello {$user['name']},</h1>";
        $body .= "<p>Here is your {$frequency} activity report for Sinapp Social.</p>";

        $body .= "<h2>Summary</h2>";
        $body .= "<ul>";
        $body .= "<li><strong>Posts Created:</strong> {$stats['posts_count']}</li>";
        $body .= "<li><strong>Posts Published:</strong> {$stats['posts_published']}</li>";
        $body .= "<li><strong>Posts Failed:</strong> {$stats['posts_failed']}</li>";
        $body .= "</ul>";

        $body .= "<p>Thank you for using Sinapp Social!</p>";

        MailService::send($user['email'], $subject, $body, 'text/html');

        Logger::log("Sent {$frequency} report to {$user['email']}");
    }

    private function getUserStats(int $userId, string $frequency): array
    {
        $db = Database::getInstance();

        $interval = match ($frequency) {
            'daily' => '1 DAY',
            'weekly' => '1 WEEK',
            'monthly' => '1 MONTH',
            default => '1 DAY'
        };

        // Get account IDs for this user
        $stmtAccounts = $db->prepare("SELECT id FROM accounts WHERE user_id = :user_id");
        $stmtAccounts->execute(['user_id' => $userId]);
        $accounts = $stmtAccounts->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($accounts)) {
            return [
                'posts_count' => 0,
                'posts_published' => 0,
                'posts_failed' => 0
            ];
        }

        $accountIds = implode(',', $accounts);

        // Count posts created in the last interval
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM posts 
            WHERE account_id IN ($accountIds) 
            AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
        ");

        $result = $stmt->fetch();

        return [
            'posts_count' => $result['total'] ?? 0,
            'posts_published' => $result['published'] ?? 0,
            'posts_failed' => $result['failed'] ?? 0
        ];
    }
}
