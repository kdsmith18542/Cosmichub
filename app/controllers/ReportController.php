<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\ReportService;
use App\Services\UserService;
use App\Services\CreditService;
use App\Services\ReferralService;
use App\Services\GeminiService;
use App\Core\Auth\Auth;
use App\Utils\PdfGenerator;
use Exception;
use Psr\Log\LoggerInterface;

class ReportController extends Controller
{
    private ReportService $reportService;
    private UserService $userService;
    private CreditService $creditService;
    private ReferralService $referralService;
    private LoggerInterface $logger;
    
    public function __construct()
    {
        parent::__construct();
        $this->reportService = $this->resolve(ReportService::class);
        $this->userService = $this->resolve(UserService::class);
        $this->creditService = $this->resolve(CreditService::class);
        $this->referralService = $this->resolve(ReferralService::class);
        $this->logger = $this->resolve(LoggerInterface::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // Get user's reports using the service
        $result = $this->reportService->getUserReports($userId);
        
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            $reports = [];
        } else {
            $reports = $result['data'];
        }
        
        return $this->view('reports/index', ['reports' => $reports]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return $this->view('reports/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // Validate input
        $birthDate = $request->input('birth_date');
        if (!$birthDate) {
            $request->flash('error', 'Birth date is required.');
            return $this->redirect('/reports/create');
        }
        
        // Generate report using the service
        $result = $this->reportService->generateReport($userId, [
            'birth_date' => $birthDate,
            'report_title' => $request->input('report_title', '')
        ]);
        
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            return $this->redirect('/reports/create');
        }
        
        // Store in session and redirect to a preview
        $request->setSession('temp_report', $result['data']);
        
        $request->flash('success', 'Your cosmic report has been generated successfully!');
        return $this->redirect('/reports/preview');
    }

    /**
     * Display the preview of a generated report
     */
    public function preview(Request $request): Response
    {
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        // Get report data from session
        $report = $request->getSession('temp_report');
        $premiumContent = null;
        
        // Check if user has active subscription
        // This would need to be implemented based on your subscription system
        $hasActiveSubscription = false; // Placeholder
        
        // Generate premium content for subscribers
        if ($hasActiveSubscription && $report) {
            $premiumContent = $this->reportService->generatePremiumContent($report['birth_date']);
        }
        
        return $this->view('reports/preview', [
            'hasActiveSubscription' => $hasActiveSubscription,
            'premiumContent' => $premiumContent,
            'report' => $report
        ]);
    }
    
    /**
     * Clear temporary report data from session
     */
    public function clearTemp(Request $request): Response
    {
        if ($request->isPost()) {
            $request->removeSession('temp_report');
            return $this->json(['status' => 'success', 'message' => 'Temporary report cleared.']);
        }
        return $this->redirect('/reports/create');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id): Response
    {
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login');
        }
        
        $result = $this->reportService->getReport($id, $userId);
        
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            return $this->redirect('/reports');
        }
        
        $report = $result['data'];
        
        // Check if report is unlocked (this logic would need to be implemented)
        // if (!$report->isUnlocked()) {
        //     return $this->redirect('/reports/unlock/' . $id);
        // }

