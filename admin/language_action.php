<?php
	// admin/language_action.php
	header("content-type: text/html; charset=UTF-8");
	// inint
	include '../bin/inint.php';
	$ret = array();
	// ตรวจสอบ referer และ admin
	if (gcms::isReferer() && gcms::isAdmin()) {
		if (isset($_SESSION['login']['account']) && $_SESSION['login']['account'] == 'demo') {
			$ret['error'] = 'EX_MODE_ERROR';
		} else {
			// action
			$action = gcms::getVars($_POST, 'action', '');
			if ($action == 'deletelang') {
				// ลบภาษาที่ id
				$id = gcms::getVars($_POST, 'id', 0);
				$db->delete(DB_LANGUAGE, $id);
				// อ่านไฟล์ภาษาใหม่
				gcms::saveLanguage();
				// คืนค่ารายการที่ลบ
				$ret = array('error' => 'DELETE_SUCCESS', 'remove' => "L_$id");
			} elseif ($action == 'droplang') {
				// ลบชื่อภาษา
				$lang = $db->sql_trim_str($_POST, 'lang');
				if (preg_match('/^[a-z]{2,2}$/', $lang)) {
					$db->query("ALTER TABLE `".DB_LANGUAGE."` DROP `$lang`");
					// ลบไอคอนและไฟล์ภาษา
					@unlink(DATA_PATH."language/$lang.gif");
					@unlink(DATA_PATH."language/$lang.php");
					@unlink(DATA_PATH."language/$lang.js");
					// โหลด config ใหม่
					$config = array();
					if (is_file(CONFIG)) {
						include CONFIG;
					}
					foreach ($config['languages'] AS $item) {
						if ($item != $lang) {
							$languages[] = $item;
						}
					}
					// save
					$config['languages'] = $languages;
					if (gcms::saveConfig(CONFIG, $config)) {
						// คืนค่ารายการที่ลบ
						$ret['remove'] = "L_$lang";
						$ret['error'] = 'DELETE_SUCCESS';
					} else {
						$ret['error'] = 'DO_NOT_SAVE';
					}
				}
			} elseif ($action == 'changed' || $action == 'move') {
				// โหลด config ใหม่
				$config = array();
				if (is_file(CONFIG)) {
					include CONFIG;
				}
				if ($action == 'changed') {
					// เปลี่ยนแปลงสถานะการเผยแพร่ภาษา
					$datas = str_replace('check_', '', $_POST['data']);
				} else {
					// จัดลำดับภาษา
					$datas = str_replace('L_', '', $_POST['data']);
				}
				$config['languages'] = explode(',', $datas);
				if (sizeof($config['languages']) > 0) {
					// save config
					if (gcms::saveConfig(CONFIG, $config)) {
						$ret['error'] = 'SAVE_COMPLETE';
					} else {
						$ret['error'] = 'DO_NOT_SAVE';
					}
				}
			}
		}
		// คืนค่าเป็น JSON
		echo gcms::array2json($ret);
	}
