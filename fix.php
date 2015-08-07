<?php

// (c) z3n - R1V1@150806 - www.overflow.biz - rodrigo.orph@gmail.com

if (!isset($argv[1]))
	die('Usage: ' . $_SERVER['PHP_SELF'] . ' <save | load | test> [slot]');

require_once('vars.php');

$argv[1] = strtolower($argv[1]);

switch ($argv[1]) {
	case 'save':
		@ mkdir(SLOT_PATH, 0654, true);
		
		$slot = isset($argv[2]) ? intval($argv[2]) : date('ymdHis');
		exec(CMDOW . ' /P', $output);
		
		// process data
		$windows = array();
		
		foreach ($output as $i => $line) {
			if ($i == 0)
				continue;
			
			$line = explode(' ', preg_replace('/\s+/', ' ', $line));
			
			if ($line[6] == 'Vis')
				$windows[$line[0]] = array(
					'left' => $line[7],
					'top' => $line[8],
					'width' => $line[9],
					'height' => $line[10]
				);
		}
		
		$fn = SLOT_PATH . $slot . '.php';
		file_put_contents($fn, '<?php $windows = ' . var_export($windows, true) . ';');
		echo "*** Saved on `" . $fn . "`\n";
		
		break;
	
	case 'test':
	case 'load':
		$slot = isset($argv[2]) ? $argv[2] : false;
		
		if ($slot === false) { // get newest file from folder
			$files = glob(SLOT_PATH . '*.php');
			$max = 0;
			$fn = false;
			
			foreach ($files as $file) {
				$fm = filemtime($file);
				if ($fm > $max) {
					$max = $fm;
					$fn = $file;
				}
			}
			
		} else {
			$fn = SLOT_PATH . $slot . '.php';
		}
		
		if (!file_exists($fn))
			die('File `' . $fn . '` doesn\'t exists!');
		else
			echo 'Loading: `' . $fn . "` ... \n";
		
		require_once($fn);
		
		exec(CMDOW . ' /P', $output);
		
		// process data
		foreach ($output as $i => $line) {
			if ($i == 0)
				continue;
			
			$line = explode(' ', preg_replace('/\s+/', ' ', $line));
			
			if (
				$line[6] == 'Vis' &&
				isset($windows[$line[0]]) &&
				(
					$windows[$line[0]]['left'] != $line[7] ||
					$windows[$line[0]]['top'] != $line[8] ||
					$windows[$line[0]]['width'] != $line[9] ||
					$windows[$line[0]]['height'] != $line[10]
				)
			) {
				echo '*** ' . $line[0] . " has changed!\n";
				
				if ($argv[1] == 'load') // fix changed window
					exec(CMDOW . ' ' . $line[0] . ' /SIZ ' . $windows[$line[0]]['width'] . ' ' . $windows[$line[0]]['height'] . ' /MOV ' . $windows[$line[0]]['left'] . ' ' . $windows[$line[0]]['top']);
			}
		}
		
		break;
	
	default:
		die('Unknown option `' . $argv[1] . '`, aborting.');
}