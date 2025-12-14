<?php
$servername = "localhost";
$username = "root";
$password = "zong2411";
$dbname = "sound_good";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ ตรวจสอบค่าที่ส่งมาจาก ESP32
if (!isset($_POST['ear'], $_POST['ID_User'], $_POST['frequency_hz'])) {
    die("Missing required data");
}

$ear = $_POST['ear'];
$ID_User = $_POST['ID_User'];
$freq = $_POST['frequency_hz'];
$now = date("Y-m-d H:i:s");

// ✅ ตรวจสอบชื่อ table
if ($ear !== "left_ear" && $ear !== "right_ear") {
    die("Invalid ear table name");
}

// ✅ รับค่าระดับเสียงแต่ละ dB
$db_25 = $_POST['db_25'] ?? 0;
$db_40 = $_POST['db_40'] ?? 0;
$db_55 = $_POST['db_55'] ?? 0;
$db_70 = $_POST['db_70'] ?? 0;
$db_90 = $_POST['db_90'] ?? 0;
$db_100 = $_POST['db_100'] ?? 0;

// ✅ ใช้ prepared statement
$sql = "INSERT INTO $ear (ID_User, day, frequency_hz, db_25, db_40, db_55, db_70, db_90, db_100)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssss",
    $ID_User,
    $now,
    $freq,
    $db_25,
    $db_40,
    $db_55,
    $db_70,
    $db_90,
    $db_100
);

if ($stmt->execute()) {
    echo "OK";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
