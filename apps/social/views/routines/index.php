<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header" style="justify-content: space-between; align-items: center;">
        <div>
            <h2><i class="fas fa-robot"></i> AI Post Planner</h2>
            <p style="color: var(--text-muted);">Manage your AI-generated content automation.</p>
        </div>
        <a href="/routines/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> New AI Plan
        </a>
    </div>

    <?php if (empty($routines)): ?>
        <div class="empty-state" style="text-align: center; padding: 4rem 1rem;">
            <div style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;">
                <i class="fas fa-magic"></i>
            </div>
            <h3>No AI Plans Found</h3>
            <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 1.5rem;">
                You haven't created any AI content plans yet. Start by creating one to automate your social media posts.
            </p>
            <a href="/routines/create" class="btn btn-primary">Create AI Plan</a>
        </div>
    <?php else: ?>
        <div class="table-container" style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                <thead>
                    <tr>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Name</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Platform
                        </th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border-color);">Status
                        </th>
                        <th style="padding: 1rem; text-align: right; border-bottom: 1px solid var(--border-color);">Created
                            At</th>
                        <th style="padding: 1rem; text-align: right; border-bottom: 1px solid var(--border-color);">Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routines as $routine): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem;">
                                <strong>
                                    <?php echo htmlspecialchars($routine['name']); ?>
                                </strong>
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge"
                                    style="background: rgba(0,0,0,0.1); padding: 0.25rem 0.5rem; border-radius: 4px;">
                                    <?php echo strtoupper(htmlspecialchars($routine['platform'])); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php
                                $statusColor = 'var(--text-muted)';
                                if ($routine['status'] === 'active')
                                    $statusColor = 'var(--success)';
                                if ($routine['status'] === 'paused')
                                    $statusColor = 'var(--warning)';
                                ?>
                                <span style="color: <?php echo $statusColor; ?>; font-weight: 600;">
                                    <?php echo ucfirst(htmlspecialchars($routine['status'])); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right; color: var(--text-muted);">
                                <?php echo date('M j, Y', strtotime($routine['created_at'])); ?>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <!-- Actions placeholder - standard view link isn't built yet, so just deleting or listing posts would go here -->
                                <button class="btn btn-sm btn-outline disabled" disabled>View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>