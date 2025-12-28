<?php
header('Content-Type: application/json');

// ===== DB CONFIG =====
$servername = "zpfp07ebhm2zgmrm.chr7pe7iynqr.eu-west-1.rds.amazonaws.com";
$username   = "g8garelqz3jv88e0";
$password   = "q9cts0ci024183yp";
$dbname     = "imb2cytjma8phph4";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => $conn->connect_error]));
}

// ===== LOAD DATA =====
$leftRes  = $conn->query("SELECT * FROM left_ear ORDER BY day ASC");
$rightRes = $conn->query("SELECT * FROM right_ear ORDER BY day ASC");

$leftData  = [];
$rightData = [];

while ($row = $leftRes->fetch_assoc())  $leftData[]  = $row;
while ($row = $rightRes->fetch_assoc()) $rightData[] = $row;

// ===== CONFIG =====
$FREQUENCIES = [250, 500, 1000, 2000, 4000, 8000];
$TIME_LIMIT  = 10 * 60; // 10 นาที

$rounds = [];

// ===== MAIN LOGIC =====
while (count($leftData) && count($rightData)) {

    $currentRound = [];
    $roundStartTime = null;

    foreach ($FREQUENCIES as $freq) {

        // หา left
        $leftIndex = null;
        foreach ($leftData as $i => $l) {
            if ((int)$l['frequency_hz'] === $freq) {
                $leftIndex = $i;
                break;
            }
        }

        // หา right
        $rightIndex = null;
        foreach ($rightData as $i => $r) {
            if ((int)$r['frequency_hz'] === $freq) {
                $rightIndex = $i;
                break;
            }
        }

        // ถ้าขาด → ไม่ครบ 1 รอบ
        if ($leftIndex === null || $rightIndex === null) {
            // ชุดนี้ไม่ครบ → ข้ามไปเริ่ม while รอบใหม่
            $currentRound = [];
            $roundStartTime = null;
            continue 2;
        }

        $left  = $leftData[$leftIndex];
        $right = $rightData[$rightIndex];

        $leftTime  = strtotime($left['day']);
        $rightTime = strtotime($right['day']);

        // กำหนดเวลาเริ่มรอบ
        if ($roundStartTime === null) {
            $roundStartTime = min($leftTime, $rightTime);
        }

        // เช็คช่วงเวลา
        if (
            abs($leftTime  - $roundStartTime) > $TIME_LIMIT ||
            abs($rightTime - $roundStartTime) > $TIME_LIMIT
        ) {
            // รีเซ็ตรอบ แล้วลองเริ่มใหม่จากข้อมูลถัดไป
            $currentRound = [];
            $roundStartTime = null;
            continue 2; // ข้ามไปเริ่ม while รอบใหม่
        }

        $currentRound[] = [
            'frequency_hz' => $freq,
            'left'  => $left,
            'right' => $right
        ];

        // ลบออกเพื่อไม่ให้ใช้ซ้ำ
        array_splice($leftData,  $leftIndex,  1);
        array_splice($rightData, $rightIndex, 1);
    }

    // ถ้าครบ 6 ความถี่ → นับเป็น 1 รอบ
    if (count($currentRound) === count($FREQUENCIES)) {
        $rounds[] = $currentRound;
    } else {
        break;
    }
}

// ===== OUTPUT =====
echo json_encode($rounds, JSON_UNESCAPED_UNICODE);
$conn->close();
