<?php
// assign_review.php (DEBUGGING VERSION)

ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

function send_json_response($data) {
    $stray_output = ob_get_clean();
    if (!isset($data['status'])) {
        $data['status'] = 'error';
    }
    if (!empty($stray_output) && !isset($data['stray_output'])) {
        $data['stray_output'] = $stray_output;
    }
    if (!headers_sent()) {
        header("Content-Type: application/json; charset=UTF-8");
    }
    echo json_encode($data);
    exit();
}

try {
    $credentials_path = __DIR__ . '/firebase_credentials.json';
    if (!file_exists($credentials_path)) {
        throw new Exception('DEBUG: Firebase credentials file not found.');
    }

    $credentials_content = file_get_contents($credentials_path);
    $credentials = json_decode($credentials_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('DEBUG: Invalid JSON in credentials file: ' . json_last_error_msg());
    }

    $private_key_from_json = $credentials['private_key'] ?? null;
    if (!$private_key_from_json) {
        throw new Exception('DEBUG: Private key not found in JSON.');
    }

    // The fix for escaped newlines
    $private_key_processed = str_replace('\\n', "\n", $private_key_from_json);

    // Attempt to get the key
    $private_key_resource = openssl_get_privatekey($private_key_processed);
    $openssl_error = openssl_error_string(); // Get error string right after the call

    // Send back all the debug info
    send_json_response([
        'status' => 'debug',
        'message' => 'This is a debug response to inspect the private key.',
        'is_key_resource_valid' => ($private_key_resource !== false),
        'openssl_error_string' => $openssl_error,
        'processed_private_key' => $private_key_processed
    ]);

} catch (Exception $e) {
    send_json_response(['status' => 'error', 'message' => $e->getMessage()]);
}

?>