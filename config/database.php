<!-- <?php
// define('DB_SERVER', 'localhost');
// define('DB_USERNAME', 'root');
// define('DB_PASSWORD', '');
// define('DB_NAME', 'db_project_sia');
// $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// if($conn === false){
//     die("ERROR: Could not connect. " . mysqli_connect_error());
// }
?> -->

<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'db_project_sia';
$port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>

