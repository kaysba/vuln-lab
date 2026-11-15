<?php
// Single MySQL connection (mysqli). No vulnerable code here.
function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli(
        getenv('DB_HOST'),
        getenv('DB_USER'),
        getenv('DB_PASS'),
        getenv('DB_NAME')
    );

    if ($conn->connect_errno) {
        http_response_code(500);
        exit('Database unavailable');
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}
