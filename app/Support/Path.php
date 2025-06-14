<?php

namespace App\Support;

/**
 * Path helper class
 */
class Path
{
    /**
     * Normalize a file path
     *
     * @param string $path
     * @return string
     */
    public static function normalize(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];
        
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        
        $path = implode(DIRECTORY_SEPARATOR, $absolutes);
        
        // Add leading separator for absolute paths
        if (static::isAbsolute($path) || (isset($parts[0]) && $parts[0] === '')) {
            $path = DIRECTORY_SEPARATOR . $path;
        }
        
        return $path ?: DIRECTORY_SEPARATOR;
    }

    /**
     * Join path segments
     *
     * @param string ...$paths
     * @return string
     */
    public static function join(string ...$paths): string
    {
        if (empty($paths)) {
            return '';
        }

        $joined = array_shift($paths);
        
        foreach ($paths as $path) {
            if ($path !== '') {
                if ($joined === '' || static::isAbsolute($path)) {
                    $joined = $path;
                } else {
                    $joined = rtrim($joined, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
                }
            }
        }

        return static::normalize($joined);
    }

    /**
     * Check if a path is absolute
     *
     * @param string $path
     * @return bool
     */
    public static function isAbsolute(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        // Unix-style absolute path
        if ($path[0] === '/') {
            return true;
        }

        // Windows-style absolute path
        if (PHP_OS_FAMILY === 'Windows') {
            if (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/')) {
                return true;
            }
            // UNC path
            if (strlen($path) >= 2 && ($path[0] === '\\' || $path[0] === '/') && ($path[1] === '\\' || $path[1] === '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a path is relative
     *
     * @param string $path
     * @return bool
     */
    public static function isRelative(string $path): bool
    {
        return !static::isAbsolute($path);
    }

    /**
     * Get the directory name of a path
     *
     * @param string $path
     * @return string
     */
    public static function dirname(string $path): string
    {
        return dirname($path);
    }

    /**
     * Get the base name of a path
     *
     * @param string $path
     * @param string $suffix
     * @return string
     */
    public static function basename(string $path, string $suffix = ''): string
    {
        return basename($path, $suffix);
    }

    /**
     * Get the file extension
     *
     * @param string $path
     * @return string
     */
    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the filename without extension
     *
     * @param string $path
     * @return string
     */
    public static function filename(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Get path information
     *
     * @param string $path
     * @param int|null $flags
     * @return array|string
     */
    public static function info(string $path, int $flags = null)
    {
        return $flags === null ? pathinfo($path) : pathinfo($path, $flags);
    }

    /**
     * Make a path relative to another path
     *
     * @param string $path
     * @param string $base
     * @return string
     */
    public static function relative(string $path, string $base): string
    {
        $path = static::normalize($path);
        $base = static::normalize($base);

        if ($path === $base) {
            return '.';
        }

        $pathParts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        $baseParts = explode(DIRECTORY_SEPARATOR, trim($base, DIRECTORY_SEPARATOR));

        // Remove common parts
        while (!empty($pathParts) && !empty($baseParts) && $pathParts[0] === $baseParts[0]) {
            array_shift($pathParts);
            array_shift($baseParts);
        }

        // Add '..' for each remaining base part
        $relativeParts = array_fill(0, count($baseParts), '..');
        
        // Add remaining path parts
        $relativeParts = array_merge($relativeParts, $pathParts);

        return implode(DIRECTORY_SEPARATOR, $relativeParts) ?: '.';
    }

    /**
     * Resolve a path to an absolute path
     *
     * @param string $path
     * @param string|null $base
     * @return string
     */
    public static function resolve(string $path, string $base = null): string
    {
        if (static::isAbsolute($path)) {
            return static::normalize($path);
        }

        $base = $base ?: getcwd();
        return static::normalize(static::join($base, $path));
    }

    /**
     * Check if a file or directory exists
     *
     * @param string $path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check if path is a file
     *
     * @param string $path
     * @return bool
     */
    public static function isFile(string $path): bool
    {
        return is_file($path);
    }

    /**
     * Check if path is a directory
     *
     * @param string $path
     * @return bool
     */
    public static function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Check if path is readable
     *
     * @param string $path
     * @return bool
     */
    public static function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Check if path is writable
     *
     * @param string $path
     * @return bool
     */
    public static function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Get file size
     *
     * @param string $path
     * @return int|false
     */
    public static function size(string $path)
    {
        return filesize($path);
    }

    /**
     * Get file modification time
     *
     * @param string $path
     * @return int|false
     */
    public static function modified(string $path)
    {
        return filemtime($path);
    }

    /**
     * Get file access time
     *
     * @param string $path
     * @return int|false
     */
    public static function accessed(string $path)
    {
        return fileatime($path);
    }

    /**
     * Get file creation time
     *
     * @param string $path
     * @return int|false
     */
    public static function created(string $path)
    {
        return filectime($path);
    }

    /**
     * Create a directory
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        return mkdir($path, $mode, $recursive);
    }

    /**
     * Remove a directory
     *
     * @param string $path
     * @return bool
     */
    public static function removeDirectory(string $path): bool
    {
        return rmdir($path);
    }

    /**
     * Copy a file
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function copy(string $source, string $destination): bool
    {
        return copy($source, $destination);
    }

    /**
     * Move/rename a file
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    public static function move(string $source, string $destination): bool
    {
        return rename($source, $destination);
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public static function delete(string $path): bool
    {
        return unlink($path);
    }

    /**
     * Get the real path of a file
     *
     * @param string $path
     * @return string|false
     */
    public static function realpath(string $path)
    {
        return realpath($path);
    }

    /**
     * Get the permissions of a file
     *
     * @param string $path
     * @return int|false
     */
    public static function permissions(string $path)
    {
        return fileperms($path);
    }

    /**
     * Change file permissions
     *
     * @param string $path
     * @param int $mode
     * @return bool
     */
    public static function chmod(string $path, int $mode): bool
    {
        return chmod($path, $mode);
    }

    /**
     * Get files in a directory
     *
     * @param string $directory
     * @param string $pattern
     * @param int $flags
     * @return array
     */
    public static function glob(string $directory, string $pattern = '*', int $flags = 0): array
    {
        $path = static::join($directory, $pattern);
        return glob($path, $flags) ?: [];
    }

    /**
     * Get all files in a directory recursively
     *
     * @param string $directory
     * @param string $pattern
     * @return array
     */
    public static function globRecursive(string $directory, string $pattern = '*'): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Convert path separators to the current OS
     *
     * @param string $path
     * @return string
     */
    public static function convertSeparators(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Get the common path of multiple paths
     *
     * @param array $paths
     * @return string
     */
    public static function commonPath(array $paths): string
    {
        if (empty($paths)) {
            return '';
        }

        if (count($paths) === 1) {
            return static::dirname($paths[0]);
        }

        $paths = array_map([static::class, 'normalize'], $paths);
        $pathParts = array_map(function ($path) {
            return explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
        }, $paths);

        $commonParts = [];
        $minLength = min(array_map('count', $pathParts));

        for ($i = 0; $i < $minLength; $i++) {
            $part = $pathParts[0][$i];
            $isCommon = true;

            foreach ($pathParts as $parts) {
                if ($parts[$i] !== $part) {
                    $isCommon = false;
                    break;
                }
            }

            if ($isCommon) {
                $commonParts[] = $part;
            } else {
                break;
            }
        }

        return implode(DIRECTORY_SEPARATOR, $commonParts);
    }
}