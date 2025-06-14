<?php

namespace App\Models;

/**
 * BetaTestMetrics Model
 * 
 * Simple data model for beta test metrics.
 * Database operations are handled by BetaTestMetricsRepository.
 */
class BetaTestMetrics
{
    // Metric type constants
    const METRIC_DAILY_ACTIVE_USERS = 'daily_active_users';
    const METRIC_FEATURE_ADOPTION = 'feature_adoption';
    const METRIC_ERROR_RATE = 'error_rate';
    const METRIC_USER_SATISFACTION = 'user_satisfaction';
    const METRIC_PERFORMANCE_SCORE = 'performance_score';
    const METRIC_RETENTION_RATE = 'retention_rate';
    const METRIC_CONVERSION_RATE = 'conversion_rate';
    
    private $id;
    private $metricName;
    private $metricValue;
    private $metricData;
    private $dateRecorded;
    private $createdAt;
    
    public function __construct($data = [])
    {
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->metricName = $data['metric_name'] ?? null;
            $this->metricValue = $data['metric_value'] ?? null;
            $this->metricData = $data['metric_data'] ?? null;
            $this->dateRecorded = $data['date_recorded'] ?? null;
            $this->createdAt = $data['created_at'] ?? null;
        }
    }
    
    // Getters
    public function getId()
    {
        return $this->id;
    }
    
    public function getMetricName()
    {
        return $this->metricName;
    }
    
    public function getMetricValue()
    {
        return $this->metricValue;
    }
    
    public function getMetricData()
    {
        return $this->metricData;
    }
    
    public function getDateRecorded()
    {
        return $this->dateRecorded;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    // Setters
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function setMetricName($metricName)
    {
        $this->metricName = $metricName;
    }
    
    public function setMetricValue($metricValue)
    {
        $this->metricValue = $metricValue;
    }
    
    public function setMetricData($metricData)
    {
        $this->metricData = $metricData;
    }
    
    public function setDateRecorded($dateRecorded)
    {
        $this->dateRecorded = $dateRecorded;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
    
    /**
     * Convert model to array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'metric_name' => $this->metricName,
            'metric_value' => $this->metricValue,
            'metric_data' => $this->metricData,
            'date_recorded' => $this->dateRecorded,
            'created_at' => $this->createdAt
        ];
    }
}