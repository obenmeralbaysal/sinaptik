<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section">
    <div class="section-header">
        <h2>System Users</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Accounts</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                    <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($user['email']); ?></small>
                </td>
                <td>
                    <span class="status-badge" style="background: rgba(96, 165, 250, 0.1); color: var(--accent-color);">
                        <?php echo ucfirst($user['role'] ?: 'User'); ?>
                    </span>
                </td>
                <td>
                    <?php 
                    $isActive = \App\Models\User::isSubscriptionActive($user);
                    ?>
                    <span class="status-badge <?php echo $isActive ? 'status-active' : 'status-paused'; ?>">
                        <?php echo $isActive ? 'Active' : 'Expired'; ?>
                    </span>
                </td>
                <td><?php echo $user['accounts_count']; ?></td>
                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                <td>
                    <button class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="alert('Message feature coming soon: Sending to <?php echo $user['email']; ?>')">
                        Message
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
