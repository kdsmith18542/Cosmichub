<?php
/**
 * Report Model
 * 
 * Handles all database operations for the reports table.
 */

namespace App\Models;

class Report extends \App\Core\Database\Model {
    // The table name 'reports' and primary key 'id' will be inferred by the base Model.
    // Timestamps (created_at, updated_at) are handled by the base Model by default.

    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'user_id',
        'title',
        'birth_date',
        'content',
        'summary',
        'has_events',
        'has_births',
        'has_deaths',
        'created_at',
        'updated_at',
        'is_unlocked',
        'unlock_method'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'birth_date' => 'datetime', // Assuming birth_date should be a datetime object
        'content' => 'array',
        'summary' => 'string', // Ensure summary is treated as a string
        'has_events' => 'boolean',
        'has_births' => 'boolean',
        'has_deaths' => 'boolean',
        // created_at and updated_at are handled by the base Model and cast to Carbon instances
        'is_unlocked' => 'boolean'
    ];
    
    /**
     * Report type constants
     */
    const TYPE_BIRTH_CHART = 'birth_chart';
    const TYPE_COMPATIBILITY = 'compatibility';
    const TYPE_TRANSITS = 'transits';
    const TYPE_SOLAR_RETURN = 'solar_return';
    const TYPE_SYNASTRY = 'synastry';
    
    /**
     * Report status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    /**
     * Get the user that owns the report.
     */
    public function user(): \App\Core\Database\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Find all reports by a given user ID.
     *
     * @param int $userId
     * @return array
     */
    public static function findAllByUserId(int $userId): \App\Core\Database\Collection
    {
        return static::query()->where('user_id', $userId)->get();
    }
    
    /**
     * Get all reports for a specific user
     * 
     * @param int $userId
     * @return array
     */
    public static function getForUser(int $userId): \App\Core\Database\Collection
    {
        return static::query()->where('user_id', $userId)
                 ->orderBy('created_at', 'desc')
                 ->get();
    }
    
    /**
     * Get reports by status
     * 
     * @param string $status
     * @return array
     */
    public static function getByStatus(string $status): \App\Core\Database\Collection
    {
        return static::query()->where('status', $status)
                 ->orderBy('created_at', 'asc')
                 ->get();
    }
    
    /**
     * Get the report type as a human-readable string
     * 
     * @return string
     */
    public function getTypeName(): string
    {
        $types = static::getTypes();
        return $types[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
    
    /**
     * Get the report status as a human-readable string
     * 
     * @return string
     */
    public function getStatusName(): string
    {
        $statuses = static::getStatuses();
        return $statuses[$this->status] ?? ucfirst($this->status);
    }
    
    /**
     * Get all available report types
     * 
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            static::TYPE_BIRTH_CHART => 'Birth Chart',
            static::TYPE_COMPATIBILITY => 'Compatibility',
            static::TYPE_TRANSITS => 'Transits',
            static::TYPE_SOLAR_RETURN => 'Solar Return',
            static::TYPE_SYNASTRY => 'Synastry'
        ];
    }
    
    /**
     * Get all available report statuses
     * 
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            static::STATUS_PENDING => 'Pending',
            static::STATUS_PROCESSING => 'Processing',
            static::STATUS_COMPLETED => 'Completed',
            static::STATUS_FAILED => 'Failed'
        ];
    }
    
    /**
     * Check if the report is pending
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === static::STATUS_PENDING;
    }
    
    /**
     * Check if the report is processing
     * 
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->status === static::STATUS_PROCESSING;
    }
    
    /**
     * Check if the report is completed
     * 
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === static::STATUS_COMPLETED;
    }
    
    /**
     * Check if the report failed
     * 
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === static::STATUS_FAILED;
    }
    
    /**
     * Mark the report as processing
     * 
     * @return bool
     */
    public function markAsProcessing() {
        $this->status = self::STATUS_PROCESSING;
        $this->updated_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Mark the report as completed
     * 
     * @param array $content The report content
     * @return bool
     */
    public function markAsCompleted($content) {
        $this->status = self::STATUS_COMPLETED;
        $this->content = $content;
        $this->updated_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Mark the report as failed
     * 
     * @param string $errorMessage Optional error message
     * @return bool
     */
    public function markAsFailed($errorMessage = null) {
        $this->status = self::STATUS_FAILED;
        if ($errorMessage) {
            $this->content = ['error' => $errorMessage];
        }
        $this->updated_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Get the birth data as an array
     * 
     * @return array
     */
    public function getBirthData() {
        if (is_string($this->birth_data)) {
            return json_decode($this->birth_data, true) ?: [];
        }
        return (array) $this->birth_data;
    }
    
    /**
     * Get the report content as an array
     * 
     * @return array
     */
    public function getContent() {
        if (is_string($this->content)) {
            return json_decode($this->content, true) ?: [];
        }
        return (array) $this->content;
    }
    
    /**
     * Get the report file name for download
     * 
     * @param string $format The file format (pdf, html, txt)
     * @return string
     */
    public function getFileName($format = 'pdf') {
        $name = str_replace(' ', '_', $this->name);
        $type = str_replace('_', '-', $this->type);
        $date = date('Y-m-d', strtotime($this->created_at));
        
        return "{$name}_{$type}_{$date}.{$format}";
    }
    
    /**
     * Check if the report is unlocked
     *
     * @return bool
     */
    public function isUnlocked() {
        return (bool) $this->is_unlocked;
    }

    /**
     * Set the report as unlocked
     *
     * @param string $method
     * @return bool
     */
    public function unlock($method = null) {
        $this->is_unlocked = true;
        $this->unlock_method = $method;
        $this->updated_at = date('Y-m-d H:i:s');
        return $this->save();
    }
}
