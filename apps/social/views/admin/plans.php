<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="card-section" style="margin-bottom: 2rem;">
    <div class="section-header">
        <h2>Add New Plan</h2>
    </div>
    <form action="/admin/plans/store" method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
        <div class="form-group">
            <label>Plan Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Premium" required>
        </div>
        <div class="form-group">
            <label>Price (TL/mo)</label>
            <input type="number" name="price" class="form-control" placeholder="e.g. 599" required>
        </div>
        <div class="form-group">
            <label>Account Limit</label>
            <input type="number" name="x_account_limit" class="form-control" value="1" required>
        </div>
        <div class="form-group">
            <label>Daily Post Limit</label>
            <input type="number" name="daily_post_limit" class="form-control" value="5" required>
        </div>
        <div class="form-group">
            <label>Daily Reply Limit</label>
            <input type="number" name="daily_reply_limit" class="form-control" value="20" required>
        </div>
        <div class="form-group" style="grid-column: span 3;">
            <label>Features (One per line)</label>
            <textarea name="features" class="form-control" rows="5" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="grid-column: span 3;">Create Plan</button>
    </form>
</div>

<div class="card-section">
    <div class="section-header">
        <h2>Existing Plans</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Limits (Acc/Post/Reply)</th>
                <th>Features</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plans as $plan): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($plan['name']); ?></strong></td>
                <td><?php echo number_format($plan['price_monthly'] ?? 0, 2); ?> TL</td>
                <td><?php echo "{$plan['x_account_limit']} / {$plan['daily_post_limit']} / {$plan['daily_reply_limit']}"; ?></td>
                <td>
                    <small>
                    <?php 
                    $features = $plan['features'];
                    if (is_string($features)) {
                        $features = json_decode($features, true) ?: [];
                    }
                    echo implode(', ', array_slice($features, 0, 3)) . (count($features) > 3 ? '...' : '');
                    ?>
                    </small>
                </td>
                <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
