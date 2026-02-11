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

                    <div class="image-generation-section"
                        style="border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 0.5rem;">
                        <input type="hidden" name="media_urls[]" class="media-url-input">

                        <div class="image-preview" style="display: none; margin-bottom: 1rem; position: relative;">
                            <img src="" alt="Generated Image" style="width: 100%; border-radius: 8px;">
                            <button type="button" class="btn btn-sm btn-danger remove-image-btn"
                                style="position: absolute; top: 5px; right: 5px; padding: 2px 6px;">&times;</button>
                        </div>

                        <div class="image-controls">
                            <select class="form-control mb-2 image-model-select"
                                style="font-size: 0.85rem; padding: 0.3rem;">
                                <option value="dall-e-3">DALL-E 3</option>
                                <option value="dall-e-2">DALL-E 2</option>
                            </select>
                            <input type="text" class="form-control mb-2 image-prompt-input" placeholder="Image prompt..."
                                style="font-size: 0.85rem;">

                            <div style="display: flex; justify-content: space-between;">
                                <button type="button" class="btn btn-sm btn-outline generate-image-btn"
                                    data-index="<?php echo $index; ?>">
                                    <i class="fas fa-image"></i> Generate Image
                                </button>
                                <button type="button" class="btn btn-sm btn-outline regenerate-btn"
                                    data-index="<?php echo $index; ?>">
                                    <i class="fas fa-sync-alt"></i> Rewrite
                                </button>
                            </div>
                        </div>
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
    // Text Regeneration
    document.querySelectorAll('.regenerate-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const index = this.dataset.index;
            const textarea = this.closest('.post-card').querySelector('textarea');
            const originalIcon = this.innerHTML;

            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
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
                    textarea.style.borderColor = 'var(--accent-color)';
                    setTimeout(() => textarea.style.borderColor = '', 1000);
                    // Update char count
                    const count = textarea.value.length;
                    const counter = textarea.closest('.post-card').querySelector('.char-count');
                    counter.textContent = count + ' chars';
                    counter.style.color = count > 280 ? 'red' : 'var(--text-muted)';
                }
            } catch (error) {
                console.error('Error generating post:', error);
                alert('Failed to regenerate post.');
            } finally {
                this.innerHTML = originalIcon;
                this.disabled = false;
            }
        });
    });

    // Fetch available image models
    async function loadImageModels() {
        try {
            const response = await fetch('/api/routines/image-models');
            if (response.ok) {
                const models = await response.json();
                const selects = document.querySelectorAll('.image-model-select');
                selects.forEach(select => {
                    select.innerHTML = '';

                    // Add fetched models
                    if (Array.isArray(models)) {
                        models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            option.textContent = model;
                            select.appendChild(option);
                        });
                    }

                    // Add custom option
                    const customOption = document.createElement('option');
                    customOption.value = 'custom';
                    customOption.textContent = 'Custom Model...';
                    select.appendChild(customOption);
                });

                // Handle custom model selection
                selects.forEach(select => {
                    select.addEventListener('change', function () {
                        if (this.value === 'custom') {
                            const customModel = prompt("Enter model name (e.g., imagen-3.0-generate-001):");
                            if (customModel && customModel.trim() !== '') {
                                // Check if option already exists
                                let existing = Array.from(this.options).find(opt => opt.value === customModel);
                                if (!existing) {
                                    const option = document.createElement('option');
                                    option.value = customModel;
                                    option.textContent = customModel;
                                    this.insertBefore(option, this.lastChild); // Insert before 'Custom...'
                                    this.value = customModel;
                                } else {
                                    this.value = customModel;
                                }
                            } else {
                                // Revert to first option if cancelled or empty
                                this.value = this.options[0] ? this.options[0].value : 'custom';
                            }
                        }
                    });
                });
            }
        } catch (e) {
            console.error("Failed to load image models", e);
        }
    }

    // Call it
    loadImageModels();

    // Image Generation
    document.querySelectorAll('.generate-image-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const card = this.closest('.post-card');
            const promptInput = card.querySelector('.image-prompt-input');
            const modelSelect = card.querySelector('.image-model-select');
            const previewDiv = card.querySelector('.image-preview');
            const imgTag = previewDiv.querySelector('img');
            const urlInput = card.querySelector('.media-url-input');

            let prompt = promptInput.value.trim();
            if (!prompt) {
                // If prompt is empty, use the tweet content as prompt
                prompt = card.querySelector('textarea').value;
                promptInput.value = prompt; // Fill it in for visibility
            }

            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            this.disabled = true;

            try {
                const response = await fetch('/api/routines/generate-image', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        prompt: prompt,
                        model: modelSelect.value
                    })
                });

                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                if (data.url) {
                    imgTag.src = data.url;
                    urlInput.value = data.url;
                    previewDiv.style.display = 'block';

                    // Change button to Regenerate
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Regenerate Image';
                }

            } catch (error) {
                console.error('Image generation error:', error);
                alert('Image generation failed: ' + error.message);
                this.innerHTML = originalContent;
            } finally {
                this.disabled = false;
                if (this.innerHTML.includes('Generating')) {
                    this.innerHTML = originalContent;
                }
            }
        });
    });

    // Remove Image
    document.querySelectorAll('.remove-image-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const card = this.closest('.post-card');
            const previewDiv = card.querySelector('.image-preview');
            const urlInput = card.querySelector('.media-url-input');

            previewDiv.style.display = 'none';
            urlInput.value = '';

            // Reset generate button text
            const genBtn = card.querySelector('.generate-image-btn');
            genBtn.innerHTML = '<i class="fas fa-image"></i> Generate Image';
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