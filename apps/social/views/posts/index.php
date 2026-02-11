<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2><i class="fas fa-calendar-alt"></i> Scheduled & Sent Posts</h2>
        <p style="color: var(--text-muted);">View the history and status of your automated posts.</p>
    </div>

    <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <button id="bulkTimeShiftBtn" class="btn btn-outline" style="display: none;" onclick="openBulkShiftModal()">
                <i class="fas fa-clock"></i> Shift Time
            </button>
        </div>
    </div>

    <?php if (empty($posts)): ?>
        <div class="empty-state" style="text-align: center; padding: 4rem 1rem;">
            <div style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;">
                <i class="fas fa-history"></i>
            </div>
            <h3>No Posts Found</h3>
            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 1.5rem;">
                You haven't scheduled any posts yet. Create a routine to start automating your content.
            </p>
            <a href="/routines/create" class="btn btn-primary">Create Routine</a>
        </div>
    <?php else: ?>
        <div class="table-container" style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr>
                        <th
                            style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border-color); width: 40px;">
                            <input type="checkbox" id="selectAllPosts" onclick="toggleSelectAll()">
                        </th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Content
                        </th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Platform
                        </th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Scheduled
                            For</th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border-color);">Status
                        </th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border-color);">Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem; text-align: center;">
                                <?php if (in_array($post['status'], ['pending', 'failed'])): ?>
                                    <input type="checkbox" class="post-checkbox" value="<?php echo $post['id']; ?>"
                                        onclick="updateBulkActions()">
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; max-width: 400px;">
                                <div
                                    style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($post['content']); ?>
                                </div>
                                <?php if (!empty($post['media_url'])): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Image"
                                            style="max-width: 100px; max-height: 100px; border-radius: 4px; border: 1px solid var(--border-color);">
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center;">
                                    <span class="badge"
                                        style="background: rgba(0,0,0,0.1); padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem;">
                                        <?php echo strtoupper(htmlspecialchars($post['platform'])); ?>
                                    </span>
                                    <small
                                        style="color: var(--text-muted);">@<?php echo htmlspecialchars($post['account_name']); ?></small>
                                </div>
                            </td>
                            <td style="padding: 1rem; color: var(--text-muted);">
                                <?php echo date('M j, Y H:i', strtotime($post['scheduled_at'])); ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php
                                $statusColor = 'var(--text-muted)';
                                $icon = 'fa-clock';
                                if ($post['status'] === 'posted') {
                                    $statusColor = 'var(--success)';
                                    $icon = 'fa-check-circle';
                                } elseif ($post['status'] === 'failed') {
                                    $statusColor = 'var(--error)';
                                    $icon = 'fa-exclamation-circle';
                                }
                                ?>
                                <span
                                    style="color: <?php echo $statusColor; ?>; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if (in_array($post['status'], ['pending', 'failed'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" style="margin-right: 0.5rem;"
                                        onclick="openEditModal(<?php echo $post['id']; ?>, '<?php echo addslashes(htmlspecialchars($post['content'])); ?>', '<?php echo date('Y-m-d\TH:i', strtotime($post['scheduled_at'])); ?>')"
                                        title="Edit Post">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="margin-right: 0.5rem;"
                                        onclick="openImageModal(<?php echo $post['id']; ?>, '<?php echo addslashes(htmlspecialchars($post['content'])); ?>')"
                                        title="Generate Image">
                                        <i class="fas fa-magic"></i>
                                    </button>

                                    <form action="/posts/delete/<?php echo $post['id']; ?>" method="POST" style="display: inline;"
                                        onsubmit="return confirm('Are you sure you want to delete this post?');">
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            style="background: none; border: none; color: var(--error); cursor: pointer;"
                                            title="Delete Post">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Image Generation Modal (Existing) -->
    <div id="imageGenModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div
            style="background: var(--card-bg); padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; position: relative;">
            <button onclick="closeModal()"
                style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
            <h3 style="margin-top: 0;">Generate Image</h3>

            <div class="form-group mb-3">
                <label>Model</label>
                <select id="modalModelSelect" class="form-control" style="width: 100%;">
                    <option>Loading...</option>
                </select>
            </div>

            <div class="form-group mb-3">
                <label>Prompt</label>
                <textarea id="modalPrompt" class="form-control" rows="3" style="width: 100%;"></textarea>
            </div>

            <div id="modalPreview" style="display: none; margin-bottom: 1rem; text-align: center;">
                <img id="modalImage" src="" style="max-width: 100%; max-height: 300px; border-radius: 4px;">
            </div>

            <div style="text-align: right;">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="modalGenerateBtn" onclick="generateImage()">
                    <i class="fas fa-magic"></i> Generate
                </button>
                <button type="button" class="btn btn-success" id="modalSaveBtn" onclick="saveImage()"
                    style="display: none;">
                    <i class="fas fa-save"></i> Save to Post
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div
            style="background: var(--card-bg); padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; position: relative;">
            <button onclick="closeEditModal()"
                style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
            <h3 style="margin-top: 0;">Edit Post</h3>

            <input type="hidden" id="editPostId">
            <div class="form-group mb-3">
                <label>Content</label>
                <textarea id="editContent" class="form-control" rows="4" style="width: 100%;"></textarea>
            </div>

            <div class="form-group mb-3">
                <label>Scheduled For</label>
                <input type="datetime-local" id="editTime" class="form-control" style="width: 100%;">
            </div>

            <div style="text-align: right;">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEdit()">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Bulk Shift Modal -->
    <div id="bulkShiftModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div
            style="background: var(--card-bg); padding: 2rem; border-radius: 8px; width: 90%; max-width: 400px; position: relative;">
            <button onclick="closeBulkShiftModal()"
                style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
            <h3 style="margin-top: 0;">Shift Time</h3>
            <p>Shift selected posts forward or backward in time.</p>

            <div class="form-group mb-3">
                <label>Minutes Offset (Use negative to go back)</label>
                <input type="number" id="shiftMinutes" class="form-control" value="60" step="15" style="width: 100%;">
                <small class="text-muted">e.g., 60 for +1 hour, -30 for -30 mins.</small>
            </div>

            <div style="text-align: right;">
                <button type="button" class="btn btn-outline" onclick="closeBulkShiftModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="applyBulkShift()">Apply Shift</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPostId = null;
    let currentMediaUrl = null;

    // --- Image Modal ---
    function openImageModal(postId, content) {
        currentPostId = postId;
        currentMediaUrl = null;
        document.getElementById('modalPrompt').value = content;
        document.getElementById('imageGenModal').style.display = 'flex';
        document.getElementById('modalPreview').style.display = 'none';
        document.getElementById('modalSaveBtn').style.display = 'none';
        document.getElementById('modalGenerateBtn').innerHTML = '<i class="fas fa-magic"></i> Generate';

        // Load models if empty
        const select = document.getElementById('modalModelSelect');
        if (select.options.length <= 1) {
            fetch('/api/routines/image-models')
                .then(res => res.json())
                .then(models => {
                    select.innerHTML = '';
                    models.forEach(model => {
                        const opt = document.createElement('option');
                        opt.value = model;
                        opt.textContent = model;
                        select.appendChild(opt);
                    });
                    // Custom option
                    const custom = document.createElement('option');
                    custom.value = 'custom';
                    custom.textContent = 'Custom Model...';
                    select.appendChild(custom);
                });
        }
    }

    function closeModal() {
        document.getElementById('imageGenModal').style.display = 'none';
    }

    document.getElementById('modalModelSelect').addEventListener('change', function () {
        if (this.value === 'custom') {
            const val = prompt('Enter model name:');
            if (val) {
                const opt = document.createElement('option');
                opt.value = val;
                opt.textContent = val;
                opt.selected = true;
                this.appendChild(opt); // append to end but simplistic
            } else {
                this.value = this.options[0].value;
            }
        }
    });

    async function generateImage() {
        const btn = document.getElementById('modalGenerateBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        btn.disabled = true;

        try {
            const prompt = document.getElementById('modalPrompt').value;
            const model = document.getElementById('modalModelSelect').value;

            const res = await fetch('/api/routines/generate-image', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt, model })
            });

            const data = await res.json();

            if (data.error) throw new Error(data.error);

            if (data.url) {
                currentMediaUrl = data.url;
                document.getElementById('modalImage').src = data.url;
                document.getElementById('modalPreview').style.display = 'block';
                document.getElementById('modalSaveBtn').style.display = 'inline-block';
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Regenerate';
            }
        } catch (e) {
            alert('Error: ' + e.message);
            btn.innerHTML = originalText;
        } finally {
            btn.disabled = false;
        }
    }

    async function saveImage() {
        if (!currentPostId || !currentMediaUrl) return;

        try {
            const res = await fetch(`/api/posts/${currentPostId}/update-media`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ media_url: currentMediaUrl })
            });

            const data = await res.json();
            if (data.success) {
                closeModal();
                location.reload(); // Refresh to see the change
            } else {
                throw new Error(data.error);
            }
        } catch (e) {
            alert('Failed to save: ' + e.message);
        }
    }

    // --- Edit Modal ---
    function openEditModal(id, content, time) {
        document.getElementById('editPostId').value = id;
        document.getElementById('editContent').value = content;
        document.getElementById('editTime').value = time;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    async function saveEdit() {
        const id = document.getElementById('editPostId').value;
        const content = document.getElementById('editContent').value;
        const scheduled_at = document.getElementById('editTime').value;

        try {
            const res = await fetch(`/posts/update/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content, scheduled_at })
            });
            const data = await res.json();
            if (data.success) {
                closeEditModal();
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to update'));
            }
        } catch (e) {
            alert('Error updating post');
        }
    }

    // --- Bulk Actions ---
    function toggleSelectAll() {
        const checked = document.getElementById('selectAllPosts').checked;
        document.querySelectorAll('.post-checkbox').forEach(cb => cb.checked = checked);
        updateBulkActions();
    }

    function updateBulkActions() {
        const selected = document.querySelectorAll('.post-checkbox:checked').length;
        const btn = document.getElementById('bulkTimeShiftBtn');
        if (selected > 0) {
            btn.style.display = 'block';
            btn.innerText = `Shift Time (${selected})`;
        } else {
            btn.style.display = 'none';
        }
    }

    function openBulkShiftModal() {
        document.getElementById('bulkShiftModal').style.display = 'flex';
    }

    function closeBulkShiftModal() {
        document.getElementById('bulkShiftModal').style.display = 'none';
    }

    async function applyBulkShift() {
        const minutes = document.getElementById('shiftMinutes').value;
        const checkboxes = document.querySelectorAll('.post-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);

        if (ids.length === 0) return;

        try {
            const res = await fetch('/posts/bulk-schedule', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_ids: ids, minutes_offset: minutes })
            });

            const data = await res.json();
            if (data.success) {
                closeBulkShiftModal();
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to bulk schedule'));
            }
        } catch (e) {
            alert('Error during bulk schedule');
        }
    }
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>