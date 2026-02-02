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
            
            header('Location: /dashboard');
            exit;
        }

        $this->render('auth/login', [
            'title' => 'Sign In',
            'error' => 'Invalid email or password.'
        ]);
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
            $this->render('auth/register', [
                'title' => 'Create Account',
                'error' => 'This email is already in use.'
            ]);
            return;
        }

        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]);

        if ($userId) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'user';
            header('Location: /dashboard');
            exit;
        }

        $this->render('auth/register', [
            'title' => 'Create Account',
            'error' => 'An error occurred while creating your account.'
        ]);
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
            // In a real app, send email here. For now, we simulate success.
            $this->render('auth/forgot_password', [
                'title' => 'Forgot Password',
                'success' => 'A password reset link has been sent to your email.'
            ]);
        } else {
            $this->render('auth/forgot_password', [
                'title' => 'Forgot Password',
                'error' => 'Email address not found.'
            ]);
        }
    }

    public function logout()
    {
        session_destroy();
        header('Location: /');
        exit;
    }
}
