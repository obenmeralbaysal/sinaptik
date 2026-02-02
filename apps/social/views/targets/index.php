<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2>Monitored Users</h2>
        <a href="/targets/create" class="btn btn-primary">Add New Target</a>
    </div>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>AI Tone</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($targets)): ?>
            <tr>
                <td colspan="4" style="text-align: center; color: var(--text-secondary);">No target users found.</td>
            </tr>
            <?php else: ?>
                <?php foreach ($targets as $target): ?>
                <tr>
                    <td>@<?php echo $target['target_username']; ?></td>
                    <td><span style="text-transform: capitalize;"><?php echo $target['ai_tone']; ?></span></td>
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
