<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="header">
    <div class="page-title">
        <h1>Subscription Plans</h1>
        <p style="color: var(--text-secondary); margin-top: 0.5rem;">Choose the plan that's right for your growth.</p>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
    <?php foreach ($plans as $plan): ?>
        <div class="card-section" style="display: flex; flex-direction: column; align-items: center; text-align: center; border: 2px solid <?php echo $plan['slug'] === 'pro' ? 'var(--accent-color)' : 'var(--border-color)'; ?>;">
            <?php if ($plan['slug'] === 'pro'): ?>
                <span style="background: var(--accent-color); color: #fff; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; margin-bottom: 1rem;">MOST POPULAR</span>
            <?php endif; ?>
            
            <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?php echo $plan['name']; ?></h2>
            <div style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1.5rem;">
                <?php echo number_format($plan['price_monthly'], 0); ?> <span style="font-size: 1rem; color: var(--text-secondary);">TL / mo</span>
            </div>

            <ul style="list-style: none; padding: 0; margin-bottom: 2rem; width: 100%; text-align: left;">
                <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>
                    <strong><?php echo $plan['x_account_limit']; ?></strong> Social Accounts
                </li>
                <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>
                    Daily <strong><?php echo $plan['daily_post_limit']; ?></strong> Automated Posts
                </li>
                <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>
                    Daily <strong><?php echo $plan['daily_reply_limit']; ?></strong> AI Replies
                </li>
                <?php 
                $features = json_decode($plan['features'], true);
                if ($features):
                    foreach ($features as $key => $val): if ($val):
                ?>
                <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);">
                    <i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $key)); ?>
                </li>
                <?php endif; endforeach; endif; ?>
            </ul>

            <a href="/checkout?plan_id=<?php echo $plan['id']; ?>" class="btn btn-primary" style="width: 100%; text-decoration: none; text-align: center;">
                Choose Plan
            </a>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .card-section {
        transition: transform 0.3s ease;
    }
    .card-section:hover {
        transform: translateY(-10px);
    }
</style>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
