<?php

namespace App\Support;

/**
 * String helper class
 */
class Str
{
    /**
     * Convert a string to camel case
     *
     * @param string $value
     * @return string
     */
    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    /**
     * Convert a string to studly case
     *
     * @param string $value
     * @return string
     */
    public static function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    /**
     * Convert a string to snake case
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }
        
        return $value;
    }

    /**
     * Convert a string to kebab case
     *
     * @param string $value
     * @return string
     */
    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    /**
     * Convert a string to title case
     *
     * @param string $value
     * @return string
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Generate a random string
     *
     * @param int $length
     * @return string
     */
    public static function random(int $length = 16): string
    {
        $string = '';
        
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        
        return $string;
    }

    /**
     * Check if a string starts with a given substring
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function startsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a string ends with a given substring
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function endsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a string contains a given substring
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    public static function contains(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Limit the number of characters in a string
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
        
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Limit the number of words in a string
     *
     * @param string $value
     * @param int $words
     * @param string $end
     * @return string
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);
        
        if (!isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }
        
        return rtrim($matches[0]) . $end;
    }

    /**
     * Get the length of a string
     *
     * @param string $value
     * @param string|null $encoding
     * @return int
     */
    public static function length(string $value, string $encoding = null): int
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }
        
        return mb_strlen($value);
    }

    /**
     * Convert the given string to lower-case
     *
     * @param string $value
     * @return string
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert the given string to upper-case
     *
     * @param string $value
     * @return string
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Replace the first occurrence of a given value in the string
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }
        
        $position = strpos($subject, $search);
        
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        
        return $subject;
    }

    /**
     * Replace the last occurrence of a given value in the string
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }
        
        $position = strrpos($subject, $search);
        
        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }
        
        return $subject;
    }

    /**
     * Generate a URL friendly "slug" from a given string
     *
     * @param string $title
     * @param string $separator
     * @param string|null $language
     * @return string
     */
    public static function slug(string $title, string $separator = '-', string $language = 'en'): string
    {
        $title = $language ? static::ascii($title, $language) : $title;
        
        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';
        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);
        
        // Replace @ with the word 'at'
        $title = str_replace('@', $separator . 'at' . $separator, $title);
        
        // Remove all characters that are not the separator, letters, numbers, or whitespace
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', static::lower($title));
        
        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);
        
        return trim($title, $separator);
    }

    /**
     * Transliterate a UTF-8 value to ASCII
     *
     * @param string $value
     * @param string $language
     * @return string
     */
    public static function ascii(string $value, string $language = 'en'): string
    {
        $languageSpecific = static::languageSpecificCharsArray($language);
        
        if (!is_null($languageSpecific)) {
            $value = str_replace($languageSpecific[0], $languageSpecific[1], $value);
        }
        
        return preg_replace('/[^\x20-\x7E]/u', '', $value);
    }

    /**
     * Get the language specific character replacements
     *
     * @param string $language
     * @return array|null
     */
    protected static function languageSpecificCharsArray(string $language): ?array
    {
        static $languageSpecific = [
            'de' => [
                ['Ä',  'Ö',  'Ü',  'ä',  'ö',  'ü',  'ß'],
                ['AE', 'OE', 'UE', 'ae', 'oe', 'ue', 'ss'],
            ],
        ];
        
        return $languageSpecific[$language] ?? null;
    }

    /**
     * Determine if a given string matches a given pattern
     *
     * @param string|array $pattern
     * @param string $value
     * @return bool
     */
    public static function is($pattern, string $value): bool
    {
        $patterns = is_array($pattern) ? $pattern : [$pattern];
        
        if (empty($patterns)) {
            return false;
        }
        
        foreach ($patterns as $pattern) {
            if ($pattern === $value) {
                return true;
            }
            
            $pattern = preg_quote($pattern, '#');
            $pattern = str_replace('\*', '.*', $pattern);
            
            if (preg_match('#^' . $pattern . '\z#u', $value) === 1) {
                return true;
            }
        }
        
        return false;
    }
}