        return $this->view('reports/show', ['report' => $report]);
    }

    /**
     * Export the specified resource in a given format (e.g., PDF, image).
     */
    public function export(Request $request, $id, $format): Response
    {
        $userId = $request->getSession('user_id');
        if (!$userId) {
            $request->flash('error', 'You must be logged in to download reports.');
            return $this->redirect('/login');
        }

        $result = $this->reportService->getReport($id, $userId);
        
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            return $this->redirect('/reports');
        }
        
        $report = $result['data'];

        if (strtolower($format) === 'pdf') {
            $user = $this->userService->getUserById($userId);
            $pdfCost = 2; // Cost in credits for PDF download

            // Check if the report is unlocked or if user has enough credits
            $isUnlocked = $report->isUnlocked(); // Assuming isUnlocked() checks if the report is accessible without further payment
            
            if (!$isUnlocked && (!$user || $user->credits < $pdfCost)) {
                $request->flash('error', 'Please unlock the full report or ensure you have at least '.$pdfCost.' credits to download the PDF.');
                return $this->redirect('/reports/show/' . $id);
            }

            // Deduct credits if the report wasn't unlocked and user is paying with credits
            if (!$isUnlocked && $user && $user->credits >= $pdfCost) {
                if ($user->deductCredits($pdfCost)) { // deductCredits now part of User model
                    $request->flash('success', $pdfCost . ' credits deducted for PDF download.');
                    // Optionally, mark the report as purchased/unlocked for this user to avoid future charges for the same report PDF
                    // $report->markAsPurchasedBy($userId, 'pdf'); // Example method
                } else {
                    $request->flash('error', 'Failed to deduct credits. Please try again.');
                    return $this->redirect('/reports/show/' . $id);
                }
            }
            
            // Generate HTML content for the PDF
            $reportContent = json_decode($report->content, true);
            
            $html = "<h1>Report: " . htmlspecialchars($report->title) . "</h1>";
            $html .= "<p>Birth Date: " . htmlspecialchars(format_date($report->birth_date)) . "</p>";

            // Include AI Content if available
            if (isset($reportContent['ai_content'])) {
                if (is_array($reportContent['ai_content'])) {
                    foreach ($reportContent['ai_content'] as $key => $value) {
                        $html .= "<h2>" . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . "</h2>";
                        if (is_array($value)) {
                            foreach ($value as $subKey => $subValue) {
                                $html .= "<p><strong>" . htmlspecialchars(ucwords(str_replace('_', ' ', $subKey))) . ":</strong> " . nl2br(htmlspecialchars($subValue)) . "</p>";
                            }
                        } else {
                            $html .= "<p>" . nl2br(htmlspecialchars($value)) . "</p>";
                        }
                    }
                } else {
                     $html .= "<div>" . nl2br(htmlspecialchars($reportContent['ai_content'])) . "</div>";
                }
            }

            // Include other free snapshot content parts if they exist
            if (isset($reportContent['free_snapshot'])) {
                 $html .= "<h2>Free Snapshot Insights</h2>";
                foreach($reportContent['free_snapshot'] as $key => $value) {
                    $html .= "<h3>" . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . "</h3>";
                     if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            $html .= "<p><strong>" . htmlspecialchars(ucwords(str_replace('_', ' ', $subKey))) . ":</strong> " . nl2br(htmlspecialchars($subValue)) . "</p>";
                        }
                    } else {
                        $html .= "<p>" . nl2br(htmlspecialchars($value)) . "</p>";
                    }
                }
            }
            
            // You might want to add more sections from $reportContent here, ensuring they are part of the premium offering

            try {
                $pdfGenerator = $this->resolve(PdfGenerator::class);
                $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', str_replace(' ', '_', $report->title)) . '_CosmicHub_Report.pdf';
                $pdfContent = $pdfGenerator->generateFromHtml($html, $filename);
                
                // Assuming PdfGenerator has been updated to return content instead of directly outputting
                $response = new \App\Core\Http\Response();
                $response->setHeader('Content-Type', 'application/pdf');
                $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
                $response->setContent($pdfContent);
                return $response;
            } catch (Exception $e) {
                $this->logger->error('PDF Generation Error: ' . $e->getMessage());
                $request->flash('error', 'Could not generate PDF: ' . $e->getMessage());
                return $this->redirect('/reports/show/' . $id);
            }
        } elseif (strtolower($format) === 'png') {
            // Placeholder for image export logic
            $request->flash('info', 'Image export is not yet implemented.');
            return $this->redirect('/reports/show/' . $id);
        } else {
            $request->flash('error', 'Invalid export format specified.');
            return $this->redirect('/reports/show/' . $id);
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id): Response
    {
        if (!$request->isPost() || $request->input('_method') !== 'DELETE') {
            return $this->redirect('/reports');
        }

        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login');
        }

        $result = $this->reportService->deleteReport($id, $userId);
        
        if ($result['success']) {
            $request->flash('success', $result['message']);
        } else {
            $request->flash('error', 'Failed to delete report.');
        }

        return $this->redirect('/reports');
    }


    
    public function unlock(Request $request, $id): Response
    {
        $userId = $request->getSession('user_id');
        if (!$userId) {
            return $this->redirect('/login');
        }

        $result = $this->reportService->getReport($id, $userId);
        
        if (!$result['success']) {
            $request->flash('error', $result['message']);
            return $this->redirect('/reports');
        }
        
        $report = $result['data'];
        
        // If already unlocked, redirect to report
        $unlockStatus = $this->reportService->isReportUnlocked($id);
        if ($unlockStatus['success'] && $unlockStatus['data']['is_unlocked']) {
            return $this->redirect('/reports/' . $id);
        }
        
        // Load or create referral for this user/report
        $referral = $this->referralService->createForUser($userId, 'report-unlock');
        $successfulReferrals = $this->referralService->getSuccessfulReferralsCount($referral);
        $referralUrl = $this->referralService->getReferralUrl($referral);
        
        // If POST and enough referrals, unlock the report
        if ($request->isPost() && $this->referralService->hasEnoughReferrals($referral, 3)) {
            $unlockResult = $this->reportService->unlockReport($id, 'referral');
            if ($unlockResult['success']) {
                $request->flash('success', 'Your report has been unlocked!');
                return $this->redirect('/reports/' . $id);
            } else {
                $request->flash('error', $unlockResult['message']);
                return $this->redirect('/reports/unlock/' . $id);
            }
        }
        
        // Show unlock wall
        return $this->view('reports/unlock', [
            'reportId' => $id,
            'referralUrl' => $referralUrl,
            'successfulReferrals' => $successfulReferrals
        ]);
    }
}