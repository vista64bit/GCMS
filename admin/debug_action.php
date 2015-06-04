<?php
	// admin/debug_action.php
	header("content-type: text/html; charset=UTF-8");
	// inint
	include '../bin/inint.php';
	// ตรวจสอบ referer และ admin
	if (gcms::isReferer() && gcms::isAdmin()) {
		// action
		$action = gcms::getVars($_POST, 'action', '');
		if ($action == 'get' && is_file(DATA_PATH.'debug.php')) {
			$t = (int)$_POST['t'];
			foreach (file(DATA_PATH.'debug.php') AS $i => $row) {
				if (preg_match('/^([0-9]+)\|(.*)/', trim($row), $match)) {
					if ((int)$match[1] > $t) {
						echo "$match[1]|$match[2]\n";
					}
				}
			}
		} elseif ($action == 'clear') {
			@unlink(DATA_PATH.'debug.php');
		}
	}
