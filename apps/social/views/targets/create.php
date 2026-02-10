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
                        <option value="<?php echo $account['id']; ?>"
                            data-model="<?php echo htmlspecialchars($account['ai_model'] ?? 'gemini-1.5-flash'); ?>">
                            @<?php echo htmlspecialchars($account['username']); ?>
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
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">AI Model (Overrides
                Account Default)</label>
            <select name="ai_model"
                style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white;">
                <option value="">Use Account Default</option>
                <?php if (isset($availableModels)): ?>
                    <?php foreach ($availableModels as $model): ?>
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

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary);">Custom Prompt /
                Instructions (e.g. Tone)</label>
            <textarea name="custom_prompt" placeholder="e.g. Speak like a pirate, or focus on technical details..."
                style="width: 100%; padding: 0.75rem; background: var(--sidebar-bg); border: 1px solid var(--border-color); border-radius: 8px; color: white; min-height: 100px; resize: vertical;"></textarea>
            <small style="color: var(--text-muted);">If provided, this overrides the standard tone prompt.</small>
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

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Add Target</button>
            <a href="/targets" class="btn"
                style="background: var(--card-bg); color: white; text-decoration: none; text-align: center; flex: 1; padding: 0.75rem;">Cancel</a>
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