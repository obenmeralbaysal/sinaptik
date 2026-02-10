<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2>Auto-Reply Manager</h2>
        <a href="/targets/create" class="btn btn-primary">Add New Target</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>AI Tone</th>
                <th>Custom Prompt</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($targets)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">No target users found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($targets as $target): ?>
                    <tr>
                        <td>@<?php echo htmlspecialchars($target['target_username']); ?></td>
                        <td><span style="text-transform: capitalize;"><?php echo htmlspecialchars($target['ai_tone']); ?></span>
                        </td>
                        <td><small
                                style="color: var(--text-muted); display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo $target['custom_prompt'] ? htmlspecialchars($target['custom_prompt']) : '-'; ?></small>
                        </td>
                        <td><span class="status-badge status-active">Monitoring</span></td>
                        <td>
                            <button class="btn" style="background: var(--card-bg); color: white;">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>