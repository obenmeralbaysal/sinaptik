<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div style="margin-bottom: 2rem;">
    <h2><i class="fas fa-magic" style="color: var(--accent-color);"></i> Design Your AI Content Plan</h2>
    <p style="color: var(--text-muted);">Configure your AI assistant to generate a personalized posting schedule.</p>
</div>

<form action="/api/routines/generate" method="POST" class="routine-form" id="routineForm">
    <!-- Error Alert -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"
            style="background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.2); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Account Selector Banner -->
    <?php if (empty($accounts)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> You need to connect an X account first.
            <a href="/accounts/connect">Connect Now</a>
        </div>
    <?php else: ?>
        <div
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
            <div
                style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <i class="fa-brands fa-x-twitter" style="font-size: 1.2rem;"></i>
            </div>
            <div style="flex: 1;">
                <label
                    style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 0.25rem;">Target
                    Account</label>
                <select name="account_id" class="form-control"
                    style="width: 100%; max-width: 300px; padding: 0.5rem; border: none; background: transparent; color: var(--text-primary); font-size: 1.1rem; font-weight: 600; cursor: pointer;"
                    required>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>">@
                            <?php echo htmlspecialchars($account['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <i class="fas fa-chevron-down" style="color: var(--text-secondary); margin-right: 1rem;"></i>
        </div>
    <?php endif; ?>

    <!-- Model Selector -->
    <div
        style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 1rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
        <div
            style="width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent-color);">
            <i class="fas fa-robot" style="font-size: 1.2rem;"></i>
        </div>
        <div style="flex: 1;">
            <label
                style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 0.25rem;">
                AI Model
            </label>
            <select name="model" class="form-control"
                style="width: 100%; max-width: 300px; padding: 0.5rem; border: none; background: transparent; color: var(--text-primary); font-size: 1.1rem; font-weight: 600; cursor: pointer;">
                <?php if (!empty($ai_models)): ?>
                    <?php foreach ($ai_models as $model): ?>
                        <option value="<?php echo htmlspecialchars($model); ?>" <?php echo $model === 'gemini-1.5-flash' ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($model); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="gemini-1.5-flash" selected>gemini-1.5-flash (Default)</option>
                <?php endif; ?>
            </select>
        </div>
        <i class="fas fa-chevron-down" style="color: var(--text-secondary); margin-right: 1rem;"></i>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

        <!-- Left Column: Scheduler -->
        <div class="step-card">
            <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                <span class="step-number">1</span>
                <h3 style="margin: 0;">Schedule & Timing</h3>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display: flex; justify-content: space-between;">
                    Duration
                    <span id="durationValue" style="color: var(--accent-color); font-weight: bold;">30 Days</span>
                </label>
                <input type="range" name="days" class="form-control" min="7" max="60" value="30"
                    oninput="document.getElementById('durationValue').innerText = this.value + ' Days'"
                    style="width: 100%; margin-top: 0.5rem;">
                <div
                    style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                    <span>1 Week</span>
                    <span>2 Months</span>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Timezone</label>
                <div style="position: relative;">
                    <i class="fas fa-globe"
                        style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <select name="timezone" class="form-control" style="padding-left: 2.5rem;">
                        <option value="Europe/Istanbul" selected>Istanbul (GMT+3)</option>
                        <option value="Europe/London">London (GMT+0)</option>
                        <option value="America/New_York">New York (GMT-4)</option>
                        <option value="Asia/Tokyo">Tokyo (GMT+9)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Start Window</label>
                    <input type="time" name="time_start" class="form-control" value="09:00" required>
                </div>
                <div class="form-group">
                    <label>End Window</label>
                    <input type="time" name="time_end" class="form-control" value="11:00" required>
                </div>
            </div>

            <div
                style="margin-top: 1rem; padding: 1rem; background: rgba(16, 185, 129, 0.05); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <label class="checkbox-container"
                    style="display: flex; align-items: start; gap: 0.75rem; cursor: pointer;">
                    <input type="checkbox" name="is_random_time" value="1" checked style="margin-top: 0.25rem;">
                    <div>
                        <strong style="display: block; color: var(--success); font-size: 0.9rem;">Smart
                            Randomization</strong>
                        <span style="font-size: 0.8rem; color: var(--text-secondary);">Post at different times within
                            this window to feel more natural.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Right Column: Content -->
        <div class="step-card">
            <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                <span class="step-number">2</span>
                <h3 style="margin: 0;">Persona & Content</h3>
            </div>

            <!-- Hidden inputs for pills -->
            <input type="hidden" name="language" id="languageInput" value="English">
            <input type="hidden" name="mode" id="modeInput" value="Professional">

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Language</label>
                <div class="selection-pills" id="langPills">
                    <div class="pill-option selected" data-value="English">🇬🇧 English</div>
                    <div class="pill-option" data-value="Turkish">🇹🇷 Turkish</div>
                    <div class="pill-option" data-value="Spanish">🇪🇸 Spanish</div>
                    <div class="pill-option" data-value="French">🇫🇷 French</div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Tone / Vibe</label>
                <div class="selection-pills" id="tonePills">
                    <div class="pill-option selected" data-value="Professional">Professional</div>
                    <div class="pill-option" data-value="Witty">Witty</div>
                    <div class="pill-option" data-value="Casual">Casual</div>
                    <div class="pill-option" data-value="Bold">Bold</div>
                    <div class="pill-option" data-value="Empathetic">Empathetic</div>
                    <div class="pill-option" data-value="Sassy">Sassy</div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Who is speaking? (Persona)</label>
                <div style="position: relative;">
                    <i class="fas fa-user-tag"
                        style="position: absolute; left: 1rem; top: 1rem; color: var(--text-secondary);"></i>
                    <textarea name="persona" class="form-control" rows="2" style="padding-left: 2.5rem;"
                        placeholder="e.g. A friendly tech expert who loves coffee and minimalist design."
                        required></textarea>
                </div>
            </div>

            <div class="form-group">
                <label>What's the topic? (Prompt)</label>
                <div style="position: relative;">
                    <i class="fas fa-pen-fancy"
                        style="position: absolute; left: 1rem; top: 1rem; color: var(--text-secondary);"></i>
                    <textarea name="topic" class="form-control" rows="3" style="padding-left: 2.5rem;"
                        placeholder="e.g. Daily tips about productivity, checking in on followers, sharing morning thoughts."
                        required></textarea>
                </div>
            </div>

        </div>
    </div>

    <div class="form-actions" style="margin-top: 2rem; text-align: center;">
        <button type="submit" class="btn btn-primary btn-lg"
            style="padding: 1rem 3rem; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 10px 25px var(--accent-glow);">
            <i class="fas fa-wand-magic-sparkles"></i> Generate 30-Day Plan
        </button>
        <p style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary);">
            <i class="fas fa-info-circle"></i> You'll be able to review and edit every post before scheduling.
        </p>
    </div>
</form>

<script>
    // Pill Selection Logic
    function setupPills(containerId, inputId) {
        const container = document.getElementById(containerId);
        const input = document.getElementById(inputId);

        container.querySelectorAll('.pill-option').forEach(pill => {
            pill.addEventListener('click', () => {
                // Deselect all
                container.querySelectorAll('.pill-option').forEach(p => p.classList.remove('selected'));
                // Select click
                pill.classList.add('selected');
                // Update input
                input.value = pill.dataset.value;
            });
        });
    }

    setupPills('langPills', 'languageInput');
    setupPills('tonePills', 'modeInput');
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>