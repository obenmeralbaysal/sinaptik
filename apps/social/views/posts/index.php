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
                                        <img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Post Media"
                                            style="max-width: 100px; max-height: 100px; border-radius: 4px; object-fit: cover; border: 1px solid var(--border-color);">
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
                                    <button type="button" class="btn btn-sm btn-success" style="margin-right: 0.5rem;"
                                        id="btn-send-now-<?php echo $post['id']; ?>" onclick="sendNow(<?php echo $post['id']; ?>)"
                                        title="Send Now">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-primary" style="margin-right: 0.5rem;"
                                        onclick="openEditModal(<?php echo $post['id']; ?>, <?php echo htmlspecialchars(json_encode($post['content'])); ?>, '<?php echo date('Y-m-d\TH:i', strtotime($post['scheduled_at'])); ?>', '<?php echo !empty($post['media_url']) ? htmlspecialchars($post['media_url']) : ''; ?>')"
                                        title="Edit Post">
                                        <i class="fas fa-edit"></i>
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

                                <?php if (in_array($post['status'], ['posted', 'failed'])): ?>
                                    <!-- Send Again Button (visible for posted and failed posts, for failed it acts as 'create new copy') -->
                                    <!-- Note: For failed posts, user sees both Send Now (Retry) and Send Again (New Copy). This is intended. -->
                                    <button type="button" class="btn btn-sm btn-outline-info" style="margin-left: 0.5rem;"
                                        id="btn-send-again-<?php echo $post['id']; ?>"
                                        onclick="sendAgain(<?php echo $post['id']; ?>)" title="Send Again">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>



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

            <div class="form-group mb-3">
                <label>Attach Media (Optional)</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="file" id="editMedia" class="form-control" accept="image/*" style="flex: 1;">
                    <button type="button" class="btn btn-outline-primary" onclick="toggleAiGeneration()">
                        <i class="fas fa-magic"></i> Generate AI
                    </button>
                </div>

                <!-- AI Generation Input (Hidden by default) -->
                <div id="aiGenerationDiv"
                    style="display: none; margin-top: 0.5rem; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 4px;">
                    <select id="aiModelSelect" class="form-control mb-2" style="width: 100%;">
                        <option value="imagen-3.0-generate-001">imagen-3.0-generate-001 (Default)</option>
                        <!-- Other models will be populated here -->
                    </select>
                    <input type="text" id="aiPrompt" class="form-control mb-2"
                        placeholder="Enter your image prompt here..." style="width: 100%;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="generateAiImage()">Generate</button>
                </div>

                <input type="hidden" id="editMediaUrl"> <!-- Hidden input to store generated URL -->
                <div id="currentMedia" style="margin-top: 5px; font-size: 0.9em; color: var(--text-muted);"></div>
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
    // --- Send Now & Send Again ---
    async function sendNow(id) {
        if (!confirm('Are you sure you want to send this post immediately?')) return;

        const btn = document.getElementById('btn-send-now-' + id);
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        try {
            const res = await fetch(`/posts/send-now/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                alert('Post sent successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to send post'));
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        } catch (e) {
            alert('Error sending post');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    }

    async function sendAgain(id) {
        if (!confirm('Are you sure you want to create a new copy and send it immediately?')) return;

        // Visual feedback might be tricky since we don't have a specific button ID for send again easily unique if appeared twice
        // But we can generate one.
        const btn = document.getElementById('btn-send-again-' + id);
        const originalContent = btn ? btn.innerHTML : '';
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
        }

        try {
            const res = await fetch(`/posts/send-again/${id}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                alert('Post sent again successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to send post'));
                if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            }
        } catch (e) {
            alert('Error sending post again');
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        }
    }

    // --- Edit Modal ---
    function openEditModal(id, content, time, mediaUrl) {
        document.getElementById('editPostId').value = id;
        document.getElementById('editContent').value = content;
        document.getElementById('editTime').value = time;
        // Reset inputs
        document.getElementById('editMedia').value = '';
        document.getElementById('editMediaUrl').value = '';
        document.getElementById('aiGenerationDiv').style.display = 'none';

        // Pre-fill prompt with content (truncated)
        document.getElementById('aiPrompt').value = content.substring(0, 100);

        const mediaContainer = document.getElementById('currentMedia');
        if (mediaUrl) {
            mediaContainer.innerHTML = 'Current:<br><img src="' + mediaUrl + '" style="max-width: 100%; max-height: 200px; border-radius: 4px; margin-top: 5px; border: 1px solid var(--border-color); object-fit: cover;">';
        } else {
            mediaContainer.innerHTML = '';
        }

        document.getElementById('editModal').style.display = 'flex';
    }

    async function toggleAiGeneration() {
        const div = document.getElementById('aiGenerationDiv');
        const isHidden = div.style.display === 'none';
        div.style.display = isHidden ? 'block' : 'none';

        if (isHidden) {
            // Fetch models if not already fetched
            const select = document.getElementById('aiModelSelect');
            if (select.options.length <= 1) { // Check if only default option exists
                try {
                    const res = await fetch('/api/routines/image-models');
                    const models = await res.json();
                    if (Array.isArray(models)) {
                        select.innerHTML = ''; // Clear default
                        models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            option.innerText = model;
                            select.appendChild(option);
                        });
                    }
                } catch (e) {
                    console.error('Failed to fetch image models', e);
                }
            }
        }
    }

    async function generateAiImage() {
        const prompt = document.getElementById('aiPrompt').value;
        const model = document.getElementById('aiModelSelect').value;
        const btn = document.querySelector('#aiGenerationDiv button');
        const originalText = btn.innerText;

        if (!prompt) {
            alert('Please enter a prompt');
            return;
        }

        btn.innerText = 'Generating...';
        btn.disabled = true;

        try {
            const res = await fetch('/api/routines/generate-image', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: prompt, model: model })
            });
            const data = await res.json();

            if (data.url) {
                document.getElementById('editMediaUrl').value = data.url;
                document.getElementById('currentMedia').innerHTML = `Generated:<br><img src="${data.url}" style="max-width: 100%; max-height: 200px; border-radius: 4px; margin-top: 5px; border: 1px solid var(--border-color); object-fit: cover;"><br><span class="text-success small"><i class="fas fa-check"></i> (Click Save to apply)</span>`;
                // Hide file input to avoid confusion? Or just let user know URL takes precedence if file not selected.
                // alert('Image generated successfully! Click Save Changes to apply.'); 
            } else {
                alert('Error: ' + (data.error || 'Failed to generate image'));
            }
        } catch (e) {
            alert('Error generating image: ' + e.message);
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    async function saveEdit() {
        const id = document.getElementById('editPostId').value;
        const content = document.getElementById('editContent').value;
        const scheduled_at = document.getElementById('editTime').value;

        try {
            const formData = new FormData();
            formData.append('content', content);
            formData.append('scheduled_at', scheduled_at);

            const fileInput = document.getElementById('editMedia');
            if (fileInput.files.length > 0) {
                formData.append('media', fileInput.files[0]);
            }

            const mediaUrl = document.getElementById('editMediaUrl').value;
            if (mediaUrl) {
                formData.append('media_url', mediaUrl);
            }

            const res = await fetch(`/posts/update/${id}`, {
                method: 'POST',
                // headers: { 'Content-Type': 'application/json' }, // Remove content-type for FormData
                body: formData
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