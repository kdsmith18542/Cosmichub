<?php

namespace App\Controllers;

use App\Libraries\Controller;
use App\Models\CelebrityReport;
use App\Models\Archetype;

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
            $reportData = $this->generateReportContent($birth_date);
            $celebrity = new CelebrityReport();
            $celebrity->name = $name;
            $celebrity->birth_date = $birth_date;
            $celebrity->report_content = json_encode($reportData);
            $celebrity->slug = CelebrityReport::generateSlug($name);
            $celebrity->save();
            // Save archetype associations
            $archetype_ids = $_POST['archetype_ids'] ?? []; // Ensure it's an array
if (!empty($archetype_ids) && is_array($archetype_ids)) {
                // Sanitize each archetype ID before syncing
$sanitized_archetype_ids = array_map('intval', $archetype_ids);
$celebrity->archetypes()->sync($sanitized_archetype_ids);
            }
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
    private function generateReportContent($birthDate)
    {
        // Extract month and day from birth date
        list($year, $month, $day) = explode('-', $birthDate);
        
        // Fetch historical data
        $historicalData = $this->getHistoricalData($month, $day);
        
        if (!$historicalData) {
            throw new \Exception('Unable to fetch historical data.');
        }
        
        // Process the data
        return $this->processHistoricalData($historicalData, [
            'include_births' => true,
            'include_deaths' => true,
            'include_events' => true
        ]);
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