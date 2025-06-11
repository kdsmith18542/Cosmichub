<?php

namespace App\Models;

class CelebrityReport extends Model
{
    /**
     * @var string The database table name
     */
    protected static $table = 'celebrity_reports';
    
    /**
     * @var string The primary key for the table
     */
    protected static $primaryKey = 'id';
    
    /**
     * @var array The model's fillable attributes
     */
    protected $fillable = [
        'name',
        'birth_date',
        'report_content',
        'slug',
        'created_at',
        'updated_at'
    ];
    
    /**
     * The attributes that should be cast
     * 
     * @var array
     */
    protected $casts = [
        'birth_date' => 'date',
        'report_content' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Generate a unique slug for the celebrity report
     *
     * @param string $name
     * @return string
     */
    public static function generateSlug($name)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Find a celebrity report by its slug
     *
     * @param string $slug
     * @return CelebrityReport|null
     */
    public static function findBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Search celebrity reports by name
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public static function searchByName($query, $limit = 10)
    {
        return self::where('name', 'LIKE', "%{$query}%")
                   ->orderBy('name', 'ASC')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get celebrity reports by birth month and day
     *
     * @param int $month
     * @param int $day
     * @return array
     */
    public static function getByBirthDate($month, $day)
    {
        return self::where('birth_date', 'LIKE', "%-{$month:02d}-{$day:02d}")
                   ->orderBy('name', 'ASC')
                   ->get();
    }

    /**
     * Get the most recently added celebrity reports
     *
     * @param int $limit
     * @return array
     */
    public static function getRecent($limit = 10)
    {
        return self::orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->get();
    }
    
    public function archetypes()
    {
        return $this->belongsToMany(Archetype::class, 'archetype_celebrity', 'celebrity_report_id', 'archetype_id');
    }
}