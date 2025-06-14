<?php

namespace App\Controllers;

use Exception;
use App\Services\RarityScoreService;
use App\Services\UserService;
use App\Services\AuthService;
use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;
use Psr\Log\LoggerInterface;

/**
 * Rarity Score Controller
 * 
 * Handles birthday rarity score calculation and display
 * Refactored to use service layer for business logic separation
 */
class RarityScoreController extends Controller
{
    /**
     * @var RarityScoreService The rarity score service
     */
    private RarityScoreService $rarityScoreService;
    
    /**
     * @var UserService The user service
     */
    private UserService $userService;
    
    /**
     * @var AuthService The authentication service
     */
    private AuthService $authService;
    
    /**
     * Constructor - inject dependencies
     */
    public function __construct(
        RarityScoreService $rarityScoreService,
        UserService $userService,
        AuthService $authService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->rarityScoreService = $rarityScoreService;
        $this->userService = $userService;
        $this->authService = $authService;
        $this->logger = $logger;
    }
    /**
     * Calculate the rarity score for a given birthdate
     * 
     * @param string $birthdate The birthdate in Y-m-d format
     * @return int The rarity score (1-100)
     */
    public function calculateRarityScore($birthdate) {
        try {
            // Parse the birthdate
            $date = new DateTime($birthdate);
            $month = (int)$date->format('n'); // 1-12
            $day = (int)$date->format('j');   // 1-31
            
            // Base rarity factors
            $monthRarity = $this->getMonthRarityFactor($month);
            $dayRarity = $this->getDayRarityFactor($month, $day);
            $specialDateRarity = $this->getSpecialDateRarity($month, $day);
            $leapYearRarity = $this->getLeapYearRarity($month, $day, (int)$date->format('Y'));
            
            // Calculate final score (1-100 scale)
            $rawScore = $monthRarity + $dayRarity + $specialDateRarity + $leapYearRarity;
            
            // Normalize to 1-100 scale
            $normalizedScore = min(100, max(1, $rawScore));
            
            return $normalizedScore;
            
        } catch (Exception $e) {
            $this->logger->error('Error calculating rarity score: ' . $e->getMessage(), ['exception' => $e]);
            return 50; // Default middle score in case of error
        }
    }
    
    // Note: All calculation methods have been moved to RarityScoreService
    // This follows the refactoring plan to separate business logic from controllers
    
    /**
     * Main page handler
     */
    public function index(Request $request, Response $response): Response
    {
        // Check if user is logged in
        if (!$this->authService->isLoggedIn($request)) {
            return $response->redirect('/login');
        }
        
        $user = $this->authService->getCurrentUser($request);
        
        try {
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Get rarity score data using service
            $rarityData = $this->rarityScoreService->getUserRarityData($user);
            $hasRarityScore = $rarityData !== null;
            
            // Handle referral system using service
            $referralStatus = $this->rarityScoreService->getReferralStatus($user, 3);
            
            // Handle incoming referral
            $ref = $request->query('ref');
            if ($ref) {
                $this->rarityScoreService->handleReferral($ref, $user['id']);
            }
            
            // Check for active subscription
            $hasActiveSubscription = $user->hasActiveSubscription();
            
            // Prepare data for view
            $data = [
                'user' => $user,
                'rarityData' => $rarityData,
                'hasRarityScore' => $hasRarityScore,
                'referral' => $referralStatus['referral'],
                'referralUrl' => $referralStatus['referralUrl'],
                'hasEnoughReferrals' => $referralStatus['hasEnoughReferrals'],
                'remainingReferrals' => $referralStatus['remainingReferrals'],
                'successfulReferrals' => $referralStatus['successfulReferrals'],
                'hasActiveSubscription' => $hasActiveSubscription,
                'pageTitle' => 'Birthday Rarity Score',
                'pageDescription' => 'Discover how rare your birthday is!'
            ];
            
            // Load the view
            return $response->render('rarity-score', $data);
            
        } catch (Exception $e) {
            // Log error and show error page
            $this->logger->error('RarityScore Error: ' . $e->getMessage(), ['exception' => $e]);
            return $response->render('error', [
                'error' => 'Unable to load rarity score page. Please try again later.',
                'pageTitle' => 'Error'
            ]);
        }
    }
    
    /**
     * AJAX endpoint to calculate rarity score
     */
    public function calculate(Request $request, Response $response): Response
    {
        try {
            // Check if user is logged in
            if (!$this->authService->isLoggedIn($request)) {
                return $response->json([
                    'success' => false,
                    'error' => 'User not logged in'
                ]);
            }
            
            // Get birthdate from POST data
            $birthdate = $request->input('birthdate', '');
            
            // Validate birthdate format
            if (empty($birthdate)) {
                return $response->json([
                    'success' => false,
                    'error' => 'Birthdate is required'
                ]);
            }
            
            // Calculate rarity score using service
            $result = $this->rarityScoreService->calculateRarityScore($birthdate);
            
            return $response->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            return $response->json([
                'success' => false,
                'error' => 'Unable to calculate rarity score: ' . $e->getMessage()
            ]);
        }
    }
    
    // Note: All helper methods have been moved to RarityScoreService
    // This controller now focuses solely on HTTP request/response handling
}