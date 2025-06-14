<?php
namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Libraries\PdfGenerator;
use App\Helpers\AstrologyHelper;
use App\Controllers\RarityScoreController;
use App\Services\CelebrityReportService;
use App\Services\UserService;
use App\Services\AstrologyService;
use App\Services\RarityScoreService;
use App\Services\AuthService; // Added AuthService
use Exception;
use DateTime;
use Carbon\Carbon; // Added Carbon for date handling

class HomeController extends Controller
{
    private CelebrityReportService $celebrityReportService;
    private UserService $userService;
    private AstrologyService $astrologyService;
    private RarityScoreService $rarityScoreService;
    private AuthService $authService; // Added AuthService property

    public function __construct(
        CelebrityReportService $celebrityReportService,
        UserService $userService,
        AstrologyService $astrologyService,
        RarityScoreService $rarityScoreService,
        AuthService $authService // Injected AuthService
    ) {
        parent::__construct();
        $this->celebrityReportService = $celebrityReportService;
        $this->userService = $userService;
        $this->astrologyService = $astrologyService;
        $this->rarityScoreService = $rarityScoreService;
        $this->authService = $authService; // Assigned injected AuthService
    }
    
    /**
     * Display the viral landing page
     */
    public function index(Request $request, Response $response): Response
    {
        $title = 'Cosmic Hub - Reveal Your Blueprint';
        $description = 'Discover your unique cosmic identity in seconds. Get your personalized astrology and numerology snapshot instantly.';
        
        // Use no layout for the viral landing page since it's a standalone full-screen experience
        return $response->render('home/viral-landing', compact('title', 'description'), ['layout' => null]); // Assuming render can take layout option
    }

    /**
     * Generate instant cosmic snapshot from birthday
     */
    public function generateSnapshot(Request $request, Response $response): Response
    {
        if (!$request->isPost()) {
            return $response->redirect('/');
        }

        try {
            // Get and validate input
            $month = (int)$request->input('month', 0);
            $day = (int)$request->input('day', 0);
            $year = (int)$request->input('year', 0);

            if (!$this->isValidDate($month, $day, $year)) {
                $request->flash('error', 'Please enter a valid birth date.');
                return $response->redirect('/');
            }

            $birthDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dateObj = Carbon::createFromDate($year, $month, $day); // Using Carbon

            // Generate cosmic snapshot data
            $snapshotData = $this->generateCosmicSnapshot($dateObj, $month, $day, $year);

            // Create a unique URL slug for this birthday
            $slug = $this->generateBirthdaySlug($month, $day, $year);

            // Store snapshot in session for the generated page
            $request->setSession('cosmic_snapshot_' . $slug, $snapshotData);

            // Redirect to the permanent snapshot page
            return $response->redirect('/cosmic-snapshot/' . $slug);

        } catch (Exception $e) {
            $this->logger->error('Error generating cosmic snapshot: ' . $e->getMessage());
            $request->flash('error', 'Unable to generate your cosmic snapshot. Please try again.');
            return $response->redirect('/');
        }
    }

    /**
     * Display the cosmic snapshot page
     */
    public function showSnapshot(Request $request, Response $response, $slug): Response
    {
        // Try to get snapshot from session first
        $snapshotData = $request->getSession('cosmic_snapshot_' . $slug);

        if (!$snapshotData) {
            // If not in session, try to regenerate from slug
            $dateInfo = $this->parseBirthdaySlug($slug);
            if ($dateInfo) {
                $dateObj = Carbon::createFromDate($dateInfo['year'], $dateInfo['month'], $dateInfo['day']); // Using Carbon
                $snapshotData = $this->generateCosmicSnapshot($dateObj, $dateInfo['month'], $dateInfo['day'], $dateInfo['year']);
            }
        }

        if (!$snapshotData) {
            return $response->render('home/snapshot-not-found', ['title' => 'Cosmic Snapshot Not Found']);
        }

        // Check if user is logged in for unlock options
        $user = null;
        $hasActiveSubscription = false;
        $referralUrl = null;
        $hasEnoughReferrals = false;
        $remainingReferrals = 3;

        // Get authenticated user using AuthService (now injected)
        if ($this->authService->isLoggedIn($request)) {
            $user = $this->authService->getCurrentUser($request);
            if ($user && method_exists($user, 'hasActiveSubscription')) {
                $hasActiveSubscription = $user->hasActiveSubscription();
            }

            // Create referral for unlocking premium content
            $referral = $this->userService->createReferralForUser($user->id, 'cosmic-snapshot-unlock');
            $referralUrl = $referral->getReferralUrl();
            $hasEnoughReferrals = $referral->hasEnoughReferrals(3);
            $remainingReferrals = max(0, 3 - $referral->successful_referrals);
        }

        $data = [
            'title' => 'Your Cosmic Blueprint - ' . $snapshotData['birth_date_formatted'],
            'snapshotData' => $snapshotData,
            'user' => $user,
            'hasActiveSubscription' => $hasActiveSubscription,
            'referralUrl' => $referralUrl,
            'hasEnoughReferrals' => $hasEnoughReferrals,
            'remainingReferrals' => $remainingReferrals,
            'slug' => $slug
        ];

        return $response->render('home/cosmic-snapshot', $data);
    }

