<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Active Accounts</h3>
        <div class="value"><?php echo $stats['active_accounts']; ?></div>
    </div>
    <div class="stat-card">
        <h3>Daily Posts</h3>
        <div class="value"><?php echo $stats['daily_posts']; ?></div>
    </div>
    <div class="stat-card">
        <h3>Pending Replies</h3>
        <div class="value"><?php echo $stats['pending_replies']; ?></div>
    </div>
</div>

<div class="card-section">
    <div class="section-header">
        <h2>Recent Activity</h2>
        <button class="btn btn-primary">Refresh</button>
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
            <tr>
                <td colspan="4" style="text-align: center; color: var(--text-secondary);">No recent activity found.</td>
            </tr>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
