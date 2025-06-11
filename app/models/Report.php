<?php
/**
 * Report Model
 * 
 * Handles all database operations for the reports table.
 */

namespace App\Models;

class Report extends Model {
    /**
     * @var string The database table name
     */
    protected static $table = 'reports';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
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
        'birth_data' => 'array',
        'content' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Find all reports by a given user ID.
     *
     * @param int $userId
     * @return array
     */
    public static function findAllByUserId(int $userId)
    {
        return static::where('user_id', $userId)->get();
    }
    
    /**
     * Get all reports for a specific user
     * 
     * @param int $userId
     * @return array
     */
    public static function getForUser($userId) {
        return self::where('user_id', '=', $userId)
                 ->orderBy('created_at', 'desc')
                 ->get();
    }
    
    /**
     * Get reports by status
     * 
     * @param string $status
     * @return array
     */
    public static function getByStatus($status) {
        return self::where('status', '=', $status)
                 ->orderBy('created_at', 'asc')
                 ->get();
    }
    
    /**
     * Get the report type as a human-readable string
     * 
     * @return string
     */
    public function getTypeName() {
        $types = self::getTypes();
        return $types[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
    
    /**
     * Get the report status as a human-readable string
     * 
     * @return string
     */
    public function getStatusName() {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? ucfirst($this->status);
    }
    
    /**
     * Get all available report types
     * 
     * @return array
     */
    public static function getTypes() {
        return [
            self::TYPE_BIRTH_CHART => 'Birth Chart',
            self::TYPE_COMPATIBILITY => 'Compatibility',
            self::TYPE_TRANSITS => 'Transits',
            self::TYPE_SOLAR_RETURN => 'Solar Return',
            self::TYPE_SYNASTRY => 'Synastry'
        ];
    }
    
    /**
     * Get all available report statuses
     * 
     * @return array
     */
    public static function getStatuses() {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed'
        ];
    }
    
    /**
     * Check if the report is pending
     * 
     * @return bool
     */
    public function isPending() {
        return $this->status === self::STATUS_PENDING;
    }
    
    /**
     * Check if the report is processing
     * 
     * @return bool
     */
    public function isProcessing() {
        return $this->status === self::STATUS_PROCESSING;
    }
    
    /**
     * Check if the report is completed
     * 
     * @return bool
     */
    public function isCompleted() {
        return $this->status === self::STATUS_COMPLETED;
    }
    
    /**
     * Check if the report failed
     * 
     * @return bool
     */
    public function isFailed() {
        return $this->status === self::STATUS_FAILED;
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
