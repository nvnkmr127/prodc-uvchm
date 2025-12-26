<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=uvchm_live_app', 'root', '');
    $stmt = $pdo->prepare('SELECT key, value, is_encrypted FROM settings WHERE key = ?');
    $stmt->execute(['gdrive_client_secret']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($result) {
        echo "Setting found:\n";
        echo "Key: " . $result['key'] . "\n";
        echo "Has value: " . (!empty($result['value']) ? 'yes (' . strlen($result['value']) . ' chars)' : 'no') . "\n";
        echo "Is encrypted: " . ($result['is_encrypted'] ? 'yes' : 'no') . "\n";
        
        // Check if it looks like encrypted data
        if (!empty($result['value'])) {
            echo "Value starts with: " . substr($result['value'], 0, 20) . "...\n";
        }
    } else {
        echo "Setting not found\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}