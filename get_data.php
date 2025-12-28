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
$TIME_LIMIT  = 30 * 60; // 30 นาที

$rounds = [];
$leftPointer = 0;
$rightPointer = 0;

// ===== MAIN LOGIC =====
while ($leftPointer < count($leftData) && $rightPointer < count($rightData)) {

    $currentRound   = [];
    $roundStartTime = null;
    $tempLeftPointer  = $leftPointer;
    $tempRightPointer = $rightPointer;

    foreach ($FREQUENCIES as $freq) {
        
        $leftFound = null;
        $rightFound = null;
        $leftIdx = null;
        $rightIdx = null;

        // หา left
        for ($i = $tempLeftPointer; $i < count($leftData); $i++) {
            if ((int)$leftData[$i]['frequency_hz'] === $freq) {
                $leftTime = strtotime($leftData[$i]['day']);
                
                if ($roundStartTime === null || abs($leftTime - $roundStartTime) <= $TIME_LIMIT) {
                    $leftFound = $leftData[$i];
                    $leftIdx = $i;
                    if ($roundStartTime === null) {
                        $roundStartTime = $leftTime;
                    }
                    break;
                } else {
                    break;
                }
            }
        }

        // หา right
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

        if ($leftFound === null || $rightFound === null) {
            $leftTime = ($leftPointer < count($leftData)) ? strtotime($leftData[$leftPointer]['day']) : PHP_INT_MAX;
            $rightTime = ($rightPointer < count($rightData)) ? strtotime($rightData[$rightPointer]['day']) : PHP_INT_MAX;
            
            if ($leftTime <= $rightTime) {
                $leftPointer++;
            } else {
                $rightPointer++;
            }
            break;
        }

        // เก็บข้อมูลในรูปแบบที่ตรงกับ index.php
        $currentRound[] = [
            'frequency_hz' => $freq,
            'left'  => [
                'frequency_hz' => $leftFound['frequency_hz'],
                'db_25'  => $leftFound['db_25'],
                'db_40'  => $leftFound['db_40'],
                'db_55'  => $leftFound['db_55'],
                'db_70'  => $leftFound['db_70'],
                'db_90'  => $leftFound['db_90'],
                'db_100' => $leftFound['db_100'],
                'day'    => $leftFound['day']
            ],
            'right' => [
                'frequency_hz' => $rightFound['frequency_hz'],
                'db_25'  => $rightFound['db_25'],
                'db_40'  => $rightFound['db_40'],
                'db_55'  => $rightFound['db_55'],
                'db_70'  => $rightFound['db_70'],
                'db_90'  => $rightFound['db_90'],
                'db_100' => $rightFound['db_100'],
                'day'    => $rightFound['day']
            ]
        ];

        $tempLeftPointer = $leftIdx + 1;
        $tempRightPointer = $rightIdx + 1;
    }

    if (count($currentRound) === count($FREQUENCIES)) {
        $rounds[] = $currentRound;
        $leftPointer = $tempLeftPointer;
        $rightPointer = $tempRightPointer;
    }
}

// ส่งข้อมูลเป็น array ของ rounds
echo json_encode($rounds, JSON_UNESCAPED_UNICODE);
$conn->close();