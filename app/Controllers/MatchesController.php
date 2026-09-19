<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\View;
use App\Services\MatchingService;

class MatchesController {
    public function index(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $tab = $_GET['tab'] ?? 'matches';
        if (!in_array($tab, ['matches', 'received', 'sent'], true)) {
            $tab = 'matches';
        }

        $matches = MatchingService::getMatches($user['id']);
        $received = MatchingService::getInterestsReceived($user['id']);
        $sent = MatchingService::getInterestsSent($user['id']);

        View::render('matches/index', [
            'currentTab' => $tab,
            'matches' => $matches,
            'received' => $received,
            'sent' => $sent,
            'matchesCount' => count($matches),
            'receivedCount' => count($received),
            'sentCount' => count($sent),
        ], 'main');
    }
}
