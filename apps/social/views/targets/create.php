<?php require_once __DIR__ . '/../partials/header.php'; ?>

<form action="/targets" method="POST" style="max-width: 600px;">
    <!-- 1. Target Username -->
    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">1. Target Username (Who to reply
            to)</label>
        <div style="display: flex; gap: 0.5rem;">
            <div
                style="flex: 1; display: flex; align-items: center; background: var(--bg-color); border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem 1rem;">
                <span style="color: var(--text-muted); margin-right: 0.5rem;">@</span>
                <input type="text" name="target_username" id="target_username" class="form-control"
                    style="border: none; padding: 0; background: transparent;" placeholder="elonmusk" required>
            </div>
            <button type="button" class="btn btn-outline" onclick="verifyUser()">
                <i class="fas fa-check-circle"></i> Verify User
            </button>
        </div>
        <input type="hidden" name="user_id" id="user_id">
        <small id="user_verification_status"
            style="display: block; margin-top: 0.5rem; color: var(--text-muted);"></small>
    </div>

    <!-- 2. Your Account -->
    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">2. Your Account (Replier)</label>
        <select name="account_id" id="account_id" class="form-control" required>
            <option value="">Select Account...</option>
            <?php foreach ($accounts as $account): ?>
                <option value="<?php echo $account['id']; ?>"
                    data-model="<?php echo htmlspecialchars($account['ai_model'] ?? 'gemini-1.5-flash'); ?>">
                    @<?php echo htmlspecialchars($account['username']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 3. Fetch Last Tweet -->
    <div style="margin-bottom: 1.5rem;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">3. Context</label>
        <button type="button" class="btn btn-outline" id="fetch_tweet_btn" onclick="fetchLastTweet()">
            <i class="fas fa-sync-alt"></i> Fetch Last Tweet
        </button>
        <span id="fetch_loading"
            style="display: none; margin-left: 0.5rem; color: var(--text-muted);">Fetching...</span>
        <small style="display: block; color: var(--text-muted); margin-top: 0.5rem;">Fetches the latest tweet from the
            target user to test your prompt below.</small>
    </div>

    <!-- 4. Custom Prompt -->
    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">4. Custom Instructions (Prompt)</label>
        <textarea name="custom_prompt" id="custom_prompt"
            placeholder="e.g. Disagree politely, or ask a follow-up question."
            style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white; min-height: 80px; resize: vertical;"></textarea>
    </div>

    <!-- 5. AI Model -->
    <div style="margin-bottom: 1.5rem;">
        <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">5. AI Model</label>
        <select name="ai_model" id="ai_model"
            style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
            <option value="">Use Account Default</option>
            <?php if (isset($available_models)): ?>
                <?php foreach ($available_models as $model): ?>
                    <option value="<?php echo htmlspecialchars($model); ?>"><?php echo htmlspecialchars($model); ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="gemini-1.5-flash">Gemini 1.5 Flash (Fast)</option>
                <option value="gemini-pro">Gemini Pro (Capable)</option>
                <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                <option value="gpt-4">GPT-4 (Smartest)</option>
            <?php endif; ?>
        </select>
    </div>

    <!-- 6. Preview -->
    <div style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 2rem; margin-bottom: 2rem;">
        <h4 style="margin-top: 0; margin-bottom: 1rem;">6. Preview AI Reply</h4>

        <div class="form-group" style="margin-bottom: 1rem;">
            <label style="display: block; margin-bottom: 0.5rem;">Last Tweet Content (for Preview)</label>
            <textarea id="preview_content" class="form-control" rows="3"
                style="font-size: 0.9rem; color: var(--text-muted);"
                readonly>Click 'Fetch Last Tweet' above to populate this.</textarea>
        </div>

        <div style="margin-bottom: 1rem;">
            <button type="button" class="btn btn-primary" onclick="generatePreview()">
                <i class="fas fa-play"></i> Generate Preview
            </button>
            <span id="preview_loading"
                style="display: none; margin-left: 0.5rem; color: var(--text-muted);">Generating...</span>
        </div>

        <div id="preview_result"
            style="background: var(--bg-color); padding: 1rem; border-radius: 8px; font-size: 0.9rem; display: none; border: 1px solid var(--border-color);">
            <strong style="display: block; margin-bottom: 0.5rem; color: var(--accent-color);">AI Reply:</strong>
            <p style="margin: 0; color: var(--text-primary); white-space: pre-wrap;" id="preview_text"></p>
            <small style="display: block; margin-top: 0.5rem; color: var(--text-muted);" id="preview_meta"></small>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
        <a href="/targets" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">7. Save & Activate Job</button>
    </div>
</form>
</div>

<script>
    function fetchLastTweet() {
        const username = document.getElementById('target_username').value;
        const accountId = document.getElementById('account_id').value;
        const btn = document.getElementById('fetch_tweet_btn');
        const loading = document.getElementById('fetch_loading');
        const previewContent = document.getElementById('preview_content');

        if (!username) {
            alert('Please enter a target username first.');
            return;
        }
        if (!accountId) {
            alert('Please select your account first.');
            return;
        }

        btn.disabled = true;
        loading.style.display = 'inline';
        previewContent.value = 'Fetching...';

        fetch('/targets/fetch-tweet', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'username=' + encodeURIComponent(username) + '&account_id=' + accountId
        })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                loading.style.display = 'none';

                if (data.tweet) {
                    previewContent.value = data.tweet.text;
                } else {
                    previewContent.value = '';
                    alert('Error: ' + (data.error || 'No tweet found'));
                }
            })
            .catch(error => {
                btn.disabled = false;
                loading.style.display = 'none';
                previewContent.value = '';
                alert('Error: ' + error);
            });
    }

    function generatePreview() {
        const content = document.getElementById('preview_content').value;

        // Validation
        if (!content || content.includes('Click \'Fetch Last Tweet\'')) {
            alert('Please fetch a tweet or enter content to preview.');
            return;
        }

        const accountSelect = document.getElementById('account_id');
        const modelSelect = document.getElementById('ai_model');
        const promptInput = document.getElementById('custom_prompt');

        if (!accountSelect.value) {
            alert('Please select an account.');
            return;
        }

        // Get model: either selected explicit target model OR account default from data attribute
        let model = modelSelect.value;
        if (!model) {
            const selectedOption = accountSelect.options[accountSelect.selectedIndex];
            model = selectedOption.getAttribute('data-model') || 'gemini-1.5-flash';
        }

        const prompt = promptInput.value;
        const accountName = accountSelect.options[accountSelect.selectedIndex].text.trim();

        const loading = document.getElementById('preview_loading');
        const result = document.getElementById('preview_result');
        const resultText = document.getElementById('preview_text');
        const resultMeta = document.getElementById('preview_meta');

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
                ai_tone: 'custom',
                custom_prompt: prompt
            })
        })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.reply) {
                    resultText.innerText = data.reply;
                    // resultMeta.innerText = `Generated using ${model} via ${accountName}`;
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
    function verifyUser() {
        const username = document.getElementById('target_username').value;
        const accountId = document.getElementById('account_id').value;
        const status = document.getElementById('user_verification_status');
        const userIdInput = document.getElementById('user_id');

        if (!username) {
            alert('Please enter a username');
            return;
        }
        if (!accountId) {
            alert('Please select an account first (needed for API access)');
            return;
        }

        status.style.color = 'var(--text-muted)';
        status.innerText = 'Verifying...';

        fetch('/targets/verify-user', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'username=' + encodeURIComponent(username) + '&account_id=' + accountId
        })
            .then(res => res.json())
            .then(data => {
                if (data.user_id) {
                    userIdInput.value = data.user_id;
                    status.style.color = 'var(--success-color)';
                    status.innerText = '✓ Verified: User ID ' + data.user_id;
                } else {
                    status.style.color = 'var(--danger-color)';
                    status.innerText = '✗ Error: ' + (data.error || 'Unknown error');
                }
            })
            .catch(err => {
                status.style.color = 'var(--danger-color)';
                status.innerText = '✗ Error: ' + err;
            });
    }
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>