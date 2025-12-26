<?php
/**
 * Redis Connection Test for cPanel Hosting
 * Upload this file to your public_html folder as redis-test.php
 * Then visit: https://uvchm.digicloudify.com/redis-test.php
 */

echo "<h2>🔧 Redis Connection Test for UVCHM</h2>";
echo "<hr>";

// Test 1: Check if Redis extension is loaded
echo "<h3>1. PHP Redis Extension Check</h3>";
if (extension_loaded('redis')) {
    echo "✅ Redis extension is loaded<br>";
} else {
    echo "❌ Redis extension not loaded<br>";
    echo "📋 Available extensions: " . implode(', ', get_loaded_extensions()) . "<br>";
}

// Test 2: Check Predis (composer package)
echo "<h3>2. Predis Library Check</h3>";
if (class_exists('Predis\Client')) {
    echo "✅ Predis library is available<br>";
} else {
    echo "❌ Predis library not found<br>";
}

// Test 3: Try to connect to Redis server
echo "<h3>3. Redis Server Connection Test</h3>";

// Test with native Redis extension
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $connected = $redis->connect('127.0.0.1', 6379, 5); // 5 second timeout
        
        if ($connected) {
            echo "✅ Connected to Redis server using native extension<br>";
            
            // Test basic operations
            $redis->set('test_key_' . time(), 'Hello from UVCHM!', 60);
            $testValue = $redis->get('test_key_' . time());
            echo "✅ Redis read/write operations work<br>";
            
            // Get Redis info
            $info = $redis->info();
            if ($info) {
                echo "📊 Redis Version: " . ($info['redis_version'] ?? 'Unknown') . "<br>";
                echo "📊 Memory Usage: " . ($info['used_memory_human'] ?? 'Unknown') . "<br>";
            }
            
            $redis->close();
        } else {
            echo "❌ Failed to connect to Redis server (native extension)<br>";
        }
    } catch (Exception $e) {
        echo "❌ Redis connection error (native): " . $e->getMessage() . "<br>";
    }
}

// Test 4: Test with Predis (if available)
echo "<h3>4. Predis Connection Test</h3>";
try {
    // Try to use Predis (Laravel's preferred Redis client)
    if (class_exists('Predis\Client')) {
        $predis = new Predis\Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
            'timeout' => 5.0,
        ]);
        
        $predis->connect();
        echo "✅ Connected to Redis using Predis<br>";
        
        $predis->set('predis_test_' . time(), 'Predis working!', 'EX', 60);
        echo "✅ Predis read/write operations work<br>";
        
    } else {
        echo "❌ Predis client not available<br>";
    }
} catch (Exception $e) {
    echo "❌ Predis connection error: " . $e->getMessage() . "<br>";
}

// Test 5: Environment check
echo "<h3>5. Server Environment Check</h3>";
echo "📋 PHP Version: " . PHP_VERSION . "<br>";
echo "📋 Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "📋 Operating System: " . PHP_OS . "<br>";

// Test 6: Port connectivity
echo "<h3>6. Network Connectivity Test</h3>";
$host = '127.0.0.1';
$port = 6379;
$timeout = 5;

$connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($connection) {
    echo "✅ Port 6379 is open and accepting connections<br>";
    fclose($connection);
} else {
    echo "❌ Cannot connect to port 6379. Error: $errstr ($errno)<br>";
}

// Test 7: Laravel-specific test (if Laravel is available)
echo "<h3>7. Laravel Redis Configuration Test</h3>";

// Check if we're in a Laravel environment
if (function_exists('config') || file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "🔧 Laravel environment detected<br>";
    
    // Show what Laravel would use for Redis
    echo "📋 Expected Redis Config:<br>";
    echo "&nbsp;&nbsp;Host: 127.0.0.1<br>";
    echo "&nbsp;&nbsp;Port: 6379<br>";
    echo "&nbsp;&nbsp;Client: predis<br>";
    echo "&nbsp;&nbsp;Database 0: Default<br>";
    echo "&nbsp;&nbsp;Database 1: Cache<br>";
    echo "&nbsp;&nbsp;Database 2: Queue<br>";
} else {
    echo "ℹ️ Not in Laravel environment<br>";
}

// Summary and recommendations
echo "<hr>";
echo "<h3>🎯 Summary & Next Steps</h3>";

if (extension_loaded('redis') || class_exists('Predis\Client')) {
    echo "<div style='color: green; padding: 10px; background: #f0f8f0; border: 1px solid #4CAF50;'>";
    echo "<strong>✅ GOOD NEWS!</strong><br>";
    echo "Redis support is available on your server. You can proceed with Redis configuration.";
    echo "</div>";
    
    echo "<h4>📝 To enable Redis in your Laravel app:</h4>";
    echo "<ol>";
    echo "<li>Update your .env file with the Redis settings I provided</li>";
    echo "<li>Run: <code>php artisan config:clear</code></li>";
    echo "<li>Run: <code>php artisan cache:clear</code></li>";
    echo "<li>Test with: <code>php artisan tinker</code> then <code>Cache::put('test', 'works!');</code></li>";
    echo "</ol>";
    
} else {
    echo "<div style='color: red; padding: 10px; background: #fff0f0; border: 1px solid #f44336;'>";
    echo "<strong>❌ REDIS NOT AVAILABLE</strong><br>";
    echo "Redis is not available on your current hosting plan.";
    echo "</div>";
    
    echo "<h4>🔧 Alternative Solutions:</h4>";
    echo "<ol>";
    echo "<li><strong>Contact your hosting provider</strong> (digicloudify.com) to enable Redis</li>";
    echo "<li><strong>Use Redis Cloud</strong> (free tier available)</li>";
    echo "<li><strong>Stick with database</strong> drivers for now (keep current config)</li>";
    echo "</ol>";
}

// Cleanup
echo "<hr>";
echo "<small>🧹 Test completed at " . date('Y-m-d H:i:s') . "</small>";
echo "<br><small>⚠️ Remember to delete this file after testing for security!</small>";
?>