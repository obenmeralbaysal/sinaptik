<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2>Your X Accounts</h2>
        <div style="display: flex; gap: 0.5rem;">
            <a href="/accounts/connect" class="btn btn-primary"
                style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fab fa-twitter"></i> Connect X Account
            </a>
            <a href="/accounts/connect?force=1" class="btn btn-outline"
                style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-random"></i> Switch Account
            </a>
        </div>
    </div>

    <?php if (empty($accounts)): ?>
        <div class="empty-state" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🐦</div>
            <h3>No accounts connected yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Connect your X account to start automating your social
                presence.</p>
            <a href="/accounts/connect" class="btn btn-primary">Connect Now</a>
        </div>
    <?php else: ?>
        <div class="accounts-grid"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($accounts as $account): ?>
                <div class="account-card card" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem;">
                    <div class="account-avatar"
                        style="width: 48px; height: 48px; background: var(--accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--accent-color);">
                        <i class="fab fa-twitter"></i>
                    </div>
                    <div class="account-info" style="flex: 1;">
                        <h4 style="margin: 0;"><?php echo htmlspecialchars($account['username']); ?></h4>
                        <div class="status-badge status-active" style="display: inline-block; margin-top: 0.25rem;">Connected
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                            <i class="fas fa-robot"></i>
                            Reply every <?php echo $account['reply_frequency_minutes'] ?? 10; ?>m
                            (Max <?php echo $account['daily_reply_limit'] ?? 10; ?>/day)
                        </div>
                    </div>
                    <div class="account-actions" style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-icon"
                            onclick="openSettingsModal(<?php echo htmlspecialchars(json_encode($account)); ?>)"
                            title="Settings">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button class="btn btn-icon" title="Reconnect"><i class="fas fa-sync-alt"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="modal"
    style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content"
        style="background-color: var(--card-bg); margin: 10% auto; padding: 2rem; border: 1px solid var(--border-color); width: 90%; max-width: 500px; border-radius: 12px; position: relative;">
        <span onclick="closeSettingsModal()"
            style="position: absolute; right: 1.5rem; top: 1rem; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</span>

        <h3 style="margin-top: 0; margin-bottom: 1.5rem;">Automation Settings</h3>

        <form action="/accounts/update-settings" method="POST">
            <input type="hidden" name="account_id" id="modal_account_id">

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Reply Frequency (Minutes)</label>
                <input type="number" name="reply_frequency_minutes" id="modal_frequency" class="form-control" min="1"
                    required>
                <small style="color: var(--text-muted);">How often to check for new tweets to reply to.</small>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Daily Reply Limit</label>
                <input type="number" name="daily_reply_limit" id="modal_limit" class="form-control" min="1" required>
                <small style="color: var(--text-muted);">Maximum number of AI replies to post per day.</small>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem;">AI Model</label>
                <select name="ai_model" id="modal_model" class="form-control">
                    <?php if (isset($availableModels)): ?>
                        <?php foreach ($availableModels as $model): ?>
                            <option value="<?php echo htmlspecialchars($model); ?>"><?php echo htmlspecialchars($model); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="gemini-1.5-flash">Gemini 1.5 Flash (Fast)</option>
                        <option value="gemini-pro">Gemini Pro (Capable)</option>
                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                        <option value="gpt-4">GPT-4 (Smartest)</option>
                    <?php endif; ?>
                </select>
                <small style="color: var(--text-muted);">Select the AI model used for generating replies.</small>
            </div>

            <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem;">
                <h4 style="margin-top: 0; margin-bottom: 1rem;">Preview Reply</h4>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Test Content (Mock Tweet)</label>
                    <textarea id="preview_content" class="form-control" rows="2"
                        style="font-size: 0.9rem;">Just launched a new feature for our social media automation tool! #SaaS #Growth</textarea>
                </div>
                <div style="margin-bottom: 1rem;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="generatePreview()">Generate
                        Preview</button>
                    <span id="preview_loading"
                        style="display: none; margin-left: 0.5rem; color: var(--text-muted);">Generating...</span>
                </div>
                <div id="preview_result"
                    style="background: var(--bg-color); padding: 1rem; border-radius: 8px; font-size: 0.9rem; display: none;">
                    <strong>AI Reply:</strong>
                    <p style="margin: 0.5rem 0 0 0; color: var(--text-primary);" id="preview_text"></p>
                </div>
            </div>

            <div style="text-align: right; margin-top: 2rem;">
                <button type="button" class="btn btn-outline" onclick="closeSettingsModal()"
                    style="margin-right: 0.5rem;">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSettingsModal(account) {
        document.getElementById('modal_account_id').value = account.id;
        document.getElementById('modal_frequency').value = account.reply_frequency_minutes || 10;
        document.getElementById('modal_limit').value = account.daily_reply_limit || 10;
        document.getElementById('modal_model').value = account.ai_model || 'gemini-1.5-flash';
        document.getElementById('settingsModal').style.display = 'block';

        // Reset preview
        document.getElementById('preview_result').style.display = 'none';
        document.getElementById('preview_text').innerText = '';
    }

    function closeSettingsModal() {
        document.getElementById('settingsModal').style.display = 'none';
    }

    function generatePreview() {
        const content = document.getElementById('preview_content').value;
        const model = document.getElementById('modal_model').value;
        const loading = document.getElementById('preview_loading');
        const result = document.getElementById('preview_result');
        const resultText = document.getElementById('preview_text');

        if (!content) return;

        loading.style.display = 'inline';
        result.style.display = 'none';

        fetch('/accounts/preview-reply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                test_content: content,
                ai_model: model,
                ai_tone: 'supportive' // Default tone for preview
            })
        })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.reply) {
                    resultText.innerText = data.reply;
                    result.style.display = 'block';
                } else {
                    alert('Error generating preview: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                alert('Error request: ' + error);
            });
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        if (event.target == document.getElementById('settingsModal')) {
            closeSettingsModal();
        }
    }
</script>

<div class="info-box"
    style="margin-top: 2rem; padding: 1.5rem; background: var(--card-bg); border-left: 4px solid var(--accent-color); border-radius: 8px;">
    <h4 style="margin-top: 0;"><i class="fas fa-shield-alt"></i> Security Information</h4>
    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0;">
        Sinapp Social uses official X OAuth 2.0 to connect your accounts. We never see your password, and you can revoke
        access at any time from your X account settings.
    </p>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>