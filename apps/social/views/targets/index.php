<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2>Auto-Reply Manager</h2>
        <a href="/targets/create" class="btn btn-primary">Add New Target</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Replying Account (You)</th>
                <th>Target Username (Them)</th>
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
                        <td style="padding: 1rem;">
                            <div style="display: flex; align-items: center;">
                                <?php if (!empty($target['account_username'])): ?>
                                    <div
                                        style="width: 32px; height: 32px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0.75rem;">
                                        <i class="fa-brands fa-x-twitter"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;">@<?php echo htmlspecialchars($target['account_username']); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.1); color: #60a5fa;">All
                                        Accounts</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600;">@<?php echo htmlspecialchars($target['target_username']); ?></div>
                        </td>
                        <td style="padding: 1rem;">
                            <span class="badge"
                                style="background: rgba(16, 185, 129, 0.1); color: #34d399; text-transform: capitalize;">
                                <?php echo htmlspecialchars($target['ai_tone']); ?>
                            </span>
                        </td>
                        <td><small
                                style="color: var(--text-muted); display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo $target['custom_prompt'] ? htmlspecialchars($target['custom_prompt']) : '-'; ?></small>
                        </td>
                        <td><span class="status-badge status-active">Monitoring</span></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="/targets/edit/<?php echo $target['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                <form action="/targets/delete/<?php echo $target['id']; ?>" method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this target?');"
                                    style="display: inline;">
                                    <button type="submit" class="btn btn-sm btn-outline"
                                        style="color: var(--danger-color); border-color: var(--danger-color);">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>