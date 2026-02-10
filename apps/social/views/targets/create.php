<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section" style="max-width: 600px; margin: 0 auto;">
    <div class="section-header">
        <h2>Add Target User to Monitor</h2>
    </div>

    <form action="/targets" method="POST">
        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Replying Account</label>
            <select name="account_id" required
                style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                <?php if (empty($accounts)): ?>
                    <option value="" disabled selected>No X accounts connected</option>
                <?php else: ?>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>">@<?php echo htmlspecialchars($account['username']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <?php if (empty($accounts)): ?>
                <small style="color: var(--error); display: block; margin-top: 0.5rem;">You need to connect an X account
                    first.</small>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Target Username
                (X/Twitter)</label>
            <input type="text" name="target_username" required placeholder="e.g. elonmusk"
                style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">AI Response Tone</label>
            <select name="ai_tone"
                style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                <option value="supportive">Supportive (Destekleyici)</option>
                <option value="ironic">Ironic (İronik)</option>
                <option value="critical">Critical (Eleştirel)</option>
            </select>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Custom Prompt /
                Instructions (Optional)</label>
            <textarea name="custom_prompt" placeholder="e.g. Speak like a pirate, or focus on technical details..."
                style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white; min-height: 100px; resize: vertical;"></textarea>
            <small style="color: var(--text-muted);">If provided, this overrides the standard tone prompt.</small>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Add Target</button>
            <a href="/targets" class="btn"
                style="background: var(--card-bg); color: white; text-decoration: none; text-align: center; flex: 1; padding: 0.75rem;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>