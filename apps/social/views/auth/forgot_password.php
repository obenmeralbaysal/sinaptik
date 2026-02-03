<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Sinapp Social</title>
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'light') {
                document.documentElement.classList.add('light-mode');
            }
        })();
    </script>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        .auth-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 2.5rem;
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }

        .auth-card h2 {
            margin-bottom: 1rem;
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-color);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
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

        .btn-auth {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 12px;
            background: var(--accent-color);
            color: var(--btn-text);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 1rem;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            opacity: 0.95;
            box-shadow: 0 5px 15px var(--accent-glow);
        }

        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="auth-card">
        <h2>Sinapp Social</h2>
        <p style="color: var(--text-secondary); text-align: center; margin-bottom: 1.5rem; font-size: 0.9rem;">
            Forgot Password? Enter your email and we'll send you a link to reset it.
        </p>

        <?php if (isset($success)): ?>
            <div style="color: var(--success); text-align: center; margin-bottom: 1rem;"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div style="color: var(--error); text-align: center; margin-bottom: 1rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="/forgot-password" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required
                    placeholder="john@example.com">
            </div>
            <button type="submit" class="btn-auth">Send Reset Link</button>
        </form>

        <div class="auth-footer">
            <a href="/login">Back to Sign In</a>
        </div>
    </div>
</body>

</html>