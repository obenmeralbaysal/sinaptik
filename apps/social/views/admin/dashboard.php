<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Users</h3>
        <div class="value"><?php echo $stats['total_users']; ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Active Plans</h3>
        <div class="value"><?php echo $stats['total_plans']; ?></div>
    </div>
    <div class="stat-card">
        <h3>Social Accounts</h3>
        <div class="value"><?php echo $stats['total_accounts']; ?></div>
    </div>
    <div class="stat-card" style="cursor: pointer; border: 1px solid var(--accent-color);" onclick="window.location.href='/admin/status'">
        <h3 style="color: var(--accent-color);">System Health</h3>
        <div class="value" style="font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem; color: var(--accent-color);">
            <i class="fas fa-heartbeat"></i> Check Status
        </div>
    </div>
</div>

<div class="card-section" style="margin-top: 2rem;">
    <div class="section-header">
        <h2>Admin Quick Links</h2>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <a href="/admin/plans" class="btn btn-primary" style="text-align: center; text-decoration: none;">Manage Plans</a>
        <a href="/admin/users" class="btn btn-primary" style="text-align: center; text-decoration: none;">Manage Users</a>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
