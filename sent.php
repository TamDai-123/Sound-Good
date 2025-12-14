<?php
date_default_timezone_set('Asia/Bangkok');

$servername = "zpfp07ebhm2zgmrm.chr7pe7iynqr.eu-west-1.rds.amazonaws.com";
$username = "g8garelqz3jv88e0";
$password = "q9cts0ci024183yp";
$dbname = "imb2cytjma8phph4";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ ตั้ง timezone ให้ MySQL ด้วย
$conn->query("SET time_zone = '+07:00'");

// ตรวจสอบค่าที่ส่งมา
if (!isset($_POST['ear'], $_POST['ID_User'], $_POST['frequency_hz'])) {
    die("Missing required data");
}

$ear = $_POST['ear'];
$ID_User = (int)$_POST['ID_User'];
$freq = (int)$_POST['frequency_hz'];
$now = date("Y-m-d H:i:s");

// ตรวจชื่อ table
if ($ear !== "left_ear" && $ear !== "right_ear") {
    die("Invalid ear table name");
}

// ค่า dB
$db_25  = (int)($_POST['db_25']  ?? 0);
$db_40  = (int)($_POST['db_40']  ?? 0);
$db_55  = (int)($_POST['db_55']  ?? 0);
$db_70  = (int)($_POST['db_70']  ?? 0);
$db_90  = (int)($_POST['db_90']  ?? 0);
$db_100 = (int)($_POST['db_100'] ?? 0);

// Prepared statement
$sql = "INSERT INTO $ear
(ID_User, day, frequency_hz, db_25, db_40, db_55, db_70, db_90, db_100)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isiiiiiii",
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
