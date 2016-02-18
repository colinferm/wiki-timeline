<?php
$input = file('./data.txt');
$output = fopen('./data.sql', 'w+');

$age = 1;
$year = 0;
$order = 1;
$count = 0;
echo 'Size of file: '.sizeof($input)."\n";
foreach ($input as $lineNumber => $line) {
	if (strpos($line, "===") !== false && strpos($line, "===") == 0) {
		$year = trim(substr($line, 3, 4));
		$order = 1;
		//echo 'Found year: '.$year."\n";

	} else if (strpos($line, "=") !== false && strpos($line, "=") == 0) {
		if (strpos($line, 'Colonization') !== false) {
			$age = 1;
			echo "Found: Age of Colonization\n";
		} else if (strpos($line, 'War') !== false) {
			$age = 2;
			echo "Found: Age of War\n";
		} else if (strpos($line, 'Betrayal') !== false) {
			$age = 3;
			echo "Found: Age of Betrayal\n";
		}

	} else if (strpos($line, "*") !== false && strpos($line, "*") == 0) {
		$notes = trim(addslashes(substr($line, 1)));
		$insertStatement = "INSERT INTO urswiki_timeline_events VALUES (0, '$year', '$year', '$age', $order, NULL, NULL, '$notes', NULL, NULL, NULL);\n";
		fwrite($output, $insertStatement);
		$order++;
		$count++;
	}
}
//echo 'Last Note: '.$notes."\n";
echo 'Counted '.$count.' number of timeline items.'."\n";

fclose($output);
?>
