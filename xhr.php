<?php
	// xhr.php
	header("content-type: text/html; charset=UTF-8");
	// inint
	include (dirname(__FILE__).'/bin/inint.php');
	// ตรวจสอบ referer
	if (gcms::isReferer()) {
		if (is_dir(ROOT_PATH.'admin/install') || (isset($config['maintenance_mode']) && $config['maintenance_mode'] == 1 && !gcms::isAdmin())) {
			$ret['content'] = rawurlencode($lng['MAINTENANCE_DETAIL']);
			$ret['title'] = rawurlencode(strip_tags($config['web_title']));
		} else {
			$ret = array();
			// query จาก URL ที่ส่งมา
			$urls = array();
			foreach ($_POST AS $key => $value) {
				if (!in_array($key, array('action', 'lang', 'f', 'c'))) {
					$urls[$key] = "$key=$value";
				}
				if ($key == 'module') {
					$module = $value;
				}
			}
			// ค่าคงที่สำหรับป้องกันการเรียกหน้าเพจโดยตรง
			DEFINE('MAIN_INIT', 'xhr');
			// โหลดเมนูทั้งหมดเรียงตามลำดับเมนู (รายการแรกคือหน้า Home)
			$sql = "SELECT M.`id` AS `module_id`,M.`module`,M.`owner`,M.`config`,U.`index_id`,U.`parent`,U.`level`,U.`menu_text`,U.`menu_tooltip`,U.`accesskey`,U.`menu_url`,U.`menu_target`,U.`alias`";
			$sql .= ",(CASE U.`parent` WHEN 'MAINMENU' THEN 0 WHEN 'BOTTOMMENU' THEN 1 WHEN 'SIDEMENU' THEN 2 ELSE 3 END ) AS `pos`";
			$sql .= " FROM `".DB_MENUS."` AS U";
			$sql .= " LEFT JOIN `".DB_INDEX."` AS I ON I.`id`=U.`index_id` AND I.`index`='1' AND I.`language` IN ('".LANGUAGE."','')";
			$sql .= " LEFT JOIN `".DB_MODULES."` AS M ON M.`id`=I.`module_id`";
			$sql .= " WHERE U.`language` IN ('".LANGUAGE."','')";
			$sql .= " ORDER BY `pos` ASC,U.`parent` ASC ,U.`menu_order` ASC";
			$menus = $cache->get($sql);
			if (!$menus) {
				$menus = $db->customQuery($sql);
				$cache->save($sql, $menus);
			}
			foreach ($menus AS $item) {
				if (!isset($install_modules[$item['module']]) && $item['module'] != '') {
					$install_modules[$item['module']] = $item;
					$install_owners[$item['owner']][] = $item['module'];
					$module_list[] = $item['module'];
				}
			}
			// โหลดโมดูลทั้งหมดที่ติดตั้ง
			$sql = "SELECT `id` AS `module_id`,`module`,`owner`,`config` FROM `".DB_MODULES."`";
			$_modules = $cache->get($sql);
			if (!$_modules) {
				$_modules = $db->customQuery($sql);
				$cache->save($sql, $_modules);
			}
			foreach ($_modules AS $item) {
				if (!isset($install_modules[$item['module']])) {
					$install_modules[$item['module']] = $item;
					$install_owners[$item['owner']][] = $item['module'];
					$module_list[] = $item['module'];
				}
			}
			// โมดูลที่ติดตั้ง
			$dir = ROOT_PATH.'modules/';
			$f = @opendir($dir);
			if ($f) {
				while (false !== ($text = readdir($f))) {
					if ($text != '.' && $text != '..') {
						if (is_dir($dir.$text)) {
							if (!isset($install_owners[$text])) {
								$install_owners[$text] = array();
							}
							$module_list[] = $text;
						}
					}
				}
				closedir($f);
			}
			// ไม่มีโมดูลใช้โมดูลแรกสุด
			$module = empty($module) ? $module_list[0] : $module;
			// ตรวจสอบโมดูลที่เรียก
			include ROOT_PATH.'module.php';
			// โหลดภาษา,config,ไฟล์ inint ของโมดูลที่ติดตั้ง
			foreach ($install_owners AS $owner => $items) {
				if (is_file(ROOT_PATH."modules/$owner/config.php")) {
					include_once (ROOT_PATH."modules/$owner/config.php");
				}
				if (is_file(ROOT_PATH."modules/$owner/inint.php")) {
					include_once (ROOT_PATH."modules/$owner/inint.php");
				}
			}
			// canonical
			$canonical = WEB_URL.'/index.php';
			// ค่า title,description และ keywords ของเว็บหลัก
			$title = strip_tags($config['web_title']);
			$description = $config['web_description'];
			$keywords = $description;
			// login
			$isMember = gcms::isMember();
			// admin
			$isAdmin = gcms::isAdmin();
			// ตัวแปรหลังจากแสดงผลแล้ว
			$custom_patt = array();
			// เรียกโมดูล
			if (is_file(ROOT_PATH."modules/$modules[2]/$modules[3].php")) {
				include ROOT_PATH."modules/$modules[2]/$modules[3].php";
			} else {
				// ไม่พบ module
				$title = $lng['PAGE_NOT_FOUND'];
				$content = '<div class=error>'.$title.'</div>';
			}
			// คืนค่าเนื้อหา
			$ret['menu'] = rawurlencode($menu);
			$ret['title'] = rawurlencode(strip_tags($title));
			// เตรียมข้อมูลสำหรับใส่ใน template
			$main_patt = array();
			$main_patt['/{URL}/'] = $canonical;
			$main_patt['/{XURL}/'] = rawurlencode($canonical);
			$main_patt['/{WIDGET_([A-Z]+)(([\s_])(.*))?}/e'] = OLD_PHP ? 'gcms::getWidgets(array(1=>\'$1\',3=>\'$3\',4=>\'$4\'))' : 'gcms::getWidgets';
			$main_patt['/{(LNG_[A-Z0-9_]+)}/e'] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
			$main_patt['/{SKIN}/'] = SKIN;
			$main_patt['/{WEBURL}/'] = WEB_URL;
			$main_patt['/{LANGUAGE}/'] = LANGUAGE;
			$main_patt['/{WEBTITLE}/'] = strip_tags($config['web_title']);
			// ตัวแปรหลังจากแสดงผลแล้ว
			$main_patt = array_merge($main_patt, $custom_patt);
			// แสดงผล
			$ret['content'] = rawurlencode(gcms::pregReplace(array_keys($main_patt), array_values($main_patt), $content));
			if (isset($_POST['to'])) {
				$ret['to'] = rawurlencode($_POST['to']);
			}
			// บันทึก useronline
			include ROOT_PATH.'useronline.php';
			// อัปเดท pagesview
			$db->query("UPDATE `".DB_COUNTER."` SET `pages_view`=`pages_view`+1,`time`='$mmktime' WHERE `date`='".date('Y-m-d', $mmktime)."' LIMIT 1");
			// คืนค่า เวลาที่ใช้ไป
			$ret['db_elapsed'] = $db->timer_stop();
			$ret['db_quries'] = $db->query_count();
		}
		// คืนค่า JSON
		echo gcms::array2json($ret);
	}
