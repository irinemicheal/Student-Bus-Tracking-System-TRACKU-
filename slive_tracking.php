<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("
    SELECT s.student_id, s.pickup_stop_id, st.stop_order AS student_stop_order,
           a.bus_id, b.bus_number, d.full_name AS driver_name, d.phone AS driver_phone
    FROM students s
    LEFT JOIN assignments a ON s.student_id = a.student_id
    LEFT JOIN buses b ON a.bus_id = b.bus_id
    LEFT JOIN drivers d ON a.driver_id = d.driver_id
    LEFT JOIN stops st ON s.pickup_stop_id = st.stop_id
    WHERE s.user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$studentRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$studentRow || empty($studentRow['bus_id'])) {
    if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode(['error'=>'no_assignment']); exit(); }
    echo "<!doctype html><html><head><meta charset='utf-8'><title>No Bus Assigned</title></head><body><h2>No bus assigned</h2><p>Contact admin</p></body></html>";
    exit();
}

$bus_id = (int)$studentRow['bus_id'];
$studentPickupStopId = (int)$studentRow['pickup_stop_id'];
$studentStopOrder = (int)($studentRow['student_stop_order'] ?? 0);
$busNumber = $studentRow['bus_number'] ?? '';
$driverName = $studentRow['driver_name'] ?? '';
$driverPhone = $studentRow['driver_phone'] ?? '';
$student_id = (int)($studentRow['student_id'] ?? 0);

$route_id = null;
if ($studentPickupStopId) {
    $r = $mysqli->query("SELECT route_id FROM stops WHERE stop_id = {$studentPickupStopId} LIMIT 1")->fetch_assoc();
    $route_id = $r['route_id'] ?? null;
}

$route_id = $route_id ?: null;
if (!$route_id && $student_id) {
    $ar = $mysqli->query("SELECT route_id FROM assignments WHERE student_id = {$student_id} LIMIT 1")->fetch_assoc();
    $route_id = $ar['route_id'] ?? null;
}
if (!$route_id && $bus_id) {
    $br = $mysqli->query("SELECT route_id FROM assignments WHERE bus_id = {$bus_id} LIMIT 1")->fetch_assoc();
    $route_id = $br['route_id'] ?? null;
}

$stops = [];
if ($route_id) {
    $res = $mysqli->query("SELECT stop_id, stop_name, stop_order FROM stops WHERE route_id = ".intval($route_id)." ORDER BY stop_order ASC");
} else {
    $res = false;
}
if ($res && $res !== false) {
    while ($row = $res->fetch_assoc()) $stops[] = $row;
}

