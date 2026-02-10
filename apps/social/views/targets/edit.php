<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2>Edit Target User</h2>
        <a href="/targets" class="btn btn-outline">Back to List</a>
    </div>

    <form action="/targets/update/<?php echo $target['id']; ?>" method="POST" style="max-width: 600px;">
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Target Username (Them)</label>
            <div
                style="display: flex; align-items: center; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem 1rem;">
                <span style="color: var(--text-muted); margin-right: 0.5rem;">@</span>
                <input type="text" name="target_username" class="form-control"
                    style="border: none; padding: 0; background: transparent;" placeholder="elonmusk" required
                    value="<?php echo htmlspecialchars($target['target_username']); ?>">
            </div>
            <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">The X user you want to
                automatically reply to.</small>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Your Account (Replier)</label>
            <select name="account_id" class="form-control">
                <option value="">All Active Accounts</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?php echo $account['id']; ?>" <?php echo ($target['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                        @
                        <?php echo htmlspecialchars($account['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">Which of your accounts should
                reply to this target. Leave empty for all.</small>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">AI Tone</label>
            <select name="ai_tone" class="form-control">
                <option value="supportive" <?php echo ($target['ai_tone'] == 'supportive') ? 'selected' : ''; ?>
                    >Supportive</option>
                <option value="professional" <?php echo ($target['ai_tone'] == 'professional') ? 'selected' : ''; ?>
                    >Professional</option>
                <option value="witty" <?php echo ($target['ai_tone'] == 'witty') ? 'selected' : ''; ?>>Witty / Funny
                </option>
                <option value="controversial" <?php echo ($target['ai_tone'] == 'controversial') ? 'selected' : ''; ?>
                    >Controversial / Debate</option>
                <option value="questioning" <?php echo ($target['ai_tone'] == 'questioning') ? 'selected' : ''; ?>
                    >Questioning</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 2rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Custom Instructions
                (Optional)</label>
            <textarea name="custom_prompt" class="form-control" rows="3"
                placeholder="E.g., Always mention our product 'Sinapp' in a subtle way."><?php echo htmlspecialchars($target['custom_prompt'] ?? ''); ?></textarea>
            <small style="color: var(--text-muted);">Specific instructions for the AI when generating replies for this
                target.</small>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="/targets" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Target</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>