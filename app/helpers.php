<?php
/**
 * Helper functions for the application
 */

if (!function_exists('config')) {
    /**
     * Get a configuration value
     * 
     * @param string $key Dot notation key (e.g., 'database.host')
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    function config($key, $default = null) {
        try {
            // Try to get config from application container first
            $app = \App\Core\Application::getInstance();
            $container = $app->getContainer();
            
            if ($container->has('config')) {
                $configManager = $container->get('config');
                return $configManager->get($key, $default);
            }
            
            // If no config manager, try enhanced config manager
            if ($container->has(\App\Core\Config\EnhancedConfigManager::class)) {
                $configManager = $container->get(\App\Core\Config\EnhancedConfigManager::class);
                return $configManager->get($key, $default);
            }
            
            return $default;
            
        } catch (Exception $e) {
            // Log the error for debugging
            if ($container->has(\Psr\Log\LoggerInterface::class)) {
                $logger = $container->get(\Psr\Log\LoggerInterface::class);
                $logger->error('Config helper error: ' . $e->getMessage(), ['exception' => $e]);
            }
            return $default;
        }
    }
}

if (!function_exists('loadEnv')) {
    /**
     * Load environment variables from .env file
     */
    function loadEnv($path) {
        if (!file_exists($path)) {
            return false;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse the line
            list($name, $value) = array_map('trim', explode('=', $line, 2));
            $name = trim($name);
            $value = trim($value, "'\" ");
            
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
        
        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null) {
        // First check getenv()
        $value = getenv($key);
        
        // Then check $_ENV
        if ($value === false && array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        }
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans to actual booleans
        $lowerValue = strtolower($value);
        if (in_array($lowerValue, ['true', 'false'])) {
            return $lowerValue === 'true';
        }
        
        // Convert numeric strings to numbers
        if (is_numeric($value)) {
            return strpos($value, '.') === false ? (int)$value : (float)$value;
        }
        
        return $value;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump the passed variables and end the script
     * 
     * @param mixed ...$args
     * @return void
     */
    function dd(...$args) {
        foreach ($args as $arg) {
            // Debugging output removed for production
        }
        die(1);
    }
}

if (!function_exists('abort')) {
    /**
     * Throw an HttpException with the given data
     * 
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return void
     * @throws HttpException
     */
    function abort($code, $message = '', array $headers = []) {
        throw new HttpException($code, $message, null, $headers);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a new URL
     * 
     * @param string $url
     * @param int $status
     * @return void
     */
    function redirect($url, $status = 302) {
        header('Location: ' . $url, true, $status);
        exit;
    }
}

if (!function_exists('back')) {
    /**
     * Redirect back to the previous page
     * 
     * @return void
     */
    function back() {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        redirect($url);
    }
}

if (!function_exists('session')) {
    /**
     * Get or set a session value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function session($key = null, $default = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (is_null($key)) {
            return $_SESSION;
        }
        
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
            return null;
        }
        
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Flash a message to the session
     * 
     * @param string $key
     * @param string $message
     * @return void
     */
    function flash($key, $message = null) {
        if (is_null($message)) {
            $message = session("_flash.{$key}");
            unset($_SESSION["_flash.{$key}"]);
            return $message;
        }
        
        session(["_flash.{$key}" => $message]);
    }
}

if (!function_exists('snake_case')) {
    /**
     * Convert a string to snake_case.
     *
     * @param string $value
     * @return string
     */
    function snake_case($value)
    {
        $value = preg_replace('/\s+/u', '', ucwords($value));
        return preg_replace('/(.)(?=[A-Z])/u', '$1_', $value);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class basename of the given object or class name.
     *
     * @param string|object $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date using the application's configured date format
     * 
     * @param string|int $timestamp Date string or timestamp
     * @return string Formatted date
     */
    function format_date($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        $format = config('app.date_format', 'm/d/Y');
        
        if (is_numeric($timestamp)) {
            return date($format, $timestamp);
        }
        
        return date($format, strtotime($timestamp));
    }
}

if (!function_exists('format_time')) {
    /**
     * Format a time using the application's configured time format
     * 
     * @param string|int $timestamp Time string or timestamp
     * @return string Formatted time
     */
    function format_time($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        $format = config('app.time_format', 'h:i A');
        
        if (is_numeric($timestamp)) {
            return date($format, $timestamp);
        }
        
        return date($format, strtotime($timestamp));
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a datetime using the application's configured date and time formats
     * 
     * @param string|int $timestamp DateTime string or timestamp
     * @return string Formatted datetime
     */
    function format_datetime($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        $dateFormat = config('app.date_format', 'm/d/Y');
        $timeFormat = config('app.time_format', 'h:i A');
        $format = $dateFormat . ' ' . $timeFormat;
        
        if (is_numeric($timestamp)) {
            return date($format, $timestamp);
        }
        
        return date($format, strtotime($timestamp));
    }
}

if (!function_exists('format_date_short')) {
    /**
     * Format a date in short format (e.g., Jan 15, 2024)
     * 
     * @param string|int $timestamp Date string or timestamp
     * @return string Formatted date
     */
    function format_date_short($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        $format = 'M j, Y'; // Short month format
        
        if (is_numeric($timestamp)) {
            return date($format, $timestamp);
        }
        
        return date($format, strtotime($timestamp));
    }
}

if (!function_exists('format_date_long')) {
    /**
     * Format a date in long format (e.g., January 15, 2024)
     * 
     * @param string|int $timestamp Date string or timestamp
     * @return string Formatted date
     */
    function format_date_long($timestamp) {
        if (empty($timestamp)) {
            return '';
        }
        
        $format = 'F j, Y'; // Full month format
        
        if (is_numeric($timestamp)) {
            return date($format, $timestamp);
        }
        
        return date($format, strtotime($timestamp));
     }
}

if (!function_exists('old')) {
    /**
     * Retrieve an old input value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function old($key, $default = null) {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value
     * 
     * @param string $formName The name/identifier for the form
     * @return string
     */
    function csrf_token($formName = 'default')
    {
        if (!class_exists('App\\Libraries\\Security\\CSRF')) {
            require_once __DIR__ . '/Libraries/Security/CSRF.php';
        }
        return \App\Libraries\Security\CSRF::generateToken($formName);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field
     * 
     * @param string $formName The name/identifier for the form
     * @return string
     */
    function csrf_field($formName = 'default')
    {
        if (!class_exists('App\\Libraries\\Security\\CSRF')) {
            require_once __DIR__ . '/Libraries/Security/CSRF.php';
        }
        return '\n' . \App\Libraries\Security\CSRF::getTokenField($formName);
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Verify the CSRF token for the current request
     * 
     * @param string $formName The name/identifier for the form
     * @param bool $throwException Whether to throw an exception on failure
     * @return bool
     * @throws \RuntimeException If token is invalid and $throwException is true
     */
    function csrf_verify($formName = 'default', $throwException = true)
    {
        if (!class_exists('App\\Libraries\\Security\\CSRF')) {
            require_once __DIR__ . '/Libraries/Security/CSRF.php';
        }
        
        $token = $_POST['_token'] ?? $_GET['_token'] ?? '';
        return \App\Libraries\Security\CSRF::validateToken($token, $formName);
    }
}

if (!function_exists('csrf_verify_request')) {
    /**
     * Verify the CSRF token for the current request and throw an exception if invalid
     * 
     * @param string $formName The name/identifier for the form
     * @return bool
     * @throws \RuntimeException If token is invalid
     */
    function csrf_verify_request($formName = 'default')
    {
        return csrf_verify($formName, true);
    }
}

if (!function_exists('method_field')) {
    /**
     * Generate a form method field
     * 
     * @param string $method
     * @return string
     */
    function method_field($method) {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset URL
     * 
     * @param string $path
     * @return string
     */
    function asset($path) {
        static $baseUrl = null;
        
        if ($baseUrl === null) {
            $baseUrl = rtrim(env('APP_URL', ''), '/');
        }
        
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for the application
     * 
     * @param string $path
     * @param array $parameters
     * @param bool $secure
     * @return string
     */
    function url($path = '', $parameters = [], $secure = null) {
        $baseUrl = rtrim(env('APP_URL', ''), '/');
        $url = $baseUrl . '/' . ltrim($path, '/');
        
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }
        
        return $url;
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL to a named route
     * 
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    function route($name, $parameters = [], $absolute = true) {
        // This would be implemented with a router
        // For now, we'll just return a basic URL
        return url($name, $parameters);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * @param  string|null  $value
     * @param  bool  $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = true)
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('sanitize_input')) {
    /**
     * Sanitize a string input to prevent XSS.
     *
     * @param string|null $string The string to sanitize.
     * @return string The sanitized string.
     */
    function sanitize_input(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars(trim($string), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash the given value against the bcrypt algorithm
     * 
     * @param string $value
     * @param array $options
     * @return string
     */
    function bcrypt($value, $options = []) {
        $cost = $options['rounds'] ?? 10;
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}

if (!function_exists('now')) {
    /**
     * Get the current date and time
     * 
     * @param string $format
     * @return string|DateTime
     */
    function now($format = null) {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        return $format ? $now->format($format) : $now;
    }
}

if (!function_exists('today')) {
    /**
     * Get the current date
     * 
     * @param string $format
     * @return string|DateTime
     */
    function today($format = null) {
        $today = new DateTime('today', new DateTimeZone('UTC'));
        return $format ? $today->format($format) : $today;
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a random string
     * 
     * @param int $length
     * @return string
     */
    function str_random($length = 16) {
        $string = '';
        
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        
        return $string;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string
     * 
     * @param string $value
     * @param bool $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = true) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('view')) {
    /**
     * Get a view instance or render a view
     * 
     * @param string $view
     * @param array $data
     * @return string
     */
    function view($view = null, $data = []) {
        static $viewInstance = null;
        
        if (is_null($viewInstance)) {
            $viewInstance = new View();
        }
        
        if (is_null($view)) {
            return $viewInstance;
        }
        
        return $viewInstance->make($view, $data);
    }
}

if (!function_exists('response')) {
    /**
     * Return a new response
     * 
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function response($content = '', $status = 200, array $headers = []) {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    /**
     * Return a new JSON response
     * 
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return JsonResponse
     */
    function json($data = [], $status = 200, array $headers = [], $options = 0) {
        return new JsonResponse($data, $status, $headers, $options);
    }
}

if (!function_exists('abort_if')) {
    /**
     * Throw an HttpException with the given data if the given condition is true
     * 
     * @param bool $boolean
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return void
     * @throws HttpException
     */
    function abort_if($boolean, $code, $message = '', array $headers = []) {
        if ($boolean) {
            abort($code, $message, $headers);
        }
    }
}

if (!function_exists('abort_unless')) {
    /**
     * Throw an HttpException with the given data unless the given condition is true
     * 
     * @param bool $boolean
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return void
     * @throws HttpException
     */
    function abort_unless($boolean, $code, $message = '', array $headers = []) {
        if (!$boolean) {
            abort($code, $message, $headers);
        }
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class
     * 
     * @param string|object $class
     * @return string
     */
    function class_basename($class) {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value
     * 
     * @param mixed $value
     * @return mixed
     */
    function value($value) {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value, optionally passed through the given callback
     * 
     * @param mixed $value
     * @param callable|null $callback
     * @return mixed
     */
    function with($value, callable $callback = null) {
        return is_null($callback) ? $value : $callback($value);
    }
}