if (count($stops) === 0) {
    if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode(['error'=>'no_stops_assigned']); exit(); }
    echo "<!doctype html><html><head><meta charset='utf-8'><title>No Stops Assigned</title></head><body><h2>No stops assigned for your student</h2><p>Please contact the administrator to assign a route.</p></body></html>";
    exit();
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $live = $mysqli->query("SELECT current_stop, next_stop, is_started, remaining_time, trip_type FROM bus_live WHERE bus_id = {$bus_id} LIMIT 1")->fetch_assoc();
    if (!$live) { echo json_encode(['error'=>'no_live_row']); exit(); }
    $currentStopOrder = null;
    if (!empty($live['current_stop'])) {
        $cs = $mysqli->query("SELECT stop_order FROM stops WHERE stop_id = ".intval($live['current_stop'])." LIMIT 1")->fetch_assoc();
        $currentStopOrder = $cs['stop_order'] ?? null;
    }
    $stops_min = array_map(function($s){ return ['stop_id'=>(int)$s['stop_id'],'stop_name'=>$s['stop_name'],'stop_order'=>(int)$s['stop_order']]; }, $stops);

    $tsFile = __DIR__ . "/bus_update_ts_{$bus_id}.json";
    $server_ts_val = time();
    if (file_exists($tsFile)) {
        $c = @file_get_contents($tsFile);
        $j = @json_decode($c, true);
        if (!empty($j['ts'])) $server_ts_val = (int)$j['ts'];
    }

    echo json_encode([
        'is_started' => (int)($live['is_started'] ?? 0),
        'current_stop' => isset($live['current_stop']) ? (int)$live['current_stop'] : null,
        'current_stop_order' => $currentStopOrder !== null ? (int)$currentStopOrder : null,
        'next_stop' => isset($live['next_stop']) ? (int)$live['next_stop'] : null,
        'remaining_time' => isset($live['remaining_time']) ? (int)$live['remaining_time'] : null,
        'trip_type' => $live['trip_type'] ?? null,
        'stops' => $stops_min,
        'student_pickup_stop_id' => $studentPickupStopId,
        'student_stop_order' => $studentStopOrder,
        'bus_number' => $busNumber,
        'driver_name' => $driverName,
        'driver_phone' => $driverPhone
        , 'server_ts' => $server_ts_val
    ]);
    exit();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Student Live Tracking</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{ font-family:Inter,Arial,Helvetica,sans-serif; background:linear-gradient(180deg,#071029,#11203a); color:#fff; margin:0; padding:18px; display:flex; justify-content:center; }
.container{ width:100%; max-width:1100px; }
.card{ background:#0f2742; border-radius:14px; padding:18px; box-shadow:0 8px 30px rgba(0,0,0,0.45); }
.header{ display:flex; justify-content:space-between; align-items:center; gap:12px; }
.route{ position:relative; height:140px; background:#fff; color:#000; border-radius:12px; padding:20px; margin-top:16px; display:flex; align-items:center; justify-content:center; }
.line{ position:absolute; top:60px; left:10px; right:10px; height:6px; background:#1976d2; border-radius:3px; }
.line-progress{ position:absolute; top:60px; left:10px; height:6px; background:#2ecc71; border-radius:3px; width:0; transition:width .6s linear; }
.stop{ width:18px; height:18px; background:white; border:3px solid #1565c0; border-radius:50%; position:absolute; top:60px; transform:translate(-50%,-50%); display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:10px; transition:all .4s ease; }
.stop.passed{ background:#2ecc71; border-color:#1e8e4b; color:white; transform:scale(.95); }
.stop.current{ background:#e53935; color:white; border-color:#c62828; animation:blink .8s infinite, pulse 1s infinite; transform:scale(1.1); }
.stop-label{ position:absolute; top:86px; width:140px; text-align:center; font-size:13px; font-weight:700; transform:translateX(-50%); color:#08304f; }
.bus{ position:absolute; font-size:28px; top:60px; transform:translate(-50%,-50%); transition:left 1s linear; }
.info{ margin-top:12px; display:flex; gap:10px; align-items:center; justify-content:space-between; color:#dbefff; }
.update-details{ margin-top:12px; background:#0d233a; padding:12px; border-radius:10px; max-height:240px; overflow:auto; color:#cfe7ff; font-size:14px; }
.small{ font-size:13px; color:#9fb6d9; }
.back-btn{ display:inline-block; margin-top:12px; padding:8px 16px; background:#0d6efd; color:white; text-decoration:none; border-radius:6px; }
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <div class="header">
      <div>
        <h2 style="margin:0">🚌 Live Bus Tracking — <span id="busNum"><?= htmlspecialchars($busNumber) ?></span></h2>
        <div class="small">Driver: <span id="driverName"><?= htmlspecialchars($driverName) ?></span> <span id="driverPhone"><?= htmlspecialchars($driverPhone) ?></span></div>
    

      </div>
    </div>

    <div class="route" id="routeContainer">
      <div class="line"></div>
      <div class="line-progress" id="lineProgress"></div>
      <div id="bus" class="bus">🚌</div>

      <?php
      if (count($stops) === 0) $stops = [['stop_id'=>0,'stop_name'=>'Stop','stop_order'=>1]];
      $numStops = count($stops);
      foreach ($stops as $i => $s) {
          if ($numStops > 1) $leftPercent = ($i/($numStops-1))*100; else $leftPercent = 50;
          $leftPercent = round($leftPercent,4);
          echo '<div class="stop" data-stop-id="'.intval($s['stop_id']).'" style="left:'.$leftPercent.'%"></div>';
          echo '<div class="stop-label" style="left:'.$leftPercent.'%">'.htmlspecialchars($s['stop_name']).'</div>';
      }
      ?>
    </div>

    <div class="info">
      <div>Status: <strong id="statusText">Checking...</strong></div>
      <div>ETA: <strong id="etaText">--</strong></div>
    </div>

    <div class="update-details" id="updateDetails">
      <p>Live updates will appear here...</p>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;">
      <a href="studentdash.php" class="back-btn">⬅ Back</a>
    </div>
  </div>
</div>

<audio id="arrivalSound"><source src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg" type="audio/ogg"></audio>

<script>
const POLL_MS = 1000; 
const stops = Array.from(document.querySelectorAll('.stop'));
const stopLabels = Array.from(document.querySelectorAll('.stop-label'));
const busEl = document.getElementById('bus');
const lineProgress = document.getElementById('lineProgress');
const statusText = document.getElementById('statusText');
const etaText = document.getElementById('etaText');
const updateDetails = document.getElementById('updateDetails');
const arrivalSound = document.getElementById('arrivalSound');

let lastStopId = null;
let arrivedNotified = false;
let tripType = '—';
const stopIdToIndex = {};
stops.forEach((s,i)=> stopIdToIndex[s.dataset.stopId] = i);
const FIXED_MINUTES_PER_LEG = 10; 

(function init(){
    if (stops.length) {
        const px = stopLeftPx(stops[0]);
        busEl.style.transition = "none";
        busEl.style.left = px + "px";
        lineProgress.style.width = "0px";
        stops.forEach(s=>s.className='stop');
        stops[0].classList.add('current');
        lastStopId = parseInt(stops[0].dataset.stopId);
    }
})();

function stopLeftPx(el){
    const rect = el.getBoundingClientRect();
    const parentRect = el.parentElement.getBoundingClientRect();
    return (rect.left - parentRect.left) + (rect.width / 2);
}
function setBusLeftWithDuration(px, sec){ busEl.style.transition = `left ${sec}s linear`; busEl.style.left = px + 'px'; lineProgress.style.width = px + 'px'; }
function setStopClasses(idx){ stops.forEach((s,i)=>{ s.className='stop'; if(i < idx) s.classList.add('passed'); }); if(stops[idx]) stops[idx].classList.add('current'); }
function stopNameByIndex(i){ return stopLabels[i] ? stopLabels[i].innerText : ''; }

let smoothAnim = { running:false, from:0, to:0, start:0, duration:0, raf:0 };
function startSmoothAnim(fromPx, toPx, durationMs){
    if (smoothAnim.raf) cancelAnimationFrame(smoothAnim.raf);
    smoothAnim.running = true;
    smoothAnim.from = fromPx;
    smoothAnim.to = toPx;
    smoothAnim.start = performance.now();
    smoothAnim.duration = Math.max(100, durationMs);
    function step(now){
        const t = Math.min(1, (now - smoothAnim.start) / smoothAnim.duration);
        const cur = smoothAnim.from + (smoothAnim.to - smoothAnim.from) * t;
        busEl.style.transition = 'none';
        busEl.style.left = cur + 'px';
        lineProgress.style.width = cur + 'px';
        if (t < 1) smoothAnim.raf = requestAnimationFrame(step);
        else { smoothAnim.running = false; smoothAnim.raf = 0; }
    }
    smoothAnim.raf = requestAnimationFrame(step);
}

let prevCurrent = null;
let prevNext = null;
let prevRemaining = null;
let prevIsStarted = null;

async function fetchLive(){
    try {
        const res = await fetch(window.location.pathname + '?ajax=1', {cache:'no-cache'});
        const data = await res.json();
        if (!data || data.error) {
            if (data && data.error === 'no_assignment') { statusText.innerText = 'No assignment'; return; }
            if (data && data.error === 'no_stops_assigned') { statusText.innerText = 'No route/stops assigned'; return; }
            statusText.innerText = 'No live data';
            return;
        }

        const isStarted = parseInt(data.is_started || 0);
        const currentStopId = data.current_stop !== null ? parseInt(data.current_stop) : null;
        const currentStopOrder = data.current_stop_order !== null ? parseInt(data.current_stop_order) : null;
    const remaining = data.remaining_time !== null ? parseInt(data.remaining_time) : null;
    const studentPickupId = data.student_pickup_stop_id !== null ? parseInt(data.student_pickup_stop_id) : null;
    const serverTs = data.server_ts ? parseInt(data.server_ts) : null;

        const yourStopEl = document.getElementById('yourStopName');
        if (yourStopEl && studentPickupId && stopIdToIndex[studentPickupId] !== undefined) {
            yourStopEl.innerText = stopNameByIndex(stopIdToIndex[studentPickupId]);
        }

        tripType = data.trip_type ?? '—';
        const tripTypeEl = document.getElementById('tripType');
        if (tripTypeEl) tripTypeEl.innerText = tripType;
        etaText.innerText = remaining !== null ? `${remaining} min` : '--';

        if (!isStarted) {
            statusText.innerText = 'Journey not started yet 🕒';
           
            if (currentStopId !== null && stopIdToIndex[currentStopId] !== undefined) {
                const idx = stopIdToIndex[currentStopId];
                const px = stopLeftPx(stops[idx]);
                busEl.style.transition = "none";
                busEl.style.left = px + 'px';
                lineProgress.style.width = px + 'px';
                setStopClasses(idx);
                lastStopId = currentStopId;
            }
            prevCurrent = null; prevNext = null; prevRemaining = null; arrivedNotified = false;
            return;
        }

        if (currentStopId !== null && currentStopId !== lastStopId && stopIdToIndex[currentStopId] !== undefined) {
            const idx = stopIdToIndex[currentStopId];
            const px = stopLeftPx(stops[idx]);
            let duration = (remaining && remaining > 0) ? Math.max(1, remaining) : 1;
            if (serverTs) {
                const elapsed = Math.max(0, Math.floor(Date.now()/1000) - serverTs);
                duration = Math.max(0.5, duration - elapsed);
            }
            setBusLeftWithDuration(px, duration);
            setStopClasses(idx);
            if (prevCurrent !== currentStopId) {
                const name = stopLabels[idx] ? stopLabels[idx].innerText : ('Stop ' + currentStopId);
                updateDetails.innerHTML += `<p>✅ Arriving at ${name}</p>`;
                updateDetails.scrollTop = updateDetails.scrollHeight;
            }
            lastStopId = currentStopId;
            prevCurrent = currentStopId;
        } else if (data.next_stop !== null && data.next_stop !== undefined && stopIdToIndex[data.next_stop] !== undefined && currentStopId !== null && stopIdToIndex[currentStopId] !== undefined) {
            
            const nextIdx = stopIdToIndex[parseInt(data.next_stop)];
            const px = stopLeftPx(stops[nextIdx]);
            let duration = (remaining !== null && remaining > 0) ? Math.max(1, remaining) : 4;
            if (serverTs) {
                const elapsed = Math.max(0, Math.floor(Date.now()/1000) - serverTs);
                duration = Math.max(0.5, duration - elapsed);
            }
            duration += (POLL_MS / 1000) * 0.5; // small offset for poll latency
            // Use smooth animation to avoid jumpiness between polls
            const curLeft = stopLeftPx(busEl);
            const ms = Math.max(400, Math.round(duration * 1000));
            startSmoothAnim(curLeft, px, ms);
            setStopClasses(nextIdx);
            // append departure message when next changes
            if (prevNext !== nextIdx) {
                const curName = (currentStopId && stopIdToIndex[currentStopId] !== undefined) ? stopLabels[stopIdToIndex[currentStopId]].innerText : 'Current stop';
                const nextName = stopLabels[nextIdx] ? stopLabels[nextIdx].innerText : ('Stop ' + nextIdx);
                updateDetails.innerHTML += `<p>➡️ Departing ${curName} → ${nextName} (est ${remaining} min)</p>`;
                updateDetails.scrollTop = updateDetails.scrollHeight;
            }
            // append remaining update when it changes
            if (prevRemaining !== null && prevRemaining !== remaining) {
                const nextName = stopLabels[nextIdx] ? stopLabels[nextIdx].innerText : ('Stop ' + nextIdx);
                updateDetails.innerHTML += `<p>⏱ ${remaining} min to ${nextName}</p>`;
                updateDetails.scrollTop = updateDetails.scrollHeight;
            }
            prevNext = nextIdx;
            prevRemaining = remaining;
            // don't update lastStopId until DB reports arrival (current_stop change)
        }

        // Detect start/stop transitions and append messages so student view reflects driver button actions
        if (prevIsStarted === null) prevIsStarted = isStarted;
        if (prevIsStarted !== isStarted) {
            if (isStarted) {
                updateDetails.innerHTML += `<p>🟢 Journey started</p>`;
                // If next stop is available, also show departing for the first leg
                if (data.next_stop && stopIdToIndex[data.next_stop] !== undefined) {
                    const nextIdx = stopIdToIndex[data.next_stop];
                    const curName = (currentStopId && stopIdToIndex[currentStopId] !== undefined) ? stopLabels[stopIdToIndex[currentStopId]].innerText : 'Start';
                    const nextName = stopLabels[nextIdx] ? stopLabels[nextIdx].innerText : ('Stop ' + data.next_stop);
                    updateDetails.innerHTML += `<p>➡️ Departing ${curName} → ${nextName} (est ${remaining} min)</p>`;
                }
            } else {
                updateDetails.innerHTML += `<p>🔴 Journey ended</p>`;
            }
            updateDetails.scrollTop = updateDetails.scrollHeight;
            prevIsStarted = isStarted;
        }

        // update one-line status
        if (currentStopOrder !== null && data.student_stop_order !== null) {
            const remStops = data.student_stop_order - currentStopOrder;
            if (!isStarted) statusText.innerText = 'Journey not started yet 🕒';
            else if (remStops > 0) statusText.innerText = `Bus is ${remStops} stops away 🚍`;
            else if (remStops === 0) statusText.innerText = `Bus at your pickup stop 🔔`;
            else statusText.innerText = `Trip completed ✅`;
        } else {
            statusText.innerText = isStarted ? 'On the move 🚍' : 'Journey not started yet 🕒';
        }

        // arrival detection for pickup stop (user chose pickup)
        // Keep internal arrivedNotified flag to avoid repeat actions, but do not show the arrival popup/message
        let arrivedNow = false;
        if (studentPickupId && currentStopId && studentPickupId === currentStopId) arrivedNow = true;
        else if (remaining !== null && remaining <= 2 && isStarted) arrivedNow = true;

        if (arrivedNow && !arrivedNotified) {
            arrivedNotified = true;
            // intentionally do NOT append the arrival message or show a Notification here
            // keep silent behavior (no DOM change) to match driver UI movement without the popup
        }

    } catch (err) {
        console.error('fetchLive error', err);
        statusText.innerText = 'Network error';
    }
}

fetchLive();
setInterval(fetchLive, POLL_MS);
</script>
</body>
</html>
