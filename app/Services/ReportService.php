<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;
use App\Services\GeminiService;

/**
 * Report Service for handling report business logic
 */
class ReportService extends Service
{
    /**
     * @var ReportRepository
     */
    protected $reportRepository;
    
    /**
     * @var UserRepository
     */
    protected $userRepository;
    
    /**
     * @var GeminiService
     */
    protected $geminiService;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->reportRepository = $this->getRepository('ReportRepository');
        $this->geminiService = $this->container->resolve(GeminiService::class);
        $this->userRepository = $this->getRepository('UserRepository');
    }
    
    /**
     * Create a new report
     * 
     * @param int $userId The user ID
     * @param array $data Report data
     * @return array
     */
    public function createReport($userId, $data)
    {
        try {
            // Validate user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            // Prepare report data
            $reportData = [
                'user_id' => $userId,
                'birth_date' => $data['birth_date'],
                'birth_time' => $data['birth_time'] ?? null,
                'birth_location' => $data['birth_location'] ?? null,
                'report_type' => $data['report_type'] ?? 'basic',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Create the report
            $report = $this->reportRepository->create($reportData);
            
            if ($report) {
                $this->log('info', 'Report created successfully', ['report_id' => $report['id'], 'user_id' => $userId]);
                return $this->success('Report created successfully', $report);
            }
            
            return $this->error('Failed to create report');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error creating report: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while creating the report');
        }
    }
    
    /**
     * Get user's reports
     * 
     * @param int $userId The user ID
     * @param int $limit Optional limit
     * @return array
     */
    public function getUserReports($userId, $limit = null)
    {
        try {
            $reports = $this->reportRepository->findByUserId($userId);
            
            if ($limit) {
                $reports = array_slice($reports, 0, $limit);
            }
            
            return $this->success('Reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving user reports: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while retrieving reports');
        }
    }
    
    /**
     * Get report by ID
     * 
     * @param int $reportId The report ID
     * @param int $userId Optional user ID for ownership check
     * @return array
     */
    public function getReport($reportId, $userId = null)
    {
        try {
            $report = $this->reportRepository->find($reportId);
            
            if (!$report) {
                return $this->error('Report not found');
            }
            
            // Check ownership if user ID provided
            if ($userId && $report['user_id'] != $userId) {
                return $this->error('Access denied');
            }
            
            return $this->success('Report retrieved successfully', $report);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving report: ' . $e->getMessage(), ['report_id' => $reportId]);
            return $this->error('An error occurred while retrieving the report');
        }
    }
    
    /**
     * Update report status
     * 
     * @param int $reportId The report ID
     * @param string $status New status
     * @return array
     */
    public function updateReportStatus($reportId, $status)
    {
        try {
            $validStatuses = ['pending', 'processing', 'completed', 'failed'];
            
            if (!in_array($status, $validStatuses)) {
                return $this->error('Invalid status');
            }
            
            $updated = $this->reportRepository->updateStatus($reportId, $status);
            
            if ($updated) {
                $this->log('info', 'Report status updated', ['report_id' => $reportId, 'status' => $status]);
                return $this->success('Report status updated successfully');
            }
            
            return $this->error('Failed to update report status');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error updating report status: ' . $e->getMessage(), ['report_id' => $reportId]);
            return $this->error('An error occurred while updating the report status');
        }
    }
    
    /**
     * Delete report
     * 
     * @param int $reportId The report ID
     * @param int $userId Optional user ID for ownership check
     * @return array
     */
    public function deleteReport($reportId, $userId = null)
    {
        try {
            $report = $this->reportRepository->find($reportId);
            
            if (!$report) {
                return $this->error('Report not found');
            }
            
            // Check ownership if user ID provided
            if ($userId && $report['user_id'] != $userId) {
                return $this->error('Access denied');
            }
            
            $deleted = $this->reportRepository->delete($reportId);
            
            if ($deleted) {
                $this->log('info', 'Report deleted', ['report_id' => $reportId]);
                return $this->success('Report deleted successfully');
            }
            
            return $this->error('Failed to delete report');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deleting report: ' . $e->getMessage(), ['report_id' => $reportId]);
            return $this->error('An error occurred while deleting the report');
        }
    }
    
    /**
     * Get recent reports
     * 
     * @param int $limit Number of reports to retrieve
     * @return array
     */
    public function getRecentReports($limit = 10)
    {
        try {
            $reports = $this->reportRepository->getRecent($limit);
            return $this->success('Recent reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving recent reports: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving recent reports');
        }
    }
    
    /**
     * Get report statistics
     * 
     * @return array
     */
    public function getReportStatistics()
    {
        try {
            $stats = $this->reportRepository->getStatistics();
            return $this->success('Report statistics retrieved successfully', $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving report statistics: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving report statistics');
        }
    }
    
    /**
     * Search reports
     * 
     * @param string $search Search term
     * @param array $filters Optional filters
     * @return array
     */
    public function searchReports($search, $filters = [])
    {
        try {
            $reports = $this->reportRepository->search($search, $filters);
            return $this->success('Search completed successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error searching reports: ' . $e->getMessage());
            return $this->error('An error occurred while searching reports');
        }
    }
    
    /**
     * Process pending reports
     * 
     * @param int $limit Number of reports to process
     * @return array
     */
    public function processPendingReports($limit = 10)
    {
        try {
            $pendingReports = $this->reportRepository->findByStatus('pending', $limit);
            $processed = 0;
            
            foreach ($pendingReports as $report) {
                // Update status to processing
                $this->reportRepository->updateStatus($report['id'], 'processing');
                
                // Here you would add the actual report generation logic
                // For now, we'll just mark it as completed
                $this->reportRepository->updateStatus($report['id'], 'completed');
                
                $processed++;
            }
            
            $this->log('info', 'Processed pending reports', ['count' => $processed]);
            return $this->success("Processed {$processed} reports successfully", ['processed' => $processed]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error processing pending reports: ' . $e->getMessage());
            return $this->error('An error occurred while processing pending reports');
        }
    }
    
    /**
     * Validate report data
     * 
     * @param array $data Report data
     * @return array
     */
    protected function validateReportData($data)
    {
        $errors = [];
        
        if (empty($data['birth_date'])) {
            $errors[] = 'Birth date is required';
        } elseif (!$this->isValidDate($data['birth_date'])) {
            $errors[] = 'Invalid birth date format';
        }
        
        if (!empty($data['birth_time']) && !$this->isValidTime($data['birth_time'])) {
            $errors[] = 'Invalid birth time format';
        }
        
        if (!empty($data['report_type'])) {
            $validTypes = ['basic', 'detailed', 'premium'];
            if (!in_array($data['report_type'], $validTypes)) {
                $errors[] = 'Invalid report type';
            }
        }
        
        return $errors;
    }
    
    /**
     * Check if date is valid
     * 
     * @param string $date Date string
     * @return bool
     */
    protected function isValidDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Check if time is valid
     * 
     * @param string $time Time string
     * @return bool
     */
    protected function isValidTime($time)
    {
        $t = \DateTime::createFromFormat('H:i:s', $time);
        return $t && $t->format('H:i:s') === $time;
    }
    
    /**
     * Generate a complete report with AI content
     * 
     * @param int $userId The user ID
     * @param array $requestData Request data including birth_date, report_title, etc.
     * @return array
     */
    public function generateReport($userId, $requestData)
    {
        try {
            // Validate user exists
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $birthDate = $requestData['birth_date'];
            
            // Validate birth date
            if (!$this->isValidDate($birthDate)) {
                return $this->error('Invalid birth date format');
            }
            
            // Fetch historical data
            $reportData = $this->fetchHistoricalData($birthDate);
            
            // Generate AI content
            try {
                $soulsArchetype = $this->geminiService->generateSoulsArchetype("Provide a Soul's Archetype interpretation based on the birth date {$birthDate}. Focus on core identity, life purpose, and innate talents. Format as a concise, insightful paragraph.");
                $planetaryInfluence = $this->geminiService->generatePlanetaryInfluence("Provide a Planetary Influence interpretation based on the birth date {$birthDate}. Describe how key planets might shape personality and life events. Format as a concise, insightful paragraph.");
                $lifePathNumber = $this->geminiService->generateLifePathNumber("Provide a Life Path Number interpretation for someone born on {$birthDate}. Explain its significance for challenges, opportunities, and overall life journey. Format as a concise, insightful paragraph.");
                $cosmicSummary = $this->geminiService->generateCosmicSummary("Generate a Cosmic Summary for someone born on {$birthDate}. Synthesize the key insights into a brief, empowering overview. Format as a concise, insightful paragraph.");
                
                $aiContent = [
                    'souls_archetype' => $soulsArchetype,
                    'planetary_influence' => $planetaryInfluence,
                    'life_path_number' => $lifePathNumber,
                    'cosmic_summary' => $cosmicSummary
                ];
                
                $reportData['ai_content'] = $aiContent;
            } catch (\Exception $e) {
                $this->log('error', 'Gemini Service Error: ' . $e->getMessage());
                $reportData['ai_content'] = null;
            }
            
            // Create report in database
            $reportId = $this->reportRepository->create([
                'user_id' => $userId,
                'title' => $requestData['report_title'] ?: 'My Cosmic Report',
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
                return $this->error('Failed to save report to database');
            }
            
            $this->log('info', 'Report generated successfully', ['report_id' => $reportId, 'user_id' => $userId]);
            
            return $this->success('Report generated successfully', [
                'report_id' => $reportId,
                'title' => trim($requestData['report_title']) ?: 'My Cosmic Report',
                'birth_date' => $birthDate,
                'formatted_birth_date' => $this->formatDate($birthDate),
                'data' => $reportData,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error generating report: ' . $e->getMessage(), ['user_id' => $userId]);
            return $this->error('An error occurred while generating your report. Please try again.');
        }
    }
    
    /**
     * Generate premium content for subscribers
     * 
     * @param string $birthDate Birth date
     * @return array|null
     */
    public function generatePremiumContent($birthDate)
    {
        try {
            
            $soulsArchetype = $this->geminiService->generateSoulsArchetype("Provide an in-depth Soul's Archetype interpretation for someone born on {$birthDate}. Include detailed insights about core identity, life purpose, innate talents, and spiritual path. Format as comprehensive, engaging content.");
            $planetaryInfluence = $this->geminiService->generatePlanetaryInfluence("Provide detailed Planetary Influence interpretation for someone born on {$birthDate}. Include specific planetary aspects, their influence on personality, relationships, and life events. Format as comprehensive, engaging content.");
            $lifePathNumber = $this->geminiService->generateLifePathNumber("Provide an extensive Life Path Number interpretation for someone born on {$birthDate}. Include detailed analysis of challenges, opportunities, life lessons, and karmic patterns. Format as comprehensive, engaging content.");
            $cosmicSummary = $this->geminiService->generateCosmicSummary("Generate an extensive Cosmic Summary for someone born on {$birthDate}. Provide deep insights, spiritual guidance, and empowering perspectives. Format as comprehensive, engaging content.");
            
            return [
                'souls_archetype' => $soulsArchetype,
                'planetary_influence' => $planetaryInfluence,
                'life_path_number' => $lifePathNumber,
                'cosmic_summary' => $cosmicSummary
            ];
            
        } catch (\Exception $e) {
            $this->log('error', 'Error generating premium content: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fetch historical data for a given birth date
     * 
     * @param string $birthDate Birth date
     * @return array
     */
    protected function fetchHistoricalData($birthDate)
    {
        // This would contain the logic to fetch historical events, births, deaths
        // For now, returning empty structure
        return [
            'events' => [],
            'births' => [],
            'deaths' => []
        ];
    }
    
    /**
     * Generate summary from report data
     * 
     * @param array $reportData Report data
     * @return string
     */
    protected function generateSummary($reportData)
    {
        $summary = 'Cosmic report generated';
        
        if (!empty($reportData['events'])) {
            $summary .= ' with ' . count($reportData['events']) . ' historical events';
        }
        
        if (!empty($reportData['ai_content'])) {
            $summary .= ' and AI-powered insights';
        }
        
        return $summary;
    }
    
    /**
     * Format date for display
     * 
     * @param string $date Date string
     * @return string
     */
    protected function formatDate($date)
    {
        return date('F j, Y', strtotime($date));
    }
    
    /**
     * Get total reports count for user
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getTotalReportsCount($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $count = $this->reportRepository->countByUserId($userId);
                
            return $this->success('Total reports count retrieved successfully', [
                'count' => (int)$count
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error getting total reports count: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while calculating total reports');
        }
    }
    
    /**
     * Get reports created this month
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getReportsThisMonth($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $now = \Carbon\Carbon::now();
            $count = $this->reportRepository->countByUserIdThisMonth($userId);
                
            return $this->success('Reports this month retrieved successfully', [
                'count' => (int)$count
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error getting reports this month: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while calculating reports this month');
        }
    }
    
    /**
     * Calculate report completion rate
     * 
     * @param int $userId The user ID
     * @return array
     */
    public function getReportCompletionRate($userId)
    {
        try {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                return $this->error('User not found');
            }
            
            $totalReports = $this->reportRepository->countByUserId($userId);
            $completedReports = $this->reportRepository->countCompletedByUserId($userId);
            
            $rate = $totalReports > 0 ? round(($completedReports / $totalReports) * 100) : 0;
                
            return $this->success('Report completion rate calculated successfully', [
                'rate' => $rate,
                'total' => $totalReports,
                'completed' => $completedReports
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error calculating report completion rate: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return $this->error('An error occurred while calculating report completion rate');
        }
    }
    
    /**
     * Check if a report is unlocked
     * 
     * @param int $reportId The report ID
     * @return array
     */
    public function isReportUnlocked($reportId)
    {
        try {
            $report = $this->reportRepository->find($reportId);
            if (!$report) {
                return $this->error('Report not found');
            }
            
            return $this->success('Report unlock status retrieved successfully', [
                'is_unlocked' => $report->isUnlocked(),
                'unlock_method' => $report->unlock_method
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error checking report unlock status: ' . $e->getMessage(), [
                'report_id' => $reportId
            ]);
            return $this->error('An error occurred while checking report unlock status');
        }
    }
    
    /**
     * Unlock a report
     * 
     * @param int $reportId The report ID
     * @param string $method The unlock method
     * @return array
     */
    public function unlockReport($reportId, $method = null)
    {
        try {
            $report = $this->reportRepository->find($reportId);
            if (!$report) {
                return $this->error('Report not found');
            }
            
            if ($report->isUnlocked()) {
                return $this->success('Report is already unlocked', [
                    'is_unlocked' => true,
                    'unlock_method' => $report->unlock_method
                ]);
            }
            
            $success = $report->unlock($method);
            if (!$success) {
                return $this->error('Failed to unlock report');
            }
            
            $this->log('info', 'Report unlocked successfully', [
                'report_id' => $reportId,
                'unlock_method' => $method
            ]);
            
            return $this->success('Report unlocked successfully', [
                'is_unlocked' => true,
                'unlock_method' => $method
            ]);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error unlocking report: ' . $e->getMessage(), [
                'report_id' => $reportId,
                'unlock_method' => $method
            ]);
            return $this->error('An error occurred while unlocking the report');
        }
    }
}