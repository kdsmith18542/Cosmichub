<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Repositories\ArchetypeRepository;

/**
 * Archetype Service for handling archetype business logic
 */
class ArchetypeService extends Service
{
    /**
     * @var ArchetypeRepository
     */
    protected $archetypeRepository;
    
    /**
     * Initialize the service
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->archetypeRepository = $this->getRepository('ArchetypeRepository');
    }
    
    /**
     * Get all active archetypes
     * 
     * @return array
     */
    public function getActiveArchetypes()
    {
        try {
            $archetypes = $this->archetypeRepository->findActive();
            return $this->success('Active archetypes retrieved successfully', $archetypes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving active archetypes: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving archetypes');
        }
    }
    
    /**
     * Get archetype by ID
     * 
     * @param int $id The archetype ID
     * @return array
     */
    public function getArchetype($id)
    {
        try {
            $archetype = $this->archetypeRepository->find($id);
            
            if (!$archetype) {
                return $this->error('Archetype not found');
            }
            
            return $this->success('Archetype retrieved successfully', $archetype);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving archetype: ' . $e->getMessage(), ['archetype_id' => $id]);
            return $this->error('An error occurred while retrieving the archetype');
        }
    }
    
    /**
     * Get archetype by name
     * 
     * @param string $name The archetype name
     * @return array
     */
    public function getArchetypeByName($name)
    {
        try {
            $archetype = $this->archetypeRepository->findByName($name);
            
            if (!$archetype) {
                return $this->error('Archetype not found');
            }
            
            return $this->success('Archetype retrieved successfully', $archetype);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving archetype by name: ' . $e->getMessage(), ['name' => $name]);
            return $this->error('An error occurred while retrieving the archetype');
        }
    }
    
    /**
     * Get archetype by slug
     * 
     * @param string $slug The archetype slug
     * @return array
     */
    public function getArchetypeBySlug($slug)
    {
        try {
            $archetype = $this->archetypeRepository->findBySlug($slug);
            
            if (!$archetype) {
                return $this->error('Archetype not found');
            }
            
            return $this->success('Archetype retrieved successfully', $archetype);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving archetype by slug: ' . $e->getMessage(), ['slug' => $slug]);
            return $this->error('An error occurred while retrieving the archetype');
        }
    }
    
    /**
     * Get archetypes by category
     * 
     * @param string $category The archetype category
     * @return array
     */
    public function getArchetypesByCategory($category)
    {
        try {
            $archetypes = $this->archetypeRepository->findByCategory($category);
            return $this->success('Archetypes retrieved successfully', $archetypes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving archetypes by category: ' . $e->getMessage(), ['category' => $category]);
            return $this->error('An error occurred while retrieving archetypes');
        }
    }
    
    /**
     * Get featured archetypes
     * 
     * @return array
     */
    public function getFeaturedArchetypes()
    {
        try {
            $archetypes = $this->archetypeRepository->findFeatured();
            return $this->success('Featured archetypes retrieved successfully', $archetypes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving featured archetypes: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving featured archetypes');
        }
    }
    
    /**
     * Get random archetypes
     * 
     * @param int $limit Number of archetypes to retrieve
     * @return array
     */
    public function getRandomArchetypes($limit = 5)
    {
        try {
            $archetypes = $this->archetypeRepository->getRandom($limit);
            return $this->success('Random archetypes retrieved successfully', $archetypes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving random archetypes: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving random archetypes');
        }
    }
    
    /**
     * Search archetypes
     * 
     * @param string $search Search term
     * @return array
     */
    public function searchArchetypes($search)
    {
        try {
            if (empty($search)) {
                return $this->error('Search term is required');
            }
            
            $archetypes = $this->archetypeRepository->search($search);
            return $this->success('Search completed successfully', $archetypes);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error searching archetypes: ' . $e->getMessage(), ['search' => $search]);
            return $this->error('An error occurred while searching archetypes');
        }
    }
    
    /**
     * Create new archetype
     * 
     * @param array $data Archetype data
     * @return array
     */
    public function createArchetype($data)
    {
        try {
            // Validate required fields
            $validation = $this->validateArchetypeData($data);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            // Check if archetype with same name exists
            $existing = $this->archetypeRepository->findByName($data['name']);
            if ($existing) {
                return $this->error('Archetype with this name already exists');
            }
            
            // Prepare archetype data
            $archetypeData = [
                'name' => $data['name'],
                'description' => $data['description'],
                'category' => $data['category'] ?? 'general',
                'traits' => $data['traits'] ?? '',
                'is_active' => $data['is_active'] ?? 1,
                'is_featured' => $data['is_featured'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $archetype = $this->archetypeRepository->create($archetypeData);
            
            if ($archetype) {
                $this->log('info', 'Archetype created successfully', ['archetype_id' => $archetype['id']]);
                return $this->success('Archetype created successfully', $archetype);
            }
            
            return $this->error('Failed to create archetype');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error creating archetype: ' . $e->getMessage());
            return $this->error('An error occurred while creating the archetype');
        }
    }
    
    /**
     * Update archetype
     * 
     * @param int $id Archetype ID
     * @param array $data Updated data
     * @return array
     */
    public function updateArchetype($id, $data)
    {
        try {
            $archetype = $this->archetypeRepository->find($id);
            if (!$archetype) {
                return $this->error('Archetype not found');
            }
            
            // Validate data
            $validation = $this->validateArchetypeData($data, true);
            if (!empty($validation)) {
                return $this->error('Validation failed', $validation);
            }
            
            // Check for name conflicts (if name is being changed)
            if (isset($data['name']) && $data['name'] !== $archetype['name']) {
                $existing = $this->archetypeRepository->findByName($data['name']);
                if ($existing && $existing['id'] != $id) {
                    return $this->error('Archetype with this name already exists');
                }
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            $updated = $this->archetypeRepository->update($id, $data);
            
            if ($updated) {
                $this->log('info', 'Archetype updated successfully', ['archetype_id' => $id]);
                return $this->success('Archetype updated successfully');
            }
            
            return $this->error('Failed to update archetype');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error updating archetype: ' . $e->getMessage(), ['archetype_id' => $id]);
            return $this->error('An error occurred while updating the archetype');
        }
    }
    
    /**
     * Delete archetype
     * 
     * @param int $id Archetype ID
     * @return array
     */
    public function deleteArchetype($id)
    {
        try {
            $archetype = $this->archetypeRepository->find($id);
            if (!$archetype) {
                return $this->error('Archetype not found');
            }
            
            $deleted = $this->archetypeRepository->delete($id);
            
            if ($deleted) {
                $this->log('info', 'Archetype deleted successfully', ['archetype_id' => $id]);
                return $this->success('Archetype deleted successfully');
            }
            
            return $this->error('Failed to delete archetype');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error deleting archetype: ' . $e->getMessage(), ['archetype_id' => $id]);
            return $this->error('An error occurred while deleting the archetype');
        }
    }
    
    /**
     * Toggle archetype active status
     * 
     * @param int $id Archetype ID
     * @return array
     */
    public function toggleActive($id)
    {
        try {
            $result = $this->archetypeRepository->toggleActive($id);
            
            if ($result) {
                $this->log('info', 'Archetype active status toggled', ['archetype_id' => $id]);
                return $this->success('Archetype status updated successfully');
            }
            
            return $this->error('Failed to update archetype status');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error toggling archetype status: ' . $e->getMessage(), ['archetype_id' => $id]);
            return $this->error('An error occurred while updating the archetype status');
        }
    }
    
    /**
     * Toggle archetype featured status
     * 
     * @param int $id Archetype ID
     * @return array
     */
    public function toggleFeatured($id)
    {
        try {
            $result = $this->archetypeRepository->toggleFeatured($id);
            
            if ($result) {
                $this->log('info', 'Archetype featured status toggled', ['archetype_id' => $id]);
                return $this->success('Archetype featured status updated successfully');
            }
            
            return $this->error('Failed to update archetype featured status');
            
        } catch (\Exception $e) {
            $this->log('error', 'Error toggling archetype featured status: ' . $e->getMessage(), ['archetype_id' => $id]);
            return $this->error('An error occurred while updating the archetype featured status');
        }
    }
    
    /**
     * Get archetype statistics
     * 
     * @return array
     */
    public function getArchetypeStatistics()
    {
        try {
            $stats = $this->archetypeRepository->getStatistics();
            return $this->success('Archetype statistics retrieved successfully', $stats);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving archetype statistics: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving archetype statistics');
        }
    }
    
    /**
     * Get paginated archetypes
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function getPaginatedArchetypes($page = 1, $perPage = 10)
    {
        try {
            $result = $this->archetypeRepository->paginate($page, $perPage);
            return $this->success('Archetypes retrieved successfully', $result);
            
        } catch (\Exception $e) {
            $this->log('error', 'Error retrieving paginated archetypes: ' . $e->getMessage());
            return $this->error('An error occurred while retrieving archetypes');
        }
    }
    
    /**
     * Validate archetype data
     * 
     * @param array $data Archetype data
     * @param bool $isUpdate Whether this is an update operation
     * @return array
     */
    protected function validateArchetypeData($data, $isUpdate = false)
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
        
        if (!$isUpdate || isset($data['description'])) {
            if (empty($data['description'])) {
                $errors[] = 'Description is required';
            } elseif (strlen($data['description']) < 10) {
                $errors[] = 'Description must be at least 10 characters long';
            }
        }
        
        if (isset($data['category'])) {
            $validCategories = ['general', 'personality', 'career', 'relationship', 'spiritual'];
            if (!in_array($data['category'], $validCategories)) {
                $errors[] = 'Invalid category';
            }
        }
        
        if (isset($data['is_active']) && !in_array($data['is_active'], [0, 1, true, false])) {
            $errors[] = 'Invalid active status';
        }
        
        if (isset($data['is_featured']) && !in_array($data['is_featured'], [0, 1, true, false])) {
            $errors[] = 'Invalid featured status';
        }
        
        return $errors;
    }
}