<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="header">
    <div class="page-title">
        <h1>Select Payment Method</h1>
        <p style="color: var(--text-secondary); margin-top: 0.5rem;">Selected Plan: <strong><?php echo $plan['name']; ?></strong> - <?php echo number_format($plan['price_monthly'], 0); ?> TL/mo</p>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
    <!-- Bank Transfer Option (Always On) -->
    <div class="card-section">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-university" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem;"></i>
            <h2>Bank Transfer</h2>
            <p style="color: var(--text-secondary);">Transfer the total to the account below and notify us.</p>
        </div>

        <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
            <p style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 0.5rem;"><strong>Bank:</strong> Garanti BBVA</p>
            <p style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 0.5rem;"><strong>Receiver:</strong> Sinapp Social Ltd.</p>
            <p style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 0.5rem;"><strong>IBAN:</strong> TR00 0000 0000 0000 0000 0000 00</p>
            <p><strong>Description:</strong> <?php echo $_SESSION['user_name']; ?> - <?php echo $plan['name']; ?></p>
        </div>

        <button class="btn btn-primary coming-soon" style="width: 100%;">I've Transferred</button>
    </div>

    <!-- PayTR Option (Conditional) -->
    <?php if ($paytr_active): ?>
    <div class="card-section">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-credit-card" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem;"></i>
            <h2>Credit Card (PayTR)</h2>
            <p style="color: var(--text-secondary);">Secure and instant payment.</p>
        </div>

        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 2rem; border: 1px dashed var(--border-color); padding: 1rem; border-radius: 12px;">
            <i class="fas fa-shield-alt"></i> Your payment is secured by PayTR. Sinapp Social never stores your card details.
        </p>

        <button class="btn btn-primary coming-soon" style="width: 100%;">Pay with Credit Card</button>
    </div>
    <?php else: ?>
    <div class="card-section" style="opacity: 0.5; cursor: not-allowed;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <i class="fas fa-credit-card" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
            <h2>Credit Card</h2>
            <p style="color: var(--text-secondary);">Currently disabled.</p>
        </div>
        <p style="text-align: center; font-size: 0.8rem;">Credit card payments are temporarily disabled by the administrator.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
