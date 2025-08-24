<?php
// assign_review.php (BROWSER DEBUGGING VERSION)

// Set headers for plain text output to avoid browser rendering HTML
header('Content-Type: text/plain; charset=utf-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "--- START OF DEBUG OUTPUT ---\\n\\n";

try {
    $credentials_path = __DIR__ . '/firebase_credentials.json';
    if (!file_exists($credentials_path)) {
        throw new Exception('DEBUG: Firebase credentials file not found.');
    }

    $credentials_content = file_get_contents($credentials_path);
    echo "1. Credentials file content read successfully.\n";

    $credentials = json_decode($credentials_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('DEBUG: Invalid JSON in credentials file: ' . json_last_error_msg());
    }
    echo "2. Credentials JSON parsed successfully.\n";

    $private_key_from_json = $credentials['private_key'] ?? null;
    if (!$private_key_from_json) {
        throw new Exception('DEBUG: Private key not found in JSON.');
    }
    echo "3. Private key extracted from JSON.\n";

    // The fix for escaped newlines
    $private_key_processed = str_replace('\\n', "\n", $private_key_from_json);
    echo "4. Processed private key (replaced \\n with actual newlines).\n";

    // Attempt to get the key
    echo "5. Attempting to call openssl_get_privatekey...\n";
    $private_key_resource = openssl_get_privatekey($private_key_processed);
    
    echo "6. Call to openssl_get_privatekey finished.\n";

    $openssl_error = openssl_error_string();

    echo "\n--- DEBUG VALUES ---\n";
    echo "Is key resource valid? => " . ($private_key_resource !== false ? 'YES' : 'NO') . "\n";
    echo "OpenSSL Error String After Call => [" . $openssl_error . "]\n";
    echo "\n--- PROCESSED PRIVATE KEY STRING ---\n";
    echo $private_key_processed;
    echo "\n--- END OF PROCESSED PRIVATE KEY STRING ---\n";

} catch (Exception $e) {
    echo "\n--- SCRIPT FAILED WITH AN EXCEPTION ---\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Trace: \n" . $e->getTraceAsString() . "\n";
}

echo "\n--- END OF DEBUG OUTPUT ---";

?>