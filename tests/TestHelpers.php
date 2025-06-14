<?php

namespace Tests;

use App\Core\Application;
use App\Core\Container\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use PDO;
use Mockery;

/**
 * Test helper utilities for the CosmicHub application
 */
class TestHelpers
{
    /**
     * Create a test application instance
     *
     * @return Application
     */
    public static function createTestApplication(): Application
    {
        // Set testing environment variables
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        
        $app = Application::getInstance();
        $app->setBasePath(realpath(__DIR__ . '/../'));
        
        // Bootstrap the application
        $app->bootstrap();
        
        return $app;
    }
    
    /**
     * Create a test database connection
     *
     * @return PDO
     */
    public static function createTestDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Enable foreign key constraints
        $pdo->exec('PRAGMA foreign_keys = ON;');
        
        return $pdo;
    }
    
    /**
     * Create a mock HTTP request
     *
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return Request
     */
    public static function createRequest(
        string $method = 'GET',
        string $uri = '/',
        array $data = [],
        array $headers = []
    ): Request {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';
        
        // Set request data based on method
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $_POST = $data;
        } else {
            $_GET = $data;
        }
        
        // Set headers
        foreach ($headers as $key => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        
        return new Request();
    }
    
    /**
     * Create a mock service for testing
     *
     * @param string $class
     * @param array $methods
     * @return \Mockery\MockInterface
     */
    public static function mockService(string $class, array $methods = []): \Mockery\MockInterface
    {
        $mock = Mockery::mock($class);
        
        foreach ($methods as $method => $return) {
            $mock->shouldReceive($method)->andReturn($return);
        }
        
        return $mock;
    }
    
    /**
     * Assert that a response has the expected status code
     *
     * @param Response $response
     * @param int $expectedCode
     * @return void
     */
    public static function assertResponseStatus(Response $response, int $expectedCode): void
    {
        $actualCode = $response->getStatusCode();
        if ($actualCode !== $expectedCode) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected response status {$expectedCode}, got {$actualCode}"
            );
        }
    }
    
    /**
     * Assert that a response contains specific content
     *
     * @param Response $response
     * @param string $expectedContent
     * @return void
     */
    public static function assertResponseContains(Response $response, string $expectedContent): void
    {
        $content = $response->getContent();
        if (strpos($content, $expectedContent) === false) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Response does not contain expected content: {$expectedContent}"
            );
        }
    }
    
    /**
     * Assert that a response is JSON with expected data
     *
     * @param Response $response
     * @param array $expectedData
     * @return void
     */
    public static function assertJsonResponse(Response $response, array $expectedData = []): void
    {
        $content = $response->getContent();
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Response is not valid JSON: " . json_last_error_msg()
            );
        }
        
        if (!empty($expectedData)) {
            foreach ($expectedData as $key => $value) {
                if (!isset($data[$key]) || $data[$key] !== $value) {
                    throw new \PHPUnit\Framework\AssertionFailedError(
                        "JSON response does not contain expected data: {$key} => {$value}"
                    );
                }
            }
        }
    }
    
    /**
     * Create test database tables
     *
     * @param PDO $pdo
     * @return void
     */
    public static function createTestTables(PDO $pdo): void
    {
        // Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Credits table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS credits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                amount INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Reports table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                type VARCHAR(50) NOT NULL,
                content TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
    
    /**
     * Seed test data
     *
     * @param PDO $pdo
     * @return array
     */
    public static function seedTestData(PDO $pdo): array
    {
        // Create test user
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, name) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute(['test@example.com', password_hash('password', PASSWORD_DEFAULT), 'Test User']);
        $userId = $pdo->lastInsertId();
        
        // Create test credits
        $stmt = $pdo->prepare("
            INSERT INTO credits (user_id, amount) 
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, 100]);
        $creditId = $pdo->lastInsertId();
        
        // Create test report
        $stmt = $pdo->prepare("
            INSERT INTO reports (user_id, type, content) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, 'astrology', 'Test astrology report content']);
        $reportId = $pdo->lastInsertId();
        
        return [
            'user_id' => $userId,
            'credit_id' => $creditId,
            'report_id' => $reportId
        ];
    }
    
    /**
     * Clean up test environment
     *
     * @return void
     */
    public static function cleanup(): void
    {
        // Clear global state
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_SESSION = [];
        $_ENV['APP_ENV'] = 'testing';
        
        // Close Mockery
        Mockery::close();
    }
    
    /**
     * Create a test configuration array
     *
     * @return array
     */
    public static function getTestConfig(): array
    {
        return [
            'app' => [
                'name' => 'CosmicHub Test',
                'env' => 'testing',
                'debug' => true,
                'url' => 'http://localhost',
                'timezone' => 'UTC',
            ],
            'database' => [
                'connection' => 'sqlite',
                'database' => ':memory:',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
                'foreign_key_constraints' => true,
            ],
            'session' => [
                'name' => 'cosmichub_test_session',
                'cookie' => [
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            ],
            'cache' => [
                'default' => 'array',
                'stores' => [
                    'array' => [
                        'driver' => 'array'
                    ]
                ]
            ]
        ];
    }
}