    /**
     * Generate cosmic snapshot data
     */
    private function generateCosmicSnapshot(Carbon $dateObj, $month, $day, $year) // Type hint Carbon
    {
        // 1. Cosmic Identity
        $westernZodiac = $this->astrologyService->getWesternZodiacSign($month, $day);
        $chineseZodiac = $this->astrologyService->getChineseZodiacSign($year);
        $birthstone = $this->astrologyService->getBirthstone($month);
        $birthFlower = $this->astrologyService->getBirthFlower($month);

        // 2. Rarity Score
         $rarityResult = $this->rarityScoreService->calculateRarityScore($dateObj->format('Y-m-d'));
         $rarityScore = $rarityResult['score'];
         $rarityDescription = $rarityResult['description'];
         $rarityColor = $rarityResult['color'];

        // 3. Day in History (simplified for now)
        $dayInHistory = $this->getDayInHistory($month, $day);
        
        // 4. Famous Birthday Twin
        $birthdayTwin = $this->getFamousBirthdayTwin($month, $day);

        return [
            'birth_date' => $dateObj->format('Y-m-d'),
            'birth_date_formatted' => $dateObj->format('F j, Y'),
            'cosmic_identity' => [
                'western_zodiac' => $westernZodiac,
                'chinese_zodiac' => $chineseZodiac,
                'birthstone' => $birthstone,
                'birth_flower' => $birthFlower
            ],
            'rarity_score' => [
                'score' => $rarityScore,
                'description' => $rarityDescription,
                'color' => $rarityColor
            ],
            'day_in_history' => $dayInHistory,
            'birthday_twin' => $birthdayTwin
        ];
    }

    /**
     * Get historical event for the date
     */
    private function getDayInHistory($month, $day)
    {
        // Simplified historical events - in production this would use an API
        $events = [
            '1-1' => 'New Year\'s Day celebrations began worldwide',
            '2-14' => 'Valentine\'s Day, the day of love and romance',
            '3-17' => 'St. Patrick\'s Day celebrations in Ireland',
            '4-1' => 'April Fool\'s Day pranks around the world',
            '7-4' => 'American Independence Day was celebrated',
            '10-31' => 'Halloween traditions were observed',
            '12-25' => 'Christmas Day was celebrated globally'
        ];

        $key = $month . '-' . $day;
        return $events[$key] ?? 'A significant day in history with its own unique energy';
    }

    /**
     * Get famous person born on this date
     */
    private function getFamousBirthdayTwin($month, $day)
    {
        // Try to find a celebrity from the database
        $celebrity = $this->celebrityReportService->getCelebrityByBirthDate($month, $day);
        
        if ($celebrity && count($celebrity) > 0) {
            return $celebrity[0]->name;
        }

        // Fallback famous people by date
        $famousPeople = [
            '1-1' => 'J.D. Salinger',
            '2-14' => 'Frederick Douglass',
            '3-17' => 'Nat King Cole',
            '4-1' => 'Sergei Rachmaninoff',
            '7-4' => 'Calvin Coolidge',
            '10-31' => 'John Keats',
            '12-25' => 'Isaac Newton'
        ];

        $key = $month . '-' . $day;
        return $famousPeople[$key] ?? 'A notable historical figure';
    }

    /**
     * Generate URL slug for birthday
     */
    private function generateBirthdaySlug($month, $day, $year)
    {
        return sprintf('%s-%02d-%02d', $year, $month, $day);
    }

