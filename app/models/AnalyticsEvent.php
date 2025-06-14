<?php
/**
 * AnalyticsEvent Model
 * 
 * Data model for analytics events - database operations handled by AnalyticsRepository
 */

namespace App\Models;

class AnalyticsEvent
{
    // Event types constants
    const EVENT_PAGE_VIEW = 'page_view';
    const EVENT_FEATURE_USAGE = 'feature_usage';
    const EVENT_USER_ACTION = 'user_action';
    const EVENT_ERROR = 'error';
    const EVENT_PERFORMANCE = 'performance';
    const EVENT_CONVERSION = 'conversion';
    const EVENT_ENGAGEMENT = 'engagement';
    
    protected $userId;
    protected $eventType;
    protected $eventData;
    protected $metadata;
    protected $ipAddress;
    protected $userAgent;
    protected $createdAt;
    
    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->fill($data);
        }
    }
    
    /**
     * Fill the model with data
     */
    public function fill($data)
    {
        $this->userId = $data['user_id'] ?? null;
        $this->eventType = $data['event_type'] ?? null;
        $this->eventData = $data['event_data'] ?? [];
        $this->metadata = $data['metadata'] ?? [];
        $this->ipAddress = $data['ip_address'] ?? null;
        $this->userAgent = $data['user_agent'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }
    
    /**
     * Get user ID
     */
    public function getUserId()
    {
        return $this->userId;
    }
    
    /**
     * Get event type
     */
    public function getEventType()
    {
        return $this->eventType;
    }
    
    /**
     * Get event data
     */
    public function getEventData()
    {
        return $this->eventData;
    }
    
    /**
     * Get metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
    
    /**
     * Get IP address
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }
    
    /**
     * Get user agent
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }
    
    /**
     * Get created at timestamp
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * Convert model to array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->userId,
            'event_type' => $this->eventType,
            'event_data' => $this->eventData,
            'metadata' => $this->metadata,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt
        ];
    }
    }
}
?>