<?php
	// modules/event/view.php
	if (defined('MAIN_INIT')) {
		// ตรวจสอบรายการที่เลือกและโมดูล
		$sql = "SELECT C.*,M.`module`,D.`topic` AS `title`";
		$sql .= " FROM `".DB_EVENTCALENDAR."` AS C";
		$sql .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`module_id`=C.`module_id` AND D.`language` IN ('".LANGUAGE."','')";
		$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`=D.`module_id` AND I.`index`='1' AND I.`language`=D.`language`";
		$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`owner`='event'";
		$sql .= " WHERE C.`id`=".(int)$_REQUEST['id']." LIMIT 1";
		// ตรวจสอบข้อมูลจาก cache
		$index = $cache->get($sql);
		if (!$index) {
			$index = $db->customQuery($sql);
			$cache->save($sql, $index);
		}
		if (sizeof($index) == 0) {
			$title = $lng['PAGE_NOT_FOUND'];
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
			$canonical = gcms::getURL($index['module'], '', 0, 0, "id=$index[id]");
			// แทนที่ลงใน template ของโมดูล
			$patt = array('/{BREADCRUMS}/', '/{TOPIC}/', '/{DETAIL}/', '/{COLOR}/', '/{(LNG_[A-Z0-9_]+)}/e',
				'/{YEAR}/', '/{MONTH}/', '/{DATE}/', '/{TIME}/', '/{DAYURL}/', '/{URL}/');
			$replace = array();
			$replace[] = implode("\n", $breadcrumbs);
			$replace[] = $index['topic'];
			$replace[] = gcms::showDetail($index['detail'], true, false);
			$replace[] = $index['color'];
			$replace[] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
			preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)\s([0-9]{2,2}:[0-9]{2,2}):([0-9]{2,2})$/', $index['begin_date'], $match);
			$replace[] = (int)$match[1] + $lng['YEAR_OFFSET'];
			$replace[] = $lng['MONTH_SHORT'][(int)$match[2] - 1];
			$replace[] = (int)$match[3];
			$replace[] = $match[4];
			$replace[] = gcms::getURL($index['module'], '', 0, 0, "d=$match[1]-$match[2]-$match[3]");
			$replace[] = $canonical;
			$content = gcms::pregReplace($patt, $replace, gcms::loadtemplate($index['module'], 'event', 'view'));
			// title,keywords,description,canonical
			$title = $index['topic'];
			$keywords = $index['keywords'];
			$description = $index['detail'];
			// เลือกเมนู
			$menu = empty($install_modules[$index['module']]['alias']) ? $index['module'] : $install_modules[$index['module']]['alias'];
		}
	}
