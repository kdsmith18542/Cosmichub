<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\CelebrityReportRepository;

/**
 * Celebrity Report Service for handling celebrity report business logic
 */
class CelebrityReportService extends Service
{
    /**
     * @var CelebrityReportRepository
     */
    protected $celebrityReportRepository;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->celebrityReportRepository = $this->getRepository('CelebrityReportRepository');
    }
    
    /**
     * Get celebrity report by ID
     * 
     * @param int $id The celebrity report ID
     * @return array
     */
    public function getCelebrityReport($id)
    {
        try {
            $report = $this->celebrityReportRepository->find($id);
            
            if (!$report) {
                return $this->error('Celebrity report not found');
            }
            
            // Increment view count
            $this->celebrityReportRepository->incrementViewCount($id);
            
            return $this->success('Celebrity report retrieved successfully', $report);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving celebrity report: ' . $e->getMessage(), ['report_id' => $id]);
            return $this->error('An error occurred while retrieving the celebrity report');
        }
    }
    
    /**
     * Get celebrity report by name
     * 
     * @param string $name The celebrity name
     * @return array
     */
    public function getCelebrityReportByName($name)
    {
        try {
            $report = $this->celebrityReportRepository->findByName($name);
            
            if (!$report) {
                return $this->error('Celebrity report not found');
            }
            
            // Increment view count
            $this->celebrityReportRepository->incrementViewCount($report['id']);
            
            return $this->success('Celebrity report retrieved successfully', $report);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving celebrity report by name: ' . $e->getMessage(), ['name' => $name]);
            return $this->error('An error occurred while retrieving the celebrity report');
        }
    }
    
    /**
     * Get celebrity reports by birth date
     * 
     * @param string $birthDate Birth date (Y-m-d format)
     * @return array
     */
    public function getCelebrityReportsByBirthDate($birthDate)
    {
        try {
            if (!$this->validateDate($birthDate)) {
                return $this->error('Invalid birth date format. Use Y-m-d format.');
            }
            
            $reports = $this->celebrityReportRepository->findByBirthDate($birthDate);
            return $this->success('Celebrity reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving celebrity reports by birth date: ' . $e->getMessage(), ['birth_date' => $birthDate]);
            return $this->error('An error occurred while retrieving celebrity reports');
        }
    }
    
    /**
     * Get celebrity reports by category
     * 
     * @param string $category The category
     * @return array
     */
    public function getCelebrityReportsByCategory($category)
    {
        try {
            $reports = $this->celebrityReportRepository->findByCategory($category);
            return $this->success('Celebrity reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving celebrity reports by category: ' . $e->getMessage(), ['category' => $category]);
            return $this->error('An error occurred while retrieving celebrity reports');
        }
    }
    
    /**
     * Get featured celebrity reports
     * 
     * @return array
     */
    public function getFeaturedCelebrityReports()
    {
        try {
            $reports = $this->celebrityReportRepository->findFeatured();
            return $this->success('Featured celebrity reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving featured celebrity reports: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving featured celebrity reports');
        }
    }
    
    /**
     * Get popular celebrity reports
     * 
     * @param int $limit Number of reports to retrieve
     * @return array
     */
    public function getPopularCelebrityReports($limit = 10)
    {
        try {
            $reports = $this->celebrityReportRepository->getPopular($limit);
            return $this->success('Popular celebrity reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving popular celebrity reports: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving popular celebrity reports');
        }
    }
    
    /**
     * Get recent celebrity reports
     * 
     * @param int $limit Number of reports to retrieve
     * @return array
     */
    public function getRecentCelebrityReports($limit = 10)
    {
        try {
            $reports = $this->celebrityReportRepository->getRecent($limit);
            return $this->success('Recent celebrity reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving recent celebrity reports: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving recent celebrity reports');
        }
    }
    
    /**
     * Search celebrity reports
     * 
     * @param string $search Search term
     * @return array
     */
    public function searchCelebrityReports($search)
    {
        try {
            if (empty($search)) {
                return $this->error('Search term is required');
            }
            
            $reports = $this->celebrityReportRepository->search($search);
            return $this->success('Search completed successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error searching celebrity reports: ' . $e->getMessage(), ['search' => $search]);
            return $this->error('An error occurred while searching celebrity reports');
        }
    }
    
    /**
     * Get celebrity reports by birth month and day
     * 
     * @param int $month Birth month (1-12)
     * @param int $day Birth day (1-31)
     * @return array
     */
    public function getCelebrityReportsByBirthMonthDay($month, $day)
    {
        try {
            if (!$this->validateMonthDay($month, $day)) {
                return $this->error('Invalid month or day');
            }
            
            $reports = $this->celebrityReportRepository->getByBirthMonthDay($month, $day);
            return $this->success('Celebrity reports retrieved successfully', $reports);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving celebrity reports by birth month/day: ' . $e->getMessage(), ['month' => $month, 'day' => $day]);
            return $this->error('An error occurred while retrieving celebrity reports');
        }
    }
    
    /**
     * Create new celebrity report
     * 
     * @param array $data Celebrity report data
     * @return array
     */
    public function createCelebrityReport($data)
    {
        try {
            // Validate required fields
            $validation = $this->validateCelebrityReportData($data);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            // Check if celebrity report with same name exists
            $existing = $this->celebrityReportRepository->findByName($data['name']);
            if ($existing) {
                return $this->error('Celebrity report with this name already exists');
            }
            
            // Prepare celebrity report data
            $reportData = [
                'name' => $data['name'],
                'birth_date' => $data['birth_date'],
                'birth_time' => $data['birth_time'] ?? null,
                'birth_place' => $data['birth_place'] ?? '',
                'category' => $data['category'] ?? 'entertainment',
                'description' => $data['description'] ?? '',
                'report_content' => $data['report_content'] ?? '',
                'is_featured' => $data['is_featured'] ?? 0,
                'view_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $report = $this->celebrityReportRepository->create($reportData);
            
            if ($report) {
                $this->log('info', 'Celebrity report created successfully', ['report_id' => $report['id']]);
                return $this->success('Celebrity report created successfully', $report);
            }
            
            return $this->error('Failed to create celebrity report');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error creating celebrity report: ' . $e->getMessage());
            return $this->error('An error occurred while creating the celebrity report');
        }
    }
    
    /**
     * Update celebrity report
     * 
     * @param int $id Celebrity report ID
     * @param array $data Updated data
     * @return array
     */
    public function updateCelebrityReport($id, $data)
    {
        try {
            $report = $this->celebrityReportRepository->find($id);
            if (!$report) {
                return $this->error('Celebrity report not found');
            }
            
            // Validate data
            $validation = $this->validateCelebrityReportData($data, true);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            // Check for name conflicts (if name is being changed)
            if (isset($data['name']) && $data['name'] !== $report['name']) {
                $existing = $this->celebrityReportRepository->findByName($data['name']);
                if ($existing && $existing['id'] != $id) {
                    return $this->error('Celebrity report with this name already exists');
                }
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            $updated = $this->celebrityReportRepository->update($id, $data);
            
            if ($updated) {
                $this->log('info', 'Celebrity report updated successfully', ['report_id' => $id]);
                return $this->success('Celebrity report updated successfully');
            }
            
            return $this->error('Failed to update celebrity report');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error updating celebrity report: ' . $e->getMessage(), ['report_id' => $id]);
            return $this->error('An error occurred while updating the celebrity report');
        }
    }
    
    /**
     * Delete celebrity report
     * 
     * @param int $id Celebrity report ID
     * @return array
     */
    public function deleteCelebrityReport($id)
    {
        try {
            $report = $this->celebrityReportRepository->find($id);
            if (!$report) {
                return $this->error('Celebrity report not found');
            }
            
            $deleted = $this->celebrityReportRepository->delete($id);
            
            if ($deleted) {
                $this->log('info', 'Celebrity report deleted successfully', ['report_id' => $id]);
                return $this->success('Celebrity report deleted successfully');
            }
            
            return $this->error('Failed to delete celebrity report');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deleting celebrity report: ' . $e->getMessage(), ['report_id' => $id]);
            return $this->error('An error occurred while deleting the celebrity report');
        }
    }
    
    /**
     * Toggle celebrity report featured status
     * 
     * @param int $id Celebrity report ID
     * @return array
     */
    public function toggleFeatured($id)
    {
        try {
            $result = $this->celebrityReportRepository->toggleFeatured($id);
            
            if ($result) {
                $this->log('info', 'Celebrity report featured status toggled', ['report_id' => $id]);
                return $this->success('Celebrity report featured status updated successfully');
            }
            
            return $this->error('Failed to update celebrity report featured status');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error toggling celebrity report featured status: ' . $e->getMessage(), ['report_id' => $id]);
            return $this->error('An error occurred while updating the celebrity report featured status');
        }
    }
    
    /**
     * Get celebrity report statistics
     * 
     * @return array
     */
    public function getCelebrityReportStatistics()
    {
        try {
            $stats = $this->celebrityReportRepository->getStatistics();
            return $this->success('Celebrity report statistics retrieved successfully', $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving celebrity report statistics: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving celebrity report statistics');
        }
    }
    
    /**
     * Get paginated celebrity reports
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function getPaginatedCelebrityReports($page = 1, $perPage = 10)
    {
        try {
            $result = $this->celebrityReportRepository->paginate($page, $perPage);
            return $this->success('Celebrity reports retrieved successfully', $result);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving paginated celebrity reports: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving celebrity reports');
        }
    }
    
    /**
     * Validate celebrity report data
     * 
     * @param array $data Celebrity report data
     * @param bool $isUpdate Whether this is an update operation
     * @return array
     */
    protected function validateCelebrityReportData($data, $isUpdate = false)
    {
        $errors = [];
        
        if (!$isUpdate || isset($data['name'])) {
            if (empty($data['name'])) {
                $errors[] = 'Name is required';
            } elseif (strlen($data['name']) < 2) {
                $errors[] = 'Name must be at least 2 characters long';
            } elseif (strlen($data['name']) > 100) {
                $errors[] = 'Name must not exceed 100 characters';
            }
        }
        
        if (!$isUpdate || isset($data['birth_date'])) {
            if (empty($data['birth_date'])) {
                $errors[] = 'Birth date is required';
            } elseif (!$this->validateDate($data['birth_date'])) {
                $errors[] = 'Invalid birth date format. Use Y-m-d format.';
            }
        }
        
        if (isset($data['birth_time']) && !empty($data['birth_time'])) {
            if (!$this->validateTime($data['birth_time'])) {
                $errors[] = 'Invalid birth time format. Use H:i format.';
            }
        }
        
        if (isset($data['category'])) {
            $validCategories = ['entertainment', 'sports', 'politics', 'business', 'science', 'arts', 'other'];
            if (!in_array($data['category'], $validCategories)) {
                $errors[] = 'Invalid category';
            }
        }
        
        if (isset($data['is_featured']) && !in_array($data['is_featured'], [0, 1, true, false])) {
            $errors[] = 'Invalid featured status';
        }
        
        return $errors;
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string
     * @return bool
     */
    protected function validateDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate time format
     * 
     * @param string $time Time string
     * @return bool
     */
    protected function validateTime($time)
    {
        $t = \DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }
    
    /**
     * Validate month and day
     * 
     * @param int $month Month (1-12)
     * @param int $day Day (1-31)
     * @return bool
     */
    protected function validateMonthDay($month, $day)
    {
        return $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31;
    }
}