<?php
require_once '/var/www/html/backend/config/Database.php';


$db = new Database();

try {
    $db->connect();
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

///
$modbus_reg_addresses = [
    1101,  // Sensor 1 (Room 1, Sensor 1)
    1103,  // Sensor 2 (Room 1, Sensor 2)
    1105,  // Sensor 3 (Room 2, Sensor 1)
    1107,  // Sensor 4 (Room 2, Sensor 2)
    1109,  // Sensor 5 (Room 3, Sensor 1)
    1111,  // Sensor 6 (Room 3, Sensor 2)
    1113,  // Sensor 7 (Room 4, Sensor 1)
    1115,  // Sensor 8 (Room 4, Sensor 2)
    1117,  // Sensor 9 (Room 5, Sensor 1)
    1119,  // Sensor 10 (Room 5, Sensor 2)
];
$modbus_relay_addresses = [
    0420,  // Sensor 1 ,2 (Room 1,)
    0421,  // Sensor 3,4 (Room 2,)
    0422,  // Sensor 5,6 (Room 3,)
    0423,  // Sensor 7,8 (Room 4,)
    0424,  // Sensor 9.10 (Room 5,)
];

$sensor_mapping = [
    1 => 'Hal 1 Temp A.',
    2 => 'Hal 1 Temp B.',
    3 => 'Hal 2 Temp A.',
    4 => 'Hal 2 Temp B.',
    5 => 'Hal 3 Temp A.',
    6 => 'Hal 3 Temp B.',
    7 => 'Hal 4 Temp A.',
    8 => 'Hal 4 Temp B.',
    9 => 'Hal 5 Temp A.',
    10 => 'Hal 5 Temp B.',
];

$sensor_temperatures = [];
foreach ($modbus_reg_addresses as $index => $startAddress) {
    $temperature = get_temp_modbus($startAddress);

    if (!empty($temperature)) {
        $sensor_temperatures[] = $temperature;
    } else {
        $sensor_temperatures[] = null;  
    }
}

foreach ($sensor_temperatures as $sensor_index => $temperature) {
    $sensor_id = $sensor_index + 1; 
    if ($temperature !== null) {
        $sql = "SELECT s.id, r.setpunkt, a.status, a.id AS alarm_id, r.id AS room_id FROM sensor AS s INNER JOIN room AS r ON r.id = s.room_id INNER JOIN alarm AS a ON a.room_id = s.room_id WHERE s.sensor_name = ?";
        $params = array($sensor_mapping[$sensor_id]);
        $result = $db->select($sql, $params);
        $sensor_id = $result[0]['id'];
        $temperature_max = $result[0]['setpunkt'];
        $status = $result[0]['status'];
        $alarm_id = $result[0]['alarm_id'];
        $room_id = $result[0]['room_id'];
        
        if ($sensor_id > 0) {
            $sql2 = "INSERT INTO temperature_reading (sensor_id, temperature, reading_time) VALUES (?, ?, ?);";
            $params2 = array($sensor_id, $temperature, date('Y-m-d H:i:s'));

            if(!$db->query($sql2, $params2)) {
                //error inserting
            }//hvis temp er = eller st©ªre end temp_max aktiver alarm

            switch($sensor_id) {
                case 1:
                case 2:
                    $alarm_index = 0;
                    break;
                case 3:
                case 4: 
                    $alarm_index = 1;
                    break;
                case 5:
                case 6: 
                    $alarm_index = 2;
                    break;
                case 7:
                case 8:
                    $alarm_index = 3;
                    break;
                case 9:
                case 10:
                    $alarm_index = 4;
                    break;
            }

            $alarm_address = $modbus_relay_addresses[$alarm_index];
            $sql_afrim = "SELECT count(id) AS count FROM afrim WHERE start_time <= ? AND stop_time >= ? AND room_id = ?;";
            $params = array(date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $room_id);
            $result2 = $db->select($sql_afrim, $params);
            $afrim = ($result2[0]['count'] > 0 ? true : false);

            if ($temperature >= $temperature_max) {
                if($status != 2) {
                    if(!$afrim) {
                        if($status == 0) {
                            update_status($db, $alarm_id, 1);
                        }

                        $message = $sensor_mapping[$sensor_id] . " er i alarm";
                        write_log($db, $message);
                        set_alarm($alarm_address, 1);
                    } else {
                        update_status($db, $alarm_id, 2);
                        $message = $sensor_mapping[$sensor_id] . " er i alarm og auto kvitteret paa grund af afrimning";
                        write_log($db, $message);
                    }
                }
            } else {
                if($status != 0) {
                    update_status($db, $alarm_id, 0);
                    $message = $sensor_mapping[$sensor_id] . " er ikke i alarm";
                    write_log($db, $message);
                    set_alarm($alarm_address, 0);
                }
            }

            echo "Sensor ID $sensor_id: Temperature $temperature ¡ÆC\n";
        } else {
            echo "Sensor $sensor_id not found in the database.\n";
        }
    } else {
        echo "Sensor $sensor_id: Failed to read temperature.\n";
    }
}

$db->disconnect();

function get_temp_modbus($reg_address) {
    $temp = -100;
    $ouput = array();
    $command = "/home/hovmark/modpoll/arm-linux-gnueabihf/modpoll -m tcp -r " . $reg_address . " -c 1 -1 172.16.20.2";
    exec($command, $output);
    $temp = substr($output[10], strpos($output[10], ":") + 2) / 10;
    return $temp;
}

function set_alarm($reg_address, $state) {
    $command = "/home/hovmark/modpoll/arm-linux-gnueabihf/modpoll -m tcp -r " . $reg_address . " -t 0 172.16.20.2 " . $state;
    exec($command);
}

function write_log($db, $message) {
    $sql = "INSERT INTO log (created, type, message) VALUES (?, ?, ?);";
    $params = array(date('Y-m-d H:i:s'), 1, $message);
    $db->query($sql, $params);
}

function update_status($db, $alarm_id, $status) {
    $sql = "UPDATE alarm SET status = ? WHERE id = ?;";
    $params = array($status, $alarm_id);
    $db->query($sql, $params);
}