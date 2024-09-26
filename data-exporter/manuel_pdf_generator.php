<?php
require_once '/var/www/html/backend/config/Database.php';
require_once '/var/www/html/vendor/autoload.php';

date_default_timezone_set("Europe/Copenhagen");
$db = new Database();

if($db->connect()) {
	$data = array();
	$from = strtotime("2 DAYS AGO MIDNIGHT") - 300;
	$to = strtotime("1 DAYS AGO MIDNIGHT") + 300;
	$date_timestamp = $to - 600;
	$sql = "SELECT EXTRACT(EPOCH FROM t.reading_time) AS timestamp, t.temperature AS temp, s.sensor_name AS sensor 
			FROM temperature_reading AS t 
			INNER JOIN sensor AS s ON s.id = t.sensor_id 
			WHERE t.reading_time BETWEEN ? AND ? ORDER BY t.reading_time ASC;";
	$params = array(date('Y-m-d H:i:s', $from), date('Y-m-d H:i:s', $to));
	$results = $db->select($sql, $params);
	
	foreach($results as $result) {
		$timestamp_diffence = $result['timestamp'] % 60;
		$timestamp = $result['timestamp'] - $timestamp_diffence;
		
		if(stripos($result['sensor'], "hal 1") !== false) {
			if(stripos($result['sensor'], "temp a") !== false) {
				$data['Hal 1'][$timestamp]['tempa'] = $result['temp'];
				$data['Hal 1']['graph']['tempa']['name'] = $result['sensor'];
				$data['Hal 1']['graph']['tempa']['data'][] = array('', $result['timestamp'], $result['temp']);
			} else if(stripos($result['sensor'], "temp b") !== false) {
				$data['Hal 1'][$timestamp]['tempb'] = $result['temp'];
				$data['Hal 1']['graph']['tempb']['name'] = $result['sensor'];
				$data['Hal 1']['graph']['tempb']['data'][] = array('', $result['timestamp'], $result['temp']);
			}
		} else if(stripos($result['sensor'], "hal 2") !== false) {
			if(stripos($result['sensor'], "temp a") !== false) {
				$data['Hal 2'][$timestamp]['tempa'] = $result['temp'];
				$data['Hal 2']['graph']['tempa']['name'] = $result['sensor'];
				$data['Hal 2']['graph']['tempa']['data'][] = array('', $result['timestamp'], $result['temp']);
			} else if(stripos($result['sensor'], "temp b") !== false) {
				$data['Hal 2'][$timestamp]['tempb'] = $result['temp'];
				$data['Hal 2']['graph']['tempb']['name'] = $result['sensor'];
				$data['Hal 2']['graph']['tempb']['data'][] = array('', $result['timestamp'], $result['temp']);
			}
		} else if(stripos($result['sensor'], "hal 3") !== false) {
			if(stripos($result['sensor'], "temp a") !== false) {
				$data['Hal 3'][$timestamp]['tempa'] = $result['temp'];
				$data['Hal 3']['graph']['tempa']['name'] = $result['sensor'];
				$data['Hal 3']['graph']['tempa']['data'][] = array('', $result['timestamp'], $result['temp']);
			} else if(stripos($result['sensor'], "temp b") !== false) {
				$data['Hal 3'][$timestamp]['tempb'] = $result['temp'];
				$data['Hal 3']['graph']['tempb']['name'] = $result['sensor'];
				$data['Hal 3']['graph']['tempb']['data'][] = array('', $result['timestamp'], $result['temp']);
			}
		} else if(stripos($result['sensor'], "hal 4") !== false) {
			if(stripos($result['sensor'], "temp a") !== false) {
				$data['Hal 4'][$timestamp]['tempa'] = $result['temp'];
				$data['Hal 4']['graph']['tempa']['name'] = $result['sensor'];
				$data['Hal 4']['graph']['tempa']['data'][] = array('', $result['timestamp'], $result['temp']);
			} else if(stripos($result['sensor'], "temp b") !== false) {
				$data['Hal 4'][$timestamp]['tempb'] = $result['temp'];
				$data['Hal 4']['graph']['tempb']['name'] = $result['sensor'];
				$data['Hal 4']['graph']['tempb']['data'][] = array('', $result['timestamp'], $result['temp']);
			}
		} else if(stripos($result['sensor'], "hal 5") !== false) {
			if(stripos($result['sensor'], "temp a") !== false) {
				$data['Hal 5'][$timestamp]['tempa'] = $result['temp'];
				$data['Hal 5']['graph']['tempa']['name'] = $result['sensor'];
				$data['Hal 5']['graph']['tempa']['data'][] = array('', $result['timestamp'], $result['temp']);
			} else if(stripos($result['sensor'], "temp b") !== false) {
				$data['Hal 5'][$timestamp]['tempb'] = $result['temp'];
				$data['Hal 5']['graph']['tempb']['name'] = $result['sensor'];
				$data['Hal 5']['graph']['tempb']['data'][] = array('', $result['timestamp'], $result['temp']);
			}
		}
	}

	foreach($data as $building => $key_timestamp) {
		//both pdfs
		$header = '<table width="100%" style="font-weight:bold;vertical-align:top;"><tr><td width="50%"><img src="/var/www/html/data-exporter/sirena.png" style="height: 25px;" /></td><td width="50%" style="font-size:20px;text-align:right;">Temperatur rapport<br/>' . $building . ' ' . date("d-m-Y", $date_timestamp) . '</td></tr></table>';
		$footer = '<div width="100%"><img src="/var/www/html/assets/icon/Hovmark_Logo.png" style="height: 25px;" /></div><div width="100%"><img src="/var/www/html/assets/icon/OJ_OlsenOgJensen_Logo.jpg" style="height: 50px;" /></div><div width="100%" style="font-weight:bold;text-align:center;font-size:10px;">Side {PAGENO} af {nbpg}</div>';
		//table pdf html data
		$table_rows = "";

		foreach ($key_timestamp as $timestamp => $values) {
			if($timestamp == "graph") {
				continue;
			}

			$table_rows .= "<tr class=\"border\"><td class=\"border\">" . date("d-m-Y H:i", $timestamp) . "</td><td class=\"border\">" . (isset($values['tempa']) ? number_format($values['tempa'], 1, ',') : 'N/A') . " C&deg;</td><td class=\"border\">" . (isset($values['tempb']) ? number_format($values['tempb'], 1, ',') : 'N/A') . " C&deg;</td></tr>";
		}

		
		$table_html = "<style>.border { border: 1px solid black; border-collapse: collapse; } th, td { font-size: 10px; text-align: center; } th { width=33%; }</style><table width=\"100%\" class=\"border\"><thead><tr class=\"border\"><th class=\"border\">Dato og tid</th><th class=\"border\">Temperature A</th><th class=\"border\">Temperature B</th></tr></thead><tbody>" . $table_rows . "</tbody></table>";
		//graph pdf html data
		$colorList = array('black','blue','red','green','cyan','yellow','orange','magenta','grey');
		$plot = new \Phplot\Phplot\phplot(800, 500);
		$plot->SetImageBorderType('plain');
		$plot->SetPlotType('lines');
		$plot->SetDataType('data-data');
		$plot->SetDataValues($data[$building]['graph']['tempa']['data']);
		$plot->SetTitle($data[$building]['graph']['tempa']['name']);
		$plot->SetXLabelType('time','%H:%M');
		$plot->SetPlotAreaWorld(NULL, NULL, NULL, NULL);
		$plot->SetLineWidths(array('2','2','2'));
		$plot->SetDataColors($colorList);
		$plot->SetXTickAnchor($from);
		$plot->SetYLabelType('data');
		$plot->SetDrawXGrid(True);
		$plot->SetDrawYGrid(True);
		$plot->SetPrintImage(False);
		$plot->DrawGraph();
		$plot2 = new \Phplot\Phplot\phplot(800, 500);
		$plot2->SetImageBorderType('plain');
		$plot2->SetPlotType('lines');
		$plot2->SetDataType('data-data');
		$plot2->SetDataValues($data[$building]['graph']['tempb']['data']);
		$plot2->SetTitle($data[$building]['graph']['tempb']['name']);
		$plot2->SetXLabelType('time','%H:%M');
		$plot2->SetPlotAreaWorld(NULL, NULL, NULL, NULL);
		$plot2->SetLineWidths(array('2','2','2'));
		$plot2->SetDataColors($colorList);
		$plot2->SetXTickAnchor($from);
		$plot2->SetYLabelType('data');
		$plot2->SetDrawXGrid(True);
		$plot2->SetDrawYGrid(True);
		$plot2->SetPrintImage(False);
		$plot2->DrawGraph();
		$graph_html = "<img width=\"100%\" src=\"" . $plot->EncodeImage() . "\" /><br/><br/><br/><br/><img width=\"100%\" src=\"" . $plot2->EncodeImage() . "\" />";
		//prepare file path and name parts
		$file_path = "/data/pdf_reports/";
		$year = date("Y", $date_timestamp);
		$month = date("Y/m", $date_timestamp);
		$day = date("Y/m/d", $date_timestamp);
		create_folder($file_path . $year);
		create_folder($file_path . $month);
		create_folder($file_path . $day);
		$file_path = $file_path . $day . "/";
		$file_name = $building . "_" . date("Y-m-d", $date_timestamp);
		//table pdf
		$mpdf = new \Mpdf\Mpdf(['margin_bottom' => 30]);
		$mpdf->useSubstitutions = false;
		$mpdf->simpleTables = true;
		$mpdf->keep_table_proportions = true;
		$mpdf->SetTopMargin(25);
		$mpdf->SetHTMLHeader($header, 'O');
		$mpdf->SetHTMLHeader($header, 'E');
		$mpdf->SetHTMLFooter($footer, 'O');
		$mpdf->SetHTMLFooter($footer, 'E');
		$mpdf->WriteHTML($table_html);
		$mpdf->Output($file_path . $file_name . "_table.pdf", 'F');
		//graph pdf
		$mpdf = new \Mpdf\Mpdf(['margin_bottom' => 30]);
		$mpdf->SetTopMargin(25);
		$mpdf->SetHTMLHeader($header, 'O');
		$mpdf->SetHTMLHeader($header, 'E');
		$mpdf->SetHTMLFooter($footer, 'O');
		$mpdf->SetHTMLFooter($footer, 'E');
		$mpdf->WriteHTML($graph_html);
		$mpdf->Output($file_path . $file_name . "_graph.pdf", 'F');
	}

}

function create_folder($path) {
	if(!is_dir($path)) {
		mkdir($path);
	}
}
?>