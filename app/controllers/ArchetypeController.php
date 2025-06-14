<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\GeminiService;
use App\Services\UserService;
use App\Services\ArchetypeService;
use App\Services\AuthService; // Added AuthService
use App\Models\ArchetypeComment; // Keep for now, or move to service
use Psr\Log\LoggerInterface;
use Exception;
use App\Core\View; // Added View for rendering

class ArchetypeController extends Controller
{
    private GeminiService $geminiService;
    private UserService $userService;
    private ArchetypeService $archetypeService;
    private AuthService $authService;
    private LoggerInterface $logger;

    public function __construct(
        GeminiService $geminiService,
        UserService $userService,
        ArchetypeService $archetypeService,
        AuthService $authService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->geminiService = $geminiService;
        $this->userService = $userService;
        $this->archetypeService = $archetypeService;
        $this->authService = $authService;
        $this->logger = $logger;
    }
    public function index()
    {
        $result = $this->archetypeService->getActiveArchetypes();
        $archetypes = $result['success'] ? $result['data'] : [];
        return View::render('archetypes/index', ['archetypes' => $archetypes, 'title' => 'Archetype Hubs']);
    }

    public function show(Request $request, Response $response, $slug)
    {
        $result = $this->archetypeService->getArchetypeBySlug($slug);
        if (!$result['success']) {
            return View::render('archetypes/show', ['archetype' => null, 'title' => 'Archetype Not Found']);
        }
        
        $archetype = $result['data'];
        $comments = $archetype->comments()->where('is_moderated', true)->orderBy('created_at', 'desc')->get();
        $famousPeople = $archetype->celebrities()->orderBy('name')->get();
        $user = null;
        $hasActiveSubscription = false;
        $referralUrl = null;
        $hasEnoughReferrals = false;
        $remainingReferrals = 3;
        
        if ($this->authService->isLoggedIn($request)) {
            $user = $this->authService->getCurrentUser($request);
            if ($user) {
                $hasActiveSubscription = $user->hasActiveSubscription();
                // Archetype-specific referral
                $referral = \App\Models\Referral::createForUser($user->id, 'archetype-unlock', $archetype->id);
                $referralUrl = $referral->getReferralUrl();
                $hasEnoughReferrals = $referral->hasEnoughReferrals(3);
                $remainingReferrals = max(0, 3 - $referral->successful_referrals);
            }
        }

        // Generate premium archetype insights for subscribers or users with enough referrals
        $premiumArchetypeContent = null;
        if ($user && ($hasActiveSubscription || $hasEnoughReferrals)) {
            try {
                $prompt = "Generate insightful content for the '{$archetype->name}' archetype, described as: '{$archetype->description}'. Explore its characteristics, strengths, and shadows. Format as a detailed, engaging paragraph.";
                $premiumArchetypeContent = $this->geminiService->generateArchetypeInsights($prompt);
            } catch (Exception $e) {
                $this->logger->error('Error generating premium archetype content: ' . $e->getMessage());
                $premiumArchetypeContent = null;
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
            'remainingReferrals' => $remainingReferrals,
            'premiumArchetypeContent' => $premiumArchetypeContent
        ]);
    }

    // Method to show the form for creating a new archetype
    public function create(Request $request, Response $response)
    {
        if (!$this->authService->isAdmin($request)) {
            $this->session->setFlash('error', 'You do not have permission to create archetypes.');
            return $response->redirect('/archetypes');
        }
        return View::render('archetypes/create', ['title' => 'Create New Archetype']);
    }

    // Method to store a new archetype
    public function store(Request $request, Response $response)
    {
        if (!$this->authService->isAdmin($request)) {
            $this->session->setFlash('error', 'You do not have permission to create archetypes.');
            return $response->redirect('/archetypes');
        }

        $name = $request->input('name');
        $description = $request->input('description');

        if (empty($name) || empty($description)) {
            $this->session->setFlash('error', 'Name and description are required.');
            return $response->redirect('/archetypes/create');
        }

        $data = [
            'name' => sanitize_input($name),
            'description' => sanitize_input($description),
            // Slug will be generated by the model's boot method or a mutator
        ];
        
        $result = $this->archetypeService->createArchetype($data);

        if ($result['success']) {
            $archetype = $result['data'];
            if ($this->geminiService) { // Check if service was initialized
                $this->generateAndStoreAiContent($archetype);
            }
            $this->session->setFlash('success', 'Archetype created successfully!');
            return $response->redirect('/archetypes/' . $archetype['slug']);
        } else {
            $this->session->setFlash('error', $result['message'] ?? 'Failed to create archetype.');
            return $response->redirect('/archetypes/create');
        }
    }

    private function generateAndStoreAiContent($archetype)
    {
        if (!$this->geminiService) {
             $this->logger->warning('GeminiService not available. Skipping AI content generation for archetype: ' . $archetype['name']);
             return;
        }

        try {
            // Generate AI content using Gemini
            $prompt = "Generate insightful content for the '{$archetype['name']}' archetype, described as: '{$archetype['description']}'. Explore its characteristics, strengths, and shadows. Format as a detailed, engaging paragraph.";
            $aiContent = $this->geminiService->generateArchetypeInsights($prompt);
            
            if ($aiContent) {
                $updateData = [
                    'ai_generated_content' => json_encode(['content' => $aiContent])
                ];
                
                $this->archetypeService->updateArchetype($archetype['id'], $updateData);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error generating AI content for archetype ' . $archetype['id'] . ': ' . $e->getMessage());
        }
    }

    public function storeComment(Request $request, Response $response, $archetypeSlug)
    {
        if (!$this->authService->isLoggedIn($request)) {
            $this->session->setFlash('error', 'You must be logged in to comment.');
            return $response->redirect('/login');
        }
        
        $user = $this->authService->getCurrentUser($request);

        $result = $this->archetypeService->getArchetypeBySlug($archetypeSlug);
        if (!$result['success']) {
            $this->session->setFlash('error', 'Archetype not found.');
            return $response->redirect('/archetypes');
        }
        
        $archetype = $result['data'];
        $commentText = $request->input('comment');

        // Basic validation
        if (empty($commentText)) {
            $this->session->setFlash('error', 'Comment cannot be empty.');
            return $response->redirect('/archetypes/' . $archetypeSlug);
        }

        $commentData = [
            'archetype_id' => $archetype['id'],
            'user_id' => $user->id,
            'comment' => sanitize_input($commentText),
            'is_moderated' => false, // Comments require moderation by default
        ];

        $commentResult = $this->archetypeService->createArchetypeComment($commentData);

        if ($commentResult['success']) {
            $this->session->setFlash('success', 'Your comment has been submitted and is awaiting moderation.');
        return $response->redirect('/archetypes/' . $archetypeSlug);
    }

    // Placeholder for admin check, assuming AuthService will have this
    private function isAdmin(Request $request): bool
    {
        return $this->authService->isAdmin($request);
    }
} // Ensure this is the last line if adding methods
        } else {
            $this->session->setFlash('error', $commentResult['message'] ?? 'Failed to submit comment.');
        }
        return $response->redirect('/archetypes/' . $archetypeSlug);
    }

    // Placeholder for admin check, assuming AuthService will have this
    private function isAdmin(Request $request): bool
    {
        return $this->authService->isAdmin($request);
    }
} // Ensure this is the last line if adding methods

// Ensure there's a newline at the end of the file if it's missing.
        header('Location: /archetypes/' . $archetypeSlug);
        exit;
    }
}