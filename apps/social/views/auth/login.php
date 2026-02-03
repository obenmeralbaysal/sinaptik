<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Sinapp Social</title>
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/js/ajax-form.js" defer></script>
    <style>
        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            transition: all var(--transition-speed);
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 3rem;
            border-radius: 24px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .login-card h2 {
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            background: var(--accent-color);
            color: var(--btn-text);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            opacity: 0.95;
            box-shadow: 0 5px 15px var(--accent-glow);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <h2>Sinapp Social</h2>

        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required
                    placeholder="admin@sinapp.social">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                    placeholder="••••••••">
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="auth-footer" style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
            <a href="/forgot-password" style="color: var(--text-secondary); text-decoration: none;">Forgot
                Password?</a><br><br>
            Don't have an account? <a href="/register"
                style="color: var(--accent-color); text-decoration: none; font-weight: 600;">Create Account</a>
        </div>
    </div>
</body>

</html>