<?php

namespace App\Controllers;

use App\Libraries\Controller;
use App\Models\CelebrityReport;
use App\Models\Archetype;
use App\Helpers\AstrologyHelper;
use App\Controllers\RarityScoreController; // For rarity calculations
use App\Services\GeminiService; // For Cosmic Significance Blurb

class CelebrityReportController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display a listing of celebrity reports
     */
    public function index()
    {
        $celebrities = CelebrityReport::orderBy('name', 'ASC')->get();
        $this->view('celebrity-reports/index', ['celebrities' => $celebrities]);
    }

    /**
     * Show a specific celebrity report
     */
    public function show($slug)
    {
        $celebrity = CelebrityReport::findBySlug($slug);
        
        if (!$celebrity) {
            flash('error', 'Celebrity report not found.');
            redirect('/celebrity-reports');
            return;
        }

        $this->view('celebrity-reports/show', ['celebrity' => $celebrity]);
    }

    /**
     * Show the form for creating a new celebrity report
     */
    public function create()
    {
        if (!is_admin()) {
            flash('error', 'Unauthorized access.');
            redirect('/');
            return;
        }
        $archetypes = Archetype::orderBy('name')->get();
        $this->view('celebrity-reports/create', ['archetypes' => $archetypes]);
    }

    /**
     * Store a newly created celebrity report
     */
    public function store()
    {
        if (!is_admin()) {
            flash('error', 'Unauthorized access.');
            redirect('/');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/celebrity-reports/create');
            return;
        }
        $errors = [];
        $name = sanitize_input($_POST['name'] ?? '');
if (empty($name)) {
            $errors[] = 'Name is required.';
        }
        $birth_date = sanitize_input($_POST['birth_date'] ?? '');
if (empty($birth_date)) {
            $errors[] = 'Birth date is required.';
        } elseif (!$this->isValidDate($birth_date)) {
            $errors[] = 'Please enter a valid birth date.';
        }
        if (!empty($errors)) {
            flash('error', implode(' ', $errors));
            redirect('/celebrity-reports/create');
            return;
        }
        try {
            // Pass celebrity name to generateReportContent for more personalized blurb
            $reportData = $this->generateReportContent($birth_date, $name);
            $celebrity = new CelebrityReport();
            $celebrity->name = $name;
            $celebrity->birth_date = $birth_date;
            $celebrity->report_content = json_encode($reportData);
            $celebrity->slug = CelebrityReport::generateSlug($name);
            // The report_content is already an array from generateReportContent
            // $celebrity->report_content = json_encode($reportData); // No longer needed if $reportData is already the final array for JSON
            $celebrity->save(); // Save once to get an ID

            // Handle Archetype Association and update report_content
            $archetype_ids = $_POST['archetype_ids'] ?? [];
            $primaryArchetypeData = null;
            if (!empty($archetype_ids) && is_array($archetype_ids)) {
                $sanitized_archetype_ids = array_map('intval', $archetype_ids);
                $celebrity->archetypes()->sync($sanitized_archetype_ids);
                // Fetch the first associated archetype to embed in the report content
                if (!empty($sanitized_archetype_ids)) {
                    $primaryArchetype = Archetype::find($sanitized_archetype_ids[0]);
                    if ($primaryArchetype) {
                        $primaryArchetypeData = [
                            'name' => $primaryArchetype->name,
                            'slug' => $primaryArchetype->slug,
                            'description' => $primaryArchetype->description // Assuming Archetype model has description
                        ];
                    }
                }
            }
            // Update the report_content with archetype data
            $reportData['archetype'] = $primaryArchetypeData;
            $celebrity->report_content = json_encode($reportData); // Now encode the complete data
            $celebrity->save(); // Save again with updated report_content

            flash('success', 'Celebrity report created successfully!');
            redirect('/celebrity-reports/' . $celebrity->slug);
        } catch (\Exception $e) {
            error_log('Error creating celebrity report: ' . $e->getMessage());
            flash('error', 'An error occurred while creating the celebrity report.');
            redirect('/celebrity-reports/create');
        }
    }

    /**
     * Search celebrity reports
     */
    public function search()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            redirect('/celebrity-reports');
            return;
        }

        $query = sanitize_input($_GET['q'] ?? '');
        if (empty($query)) {
            redirect('/celebrity-reports');
            return;
        }

        $celebrities = CelebrityReport::searchByName($query);
        $this->view('celebrity-reports/index', [
            'celebrities' => $celebrities,
            'search_query' => $query
        ]);
    }

    /**
     * Get celebrity reports by birth date
     */
    public function getByBirthDate($month, $day)
    {
        $celebrities = CelebrityReport::getByBirthDate($month, $day);
        return $this->jsonResponse(['celebrities' => $celebrities]);
    }

    /**
     * Generate report content using existing report generation logic
     */
    private function generateReportContent($birthDate, $celebrityName = 'This individual') // Added name for blurb
    {
        // Ensure birthDate is in Y-m-d format
        try {
            $dateObject = new \DateTime($birthDate);
        } catch (\Exception $e) {
            throw new \Exception('Invalid birth date format provided for report generation.');
        }

        $year = (int)$dateObject->format('Y');
        $month = (int)$dateObject->format('n'); // Month without leading zeros (1-12)
        $day = (int)$dateObject->format('j');   // Day without leading zeros (1-31)

        // 1. Historical Snapshot (existing logic - assumed to be in $this->getHistoricalData & $this->processHistoricalData)
        // These methods would need to be part of this controller or its parent.
        // For now, let's assume processHistoricalData returns an array like: ['events' => [], 'births' => [], 'deaths' => []]
        $processedHistoricalData = [];
        try {
            // Placeholder: In a real scenario, getHistoricalData might be from ReportController or a service
            // $rawHistoricalData = $this->getHistoricalData($month, $day); 
            // if (!$rawHistoricalData) { 
            //     throw new \Exception('Unable to fetch historical data.'); 
            // }
            // $processedHistoricalData = $this->processHistoricalData($rawHistoricalData, [...]);
            // For now, using placeholder empty data for historical snapshot
            $processedHistoricalData = ['events' => [], 'births' => [], 'deaths' => []];
        } catch (\Exception $e) {
            // Log error or handle gracefully, maybe return partial report
            error_log('Error fetching historical data for celebrity report: ' . $e->getMessage());
            // Set empty historical data if fetching fails
            $processedHistoricalData = ['events' => [], 'births' => [], 'deaths' => []];
        }

        // 2. Astrological Profile
        $westernZodiac = AstrologyHelper::getWesternZodiacSign($month, $day);
        $chineseZodiac = AstrologyHelper::getChineseZodiacSign($year);
        $birthstone = AstrologyHelper::getBirthstone($month);
        $birthFlower = AstrologyHelper::getBirthFlower($month);
        $lifePathNumberVal = AstrologyHelper::calculateLifePathNumber($birthDate);
        $lifePathNumberDesc = AstrologyHelper::getLifePathNumberInterpretation($lifePathNumberVal);

        // 3. Rarity Score Details
        $rarityController = new RarityScoreController(); // Instantiate RarityScoreController
        $rarityScore = $rarityController->calculateRarityScore($birthDate);
        $rarityDescription = $rarityController->getRarityDescription($rarityScore);
        $rarityColor = $rarityController->getRarityColor($rarityScore);
        // Assuming getRarityExplanation exists and is public, or adapt from its logic
        $rarityExplanation = $rarityController->getRarityExplanation($rarityScore, $rarityDescription); 
        $rarityFactors = [
            'month' => $rarityController->getMonthFactorDescription($month),
            'day' => $rarityController->getDayFactorDescription($month, $day),
            'special_date' => $rarityController->getSpecialDateDescription($month, $day),
            'leap_year' => $rarityController->getLeapYearDescription($month, $day, $year)
        ];

        // 4. Archetype - This would typically be linked after report creation or passed in.
        // For now, it's a placeholder in the structure. The store() method handles linking.
        // If an archetype is known at this stage, it could be populated.

        // 5. Cosmic Significance Blurb (AI Generated)
        $cosmicSignificanceBlurb = "A unique cosmic blueprint for {$celebrityName}, born on " . $dateObject->format('F j, Y') . "."; // Placeholder
        try {
            // $geminiService = new GeminiService(); // Assuming API key is configured
            // $prompt = "Generate a brief (1-2 paragraphs) 'Cosmic Significance' blurb for someone named {$celebrityName} born on {$birthDate}, with Western Zodiac {$westernZodiac}, Chinese Zodiac {$chineseZodiac}, Life Path Number {$lifePathNumberVal}, and a birthday rarity score of {$rarityScore} ({$rarityDescription}). Highlight their unique cosmic identity.";
            // $cosmicSignificanceBlurb = $geminiService->generateCosmicSummary($prompt);
        } catch (\Exception $e) {
            error_log('Error generating Cosmic Significance blurb: ' . $e->getMessage());
            // Fallback to a simpler blurb if AI fails
        }

        return [
            'historical_snapshot' => $processedHistoricalData, // Assumes this structure
            'astrological_profile' => [
                'western_zodiac' => $westernZodiac,
                'chinese_zodiac' => $chineseZodiac,
                'birthstone' => $birthstone,
                'birth_flower' => $birthFlower,
                'life_path_number' => [
                    'number' => $lifePathNumberVal,
                    'description' => $lifePathNumberDesc,
                ]
            ],
            'rarity_score_details' => [
                'score' => $rarityScore,
                'description' => $rarityDescription,
                'color' => $rarityColor,
                'explanation' => $rarityExplanation,
                'factors' => $rarityFactors
            ],
            // Archetype will be added to the JSON separately or dynamically loaded in view
            'archetype' => null, // Placeholder, to be filled later or handled by view
            'cosmic_significance_blurb' => $cosmicSignificanceBlurb
        ];
    }

    /**
     * Validate date format
     */
    private function isValidDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}