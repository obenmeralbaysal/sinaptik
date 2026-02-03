<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Sinapp Social</title>
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
            display: block;
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

        .content-section {
            padding: 160px 5% 100px;
            max-width: 800px;
            margin: 0 auto;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .content-section h1 {
            color: var(--text-primary);
            font-size: 2.5rem;
            margin-bottom: 2rem;
        }

        .content-section h2 {
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 10000;
        }
    </style>
</head>

<body>
    <nav class="landing-nav">
        <a href="/"
            style="font-size: 1.5rem; font-weight: 800; color: var(--accent-color); text-decoration: none;">Sinapp
            Social</a>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <a href="/login" style="color: var(--text-primary); text-decoration: none; font-weight: 600;">Sign In</a>
            <a href="/register" class="btn btn-primary"
                style="padding: 0.75rem 1.75rem; border-radius: 50px; text-decoration: none; color: white; background-color: var(--accent-color);">Get
                Started</a>
        </div>
    </nav>

    <main class="content-section">
        <h1>Privacy Policy</h1>
        <p>Last updated:
            <?php echo date('F j, Y'); ?>
        </p>

        <h2>1. Introduction</h2>
        <p>Welcome to Sinapp Social. We respect your privacy and are committed to protecting your personal data. This
            privacy policy will inform you as to how we look after your personal data when you visit our website and
            tell you about your privacy rights and how the law protects you.</p>

        <h2>2. Data We Collect</h2>
        <p>We may collect, use, store and transfer different kinds of personal data about you which we have grouped
            together follows: Identity Data, Contact Data, Financial Data, Transaction Data, Technical Data, Profile
            Data, Usage Data, and Marketing and Communications Data.</p>

        <h2>3. How We Use Your Data</h2>
        <p>We will only use your personal data when the law allows us to. Most commonly, we will use your personal data
            in the following circumstances: Where we need to perform the contract we are about to enter into or have
            entered into with you. Where it is necessary for our legitimate interests (or those of a third party) and
            your interests and fundamental rights do not override those interests. Where we need to comply with a legal
            or regulatory obligation.</p>

        <h2>4. Data Security</h2>
        <p>We have put in place appropriate security measures to prevent your personal data from being accidentally
            lost, used or accessed in an unauthorized way, altered or disclosed.</p>

        <h2>5. Contact Us</h2>
        <p>If you have any questions about this privacy policy or our privacy practices, please contact us.</p>
    </main>

    <footer
        style="text-align: center; padding: 5rem 5%; background: var(--sidebar-bg); color: var(--text-secondary); border-top: 1px solid var(--border-color);">
        <div style="margin-bottom: 2rem;">
            <a href="/privacy-policy"
                style="color: var(--text-secondary); text-decoration: none; margin: 0 1rem;">Privacy Policy</a>
            <a href="/terms-of-use" style="color: var(--text-secondary); text-decoration: none; margin: 0 1rem;">Terms
                of Use</a>
        </div>
        <p>&copy; 2026 Sinapp Social. English (Global). <br><br> Securely scale your social presence.</p>
    </footer>

    <div id="theme-toggle" class="theme-float">
        <i class="fas fa-moon"></i>
    </div>

    <script src="/js/main.js"></script>
</body>

</html>