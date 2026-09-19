<?php

namespace App\Helpers;

class View {
    public static function render(string $viewPath, array $data = [], string $layout = 'main'): void {
        extract($data);
        
        // Expose helpers directly to views
        $currentUser = Auth::user();
        $csrfToken = Session::csrfToken();
        $flashSuccess = Session::getFlash('success');
        $flashError = Session::getFlash('error');

        $baseDir = dirname(__DIR__, 2);
        $viewFile = $baseDir . '/views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            die("View [{$viewPath}] not found.");
        }

        // Render view content to buffer
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        if ($layout === 'none') {
            echo $content;
            return;
        }

        $layoutFile = $baseDir . '/views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function renderAdmin(string $viewPath, array $data = []): void {
        extract($data);
        $currentAdmin = AdminAuth::admin();
        $csrfToken = Session::csrfToken();
        $flashSuccess = Session::getFlash('success');
        $flashError = Session::getFlash('error');

        $baseDir = dirname(__DIR__, 2);
        $viewFile = $baseDir . '/views/admin/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            $viewFile = $baseDir . '/admin/views/' . $viewPath . '.php';
        }

        if (!file_exists($viewFile)) {
            http_response_code(404);
            die("Admin View [{$viewPath}] not found.");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Login page has its own standalone container
        if ($viewPath === 'login') {
            echo $content;
            return;
        }

        // Fetch dynamic counts for sidebar badges
        $sidebarCounts = [
            'pending_kyc' => (int)(Database::one("SELECT COUNT(*) as c FROM kyc_records WHERE status = 'pending'")['c'] ?? 0),
            'open_reports' => (int)(Database::one("SELECT COUNT(*) as c FROM reports WHERE status = 'open'")['c'] ?? 0),
            'active_boosts' => (int)(Database::one("SELECT COUNT(*) as c FROM boosts WHERE status = 'active' AND expires_at > NOW()")['c'] ?? 0),
            'maintenance_mode' => ((Database::one("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'")['setting_value'] ?? '0') === '1'),
        ];

        // Dedicated Super Admin Layout
        $layoutFile = $baseDir . '/views/admin/layout.php';
        if (!file_exists($layoutFile)) {
            $layoutFile = $baseDir . '/admin/views/layout.php';
        }

        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    public static function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