    /**
     * Parse birthday from slug
     */
    private function parseBirthdaySlug($slug)
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $slug, $matches)) {
            return [
                'year' => (int)$matches[1],
                'month' => (int)$matches[2],
                'day' => (int)$matches[3]
            ];
        }
        return null;
    }

    /**
     * Download PDF of cosmic snapshot
     */
    public function downloadPDF(Request $request, $slug): Response
    {
        try {
            // Get snapshot data
            $snapshotData = $request->getSession('cosmic_snapshot_' . $slug);
            
            if (!$snapshotData) {
                // Try to regenerate from slug
                $dateInfo = $this->parseBirthdaySlug($slug);
                if ($dateInfo) {
                    $dateObj = new DateTime(sprintf('%04d-%02d-%02d', $dateInfo['year'], $dateInfo['month'], $dateInfo['day']));
                    $snapshotData = $this->generateCosmicSnapshot($dateObj, $dateInfo['month'], $dateInfo['day'], $dateInfo['year']);
                }
            }
            
            if (!$snapshotData) {
                $request->flash('error', 'Cosmic snapshot not found.');
                return $this->redirect('/');
            }
            
            // Check if user is logged in and has credits
            $user = null;
            $pdfCost = 2; // Cost in credits for PDF download
            
            $userId = $request->getSession('user_id');
            if ($userId) {
                $user = $this->userService->getUserById($userId);
            }
            
            // Check if user has enough credits or active subscription
            $hasActiveSubscription = false;
            if ($user && method_exists($user, 'hasActiveSubscription')) {
                $hasActiveSubscription = $user->hasActiveSubscription();
            }
            
            if (!$hasActiveSubscription && (!$user || $user->credits < $pdfCost)) {
                $request->flash('error', 'Please purchase credits or unlock the full report to download PDF. PDF download costs ' . $pdfCost . ' credits.');
                return $this->redirect('/cosmic-snapshot/' . $slug);
            }
            
            // Deduct credits if not subscription user
            if (!$hasActiveSubscription && $user && $user->credits >= $pdfCost) {
                if ($user->deductCredits($pdfCost)) {
                    $request->flash('success', $pdfCost . ' credits deducted for PDF download.');
                } else {
                    $request->flash('error', 'Failed to process credit deduction.');
                    return $this->redirect('/cosmic-snapshot/' . $slug);
                }
            }
            
            // Generate HTML content for PDF
            $html = $this->generatePDFContent($snapshotData);
            
            // Generate PDF
            $pdfGenerator = $this->resolve(PdfGenerator::class);
            $filename = 'Cosmic_Snapshot_' . $slug . '_CosmicHub.pdf';
            $pdfGenerator->generateFromHtml($html, $filename);
            
        } catch (Exception $e) {
            $this->logger->error('PDF Generation Error: ' . $e->getMessage());
            $request->flash('error', 'Could not generate PDF: ' . $e->getMessage());
            return $this->redirect('/cosmic-snapshot/' . $slug);
        }
    }
    
    /**
     * Generate HTML content for PDF
     */
    private function generatePDFContent($snapshotData)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #667eea; padding-bottom: 20px; }
                .title { font-size: 24px; font-weight: bold; color: #667eea; margin-bottom: 10px; }
                .subtitle { font-size: 16px; color: #666; }
                .section { margin-bottom: 25px; }
                .section-title { font-size: 18px; font-weight: bold; color: #667eea; margin-bottom: 10px; border-left: 4px solid #667eea; padding-left: 10px; }
                .cosmic-identity { background: #f8f9ff; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
                .identity-item { margin-bottom: 8px; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; }
                .rarity-score { text-align: center; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; }
                .score-number { font-size: 36px; font-weight: bold; }
                .score-text { font-size: 14px; margin-top: 5px; }
                .history-section { background: #fff8f0; padding: 15px; border-radius: 8px; }
                .famous-twin { background: #f0f8ff; padding: 15px; border-radius: 8px; text-align: center; }
                .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">Your Cosmic Snapshot</div>
                <div class="subtitle">Born on ' . htmlspecialchars($snapshotData['birth_date_formatted']) . '</div>
            </div>
            
            <div class="section">
                <div class="section-title">Cosmic Identity</div>
                <div class="cosmic-identity">
                    <div class="identity-item">
                        <span class="label">Western Zodiac:</span> 
                        <span class="value">' . htmlspecialchars($snapshotData['western_zodiac']) . '</span>
                    </div>
                    <div class="identity-item">
                        <span class="label">Chinese Zodiac:</span> 
                        <span class="value">' . htmlspecialchars($snapshotData['chinese_zodiac']) . '</span>
                    </div>
                    <div class="identity-item">
                        <span class="label">Birthstone:</span> 
                        <span class="value">' . htmlspecialchars($snapshotData['birthstone']) . '</span>
                    </div>
                    <div class="identity-item">
                        <span class="label">Birth Flower:</span> 
                        <span class="value">' . htmlspecialchars($snapshotData['birth_flower']) . '</span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="rarity-score">
                    <div class="score-number">' . htmlspecialchars($snapshotData['rarity_score']) . '/100</div>
                    <div class="score-text">Cosmic Rarity Score</div>
                    <div style="margin-top: 10px; font-size: 14px;">' . htmlspecialchars($snapshotData['rarity_description']) . '</div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Day in History</div>
                <div class="history-section">
                    <p><strong>Historical Event:</strong> ' . htmlspecialchars($snapshotData['historical_event']) . '</p>
                    <p><strong>#1 Song:</strong> ' . htmlspecialchars($snapshotData['number_one_song']) . '</p>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Famous Birthday Twin</div>
                <div class="famous-twin">
                    <p style="font-size: 18px; font-weight: bold; margin: 0;">' . htmlspecialchars($snapshotData['famous_birthday_twin']) . '</p>
                    <p style="margin: 5px 0 0 0; color: #666;">shares your birthday!</p>
                </div>
            </div>
            
            <div class="footer">
                <p>Generated by CosmicHub.online</p>
                <p>Discover more about your cosmic blueprint at CosmicHub.online</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Validate date
     */
    private function isValidDate($month, $day, $year)
    {
        if ($month < 1 || $month > 12) return false;
        if ($day < 1 || $day > 31) return false;
        if ($year < 1900 || $year > date('Y')) return false;
        
        return checkdate($month, $day, $year);
    }
}
