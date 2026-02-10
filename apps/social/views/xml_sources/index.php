<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <div>
            <h2><i class="fas fa-rss"></i> XML Data Sources</h2>
            <p style="color: var(--text-muted);">Manage external data feeds for automated posting.</p>
        </div>
        <a href="/xml-sources/create" class="btn btn-primary">Add New Source</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>URL</th>
                <th>Schedule</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sources)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">No XML sources found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($sources as $source): ?>
                    <tr>
                        <td><strong>
                                <?php echo htmlspecialchars($source['name']); ?>
                            </strong></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($source['url']); ?>" target="_blank"
                                style="color: var(--accent-color);">
                                <i class="fas fa-external-link-alt"></i> Link
                            </a>
                        </td>
                        <td>Daily at
                            <?php echo date('H:i', strtotime($source['schedule_time'])); ?>
                        </td>
                        <td>
                            <?php if ($source['status'] === 'active'): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
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