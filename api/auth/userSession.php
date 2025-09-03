<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");

// ✅ Step 2: credentials allow করতে হবে
header("Access-Control-Allow-Credentials: true");

// ✅ Step 3: Content type
header("Content-Type: application/json");

// যদি ইউজার লগইন থাকে
if (isset($_SESSION['user'])) {
    echo json_encode([
        'status' => 'success',
        'user' => $_SESSION['user']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in'
    ]);
}
