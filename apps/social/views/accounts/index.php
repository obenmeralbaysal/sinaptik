<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2>Your X Accounts</h2>
        <a href="/accounts/connect" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fab fa-twitter"></i> Connect X Account
        </a>
    </div>

    <?php if (empty($accounts)): ?>
        <div class="empty-state" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🐦</div>
            <h3>No accounts connected yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Connect your X account to start automating your social presence.</p>
            <a href="/accounts/connect" class="btn btn-primary">Connect Now</a>
        </div>
    <?php else: ?>
        <div class="accounts-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($accounts as $account): ?>
                <div class="account-card card" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem;">
                    <div class="account-avatar" style="width: 48px; height: 48px; background: var(--accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--accent-color);">
                        <i class="fab fa-twitter"></i>
                    </div>
                    <div class="account-info" style="flex: 1;">
                        <h4 style="margin: 0;"><?php echo htmlspecialchars($account['username']); ?></h4>
                        <div class="status-badge status-active" style="display: inline-block; margin-top: 0.25rem;">Connected</div>
                    </div>
                    <div class="account-actions">
                        <button class="btn btn-icon" title="Reconnect"><i class="fas fa-sync-alt"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="info-box" style="margin-top: 2rem; padding: 1.5rem; background: var(--card-bg); border-left: 4px solid var(--accent-color); border-radius: 8px;">
    <h4 style="margin-top: 0;"><i class="fas fa-shield-alt"></i> Security Information</h4>
    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0;">
        Sinapp Social uses official X OAuth 2.0 to connect your accounts. We never see your password, and you can revoke access at any time from your X account settings.
    </p>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
