<?php
require_once '/var/www/html/backend/config/Database.php';

date_default_timezone_set("Europe/Copenhagen");
$data = array();
$status = array(0 => "Ikke i alarm", 1 => "I alarm", 2 => "Kvitteret alarm");
$db = new Database();

if ($db->connect()) {
    if (isset($_POST['setpunkt']) && isset($_POST['old_setpunkt']) && isset($_POST['room']) && isset($_POST['room_id'])) {
        $old_setpunkt = filter_input(INPUT_POST, 'old_setpunkt', FILTER_VALIDATE_FLOAT);
        $setpunkt = filter_input(INPUT_POST, 'setpunkt', FILTER_VALIDATE_FLOAT);
        $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
        $room = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_STRING);
        $sql = "UPDATE room SET setpunkt = ? WHERE id = ?;";
        $params = array($setpunkt, $room_id);
        $db->query($sql, $params);
        $message = "Setpunkt skiftet fra " . $old_setpunkt . " til " . $setpunkt . " for rum " . $room;
        $sql = "INSERT INTO log (created, type, message) VALUES (?, ?, ?);";
        $params = array(date('Y-m-d H:i:s'), 2, $message);
        $db->query($sql, $params);
    } else if (isset($_POST['room']) && isset($_POST['room_id']) && isset($_POST['start_hour']) && isset($_POST['start_minute']) && isset($_POST['stop_hour']) && isset($_POST['stop_minute'])) {
        // Update afrim
        $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
        $room = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_STRING);
        $start_hour = filter_input(INPUT_POST, 'start_hour', FILTER_VALIDATE_INT);
        $start_minute = filter_input(INPUT_POST, 'start_minute', FILTER_VALIDATE_INT);
        $stop_hour = filter_input(INPUT_POST, 'stop_hour', FILTER_VALIDATE_INT);
        $stop_minute = filter_input(INPUT_POST, 'stop_minute', FILTER_VALIDATE_INT);

        $sql = "UPDATE afrim 
            SET start_hour = ?, start_minute = ?, stop_hour = ?, stop_minute = ?
            WHERE room_id = ?;";
        $params = array($start_hour, $start_minute, $stop_hour, $stop_minute, $room_id);
        $db->query($sql, $params);

        // Log the update
        $message = "Afrim updated to start at $start_hour:$start_minute and end at $stop_hour:$stop_minute for room $room.";
        $sql = "INSERT INTO log (created, type, message) VALUES (?, ?, ?);";
        $params = array(date('Y-m-d H:i:s'), 3, $message);
        $db->query($sql, $params);

    } else if (isset($_POST['room']) && isset($_POST['room_id'])) { //Update Alarm and Create Log
        $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
        $room = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_STRING);
        $sql = "UPDATE alarm SET status = 2 WHERE id = ?;";
        $params = array($room_id);
        $db->query($sql, $params);
        $message = "Alarm kvitteret for rum " . $room;
        $sql = "INSERT INTO log (created, type, message) VALUES (?, ?, ?);";
        $params = array(date('Y-m-d H:i:s'), 1, $message);
        $db->query($sql, $params);
    } else if (isset($_POST['room']) && isset($_POST['room_id'])) { //Update Alarm and Create Log
        $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
        $room = filter_input(INPUT_POST, 'room', FILTER_SANITIZE_STRING);
        $sql = "UPDATE alarm SET status = 2 WHERE id = ?;";
        $params = array($room_id);
        $db->query($sql, $params);
        $message = "Alarm kvitteret for rum " . $room;
        $sql = "INSERT INTO log (created, type, message) VALUES (?, ?, ?);";
        $params = array(date('Y-m-d H:i:s'), 1, $message);
        $db->query($sql, $params);
    }

    $sql = "SELECT s.id, s.sensor_name, r.setpunkt, a.status, r.id AS room_id, af.id AS afrim_id, af.start_hour, af.stop_hour, af.start_minute, af.stop_minute 
			FROM sensor AS s 
			INNER JOIN room AS r ON s.room_id = r.id 
			INNER JOIN alarm AS a ON a.room_id = s.room_id 
			INNER JOIN afrim AS af ON af.room_id = s.room_id
			ORDER BY s.sensor_name ASC;";

    if ($sensors = $db->select($sql, array())) {
        foreach ($sensors as $sensor) {
            $sql = "SELECT t.temperature FROM temperature_reading AS t WHERE t.sensor_id = ? ORDER BY t.reading_time DESC LIMIT 1;";
            $params = array($sensor['id']);
            $room = substr($sensor['sensor_name'], 0, 5);
            $sensor_name = substr($sensor['sensor_name'], 6);

            if ($temperature = $db->select($sql, $params)) {
                if (stripos($sensor_name, "a") !== false) {
                    $data[$room] = array("room_id" => $sensor['room_id'], "room" => $room, "sensor" => $sensor_name, "temp" => $temperature[0]['temperature'], "setpunkt" => $sensor['setpunkt'], "alarm" => $status[$sensor['status']], "status" => $sensor['status']);
                } else {
                    $data[$room]['sensor2'] = $sensor_name;
                    $data[$room]['temp2'] = $temperature[0]['temperature'];
                }
            }
        }
    }
}
?>

