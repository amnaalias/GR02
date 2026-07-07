<?php
/**
 * db.php
 * Detects whether the app is running on Madam's server or on local XAMPP,
 * and returns the correct database credentials automatically.
 *
 * Usage in any page:
 *   $db = require __DIR__ . '/db.php';
 *   $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['dbname']);
 */

$isLecturerServer = stripos($_SERVER['HTTP_HOST'] ?? '', 'bitp3353.utem.edu.my') !== false;

return $isLecturerServer
    ? [
        'host'   => '127.0.0.1',
        'user'   => 'GR02',
        'pass'   => 'abc1234',
        'dbname' => 'gr02',
      ]
    : [
        'host'   => 'localhost',
        'user'   => 'root',
        'pass'   => '',
        'dbname' => 'gr02',
      ];

/*
$host = "localhost";
$user = "GR02";
$password = "abc1234";
$database = "gr02";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");
*/
?>
