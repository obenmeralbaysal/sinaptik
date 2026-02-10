<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2><i class="fas fa-check-double"></i> Review & Schedule</h2>
        <p style="color: var(--text-muted);">Review the generated posts before scheduling. You can edit text or
            regenerate specific posts.</p>
    </div>

    <!-- Summary Box -->
    <div class="info-box"
        style="margin-bottom: 2rem; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
        <strong>Topic:</strong> <?php echo htmlspecialchars($input['topic']); ?> <br>
        <strong>Persona:</strong> <?php echo htmlspecialchars($input['persona']); ?> |
        <strong>Tone:</strong> <?php echo htmlspecialchars($input['mode']); ?>
    </div>

    <form action="/api/routines/store" method="POST" id="scheduleForm">
        <!-- Hidden Inputs to carry over settings -->
        <?php foreach ($input as $key => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>"
                id="input_<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>

        <div class="posts-grid"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($posts as $index => $post): ?>
                <div class="post-card card"
                    style="display: flex; flex-direction: column; padding: 1.5rem; position: relative;">
                    <div
                        style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.85rem;">
                        <span>Day <?php echo $index + 1; ?></span>
                        <span class="char-count">0 chars</span>
                    </div>

                    <textarea name="posts[]" class="form-control post-content" rows="4"
                        style="flex: 1; resize: vertical; margin-bottom: 1rem;"><?php echo htmlspecialchars($post); ?></textarea>

                    <div style="text-align: right;">
                        <button type="button" class="btn btn-sm btn-outline regenerate-btn"
                            data-index="<?php echo $index; ?>">
                            <i class="fas fa-sync-alt"></i> Regenerate
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions"
            style="margin-top: 2rem; text-align: right; position: sticky; bottom: 1rem; background: var(--bg-color); padding: 1rem; border-top: 1px solid var(--border-color); box-shadow: 0 -4px 10px rgba(0,0,0,0.1);">
            <a href="/routines/create" class="btn btn-outline" style="margin-right: 1rem;">Back</a>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-calendar-check"></i> Confirm & Schedule All
            </button>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll('.regenerate-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const index = this.dataset.index;
            const textarea = this.closest('.post-card').querySelector('textarea');
            const originalIcon = this.innerHTML;

            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            this.disabled = true;

            try {
                const criteria = {
                    language: document.getElementById('input_language').value,
                    mode: document.getElementById('input_mode').value,
                    persona: document.getElementById('input_persona').value,
                    topic: document.getElementById('input_topic').value
                };

                const response = await fetch('/api/routines/regenerate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(criteria)
                });

                const data = await response.json();

                if (data.content) {
                    textarea.value = data.content;
                    // Highlight change
                    textarea.style.borderColor = 'var(--accent-color)';
                    setTimeout(() => textarea.style.borderColor = '', 1000);
                }
            } catch (error) {
                console.error('Error generating post:', error);
                alert('Failed to regenerate post. Please try again.');
            } finally {
                this.innerHTML = originalIcon;
                this.disabled = false;
            }
        });
    });

    // Char count update logic
    document.querySelectorAll('.post-content').forEach(textarea => {
        const updateCount = () => {
            const count = textarea.value.length;
            const counter = textarea.closest('.post-card').querySelector('.char-count');
            counter.textContent = count + ' chars';
            counter.style.color = count > 280 ? 'red' : 'var(--text-muted)';
        };
        textarea.addEventListener('input', updateCount);
        updateCount(); // Initial check
    });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>