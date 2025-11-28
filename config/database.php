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
$conn = mysqli_connect(
    getenv('DB_SERVER'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD'),
    getenv('DB_NAME'),
    getenv('DB_PORT')  
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
