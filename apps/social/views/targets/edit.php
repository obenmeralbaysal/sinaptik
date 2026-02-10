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
                    <option value="<?php echo $account['id']; ?>"
                        data-model="<?php echo htmlspecialchars($account['ai_model'] ?? 'gemini-1.5-flash'); ?>" <?php echo ($target['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                        @<?php echo htmlspecialchars($account['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">Which of your accounts should
                reply to this target. Leave empty for all.</small>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">AI Tone</label>
            <select name="ai_tone" class="form-control">
                <option value="supportive" <?php echo ($target['ai_tone'] == 'supportive') ? 'selected' : ''; ?>>
                    Supportive</option>
                <option value="professional" <?php echo ($target['ai_tone'] == 'professional') ? 'selected' : ''; ?>>
                    Professional</option>
                <option value="witty" <?php echo ($target['ai_tone'] == 'witty') ? 'selected' : ''; ?>>Witty / Funny
                </option>
                <option value="controversial" <?php echo ($target['ai_tone'] == 'controversial') ? 'selected' : ''; ?>>
                    Controversial / Debate</option>
                <option value="questioning" <?php echo ($target['ai_tone'] == 'questioning') ? 'selected' : ''; ?>>
                    Questioning</option>
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

        <div
            style="border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 2rem; margin-bottom: 2rem;">
            <h4 style="margin-top: 0; margin-bottom: 1rem;">Preview AI Reply</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">See how the AI will respond
                based on your settings and the selected account's model.</p>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Test Content (Mock Tweet)</label>
                <textarea id="preview_content" class="form-control" rows="2"
                    style="font-size: 0.9rem;">Hey! Does your app support scheduling threads? #automation</textarea>
            </div>

            <div style="margin-bottom: 1rem;">
                <button type="button" class="btn btn-outline" onclick="generatePreview()">Generate Preview</button>
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
            <button type="submit" class="btn btn-primary">Update Target</button>
        </div>
    </form>
</div>

<script>
    function generatePreview() {
        const accountSelect = document.querySelector('select[name="account_id"]');
        const toneSelect = document.querySelector('select[name="ai_tone"]');
        const promptInput = document.querySelector('textarea[name="custom_prompt"]');
        const contentInput = document.getElementById('preview_content');

        // Get model from selected account option data attribute
        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        const model = selectedOption.getAttribute('data-model') || 'gemini-1.5-flash';
        const accountName = selectedOption.text.trim();

        const tone = toneSelect.value;
        const prompt = promptInput.value;
        const content = contentInput.value;

        if (!content) {
            alert('Please enter some test content.');
            return;
        }

        const loading = document.getElementById('preview_loading');
        const result = document.getElementById('preview_result');
        const resultText = document.getElementById('preview_text');
        const resultMeta = document.getElementById('preview_meta');

        loading.style.display = 'inline';
        result.style.display = 'none';

        // Using the existing preview endpoint
        fetch('/accounts/preview-reply', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                test_content: content,
                ai_model: model,
                ai_tone: tone,
                custom_prompt: prompt
            })
        })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.reply) {
                    resultText.innerText = data.reply;
                    resultMeta.innerText = `Generated using ${model} via ${accountName}`;
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
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>