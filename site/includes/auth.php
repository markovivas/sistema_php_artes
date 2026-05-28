<?php
require_once __DIR__ . '/db.php';

class Auth {
    public static function login($email, $password) {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE email = ? AND active = 1", [$email]);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];
            return true;
        }
        return false;
    }

    public static function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function user() {
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'avatar' => $_SESSION['user_avatar']
        ];
    }

    public static function role() {
        return $_SESSION['user_role'] ?? null;
    }

    public static function requireLogin() {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }

    public static function requireRole($roles) {
        self::requireLogin();
        if (!in_array(self::role(), (array)$roles)) {
            header('HTTP/1.0 403 Forbidden');
            die('Acesso negado.');
        }
    }

    public static function hasRole($roles) {
        return in_array(self::role(), (array)$roles);
    }

    public static function register($data) {
        $db = Database::getInstance();

        $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($existing) {
            return 'Este e-mail já está cadastrado.';
        }

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId = $db->insert(
            "INSERT INTO users (name, email, password, role, whatsapp) VALUES (?, ?, ?, 'client', ?)",
            [$data['name'], $data['email'], $hash, $data['whatsapp'] ?? null]
        );

        if (!empty($data['company'])) {
            $db->insert(
                "INSERT INTO clients (user_id, company, phone) VALUES (?, ?, ?)",
                [$userId, $data['company'], $data['whatsapp'] ?? null]
            );
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_email'] = $data['email'];
        $_SESSION['user_role'] = 'client';
        $_SESSION['user_avatar'] = null;

        return true;
    }
}
