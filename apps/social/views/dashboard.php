<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="stats-grid">
    <div class="stat-card" onclick="window.location.href='/accounts'" style="cursor: pointer;">
        <h3>Active Accounts</h3>
        <div class="value"><?php echo $stats['active_accounts']; ?></div>
    </div>
    <div class="stat-card" onclick="window.location.href='/posts'" style="cursor: pointer;">
        <h3>Posts Sent Today</h3>
        <div class="value"><?php echo $stats['daily_posts']; ?></div>
    </div>
    <div class="stat-card" onclick="window.location.href='/targets'" style="cursor: pointer;">
        <h3>Replies Sent Today</h3>
        <div class="value"><?php echo $stats['daily_replies']; ?></div>
    </div>
</div>

<div class="card-section">
    <div class="section-header">
        <h2>Recent Activity</h2>
        <button class="btn btn-primary" onclick="window.location.reload()">Refresh</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Account</th>
                <th>Activity</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_activity)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-secondary);">No recent activity found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recent_activity as $activity): ?>
                    <tr>
                        <td title="<?php echo $activity['created_at']; ?>">
                            <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($activity['account_name'] ?? 'Unknown'); ?>
                        </td>
                        <td>
                            <?php
                            if ($activity['type'] === 'post') {
                                echo 'Generated Post: "' . substr(htmlspecialchars($activity['content']), 0, 30) . '..."';
                            } else {
                                echo 'Replied to User: "' . substr(htmlspecialchars($activity['reply_content']), 0, 30) . '..."';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($activity['status']); ?>">
                                <?php echo ucfirst($activity['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>