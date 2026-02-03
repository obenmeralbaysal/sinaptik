<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function login()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
            exit;
        }
        $this->render('auth/login', ['title' => 'Sign In']);
    }

    public function postLogin()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            // Check for email verification
            if (empty($user['verified_at'])) {
                $this->json([
                    'success' => false,
                    'error' => 'Please verify your email address before logging in.'
                ], 403);
            }

            // Temporary auto-promote for testing if not already admin
            if ($user['email'] === 'test@test.com' && $user['role'] !== 'admin') {
                $db = \App\Core\Database::getInstance();
                $db->prepare("UPDATE users SET role = 'admin' WHERE id = :id")->execute(['id' => $user['id']]);
                $user['role'] = 'admin';
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['is_admin'] = ($user['role'] === 'admin');

            $this->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect_url' => '/dashboard'
            ]);
        }

        $this->json([
            'success' => false,
            'error' => 'Invalid email or password.'
        ], 401);
    }

    public function register()
    {
        $this->render('auth/register', ['title' => 'Create Account']);
    }

    public function postRegister()
    {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (User::findByEmail($email)) {
            $this->json([
                'success' => false,
                'error' => 'This email is already in use.'
            ], 400);
        }

        $verificationToken = bin2hex(random_bytes(32));

        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'verification_token' => $verificationToken
        ]);

        if ($userId) {
            // Send verification email
            $verifyLink = \App\Core\Config::get('APP_URL') . "/verify-email?token=" . $verificationToken;
            $body = "Welcome to Sinapp Social!\n\nPlease click the link below to verify your email address:\n\n" . $verifyLink;
            \App\Services\MailService::send($email, "Verify Your Email - Sinapp Social", $body);

            $this->json([
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account.',
                'redirect_url' => '/login'
            ]);
        }

        $this->json([
            'success' => false,
            'error' => 'An error occurred while creating your account.'
        ], 500);
    }

    public function verifyEmail()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header('Location: /login');
            exit;
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE verification_token = :token");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $db->prepare("UPDATE users SET verified_at = NOW(), verification_token = NULL WHERE id = :id");
            $update->execute(['id' => $user['id']]);

            $this->render('auth/login', [
                'title' => 'Sign In',
                'success' => 'Email verified successfully! You can now log in.'
            ]);
        } else {
            $this->render('auth/login', [
                'title' => 'Sign In',
                'error' => 'Invalid or expired verification token.'
            ]);
        }
    }

    public function forgotPassword()
    {
        $this->render('auth/forgot_password', ['title' => 'Forgot Password']);
    }

    public function postForgotPassword()
    {
        $email = $_POST['email'] ?? '';
        $user = User::findByEmail($email);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            User::setResetToken($user['id'], $token);

            $resetLink = \App\Core\Config::get('APP_URL') . "/reset-password?token=" . $token;
            $body = "Click the link below to reset your password:\n\n" . $resetLink;

            \App\Services\MailService::send($email, "Password Reset - Sinapp Social", $body);

            $this->json([
                'success' => true,
                'message' => 'A password reset link has been sent to your email.',
                'redirect_url' => '/login'
            ]);
        } else {
            $this->json([
                'success' => false,
                'error' => 'Email address not found.'
            ], 404);
        }
    }

    public function logout()
    {
        session_destroy();
        header('Location: /');
        exit;
    }
}
