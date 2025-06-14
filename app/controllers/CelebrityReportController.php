<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\CelebrityReportService;
use App\Services\ArchetypeService;
use App\Services\RarityScoreService;
use App\Services\AstrologyService;
use Psr\Log\LoggerInterface;

class CelebrityReportController extends Controller
{
    private CelebrityReportService $celebrityReportService;
    private ArchetypeService $archetypeService;
    private RarityScoreService $rarityScoreService;
    private AstrologyService $astrologyService;
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->celebrityReportService = $this->resolve(CelebrityReportService::class);
        $this->archetypeService = $this->resolve(ArchetypeService::class);
        $this->rarityScoreService = $this->resolve(RarityScoreService::class);
        $this->astrologyService = $this->resolve(AstrologyService::class);
    }

    /**
     * Display a listing of celebrity reports
     */
    public function index(Request $request): Response
    {
        $celebrities = $this->celebrityReportService->getAllCelebrities();
        return $this->view('celebrity-reports/index', ['celebrities' => $celebrities]);
    }

    /**
     * Show a specific celebrity report
     */
    public function show(Request $request, $slug): Response
    {
        $celebrity = $this->celebrityReportService->getCelebrityBySlug($slug);
        
        if (!$celebrity) {
            $request->flash('error', 'Celebrity report not found.');
            return $this->redirect('/celebrity-reports');
        }

        return $this->view('celebrity-reports/show', ['celebrity' => $celebrity]);
    }

    /**
     * Show the form for creating a new celebrity report
     */
    public function create(Request $request): Response
    {
        if (!is_admin()) {
            $request->flash('error', 'Unauthorized access.');
            return $this->redirect('/');
        }
        $archetypes = $this->celebrityReportService->getAllArchetypes();
        return $this->view('celebrity-reports/create', ['archetypes' => $archetypes]);
    }

    /**
     * Store a newly created celebrity report
     */
    public function store(Request $request): Response
    {
        if (!is_admin()) {
            $request->flash('error', 'Unauthorized access.');
            return $this->redirect('/');
        }
        if (!$request->isPost()) {
            return $this->redirect('/celebrity-reports/create');
        }
        $errors = [];
        $name = sanitize_input($request->input('name', ''));
        if (empty($name)) {
            $errors[] = 'Name is required.';
        }
        $birth_date = sanitize_input($request->input('birth_date', ''));
        if (empty($birth_date)) {
            $errors[] = 'Birth date is required.';
        } elseif (!$this->isValidDate($birth_date)) {
            $errors[] = 'Please enter a valid birth date.';
        }
        if (!empty($errors)) {
            $request->flash('error', implode(' ', $errors));
            return $this->redirect('/celebrity-reports/create');
        }
        try {
            // Pass celebrity name to generateReportContent for more personalized blurb
            $reportData = $this->generateReportContent($birth_date, $name);
            $celebrityData = [
                 'name' => $name,
                 'birth_date' => $birth_date,
                 'report_content' => json_encode($reportData),
                 'slug' => $this->celebrityReportService->generateSlug($name)
             ];
             $celebrity = $this->celebrityReportService->createCelebrity($celebrityData); // Save once to get an ID

            // Handle Archetype Association and update report_content
            $archetype_ids = $request->input('archetype_ids', []);
            $primaryArchetypeData = null;
            if (!empty($archetype_ids) && is_array($archetype_ids)) {
                $sanitized_archetype_ids = array_map('intval', $archetype_ids);
                $celebrity->archetypes()->sync($sanitized_archetype_ids);
                // Fetch the first associated archetype to embed in the report content
                if (!empty($sanitized_archetype_ids)) {
                    $primaryArchetype = $this->celebrityReportService->getArchetypeById($sanitized_archetype_ids[0]);
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

            $request->flash('success', 'Celebrity report created successfully!');
            return $this->redirect('/celebrity-reports/' . $celebrity->slug);
        } catch (\Exception $e) {
            $this->logger->error('Error creating celebrity report: ' . $e->getMessage());
            $request->flash('error', 'An error occurred while creating the celebrity report.');
            return $this->redirect('/celebrity-reports/create');
        }
    }

    /**
     * Search celebrity reports
     */
    public function search(Request $request): Response
    {
        if (!$request->isGet()) {
            return $this->redirect('/celebrity-reports');
        }

        $query = sanitize_input($request->input('q', ''));
        if (empty($query)) {
            return $this->redirect('/celebrity-reports');
        }

        $celebrities = $this->celebrityReportService->searchByName($query);
        return $this->view('celebrity-reports/index', [
            'celebrities' => $celebrities,
            'search_query' => $query
        ]);
    }

    /**
     * Get celebrity reports by birth date
     */
    public function getByBirthDate(Request $request, $month, $day): Response
    {
        $celebrities = $this->celebrityReportService->getCelebritiesByBirthDate($month, $day);
        return $this->json(['celebrities' => $celebrities]);
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
            $this->logger->error('Error fetching historical data for celebrity report: ' . $e->getMessage());
            // Set empty historical data if fetching fails
            $processedHistoricalData = ['events' => [], 'births' => [], 'deaths' => []];
        }

        // 2. Astrological Profile
        $westernZodiac = $this->astrologyService->getWesternZodiacSign($month, $day);
        $chineseZodiac = $this->astrologyService->getChineseZodiacSign($year);
        $birthstone = $this->astrologyService->getBirthstone($month);
        $birthFlower = $this->astrologyService->getBirthFlower($month);
        $lifePathNumberVal = $this->astrologyService->calculateLifePathNumber($birthDate);
        $lifePathNumberDesc = $this->astrologyService->getLifePathNumberInterpretation($lifePathNumberVal);

        // 3. Rarity Score Details
         $rarityResult = $this->rarityScoreService->calculateRarityScore($birthDate);
         $rarityScore = $rarityResult['score'];
         $rarityDescription = $rarityResult['description'];
         $rarityColor = $rarityResult['color'];
         $rarityExplanation = $rarityResult['explanation'];
         $rarityFactors = $rarityResult['factors'];

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
            $this->logger->error('Error generating Cosmic Significance blurb: ' . $e->getMessage());
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