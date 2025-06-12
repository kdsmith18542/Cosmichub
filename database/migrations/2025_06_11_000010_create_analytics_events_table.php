<?php
/**
 * Create Analytics Events Table Migration
 * 
 * This migration creates the analytics_events table for tracking user behavior,
 * feature usage, and performance metrics during Phase 3 beta testing
 */

require_once __DIR__ . '/../../app/libraries/Database.php';

use App\Libraries\Database;

try {
    $db = Database::getInstance();
    
    // Create analytics_events table
    $createAnalyticsEventsTable = "
        CREATE TABLE IF NOT EXISTS analytics_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            event_type VARCHAR(50) NOT NULL,
            event_data TEXT,
            metadata TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    $db->exec($createAnalyticsEventsTable);
    echo "✓ Created analytics_events table\n";
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_analytics_user_id ON analytics_events(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_analytics_event_type ON analytics_events(event_type)",
        "CREATE INDEX IF NOT EXISTS idx_analytics_created_at ON analytics_events(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_analytics_user_event_type ON analytics_events(user_id, event_type)",
        "CREATE INDEX IF NOT EXISTS idx_analytics_event_type_created_at ON analytics_events(event_type, created_at)"
    ];
    
    foreach ($indexes as $index) {
        $db->exec($index);
    }
    echo "✓ Created analytics indexes\n";
    
    // Create user_feedback table for beta testing feedback
    $createUserFeedbackTable = "
        CREATE TABLE IF NOT EXISTS user_feedback (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            feedback_type VARCHAR(50) NOT NULL,
            rating INTEGER,
            subject VARCHAR(255),
            message TEXT,
            page_url VARCHAR(500),
            browser_info TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    $db->exec($createUserFeedbackTable);
    echo "✓ Created user_feedback table\n";
    
    // Create indexes for user_feedback
    $feedbackIndexes = [
        "CREATE INDEX IF NOT EXISTS idx_feedback_user_id ON user_feedback(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_feedback_type ON user_feedback(feedback_type)",
        "CREATE INDEX IF NOT EXISTS idx_feedback_status ON user_feedback(status)",
        "CREATE INDEX IF NOT EXISTS idx_feedback_created_at ON user_feedback(created_at)"
    ];
    
    foreach ($feedbackIndexes as $index) {
        $db->exec($index);
    }
    echo "✓ Created user_feedback indexes\n";
    
    // Create beta_test_metrics table for tracking beta testing KPIs
    $createBetaMetricsTable = "
        CREATE TABLE IF NOT EXISTS beta_test_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            metric_name VARCHAR(100) NOT NULL,
            metric_value DECIMAL(10,2),
            metric_data TEXT,
            date_recorded DATE NOT NULL,
            created_at DATETIME NOT NULL
        )
    ";
    
    $db->exec($createBetaMetricsTable);
    echo "✓ Created beta_test_metrics table\n";
    
    // Create index for beta metrics
    $db->exec("CREATE INDEX IF NOT EXISTS idx_beta_metrics_name_date ON beta_test_metrics(metric_name, date_recorded)");
    echo "✓ Created beta_test_metrics indexes\n";
    
    // Insert initial sample analytics events for testing
    $sampleEvents = [
        [
            'user_id' => null,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/home', 'referrer' => null]),
            'metadata' => json_encode(['page_load_time' => 1.2]),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'user_id' => null,
            'event_type' => 'feature_usage',
            'event_data' => json_encode(['feature' => 'rarity_score', 'action' => 'generate']),
            'metadata' => json_encode(['execution_time' => 0.8]),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO analytics_events 
        (user_id, event_type, event_data, metadata, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($sampleEvents as $event) {
        $stmt->execute([
            $event['user_id'],
            $event['event_type'],
            $event['event_data'],
            $event['metadata'],
            $event['ip_address'],
            $event['user_agent'],
            $event['created_at']
        ]);
    }
    echo "✓ Inserted sample analytics events\n";
    
    echo "\n🎉 Analytics and beta testing tables created successfully!\n";
    echo "Phase 3 beta testing infrastructure is ready.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating analytics tables: " . $e->getMessage() . "\n";
    exit(1);
}