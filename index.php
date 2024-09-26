<?php
require_once '/var/www/html/backend/config/Database.php';

date_default_timezone_set("Europe/Copenhagen");
$data = array();
$status = array(0 => "Ikke i alarm", 1 => "I alarm", 2 => "Kvitteret alarm");
$db = new Database();

if ($db->connect()) {
    $sql = "SELECT s.id, s.sensor_name, r.setpunkt, a.status, r.id AS room_id 
            FROM sensor AS s 
            INNER JOIN room AS r ON s.room_id = r.id 
            INNER JOIN alarm AS a ON a.room_id = s.room_id 
            ORDER BY s.sensor_name ASC;";
    $sensors = $db->select($sql, array());

    foreach ($sensors as $sensor) {
        $sql = "SELECT t.temperature, t.reading_time FROM temperature_reading AS t WHERE t.sensor_id = ? ORDER BY t.reading_time DESC LIMIT 1;";
        $params = array($sensor['id']);
        $temperature = $db->select($sql, $params);
        $room = substr($sensor['sensor_name'], 0, 5);
        $sensor_name = substr($sensor['sensor_name'], 6);

        if ($temperature) {
            if(stripos($sensor_name, "a") !== false) {
                $data[$room] = array("room_id" => $sensor['room_id'],
                                     "room" => $room,
                                     "sensor" => $sensor_name,
                                     "temp" => $temperature[0]['temperature'],
                                     "setpunkt" => $sensor['setpunkt'],
                                     "alarm" => $status[$sensor['status']],
                                     "status" => $sensor['status']);
            } else {
                $data[$room]['temp2'] = $temperature[0]['temperature'];
            }
        }
    }
}
?>
<meta http-equiv="refresh" content="300">
<link rel="stylesheet" type="text/css" href="/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="/custom.css">
<img src="/assets/icon/Sirena_Logo.png"           id="sirenalogo"    alt="Sirena Logo">
<img src="/assets/icon/Hovmark_Logo.png" 	      id="hovmarklogo"   alt="Hovmark Data ApS Logo">
<img src="/assets/icon/OJ_OlsenOgJensen_Logo.jpg" id="ojcomfortlogo" alt="Olsen Og Jensen Logo">
<button onclick="window.open('/table.php')" id="button-13">Alarm</button>
<div class="container">
    <?php if (count($data) > 0): ?>
        <?php foreach ($data as $item): ?>
            <div class="room_name <?= strtolower(str_replace(' ', '', $item['room'])); ?>">
                <div class="hal-header"> <?= $item['room']; ?></div>
                <div class="room-type"> Frostlager </div>
                <div class="temp-row"> <span>Temp 1:</span>
                    <div class="temp-box"> <?= number_format($item['temp'], 2); ?> °C</div>
                </div>
                <div class="temp-row"> <span>Temp 2:</span>
                    <div class="temp-box"> <?= number_format($item['temp2'], 2); ?> °C</div>
                </div> 
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Ingen data tilgængelig.</p>
    <?php endif; ?>
</div>