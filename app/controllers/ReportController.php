<?php

namespace App\Controllers;

use App\Libraries\Controller;
use App\Models\Report;
use App\Libraries\PdfGenerator;
use App\Services\OpenAIService;

class ReportController extends Controller
{
    public function __construct()
    {
        // Load models or helpers if needed
        // Example: $this->userModel = $this->model('User');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reports = array_map(function($report) {
            return (array) $report->getAttributes();
        }, $this->model('Report')->findAllByUserId(auth_user_id()));
        
        $this->view('reports/index', ['reports' => $reports]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->view('reports/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/reports/create');
            return;
        }

        // Sanitize input data
        $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        
        // Validate required fields
        $errors = [];
        
        $birth_date_input = sanitize_input($_POST['birth_date'] ?? '');
if (empty($birth_date_input)) {
            $errors[] = 'Birth date is required.';
        } else {
            // Validate date format and ensure it's not in the future
            $birthDate = $birth_date_input;
            if (!$this->isValidDate($birthDate)) {
                $errors[] = 'Please enter a valid birth date.';
            } elseif (strtotime($birthDate) > time()) {
                $errors[] = 'Birth date cannot be in the future.';
            }
        }
        
        if (!empty($errors)) {
            flash('error', implode(' ', $errors));
            redirect('/reports/create');
            return;
        }
        
        try {
            // Extract month and day from birth date
            $birthDate = $_POST['birth_date'];
            list($year, $month, $day) = explode('-', $birthDate);
            
            // Fetch historical data
            $historicalData = $this->getHistoricalData($month, $day);
            
            if (!$historicalData) {
                flash('error', 'Unable to fetch historical data at this time. Please try again later.');
                redirect('/reports/create');
                return;
            }
            
            // Process the data based on user preferences
            $reportData = $this->processHistoricalData($historicalData, $_POST);
            
            // Generate AI-powered content
            try {
                $openAIService = new OpenAIService();
                $aiContent = $openAIService->generateReportContent([
                    'birth_date' => $birthDate,
                    'historical_data' => $reportData
                ]);
                
                // Merge AI content with processed data
                $reportData['ai_content'] = $aiContent;
            } catch (\Exception $e) {
                // Log AI service error but continue with basic report
                error_log('OpenAI Service Error: ' . $e->getMessage());
                $reportData['ai_content'] = null;
            }
            
            $reportModel = $this->model('Report');
            $reportId = $reportModel->create([
                'user_id' => auth_user_id(),
                'title' => sanitize_input($_POST['report_title'] ?? '') ?: 'My Cosmic Report',
                'birth_date' => $birthDate,
                'content' => json_encode($reportData),
                'summary' => $this->generateSummary($reportData),
                'has_events' => !empty($reportData['events']),
                'has_births' => !empty($reportData['births']),
                'has_deaths' => !empty($reportData['deaths']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$reportId) {
                flash('error', 'Failed to save report to database.');
                redirect('/reports/create');
                return;
            }

            // Store in session and redirect to a preview
            session(['temp_report' => [
                'title' => trim($_POST['report_title']) ?: 'My Cosmic Report',
                'birth_date' => $birthDate,
                'formatted_birth_date' => format_date($birthDate),
                'data' => $reportData,
                'created_at' => date('Y-m-d H:i:s')
            ]]);
            
            flash('success', 'Your cosmic report has been generated successfully!');
            redirect('/reports/preview');
            
        } catch (Exception $e) {
            error_log('Error generating report: ' . $e->getMessage());
            flash('error', 'An error occurred while generating your report. Please try again.');
            redirect('/reports/create');
        }
    }

    /**
     * Display the preview of a generated report
     */
    public function preview()
    {
        $user = auth();
        $hasActiveSubscription = false;
        if ($user && method_exists($user, 'hasActiveSubscription')) {
            $hasActiveSubscription = $user->hasActiveSubscription();
        }
        $this->view('reports/preview', ['hasActiveSubscription' => $hasActiveSubscription]);
    }
    
    /**
     * Clear temporary report data from session
     */
    public function clearTemp()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            unset($_SESSION['temp_report']);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Temporary report cleared.']);
            exit;
        }
        redirect('/reports/create');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $report = $this->model('Report')->findById($id);

        if (!$report || $report->user_id !== auth_user_id()) {
            flash('error', 'Report not found or unauthorized access.');
            redirect('/reports');
            return;
        }

        if (!$report->isUnlocked()) {
            redirect('/reports/unlock/' . $id);
            return;
        }

        $this->view('reports/show', ['report' => $report]);
    }

    /**
     * Export the specified resource in a given format (e.g., PDF, image).
     */
    public function export($id, $format)
    {
        if (!is_logged_in()) {
            flash('error', 'You must be logged in to download reports.');
            redirect('/login');
            exit;
        }

        $reportModel = $this->model('Report');
        $report = $reportModel->findById($id);

        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/reports');
            return;
        }

        // Ensure the report belongs to the authenticated user
        if ($report->user_id !== get_current_user_id()) {
            flash('error', 'Unauthorized access to report.');
            redirect('/reports');
            return;
        }

        if (strtolower($format) === 'pdf') {
            $userModel = $this->model('User');
            $user = $userModel->findById(get_current_user_id());
            $pdfCost = 2; // Cost in credits for PDF download

            // Check if the report is unlocked or if user has enough credits
            $isUnlocked = $report->isUnlocked(); // Assuming isUnlocked() checks if the report is accessible without further payment
            
            if (!$isUnlocked && (!$user || $user->credits < $pdfCost)) {
                flash('error', 'Please unlock the full report or ensure you have at least '.$pdfCost.' credits to download the PDF.');
                redirect('/reports/show/' . $id);
                return;
            }

            // Deduct credits if the report wasn't unlocked and user is paying with credits
            if (!$isUnlocked && $user && $user->credits >= $pdfCost) {
                if ($user->deductCredits($pdfCost)) { // deductCredits now part of User model
                    flash('success', $pdfCost . ' credits deducted for PDF download.');
                    // Optionally, mark the report as purchased/unlocked for this user to avoid future charges for the same report PDF
                    // $report->markAsPurchasedBy(get_current_user_id(), 'pdf'); // Example method
                } else {
                    flash('error', 'Failed to deduct credits. Please try again.');
                    redirect('/reports/show/' . $id);
                    return;
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
                $pdfGenerator = new PdfGenerator();
                $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', str_replace(' ', '_', $report->title)) . '_CosmicHub_Report.pdf';
                $pdfGenerator->generateFromHtml($html, $filename);
                // generateFromHtml in PdfGenerator handles output and exit.
                return; 
            } catch (\Exception $e) {
                error_log('PDF Generation Error: ' . $e->getMessage());
                flash('error', 'Could not generate PDF: ' . $e->getMessage());
                redirect('/reports/show/' . $id);
                return;
            }
        } elseif (strtolower($format) === 'png') {
            // Placeholder for image export logic
            flash('info', 'Image export is not yet implemented.');
            redirect('/reports/show/' . $id);
            return;
        } else {
            flash('error', 'Invalid export format specified.');
            redirect('/reports/show/' . $id);
            return;
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['_method'] ?? '') !== 'DELETE') {
            redirect('/reports');
            return;
        }

        $report = $this->model('Report')->findById($id);

        if (!$report) {
            flash('error', 'Report not found.');
            redirect('/reports');
            return;
        }

        if ($report->user_id !== auth_user_id()) {
            flash('error', 'Unauthorized to delete this report.');
            redirect('/reports');
            return;
        }

        if ($report->delete()) {
            flash('success', 'Report deleted successfully.');
        } else {
            flash('error', 'Failed to delete report.');
        }

        redirect('/reports');
    }

    /**
     * Validates if a date string is in valid format
     * @param string $date
     * @return bool
     */
    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Processes historical data based on user preferences
     * @param array $historicalData
     * @param array $userPreferences
     * @return array
     */
    private function processHistoricalData($historicalData, $userPreferences)
    {
        $processedData = [];
        
        // Process events if requested
        if (!empty($userPreferences['include_events']) && !empty($historicalData['events'])) {
            $processedData['events'] = array_slice($historicalData['events'], 0, 5); // Limit to 5 events
        }
        
        // Process births if requested
        if (!empty($userPreferences['include_births']) && !empty($historicalData['births'])) {
            $processedData['births'] = array_slice($historicalData['births'], 0, 5); // Limit to 5 births
        }
        
        // Process deaths if requested
        if (!empty($userPreferences['include_deaths']) && !empty($historicalData['deaths'])) {
            $processedData['deaths'] = array_slice($historicalData['deaths'], 0, 5); // Limit to 5 deaths
        }
        
        return $processedData;
    }

    /**
     * Fetches historical data from Wikimedia API for a given month and day.
     * @param string $month (MM)
     * @param string $day (DD)
     * @return array|false Parsed JSON data or false on failure.
     */
    private function getHistoricalData($month, $day)
    {
        // Ensure month and day are two digits
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $day = str_pad($day, 2, '0', STR_PAD_LEFT);

        $url = "https://en.wikipedia.org/api/rest_v1/feed/onthisday/all/{$month}/{$day}";
        
        // Set a User-Agent header as per Wikimedia API recommendations
        $options = [
            'http' => [
                'method' => "GET",
                'header' => "User-Agent: Cosmichub/1.0 (https://cosmichub.local; keith@example.com) PHP/" . PHP_VERSION . "\r\n"
            ]
        ];
        $context = stream_context_create($options);

        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === FALSE) {
                // Log error or handle it appropriately
                error_log("Failed to fetch data from Wikimedia API: {$url}");
                return false;
            }
            return json_decode($response, true);
        } catch (\Exception $e) {
            error_log("Exception while fetching data from Wikimedia API: " . $e->getMessage());
            return false;
        }
    }
    
    public function unlock($id)
    {
        $report = $this->model('Report')->findById($id);
        if (!$report || $report->user_id !== auth_user_id()) {
            flash('error', 'Report not found or unauthorized access.');
            redirect('/reports');
            return;
        }
        // If already unlocked, redirect to report
        if ($report->isUnlocked()) {
            redirect('/reports/' . $id);
            return;
        }
        // Load or create referral for this user/report
        $referralModel = new \App\Models\Referral();
        $referral = $referralModel::createForUser(auth_user_id(), 'report-unlock');
        $successfulReferrals = $referral->successful_referrals;
        $referralUrl = $referral->getReferralUrl();
        // If POST and enough referrals, unlock the report
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $successfulReferrals >= 3) {
            $report->unlock('referral');
            flash('success', 'Your report has been unlocked!');
            redirect('/reports/' . $id);
            return;
        }
        // Show unlock wall
        $this->view('reports/unlock', [
            'reportId' => $id,
            'referralUrl' => $referralUrl,
            'successfulReferrals' => $successfulReferrals
        ]);
    }
}