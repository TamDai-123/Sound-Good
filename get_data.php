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
$leftRes  = $conn->query("SELECT * FROM left_ear  ORDER BY day ASC");
$rightRes = $conn->query("SELECT * FROM right_ear ORDER BY day ASC");

$leftData  = [];
$rightData = [];

while ($row = $leftRes->fetch_assoc())  $leftData[]  = $row;
while ($row = $rightRes->fetch_assoc()) $rightData[] = $row;

// ===== CONFIG =====
$FREQUENCIES = [250, 500, 1000, 2000, 4000, 8000];
$TIME_LIMIT  = 30 * 60; // เพิ่มเป็น 30 นาที เพื่อรองรับข้อมูลที่ห่างกันหน่อย

$rounds = [];
$leftPointer = 0;
$rightPointer = 0;

// ===== MAIN LOGIC =====
while ($leftPointer < count($leftData) && $rightPointer < count($rightData)) {

    $currentRound   = [];
    $roundStartTime = null;
    $tempLeftPointer  = $leftPointer;
    $tempRightPointer = $rightPointer;
    $foundFrequencies = [];

    // พยายามหาครบ 6 ความถี่
    foreach ($FREQUENCIES as $freq) {
        
        $leftFound = null;
        $rightFound = null;
        $leftIdx = null;
        $rightIdx = null;

        // หา left จากตำแหน่งปัจจุบันไปข้างหน้า
        for ($i = $tempLeftPointer; $i < count($leftData); $i++) {
            if ((int)$leftData[$i]['frequency_hz'] === $freq) {
                $leftTime = strtotime($leftData[$i]['day']);
                
                // ถ้ายังไม่กำหนดเวลาเริ่ม หรือไม่เกิน TIME_LIMIT
                if ($roundStartTime === null || abs($leftTime - $roundStartTime) <= $TIME_LIMIT) {
                    $leftFound = $leftData[$i];
                    $leftIdx = $i;
                    if ($roundStartTime === null) {
                        $roundStartTime = $leftTime;
                    }
                    break;
                } else {
                    // เกินเวลาแล้ว ไม่ต้องหาต่อ
                    break;
                }
            }
        }

        // หา right จากตำแหน่งปัจจุบันไปข้างหน้า
        for ($i = $tempRightPointer; $i < count($rightData); $i++) {
            if ((int)$rightData[$i]['frequency_hz'] === $freq) {
                $rightTime = strtotime($rightData[$i]['day']);
                
                if ($roundStartTime === null || abs($rightTime - $roundStartTime) <= $TIME_LIMIT) {
                    $rightFound = $rightData[$i];
                    $rightIdx = $i;
                    if ($roundStartTime === null) {
                        $roundStartTime = $rightTime;
                    }
                    break;
                } else {
                    break;
                }
            }
        }

        // ถ้าหาไม่เจอทั้ง 2 ฝั่ง = รอบนี้ล้มเหลว
        if ($leftFound === null || $rightFound === null) {
            // ข้ามไปแถวถัดไป (ฝั่งที่มีเวลาน้อยกว่า)
            $leftTime = ($leftPointer < count($leftData)) ? strtotime($leftData[$leftPointer]['day']) : PHP_INT_MAX;
            $rightTime = ($rightPointer < count($rightData)) ? strtotime($rightData[$rightPointer]['day']) : PHP_INT_MAX;
            
            if ($leftTime <= $rightTime) {
                $leftPointer++;
            } else {
                $rightPointer++;
            }
            break;
        }

        // เก็บข้อมูล
        $currentRound[] = [
            'frequency_hz' => $freq,
            'left'  => $leftFound,
            'right' => $rightFound
        ];

        // อัปเดต pointer ชั่วคราว
        $tempLeftPointer = $leftIdx + 1;
        $tempRightPointer = $rightIdx + 1;
    }

    // ถ้าครบ 6 ความถี่ = success!
    if (count($currentRound) === count($FREQUENCIES)) {
        $rounds[] = $currentRound;
        
        // อัปเดต pointer จริง
        $leftPointer = $tempLeftPointer;
        $rightPointer = $tempRightPointer;
    }
}

// ===== OUTPUT =====
echo json_encode([
    'total_rounds' => count($rounds),
    'rounds' => $rounds
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$conn->close();