<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\View;

class LandingController {
    public function index(): void {
        // If already logged in, redirect to home
        if (Auth::check()) {
            header('Location: /home');
            exit;
        }

        // Fetch landing marketing profiles ONLY from landing_marketing_profiles table (strictly isolated)
        $marketingProfiles = Database::query(
            "SELECT * FROM landing_marketing_profiles 
             WHERE is_active = 1 
             ORDER BY display_order ASC, id ASC LIMIT 8"
        );

        $siteName = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'site_name'")['setting_value'] ?? 'Milan Dating';
        $siteTagline = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'site_tagline'")['setting_value'] ?? 'Meaningful Connections for Modern Indians';

        View::render('landing/index', [
            'marketingProfiles' => $marketingProfiles,
            'siteName' => $siteName,
            'siteTagline' => $siteTagline,
        ], 'main');
    }
}
