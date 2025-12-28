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
// จำกัดจำนวนเพื่อกัน 503 (ปรับได้)
$leftRes  = $conn->query("SELECT * FROM left_ear  ORDER BY day ASC LIMIT 500");
$rightRes = $conn->query("SELECT * FROM right_ear ORDER BY day ASC LIMIT 500");

$leftData  = [];
$rightData = [];

while ($row = $leftRes->fetch_assoc())  $leftData[]  = $row;
while ($row = $rightRes->fetch_assoc()) $rightData[] = $row;

// ===== CONFIG =====
$FREQUENCIES = [250, 500, 1000, 2000, 4000, 8000];
$TIME_LIMIT  = 10 * 60; // 10 นาที (วินาที)

$rounds = [];

// ===== MAIN LOGIC =====
while (count($leftData) && count($rightData)) {

    $currentRound   = [];
    $roundStartTime = null;
    $failed         = false;

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

        // ❌ frequency ขาด → ทิ้งข้อมูลเก่าที่สุด แล้วเริ่มใหม่
        if ($leftIndex === null || $rightIndex === null) {
            array_shift($leftData);
            array_shift($rightData);
            $failed = true;
            break;
        }

        $left  = $leftData[$leftIndex];
        $right = $rightData[$rightIndex];

        $leftTime  = strtotime($left['day']);
        $rightTime = strtotime($right['day']);

        // กำหนดเวลาเริ่มรอบ
        if ($roundStartTime === null) {
            $roundStartTime = min($leftTime, $rightTime);
        }

        // ❌ เวลาเกิน → ทิ้งข้อมูลเก่าที่สุด แล้วเริ่มใหม่
        if (
            abs($leftTime  - $roundStartTime) > $TIME_LIMIT ||
            abs($rightTime - $roundStartTime) > $TIME_LIMIT
        ) {
            array_shift($leftData);
            array_shift($rightData);
            $failed = true;
            break;
        }

        // ผ่าน → เก็บข้อมูล
        $currentRound[] = [
            'frequency_hz' => $freq,
            'left'  => $left,
            'right' => $right
        ];

        // ใช้แล้วลบออก (กันซ้ำ)
        array_splice($leftData,  $leftIndex,  1);
        array_splice($rightData, $rightIndex, 1);
    }

    // ถ้ารอบนี้พัง → ไปเริ่ม while ใหม่
    if ($failed) {
        continue;
    }

    // ถ้าครบ 6 ความถี่ → นับเป็น 1 รอบ
    if (count($currentRound) === count($FREQUENCIES)) {
        $rounds[] = $currentRound;
    }
}

// ===== OUTPUT =====
echo json_encode($rounds, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$conn->close();
