<?php
	// modules/event/month.php
	if (defined('MAIN_INIT')) {
		// ตรวจสอบโมดูล
		$sql = "SELECT I.`module_id`,M.`module`,D.`detail`,D.`topic`,D.`description`,D.`keywords`";
		$sql .= " FROM `".DB_INDEX_DETAIL."` AS D";
		$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`=D.`module_id` AND I.`index`='1' AND I.`language`=D.`language`";
		$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`owner`='event'";
		$sql .= " WHERE D.`language` IN ('".LANGUAGE."','') LIMIT 1";
		$index = $cache->get($sql);
		if (!$index) {
			$index = $db->customQuery($sql);
			$cache->save($sql, $index);
		}
		if (sizeof($index) == 0) {
			$title = $lng['LNG_DATA_NOT_FOUND'];
			$content = '<div class=error>'.$title.'</div>';
		} else {
			$index = $index[0];
			// breadcrumbs
			$breadcrumb = gcms::loadtemplate($index['module'], '', 'breadcrumb');
			$breadcrumbs = array();
			// หน้าหลัก
			$breadcrumbs['HOME'] = gcms::breadcrumb('icon-home', $canonical, $install_modules[$module_list[0]]['menu_tooltip'], $install_modules[$module_list[0]]['menu_text'], $breadcrumb);
			// โมดูล
			if ($index['module'] != $module_list[0]) {
				if (isset($install_modules[$index['module']]['menu_text'])) {
					$m = $install_modules[$index['module']]['menu_text'];
					$t = $install_modules[$index['module']]['menu_tooltip'];
				} else {
					$m = ucwords($index['module']);
					$t = $m;
				}
				$canonical = gcms::getURL($index['module']);
				$breadcrumbs['MODULE'] = gcms::breadcrumb('', $canonical, $t, $m, $breadcrumb);
			}
			// โหลด calendar
			include (ROOT_PATH.'modules/event/calendar.php');
			// แสดงผล
			$patt = array('/{BREADCRUMS}/', '/{CALENDAR}/', '/{TOPIC}/', '/{DETAIL}/', '/{(LNG_[A-Z0-9_]+)}/e');
			$replace = array();
			$replace[] = implode("\n", $breadcrumbs);
			$replace[] = implode('', $calendar);
			$replace[] = $index['topic'];
			$replace[] = $index['detail'];
			$replace[] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
			$content = gcms::pregReplace($patt, $replace, gcms::loadtemplate($index['module'], 'event', 'main'));
			// title,keywords,description
			$title = $index['topic'];
			$keywords = $index['keywords'];
			$description = $index['description'];
		}
	}
