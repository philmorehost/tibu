<?php
require_once __DIR__ . '/config/config.php';

function test_url($url) {
    echo "Testing $url... ";
    $ch = curl_init("http://localhost:8000/" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "[PASS] (Valid JSON)\n";
        } else {
            echo "[FAIL] (Invalid JSON: " . $response . ")\n";
        }
    } else {
        echo "[FAIL] (HTTP $http_code)\n";
    }
}

// Note: admin API will fail without session, but we check if it returns valid 403/Redirect or JSON
test_url("api/subcategories.php?parent_id=1");
test_url("api/admin_subcategories.php?parent_id=1");
