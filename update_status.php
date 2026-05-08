<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

$stmt = $mysqli->prepare("
    SELECT d.driver_id, a.route_id, b.bus_id, b.bus_number
    FROM drivers d
    JOIN users u ON d.user_id = u.user_id
    JOIN assignments a ON d.driver_id = a.driver_id
    JOIN buses b ON a.bus_id = b.bus_id
    WHERE u.username = ? LIMIT 1
");
$stmt->bind_param("s", $username);
$stmt->execute();
$driverData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$driverData) {
    die("No bus or route assigned to this driver.");
}

$stops = [];
$stmtStops = $mysqli->prepare("SELECT stop_id, stop_name FROM stops WHERE route_id = ? ORDER BY stop_order ASC");
$stmtStops->bind_param("i", $driverData['route_id']);
$stmtStops->execute();
$resStops = $stmtStops->get_result();
while ($row = $resStops->fetch_assoc()) {
    $stops[] = $row;
}
$stmtStops->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Driver - Bus Journey</title>
<style>
body { background:#e3f2fd; display:flex; flex-direction:column; align-items:center; font-family:Arial; padding:20px; }
.controls { margin-bottom:8px; }
button { padding:10px 18px; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:bold; margin-right:8px; }
#startBtn { background:#2ecc71; color:white; }
#stopBtn { background:#e74c3c; color:white; }
#restartBtn { background:#f39c12; color:white; }
.route { position:relative; height:140px; background:white; border-radius:20px; padding:20px; margin-top:20px; width:95%; display:flex; align-items:center; justify-content:center; }
.line { position:absolute; top:60px; left:10px; right:10px; height:6px; background:#1976d2; border-radius:3px; }
.line-progress { position:absolute; top:60px; left:10px; height:6px; background:#2ecc71; border-radius:3px; width:0; }
.stop { width:18px; height:18px; background:white; border:3px solid #1565c0; border-radius:50%; position:absolute; top:60px; transform:translateY(-50%); display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:10px; }
.stop.passed { background:#2ecc71; border-color:#1e8e4b; color:white; }
.stop.current { background:#e53935; color:white; border-color:#c62828; animation:blink .8s infinite, pulse 1s infinite; }
@keyframes blink { 50%{opacity:0.4;} } @keyframes pulse { 0% { box-shadow:0 0 0 rgba(255,0,0,0.7); } 100% { box-shadow:0 0 10px rgba(255,0,0,1); } }
.stop-label { position:absolute; top:85px; width:120px; text-align:center; font-size:12px; font-weight:bold; transform:translateX(-50%); }
.bus { position:absolute; font-size:28px; top:60px; transform:translateY(-50%); }
#timerTxt { margin-top:14px; font-weight:bold; color:#0d47a1; font-size:16px; text-align:center; }
.update-details { margin-top:15px; background:white; width:95%; padding:12px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); max-height:220px; overflow-y:auto; }
.update-details p { margin:6px 0; font-size:14px; }
.back-btn { display:inline-block; margin-top:12px; padding:8px 16px; background:#0d6efd; color:white; text-decoration:none; border-radius:6px; transition:0.3s; }
.back-btn:hover { background:#0a58ca; }
</style>
</head>
<body>

<div class="controls">
    <button id="startBtn">Start Journey</button>
    <button id="stopBtn">End Journey</button>
    <button id="restartBtn">Restart Journey</button>
</div>

<div class="route" id="routeContainer">
    <div class="line"></div>
    <div class="line-progress" id="lineProgress"></div>
    <div id="bus" class="bus">🚌</div>

    <?php
    $allStops = $stops;
    $numStops = count($allStops);
    if ($numStops === 0) $allStops = [['stop_id'=>0,'stop_name'=>'Stop']];
    $numStops = count($allStops);
    $routeWidth = 1000;
    foreach($allStops as $index=>$s):
        $leftPos = 10 + ($index/($numStops-1))*($routeWidth-20);
    ?>
        <div class="stop" data-stop-id="<?= (int)$s['stop_id'] ?>" data-label="<?= htmlspecialchars($s['stop_name']) ?>" style="left: <?= $leftPos ?>px;"></div>
        <div class="stop-label" style="left: <?= $leftPos ?>px;"><?= htmlspecialchars($s['stop_name']) ?></div>
    <?php endforeach; ?>
</div>

<div id="timerTxt">Press <strong>Start Journey</strong> to begin (Simulation: 1s = 1min).</div>

<div class="update-details" id="updateDetails">
    <p>Journey updates will appear here...</p>
</div>

<audio id="ding"><source src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg"></audio>

<a href="driverdash.php" class="back-btn">⬅ Back to Dashboard</a>

<script>
const SIM_MS_PER_MIN = 1000;
const MINUTES_PER_LEG = 10;
const busId = <?= json_encode((int)$driverData['bus_id']) ?>;
const stops = Array.from(document.querySelectorAll('.stop'));
const stopLabels = Array.from(document.querySelectorAll('.stop-label'));
const bus = document.getElementById("bus");
const lineProgress = document.getElementById("lineProgress");
const updateDetails = document.getElementById("updateDetails");
const ding = document.getElementById("ding");

let currentIndex = 0; 
let running = false;
let timerInterval = null;
let movePromiseCancel = null;
function stopLeftPx(stopEl){ return parseFloat(stopEl.style.left || window.getComputedStyle(stopEl).left); }
function setBusLeftInstant(px){ bus.style.transition = "none"; bus.style.left = px + "px"; void bus.offsetWidth; }
function setBusLeftWithDuration(px, seconds){ bus.style.transition = `left ${seconds}s linear`; bus.style.left = px + "px"; }

function setStopClasses(i){
    stops.forEach((s,idx)=> {
        s.className = "stop";
        if (idx <= i) s.classList.add("passed");
    });
    if (stops[i]) stops[i].classList.add("current");
}

function sendStatusByStopId(currentStopId, remainingMinutes, started){
    const nextIndex = Math.min(currentIndex + 1, stops.length - 1);
    const nextStopId = stops[nextIndex] ? parseInt(stops[nextIndex].dataset.stopId) : null;

    fetch("live_status.php",{
        method:"POST",
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            bus_id: busId,
            current_stop: currentStopId,
            next_stop: nextStopId,
            remaining_time: remainingMinutes,
            is_started: started ? 1 : 0,
            trip_type: "morning"
        })
    }).catch(()=>{});
}

function moveBusHoriz(i, durationSeconds){
    let cancelled = false;
    movePromiseCancel = () => { cancelled = true; };

    return new Promise(resolve=>{
        const targetPx = stopLeftPx(stops[i]);
        setBusLeftWithDuration(targetPx, durationSeconds);
        lineProgress.style.width = (targetPx - 10) + "px";
        setStopClasses(i);

        const startTime = Date.now();
        const check = setInterval(()=>{
            if (cancelled) {
                clearInterval(check);
                const curLeft = window.getComputedStyle(bus).left;
                bus.style.transition = "none";
                bus.style.left = curLeft;
                resolve();
            } else if ((Date.now()-startTime) >= durationSeconds*1000) {
                clearInterval(check); resolve();
            }
        }, 200);
    }).finally(()=> movePromiseCancel = null);
}

async function startJourney(){
    if (running) return;
    running = true;
    updateDetails.innerHTML = "";
    const startStopId = parseInt(stops[currentIndex].dataset.stopId);
    sendStatusByStopId(startStopId, 0, true);

    for(let i = currentIndex; i < stops.length - 1; i++){
        if(!running) break;
        const currStopName = stops[i].dataset.label;
        const nextIndex = i+1;
        const nextStopName = stops[nextIndex].dataset.label;
        let waitMinutes = 5;
        let w = waitMinutes;
        document.getElementById("timerTxt").innerText = `Waiting at: ${currStopName} for ${w} min`;
        updateDetails.innerHTML += `<p>🕒 Waiting at ${currStopName} for ${w} min...</p>`;
        updateDetails.scrollTop = updateDetails.scrollHeight;
        await new Promise(res=>{
            timerInterval = setInterval(()=>{
                w--;
                if(!running){ clearInterval(timerInterval); res(); return; }
                document.getElementById("timerTxt").innerText = `Waiting at: ${currStopName} for ${w} min`;
                if (w === 1) try{ ding.play(); }catch(e){}
                if (w <= 0) { clearInterval(timerInterval); res(); }
            }, SIM_MS_PER_MIN);
        });
        if(!running) break;
        let travelMinutes = MINUTES_PER_LEG;
        let t = travelMinutes;
        document.getElementById("timerTxt").innerText = `Next Stop: ${nextStopName} in ${t} min`;
        updateDetails.innerHTML += `<p>Departing ${currStopName} → ${nextStopName}</p>`;
        updateDetails.scrollTop = updateDetails.scrollHeight;
        await new Promise(async (res)=>{
            let perMin = setInterval(()=>{
                t--;
                if(!running) { clearInterval(perMin); return; }
                if (t >= 0) {
                    document.getElementById("timerTxt").innerText = `Next Stop: ${nextStopName} in ${t} min`;
                   
                    const curStopId = parseInt(stops[nextIndex].dataset.stopId); 
                    sendStatusByStopId(curStopId, t, true);
                    if (t === 1) try{ ding.play(); }catch(e){}
                } else {
                    clearInterval(perMin);
                }
            }, SIM_MS_PER_MIN);
            try { await moveBusHoriz(nextIndex, travelMinutes); } catch(e){}
            clearInterval(perMin);
            res();
        });

        currentIndex = nextIndex;
        const arriveStopId = parseInt(stops[currentIndex].dataset.stopId);
        updateDetails.innerHTML += `<p>Arrived ${nextStopName}</p>`;
        updateDetails.scrollTop = updateDetails.scrollHeight;
       
        sendStatusByStopId(arriveStopId, 0, true);
    }

    running = false;
    clearInterval(timerInterval);
    document.getElementById("timerTxt").innerText = "✅ Journey Completed";
    updateDetails.innerHTML += `<p>✅ Journey Completed</p>`;
    updateDetails.scrollTop = updateDetails.scrollHeight;
    
    const finalStopId = parseInt(stops[currentIndex].dataset.stopId);
    fetch("live_status.php", {
        method:"POST",
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ bus_id: busId, current_stop: finalStopId, next_stop: null, remaining_time: 0, is_started: 0, trip_type: "morning" })
    }).catch(()=>{});
}
document.getElementById("stopBtn").onclick = ()=>{
    if(!running) { clearInterval(timerInterval); return; }
    running = false;
    if(typeof movePromiseCancel === "function") movePromiseCancel();
    clearInterval(timerInterval);
    const curLeft = window.getComputedStyle(bus).left;
    bus.style.transition = "none";
    bus.style.left = curLeft;
    document.getElementById("timerTxt").innerText = "❌ Journey Ended";
    updateDetails.innerHTML += `<p>❌ Journey Ended</p>`;
    
    const curStopId = parseInt(stops[currentIndex].dataset.stopId);
    fetch("live_status.php", {
        method:"POST",
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ bus_id: busId, current_stop: curStopId, next_stop: (stops[currentIndex+1] ? parseInt(stops[currentIndex+1].dataset.stopId) : null), remaining_time:0, is_started: 0, trip_type: "morning" })
    }).catch(()=>{});
};

document.getElementById("restartBtn").onclick = ()=>{
    running = false;
    if(typeof movePromiseCancel === "function") movePromiseCancel();
    clearInterval(timerInterval);
    currentIndex = 0;
    const px = stopLeftPx(stops[0]);
    setBusLeftInstant(px);
    lineProgress.style.width = '0px';
    stops.forEach(s=>s.className='stop');
    stops[0].classList.add('current');
    document.getElementById("timerTxt").innerText = "🔄 Journey Restarted";
    updateDetails.innerHTML = `<p>🔄 Journey Restarted</p>`;
    
    const curStopId = parseInt(stops[0].dataset.stopId);
    fetch("live_status.php", {
        method:"POST",
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ bus_id: busId, current_stop: curStopId, next_stop: (stops[1] ? parseInt(stops[1].dataset.stopId) : null), remaining_time:0, is_started: 0, trip_type: "morning" })
    }).catch(()=>{});
};
document.getElementById("startBtn").onclick = ()=> { if(!running) startJourney(); };
function stopLeftPx(stopEl){ return parseFloat(stopEl.style.left || window.getComputedStyle(stopEl).left); }
(function init(){ const px = stopLeftPx(stops[0]); setBusLeftInstant(px); lineProgress.style.width = '0px'; stops.forEach(s=>s.className='stop'); stops[0].classList.add('current'); })();
</script>
</body>
</html>
