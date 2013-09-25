<?php
header('Content-Type: application/json; charset=utf-8');
$url = 'https://docs.google.com/spreadsheet/pub?key=0AhhyYX-BXbrFdGlHRGY0NGdxazdHMXR2SjJ6aXBXR1E&output=csv';
$content = file_get_contents($url);
$raw_rows = explode("\n", $content);
$rows = array();
// Don't skip the 0-th row.
for($r = 0; $r < count($raw_rows); $r++) {
	$row = $raw_rows[$r];
	$cells = array();
	$current_cell = '';
	$in_quotations = false;
	for($c = 0; $c <= strlen($row); $c++) {
		$char = $row[$c];
		if(($in_quotations === false && $char == ',') || $c == strlen($row)) {
			// New cell or last char.
			$cells[] = $current_cell;
			$current_cell = '';
		} elseif($in_quotations === false && $char == '"') {
			$in_quotations = true;
		} elseif($in_quotations === true && $char == '"') {
			$in_quotations = false;
		} else {
			$current_cell .= $row[$c];
		}
	}
	$rows[] = $cells;
}
echo json_encode($rows);
