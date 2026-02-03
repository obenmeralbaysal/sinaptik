<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2><i class="fas fa-calendar-alt"></i> Scheduled & Sent Posts</h2>
        <p style="color: var(--text-muted);">View the history and status of your automated posts.</p>
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
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Content
                        </th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Platform
                        </th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color);">Scheduled
                            For</th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border-color);">Status
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem; max-width: 400px;">
                                <div
                                    style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($post['content']); ?>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center;">
                                    <span class="badge"
                                        style="background: rgba(0,0,0,0.1); padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem;">
                                        <?php echo strtoupper(htmlspecialchars($post['platform'])); ?>
                                    </span>
                                    <small style="color: var(--text-muted);">@
                                        <?php echo htmlspecialchars($post['account_name']); ?>
                                    </small>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>