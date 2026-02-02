<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinapp Social - AI Powered Social Automation</title>
    <script>
        const theme = localStorage.getItem('theme') || 'dark';
        if (theme === 'light') {
            document.documentElement.classList.add('light-mode');
            window.addEventListener('DOMContentLoaded', () => document.body.classList.add('light-mode'));
        }
    </script>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: var(--bg-color);
            margin: 0;
            overflow-x: hidden;
            display: block; /* Override default sidebar flex */
        }
        .landing-nav {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--sidebar-bg);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            box-sizing: border-box;
        }
        .hero {
            padding: 160px 5% 100px;
            text-align: center;
            background: radial-gradient(circle at top right, var(--accent-glow), transparent);
        }
        .hero h1 {
            font-size: clamp(2.5rem, 8vw, 5rem);
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            line-height: 1.1;
        }
        .hero p {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto 3rem;
        }
        .cta-container {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }
        .btn-xl {
            padding: 1.25rem 3rem;
            font-size: 1.125rem;
            border-radius: 100px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-filled {
            background-color: var(--accent-color);
            color: #fff;
            box-shadow: 0 10px 20px var(--accent-glow);
        }
        .btn-filled:hover {
            transform: translateY(-4px);
            opacity: 0.9;
            box-shadow: 0 15px 30px var(--accent-glow);
        }
        .btn-outline {
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .features-grid {
            padding: 100px 5%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
            max-width: 1300px;
            margin: 0 auto;
        }
        .feature-item {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 3.5rem;
            border-radius: 32px;
            transition: all 0.3s ease;
        }
        .feature-item:hover {
            transform: translateY(-10px);
            border-color: var(--accent-color);
        }
        .icon-box {
            font-size: 3rem;
            margin-bottom: 2rem;
            display: inline-block;
        }
        .feature-item h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        .feature-item p {
            color: var(--text-secondary);
            line-height: 1.7;
        }
        .pricing-section {
            padding: 100px 5%;
            text-align: center;
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 50px auto 0;
        }
        .plan-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 3rem;
            border-radius: 32px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        .plan-card:hover {
            transform: scale(1.05);
            border-color: var(--accent-color);
            box-shadow: 0 20px 40px var(--accent-glow);
        }
        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .plan-price {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 2rem;
        }
        .plan-price span {
            font-size: 1rem;
            color: var(--text-secondary);
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0 0 3rem;
            text-align: left;
            flex-grow: 1;
        }
        .feature-list li {
            margin-bottom: 1rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .feature-list li::before {
            content: '✓';
            color: var(--success);
            font-weight: 900;
        }
        .theme-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--sidebar-bg);
            border: 1px solid var(--border-color);
            padding: 1rem;
            border-radius: 50%;
            cursor: pointer;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 10000;
        }
    </style>
</head>
<body>
    <nav class="landing-nav">
        <a href="/" style="font-size: 1.5rem; font-weight: 800; color: var(--accent-color); text-decoration: none;">Sinapp Social</a>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="/login" style="color: var(--text-primary); text-decoration: none; font-weight: 600;">Sign In</a>
            <a href="/register" class="btn btn-primary" style="padding: 0.75rem 1.75rem; border-radius: 50px; text-decoration: none;">Get Started</a>
        </div>
    </nav>

    <main>
        <section class="hero">
            <h1>Scale Your Social Impact <br><span style="color: var(--accent-color);">Without Lifting a Finger.</span></h1>
            <p>Automated content creation and engagement that sounds exactly like you. Powered by Gemini & GPT-4.</p>
            <div class="cta-container">
                <a href="/register" class="btn-xl btn-filled">Start 1-Day Free Trial</a>
                <a href="#features" class="btn-xl btn-outline">See How It Works</a>
            </div>
        </section>

        <section id="features" class="features-grid">
            <div class="feature-item">
                <span class="icon-box">🧠</span>
                <h3>Deep AI Context</h3>
                <p>We don't just post. We understand your brand voice and industry context to generate meaningful engagement.</p>
            </div>
            <div class="feature-item">
                <span class="icon-box">🎯</span>
                <h3>Strategic Replying</h3>
                <p>Target key influencers and users with tailored responses in supportive, ironic, or critical tones.</p>
            </div>
            <div class="feature-item">
                <span class="icon-box">⚡</span>
                <h3>Autopilot Mode</h3>
                <p>Set your daily limits and let Sinapp handle the rest. Real human-like distribution for maximum reach.</p>
            </div>
        </section>

        <section id="pricing" class="pricing-section">
            <h2 style="font-size: 3rem; font-weight: 800; margin-bottom: 1rem;">Simple, Transparent Pricing</h2>
            <p style="color: var(--text-secondary);">Choose the plan that's right for your growth.</p>
            
            <div class="pricing-grid">
                <?php foreach ($plans as $plan): ?>
                <div class="plan-card">
                    <div class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></div>
                    <div class="plan-price"><?php echo number_format($plan['price_monthly'] ?? 0, 0); ?>₺ <span>/ mo</span></div>
                    <ul class="feature-list">
                        <?php 
                        $features = $plan['features'];
                        if (is_string($features)) {
                            $features = json_decode($features, true) ?: [];
                        }
                        foreach ($features as $feature): 
                        ?>
                        <li><?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="/register?plan_id=<?php echo $plan['id']; ?>" class="btn btn-primary" style="border-radius: 50px; padding: 1rem;">Get Started</a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer style="text-align: center; padding: 5rem 5%; background: var(--sidebar-bg); color: var(--text-secondary); border-top: 1px solid var(--border-color);">
        <p>&copy; 2026 Sinapp Social. English (Global). <br><br> Securely scale your social presence.</p>
    </footer>

    <div id="theme-toggle" class="theme-float">
        <i class="fas fa-moon"></i>
    </div>

    <script src="/js/main.js"></script>
</body>
</html>
