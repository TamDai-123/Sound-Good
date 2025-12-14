<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SoundGood</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#fff; padding:20px;}
.container { max-width:1200px; margin:0 auto; background:linear-gradient(to bottom,#fff,#f0f8ff); border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.3); overflow:hidden;}
.header { background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%); padding:30px 20px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 4px 15px rgba(79,172,254,0.4);}
.header h1 { flex:1; text-align:center; font-size:clamp(24px,6vw,42px); font-weight:700; color:white; text-shadow:2px 2px 8px rgba(0,0,0,0.2);}
.logo { font-weight:700; font-size:clamp(18px,4vw,28px); padding:10px 20px; background:rgba(255,255,255,0.25); border-radius:15px; backdrop-filter:blur(10px); color:white; text-shadow:1px 1px 4px rgba(0,0,0,0.2);}
.content { padding:40px; background:linear-gradient(to bottom,rgba(240,248,255,0.5),rgba(255,255,255,0.9)); }
.title { font-size:clamp(26px,5vw,36px); font-weight:700; margin-bottom:30px; color:#1e3a8a; text-align:center; text-shadow:1px 1px 2px rgba(0,0,0,0.1); position:relative; padding-bottom:15px;}
.title::after { content:''; position:absolute; bottom:0; left:50%; transform:translateX(-50%); width:100px; height:4px; background:linear-gradient(90deg,#4facfe 0%,#00f2fe 100%); border-radius:2px;}
.round { margin-bottom:25px; border-radius:15px; overflow:hidden; box-shadow:0 8px 20px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;}
.round-header { background:linear-gradient(135deg,#2563eb 0%,#3b82f6 100%); padding:18px 25px; font-size:clamp(18px,4vw,24px); font-weight:600; cursor:pointer; display:flex; justify-content:space-between; align-items:center; color:white; position:relative;}
.round-header::after { content:'▼'; font-size:20px; transition:transform 0.3s;}
.round-header.active::after { transform:rotate(180deg);}
.round-content { background:linear-gradient(to bottom,#dbeafe,#eff6ff); padding:30px; display:none; min-height:180px;}
.round-content.show { display:block;}
.data-table { width:100%; max-width:800px; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.1); margin-bottom:20px;}
.data-table thead { background:linear-gradient(135deg,#2563eb 0%,#3b82f6 100%); color:white;}
.data-table th, .data-table td { padding:12px 15px; text-align:center; font-size:clamp(13px,3vw,15px);}
.data-table th { font-weight:600;}
.data-table tbody tr:hover { background-color:#f0f9ff;}
.download-btn { display:block; margin:10px auto 0 auto; padding:10px 20px; background:#2563eb; color:white; border:none; border-radius:10px; cursor:pointer; font-size:16px;}
.download-btn:hover { background:#3b82f6;}
.table-label { font-weight:600; margin-bottom:8px; font-size:16px;}
.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* โลโก้ซ้าย */
.logo-left {
    width: 70px;
    height: auto;
}

/* รูปชื่อ SoundGood */
.title-img {
    width: 180px;   /* หรือใช้ height ก็ได้ */
    height: auto;
}

/* โลโก้ขวา */
.logo-right {
    width: 80px;
    height: auto;
}
</style>
</head>
<body>
<div class="container">

<div class="header">
    <img src="img/2.png"  alt="Left Logo"  class="logo-left">
    <img src="img/1.png"      alt="SoundGood"  class="title-img">
    <img src="img/3.png" alt="Right Logo" class="logo-right">
</div>

<div class="content">
<div class="title">ประวัติ</div>
<div id="rounds-container"></div>
</div>
</div>

<script>
function toggleRound(header) {
    header.classList.toggle('active');
    const content = header.nextElementSibling;
    content.classList.toggle('show');
}

// ฟังก์ชันดาวน์โหลด CSV
function downloadCSV(roundData, roundNumber) {
    let csv = 'frequency_hz,db_25,db_40,db_55,db_70,db_90,db_100,ear,day\n';
    roundData.forEach(row => {
        ['left','right'].forEach(ear => {
            const r = row[ear];
            csv += `${r.frequency_hz},${r.db_25},${r.db_40},${r.db_55},${r.db_70},${r.db_90},${r.db_100},${ear},${r.day}\n`;
        });
    });
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `round_${roundNumber}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

async function loadData() {
    try {
        const res = await fetch('get_data.php');
        const data = await res.json();

        const container = document.getElementById('rounds-container');
        container.innerHTML = '';

        data.forEach((round, idx) => {
            const roundDiv = document.createElement('div');
            roundDiv.classList.add('round');

            // แสดงรอบ + วันเวลาแถวแรก (ไม่ใช้วงเล็บ)
            const firstDay = round[0]?.left?.day ?? '-';
            const header = document.createElement('div');
            header.classList.add('round-header');
            header.textContent = `รอบที่ ${idx+1} ${firstDay}`;
            header.onclick = () => toggleRound(header);
            roundDiv.appendChild(header);

            const content = document.createElement('div');
            content.classList.add('round-content');

            // ตาราง Left Ear
            let leftTable = `<div class="table-label">Left Ear</div><table class="data-table">
                <thead>
                    <tr>
                        <th>ความถี่ (Hz)</th>
                        <th>db_25</th>
                        <th>db_40</th>
                        <th>db_55</th>
                        <th>db_70</th>
                        <th>db_90</th>
                        <th>db_100</th>
                    </tr>
                </thead>
                <tbody>`;
            round.forEach(row => {
                leftTable += `<tr>
                    <td>${row.left.frequency_hz}</td>
                    <td>${row.left.db_25}</td>
                    <td>${row.left.db_40}</td>
                    <td>${row.left.db_55}</td>
                    <td>${row.left.db_70}</td>
                    <td>${row.left.db_90}</td>
                    <td>${row.left.db_100}</td>
                </tr>`;
            });
            leftTable += `</tbody></table>`;

            // ตาราง Right Ear
            let rightTable = `<div class="table-label">Right Ear</div><table class="data-table">
                <thead>
                    <tr>
                        <th>ความถี่ (Hz)</th>
                        <th>db_25</th>
                        <th>db_40</th>
                        <th>db_55</th>
                        <th>db_70</th>
                        <th>db_90</th>
                        <th>db_100</th>
                    </tr>
                </thead>
                <tbody>`;
            round.forEach(row => {
                rightTable += `<tr>
                    <td>${row.right.frequency_hz}</td>
                    <td>${row.right.db_25}</td>
                    <td>${row.right.db_40}</td>
                    <td>${row.right.db_55}</td>
                    <td>${row.right.db_70}</td>
                    <td>${row.right.db_90}</td>
                    <td>${row.right.db_100}</td>
                </tr>`;
            });
            rightTable += `</tbody></table>`;

            content.innerHTML = leftTable + rightTable;

            // ปุ่มดาวน์โหลด
            const downloadBtn = document.createElement('button');
            downloadBtn.classList.add('download-btn');
            downloadBtn.textContent = 'ดาวน์โหลดข้อมูลรอบนี้';
            downloadBtn.onclick = () => downloadCSV(round, idx+1);
            content.appendChild(downloadBtn);

            roundDiv.appendChild(content);
            container.appendChild(roundDiv);
        });

    } catch(err) {
        console.error('Error loading data:', err);
    }
}

loadData();
</script>
</body>
</html>