<style>


    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
        text-align: center;
        margin: 0 auto;
    }

    th, td {
        text-align: center;
        min-width: 100px;
    }

    #afrimningCol {
        width: 25px;
    }

    .vertical-offset-25 {
        padding: 25px;
    }

    input {
        width: 100px !important;
    }

    .inputDateRange {
        width: 350px !important;
    }

    input[type="text"] {
        display: block;
        margin: 0 auto;
    }

    #btnDateRange {
        display: block;
        margin: 0 auto;
    }

    div {
        display: table;
    }

    div.t {
        display: table-cell;
        width: 100%;
    }

    div.t > input {
        width: 100%;
    }

    p {
        margin-top: -10px !important;
        margin-bottom: 0 !important;
    }


    .daterangepicker .calendar-table,
    .daterangepicker .calendar-time div {
        display: none;
    }

    .daterangepicker .drp-calendar {
        width: 100%;
    }



</style>
<meta http-equiv="refresh" content="300">
<link rel="stylesheet" type="text/css" href="/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="/daterangepicker.css"/>
<script type="text/javascript" src="/jquery.min.js"></script>
<script type="text/javascript" src="/moment.min.js"></script>
<script type="text/javascript" src="/daterangepicker.min.js"></script>

<div class="container-fluid">
    <div class="row vertical-offset-25 justify-content-center">
        <div class="col-12">
            <table>
                <thead>
                <tr>
                    <th id="rumCol">Rum</th>
                    <th id="sensorCol">Føler</th>
                    <th id="temperaturCol">Temperatur</th>
                    <th id="setpunktCol">Setpunkt</th>
                    <th id="alarmStatusCol">Alarm status</th>
                    <th id="afrimningCol">Afrimning</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data as $item) : ?>
                    <tr>
                        <td><?php echo $item['room']; ?></td>
                        <td><?php echo $item['sensor']; ?><br/><?php echo $item['sensor2']; ?></td>
                        <td><?php echo $item['temp']; ?> C&deg;<br/><?php echo $item['temp2']; ?> C&deg;</td>
                        <td>
                            <form name="setpunkt_form" method="POST" action="/table.php" accept-charset="UTF-8">
                                <input type="hidden" name="room_id" value="<?php echo $item['room_id']; ?>"/>
                                <input type="hidden" class="form-control" placeholder="old_setpunkt"
                                       name="old_setpunkt" value="<?php echo $item['setpunkt']; ?>"/>
                                <input type="hidden" class="form-control" placeholder="room" name="room"
                                       value="<?php echo $item['room']; ?>"/>
                                <div class="t">
                                    <input type="text" class="form-control"
                                           placeholder="setpunkt"
                                           name="setpunkt" required=""
                                           autofocus=""
                                           value="<?php echo $item['setpunkt']; ?>"/>
                                </div>
                                <div class="t">
                                    <button id="button-13" type="submit" name="submit">Gem</button>
                                </div>
                            </form>
                        </td>
                        <td><?php echo $item['alarm']; ?><?php if ($item['status'] == 1) : ?>
                                <form name="setpunkt_form" method="POST" action="/table.php" accept-charset="UTF-8">
                                <input type="hidden" name="room_id" value="<?php echo $item['room_id']; ?>"/>
                                <input type="hidden" class="form-control" placeholder="room" name="room"
                                       value="<?php echo $item['room']; ?>"/>
                                <button id="button-13" type="submit" name="submit">Kvitter alarm</button>
                                </form><?php endif; ?></td>

                        <td class="leftText">
                            <form name="afrim_form" method="POST" action="/table.php" accept-charset="UTF-8">
                                <input type="hidden" name="room_id" value="<?php echo $item['room_id']; ?>"/>
                                <input type="hidden" class="form-control" placeholder="room" name="room"
                                       value="<?php echo $item['room']; ?>"/>
                                <div class="t">
                                    <label for="start_time">Start Tid:</label>
                                    <input type="number" class="form-control" name="start_hour" min="0" max="23" placeholder="Start HH" required>
                                    <input type="number" class="form-control" name="stop_hour" min="0" max="23" placeholder="Stop HH" required>
                                </div>
                                <div class="t">
                                    <label for="stop_time">Slut Tid:</label>
                                    <input type="number" class="form-control" name="start_minute" min="0" max="59" placeholder="Start MM" required>
                                    <input type="number" class="form-control" name="stop_minute" min="0" max="59" placeholder="Stop MM" required>
                                </div>
                                <div class="t">
                                    <button class="button-13" id="btnDateRange" type="submit" name="submit">Gem</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script type="text/javascript">
    $(function () {
        $(".datepicker").attr("autocomplete", "off");
        $('input[name="datetimes"]').daterangepicker({
            timePicker24Hour: true,
            timePicker: true,
            timePickerSeconds: false,
            singleDatePicker: false,
            showDropdowns: false,
            locale: {
                format: ' HH:mm',
                applyLabel: "Vælg",
                cancelLabel: "Annuller"
            },
            startDate: moment().startOf('hour'),
            endDate: moment().startOf('hour').add(1, 'hours')
        });
    });
</script>

