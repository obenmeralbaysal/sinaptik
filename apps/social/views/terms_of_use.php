<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use - Sinapp Social</title>
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
        <h1>Terms of Use</h1>
        <p>Last updated:
            <?php echo date('F j, Y'); ?>
        </p>

        <h2>1. Acceptance of Terms</h2>
        <p>By accessing and using this website, you accept and agree to be bound by the terms and provision of this
            agreement.</p>

        <h2>2. Use License</h2>
        <p>Permission is granted to temporarily download one copy of the materials (information or software) on Sinapp
            Social's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a
            transfer of title.</p>

        <h2>3. Disclaimer</h2>
        <p>The materials on Sinapp Social's website are provided on an 'as is' basis. Sinapp Social makes no warranties,
            expressed or implied, and hereby disclaims and negates all other warranties including, without limitation,
            implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement
            of intellectual property or other violation of rights.</p>

        <h2>4. Limitations</h2>
        <p>In no event shall Sinapp Social or its suppliers be liable for any damages (including, without limitation,
            damages for loss of data or profit, or due to business interruption) arising out of the use or inability to
            use the materials on Sinapp Social's website.</p>

        <h2>5. Governing Law</h2>
        <p>These terms and conditions are governed by and construed in accordance with the laws and you irrevocably
            submit to the exclusive jurisdiction of the courts in that location.</p>
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