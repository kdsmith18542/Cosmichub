<?php

namespace App\Controllers;

use App\Models\Archetype;
use App\Models\ArchetypeComment;
use App\Core\Controller;
use App\Core\View;

class ArchetypeController extends Controller
{
    public function index()
    {
        $archetypes = Archetype::orderBy('name')->get();
        View::render('archetypes/index', ['archetypes' => $archetypes, 'title' => 'Archetype Hubs']);
    }

    public function show($slug)
    {
        $archetype = Archetype::findBySlug($slug);
        if (!$archetype) {
            View::render('archetypes/show', ['archetype' => null, 'title' => 'Archetype Not Found']);
            return;
        }
        $comments = $archetype->comments()->where('is_moderated', true)->orderBy('created_at', 'desc')->get();
        $famousPeople = $archetype->celebrities()->orderBy('name')->get();
        $user = null;
        $hasActiveSubscription = false;
        $referralUrl = null;
        $hasEnoughReferrals = false;
        $remainingReferrals = 3;
        if (isset($_SESSION['user_id'])) {
            $user = \App\Models\User::findById($_SESSION['user_id']);
            if ($user) {
                $hasActiveSubscription = $user->hasActiveSubscription();
                // Archetype-specific referral
                $referral = \App\Models\Referral::createForUser($user->id, 'archetype-unlock', $archetype->id);
                $referralUrl = $referral->getReferralUrl();
                $hasEnoughReferrals = $referral->hasEnoughReferrals(3);
                $remainingReferrals = max(0, 3 - $referral->successful_referrals);
            }
        }
        View::render('archetypes/show', [
            'archetype' => $archetype,
            'comments' => $comments,
            'famousPeople' => $famousPeople,
            'title' => $archetype->name . ' - Archetype Hub',
            'user' => $user,
            'hasActiveSubscription' => $hasActiveSubscription,
            'referralUrl' => $referralUrl,
            'hasEnoughReferrals' => $hasEnoughReferrals,
            'remainingReferrals' => $remainingReferrals
        ]);
    }

    public function storeComment($archetypeSlug)
    {
        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            // Redirect to login or show an error
            // For now, just a simple redirect
            header('Location: /login');
            exit;
        }

        $archetype = Archetype::findBySlug($archetypeSlug);
        if (!$archetype) {
            // Handle archetype not found
            // Potentially set a flash message and redirect back
            header('Location: /archetypes');
            exit;
        }

        // Basic validation
        if (empty($_POST['comment'])) {
            // Set a flash message for error
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Comment cannot be empty.'];
            header('Location: /archetypes/' . $archetypeSlug);
            exit;
        }

        ArchetypeComment::create([
            'archetype_id' => $archetype->id,
            'user_id' => $_SESSION['user_id'],
            'comment' => sanitize_input($_POST['comment'] ?? ''),
            'is_moderated' => false, // Comments require moderation by default
        ]);

        // Set a flash message for success
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Your comment has been submitted and is awaiting moderation.'];
        header('Location: /archetypes/' . $archetypeSlug);
        exit;
    }
}