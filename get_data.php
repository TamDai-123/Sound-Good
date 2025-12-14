<?php
header('Content-Type: application/json');

// ตั้งค่าเชื่อมต่อ DB
$servername = "localhost";
$username = "root";
$password = "zong2411";
$dbname = "sound_good";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => $conn->connect_error]));
}

// ดึงข้อมูล Left Ear และ Right Ear
$leftRes = $conn->query("SELECT * FROM left_ear ORDER BY day ASC");
$rightRes = $conn->query("SELECT * FROM right_ear ORDER BY day ASC");

$leftData = [];
$rightData = [];

while($row = $leftRes->fetch_assoc()) $leftData[] = $row;
while($row = $rightRes->fetch_assoc()) $rightData[] = $row;

// ความถี่ตายตัว
$frequencies = [250, 500, 1000, 2000, 4000, 8000];

// จัดรอบ
$rounds = [];
$roundIndex = 0;

while (true) {
    $round = [];
    foreach ($frequencies as $freq) {
        // หา Left Ear
        $leftIndex = null;
        foreach ($leftData as $i => $l) {
            if (intval($l['frequency_hz']) === $freq) {
                $leftIndex = $i;
                break;
            }
        }

        // หา Right Ear
        $rightIndex = null;
        foreach ($rightData as $i => $r) {
            if (intval($r['frequency_hz']) === $freq) {
                $rightIndex = $i;
                break;
            }
        }

        if ($leftIndex === null || $rightIndex === null) {
            break 2; // ถ้าไม่ครบ 6 ความถี่ ให้หยุด
        }

        $round[] = [
            'frequency_hz' => $freq,
            'left' => $leftData[$leftIndex],
            'right' => $rightData[$rightIndex]
        ];

        // ลบออกจาก array เพื่อรอบถัดไป
        array_splice($leftData, $leftIndex, 1);
        array_splice($rightData, $rightIndex, 1);
    }

    if (!empty($round)) {
        $rounds[] = $round;
        $roundIndex++;
    } else {
        break;
    }
}

// ส่ง JSON กลับ
echo json_encode($rounds, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
