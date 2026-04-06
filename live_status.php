<?php
include 'db.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!empty($raw)) {
    @file_put_contents(__DIR__ . '/live_status.log', date('c') . ' RAW: ' . $raw . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$bus_id = intval($data['bus_id'] ?? 0);
$action = $data['action'] ?? ($data['act'] ?? '');

if ($bus_id <= 0) {
    echo json_encode(['error'=>'missing_bus_id']); exit();
}
function get_route_info($mysqli, $bus_id) {
    $row = $mysqli->query("SELECT route_id FROM assignments WHERE bus_id = {$bus_id} LIMIT 1")->fetch_assoc();
    $route_id = $row['route_id'] ?? null;
    $stops = [];
    if ($route_id) {
        $q = $mysqli->query("SELECT stop_id, stop_order FROM stops WHERE route_id = ".intval($route_id)." ORDER BY stop_order ASC");
        while ($r = $q->fetch_assoc()) $stops[] = $r;
    }
    $last_stop_id = count($stops) ? (int)$stops[count($stops)-1]['stop_id'] : null;
    return ['route_id'=>$route_id, 'stops'=>$stops, 'last_stop_id'=>$last_stop_id];
}
function write_bus_ts($bus_id, $remaining=null) {
    $path = __DIR__ . "/bus_update_ts_{$bus_id}.json";
    $data = ['ts' => time(), 'remaining' => $remaining];
    @file_put_contents($path, json_encode($data), LOCK_EX);
}

$live = $mysqli->query("SELECT current_stop, next_stop, is_started, remaining_time FROM bus_live WHERE bus_id = {$bus_id} LIMIT 1")->fetch_assoc();
$current_stop = isset($live['current_stop']) ? (int)$live['current_stop'] : 0;
$next_stop = isset($live['next_stop']) ? (int)$live['next_stop'] : 0;
$is_started = isset($live['is_started']) ? (int)$live['is_started'] : 0;
$remaining_time = isset($live['remaining_time']) ? (int)$live['remaining_time'] : null;

$FIXED_MINUTES_PER_LEG = 10;

if ($action === 'start') {
    $info = get_route_info($mysqli, $bus_id);
    if (empty($info['stops'])) {
        echo json_encode(['error'=>'no_stops']); exit();
    }

    $first_stop = (int)$info['stops'][0]['stop_id'];
    $second_stop = isset($info['stops'][1]) ? (int)$info['stops'][1]['stop_id'] : null;

    $stmt = $mysqli->prepare("
        INSERT INTO bus_live (bus_id, trip_type, current_stop, next_stop, is_started, remaining_time)
        VALUES (?, 'morning', ?, ?, 1, ?)
        ON DUPLICATE KEY UPDATE current_stop=VALUES(current_stop), next_stop=VALUES(next_stop), is_started=VALUES(is_started), remaining_time=VALUES(remaining_time)
    ");
    $stmt->bind_param("iiii", $bus_id, $first_stop, $second_stop, $FIXED_MINUTES_PER_LEG);
    $stmt->execute();
    $stmt->close();
    write_bus_ts($bus_id, $FIXED_MINUTES_PER_LEG);

    echo json_encode([
        'status'=>'started',
        'current_stop'=>$first_stop,
        'next_stop'=>$second_stop,
        'remaining_time'=>$FIXED_MINUTES_PER_LEG
    ]);
    exit();
}

if ($action === 'tick') {
    if (!$live || $is_started !== 1) {
        echo json_encode(['status'=>'not_started']); exit();
    }

    $info = get_route_info($mysqli, $bus_id);
    $stops_list = array_map(fn($r)=> (int)$r['stop_id'], $info['stops']);
    $last_stop_id = $info['last_stop_id'];

    if ($current_stop <= 0) $current_stop = $stops_list[0];
    if ($next_stop <= 0) {
        $idx = array_search($current_stop, $stops_list, true);
        $next_stop = $stops_list[$idx+1] ?? null;
    }

    if ($remaining_time === null) $remaining_time = $FIXED_MINUTES_PER_LEG;

    if ($remaining_time > 1) {
    $newRemaining = $remaining_time - 1;
    $mysqli->query("UPDATE bus_live SET remaining_time={$newRemaining} WHERE bus_id={$bus_id}");
   
    write_bus_ts($bus_id, $newRemaining);
    echo json_encode(['status'=>'tick','current_stop'=>$current_stop,'next_stop'=>$next_stop,'remaining_time'=>$newRemaining]);
        exit();
    }
    if ($next_stop === null) {
        $mysqli->query("UPDATE bus_live SET is_started=0, remaining_time=NULL WHERE bus_id={$bus_id}");
        write_bus_ts($bus_id, null);
        echo json_encode(['status'=>'ended','message'=>'no_next_stop']);
        exit();
    }

    $idx_cur = array_search($next_stop, $stops_list, true);
    $next_next = $stops_list[$idx_cur+1] ?? null;

    $stmt = $mysqli->prepare("UPDATE bus_live SET current_stop=?, next_stop=?, remaining_time=? WHERE bus_id=?");
    $stmt->bind_param("iiii", $next_stop, $next_next, $FIXED_MINUTES_PER_LEG, $bus_id);
    $stmt->execute();
    $stmt->close();
    write_bus_ts($bus_id, $FIXED_MINUTES_PER_LEG);

    if ($next_stop === $last_stop_id) {
        $mysqli->query("UPDATE bus_live SET is_started=0, remaining_time=NULL WHERE bus_id={$bus_id}");
        echo json_encode(['status'=>'arrived_last','current_stop'=>$next_stop]);
        exit();
    }

    echo json_encode(['status'=>'moved','current_stop'=>$next_stop,'next_stop'=>$next_next,'remaining_time'=>$FIXED_MINUTES_PER_LEG]);
    exit();
}

if ($action === 'end') {
    $mysqli->query("UPDATE bus_live SET is_started=0, remaining_time=NULL WHERE bus_id={$bus_id}");
   
    write_bus_ts($bus_id, null);
    echo json_encode(['status'=>'ended','message'=>'manual_end']);
    exit();
}
if (empty($action) && (array_key_exists('current_stop', $data) || array_key_exists('next_stop', $data) || array_key_exists('remaining_time', $data) || array_key_exists('is_started', $data))) {
    $in_current = array_key_exists('current_stop', $data) ? ($data['current_stop'] === null ? null : intval($data['current_stop'])) : ($current_stop ?: null);
    $in_next = array_key_exists('next_stop', $data) ? ($data['next_stop'] === null ? null : intval($data['next_stop'])) : ($next_stop ?: null);
    $in_is_started = array_key_exists('is_started', $data) ? intval($data['is_started']) : $is_started;
    $in_remaining = array_key_exists('remaining_time', $data) ? ($data['remaining_time'] === null ? null : intval($data['remaining_time'])) : $remaining_time;

    $stmt = $mysqli->prepare("INSERT INTO bus_live (bus_id, trip_type, current_stop, next_stop, is_started, remaining_time) VALUES (?, 'morning', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE current_stop=VALUES(current_stop), next_stop=VALUES(next_stop), is_started=VALUES(is_started), remaining_time=VALUES(remaining_time)");
    if ($stmt) {
        $cur_bind = $in_current !== null ? $in_current : null;
        $next_bind = $in_next !== null ? $in_next : null;
        $rem_bind = $in_remaining !== null ? $in_remaining : null;
        $is_bind = $in_is_started !== null ? $in_is_started : 0;

    $stmt->bind_param('iiiii', $bus_id, $cur_bind, $next_bind, $is_bind, $rem_bind);
        $stmt->execute();
        $stmt->close();

    write_bus_ts($bus_id, $in_remaining);

        echo json_encode([
            'status' => 'ok',
            'current_stop' => $in_current,
            'next_stop' => $in_next,
            'remaining_time' => $in_remaining,
            'is_started' => $in_is_started
        ]);
        exit();
    } else {
        echo json_encode(['error'=>'db_prepare_failed','mysqli_error'=>$mysqli->error]);
        exit();
    }

}

echo json_encode(['error'=>'invalid_action']);
