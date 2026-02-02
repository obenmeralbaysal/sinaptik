<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="header">
    <div class="page-title">
        <h1><i class="fas fa-server" style="color: var(--accent-color);"></i> System Status</h1>
        <p style="color: var(--text-secondary);">Real-time connectivity and quota checks for external APIs.</p>
    </div>
</div>

<div class="stats-grid">
    <!-- X API Status -->
    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <h3>X (Twitter) API</h3>
            <span class="status-badge <?php echo $x_status['status'] === 'online' ? 'status-active' : 'status-paused'; ?>">
                <?php echo strtoupper($x_status['status']); ?>
            </span>
        </div>
        
        <?php if ($x_status['status'] === 'online'): ?>
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Rate Limit (User Context)</div>
                <div class="value" style="font-size: 1.5rem;">
                    <?php echo $x_status['remaining']; ?> <span style="font-size: 1rem; color: var(--text-secondary);">/ <?php echo $x_status['limit']; ?></span>
                </div>
            </div>
            
            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                <i class="fas fa-clock"></i> Resets at: <?php echo $x_status['reset']; ?>
            </div>
        <?php else: ?>
            <div style="color: var(--error);">
                <i class="fas fa-exclamation-circle"></i> Connection Failed
                <div style="font-size: 0.85rem; margin-top: 0.5rem;"><?php echo $x_status['message'] ?? 'No active token found for admin.'; ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- AI Engine Status -->
    <div class="stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <h3>AI Engine (<?php echo ucfirst($ai_status['provider']); ?>)</h3>
            <span class="status-badge <?php echo $ai_status['status'] === 'online' ? 'status-active' : 'status-paused'; ?>">
                <?php echo strtoupper($ai_status['status']); ?>
            </span>
        </div>

        <?php if ($ai_status['status'] === 'online'): ?>
            <div style="margin-bottom: 1rem;">
                <div class="value" style="font-size: 1.5rem; color: var(--success);">Operational</div>
            </div>
            <p style="font-size: 0.9rem; color: var(--text-secondary);">
                Test generation was successful. The AI operational status is normal.
            </p>
        <?php else: ?>
            <div style="color: var(--error);">
                <i class="fas fa-exclamation-circle"></i> Service Unreachable
                <div style="font-size: 0.85rem; margin-top: 0.5rem;"><?php echo $ai_status['message'] ?? 'Check API keys in .env'; ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
