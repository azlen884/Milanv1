<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Helpers\Security;
use App\Helpers\RateLimiter;
use App\Helpers\View;
use App\Services\SubscriptionService;

class AuthController {
    public function showRegister(): void {
        if (Auth::check()) {
            header('Location: /home');
            exit;
        }

        $cities = ['Mumbai', 'Delhi NCR', 'Bengaluru', 'Pune', 'Hyderabad', 'Chennai', 'Kolkata', 'Ahmedabad', 'Jaipur', 'Chandigarh', 'Lucknow', 'Indore'];
        View::render('auth/register', ['cities' => $cities], 'main');
    }

    public function register(): void {
        $ip = RateLimiter::getClientIp();
        if (!RateLimiter::check('register', $ip, 5, 3600)) {
            Session::flash('error', 'Too many registration attempts. Please try again later.');
            header('Location: /register');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token. Please refresh and try again.');
            header('Location: /register');
            exit;
        }

        $name = Security::sanitize($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $city = Security::sanitize($_POST['city'] ?? '');

        // Validation
        if (empty($name) || empty($email) || empty($password) || empty($gender) || empty($dob) || empty($city)) {
            Session::flash('error', 'All fields are required.');
            header('Location: /register');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid email address.');
            header('Location: /register');
            exit;
        }

        if (strlen($password) < 8) {
            Session::flash('error', 'Password must be at least 8 characters long.');
            header('Location: /register');
            exit;
        }

        // STRICT 18+ REQUIREMENT (Rule 9)
        if (!Security::isAtLeast18($dob)) {
            Session::flash('error', 'You must be at least 18 years old to create a profile.');
            header('Location: /register');
            exit;
        }

        $age = Security::calculateAge($dob);

        // Check if email already registered
        $existing = Database::one("SELECT id FROM users WHERE email = :email", [':email' => $email]);
        if ($existing) {
            Session::flash('error', 'An account with this email address already exists. Please sign in.');
            header('Location: /login');
            exit;
        }

        // Create User
        $passHash = Security::hashPassword($password);
        $userId = Database::insert(
            "INSERT INTO users (email, password_hash, status, created_at, last_active_at) 
             VALUES (:email, :pass, 'active', NOW(), NOW())",
            [':email' => $email, ':pass' => $passHash]
        );

        // Create Profile
        Database::insert(
            "INSERT INTO user_profiles (user_id, name, dob, age, gender, city, created_at)
             VALUES (:uid, :name, :dob, :age, :gender, :city, NOW())",
            [
                ':uid' => $userId,
                ':name' => $name,
                ':dob' => $dob,
                ':age' => $age,
                ':gender' => $gender,
                ':city' => $city,
            ]
        );

        // Create Default Dating Preferences
        $interestedGender = ($gender === 'male') ? 'female' : (($gender === 'female') ? 'male' : 'all');
        Database::insert(
            "INSERT INTO dating_preferences (user_id, interested_in_gender, age_min, age_max)
             VALUES (:uid, :gend, 18, 55)",
            [':uid' => $userId, ':gend' => $interestedGender]
        );

        // AUTOMATICALLY ASSIGN FREE SUBSCRIPTION (Rule 9, 11)
        SubscriptionService::assignFreePlan($userId);

        // Log user in
        Auth::login($userId);
        Session::flash('success', "Welcome to Milan, {$name}! Your account has been created.");
        header('Location: /home');
        exit;
    }

    public function showLogin(): void {
        if (Auth::check()) {
            header('Location: /home');
            exit;
        }

        View::render('auth/login', [], 'main');
    }

    public function login(): void {
        $ip = RateLimiter::getClientIp();
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!RateLimiter::check('login', $ip . ':' . $email, 6, 900)) {
            Session::flash('error', 'Too many failed attempts. Please try again after 15 minutes.');
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /login');
            exit;
        }

        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            Session::flash('error', 'Please enter your email and password.');
            header('Location: /login');
            exit;
        }

        $user = Database::one(
            "SELECT id, email, password_hash, status FROM users WHERE email = :email LIMIT 1",
            [':email' => $email]
        );

        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            Session::flash('error', 'Incorrect email or password.');
            header('Location: /login');
            exit;
        }

        if ($user['status'] === 'banned') {
            Session::flash('error', 'This account has been banned due to terms violations.');
            header('Location: /login');
            exit;
        }

        if ($user['status'] === 'suspended') {
            Session::flash('error', 'Your account is temporarily suspended. Please contact support.');
            header('Location: /login');
            exit;
        }

        Auth::login((int)$user['id']);
        header('Location: /home');
        exit;
    }

    public function logout(): void {
        Auth::logout();
        header('Location: /');
        exit;
    }

    public function deleteAccount(): void {
        $userId = Auth::id();
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /settings/account');
            exit;
        }

        $password = $_POST['password'] ?? '';
        $user = Database::one("SELECT password_hash FROM users WHERE id = :id", [':id' => $userId]);

        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            Session::flash('error', 'Incorrect password. Account was not deleted.');
            header('Location: /settings/account');
            exit;
        }

        // Delete user (cascades via foreign keys)
        Database::execute("DELETE FROM users WHERE id = :id", [':id' => $userId]);
        Auth::logout();

        Session::flash('success', 'Your account and all associated data have been permanently deleted.');
        header('Location: /');
        exit;
    }
}
