<?php
	// modules/event/day.php
	if (defined('MAIN_INIT') && preg_match('/^([0-9]{4,4})\-([0-9]{1,2})\-([0-9]{1,2})$/', $_REQUEST['d'], $match)) {
		// ตรวจสอบโมดูลที่ติดตั้ง
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
			// url ของหน้านี้
			$canonical = gcms::getURL($index['module'], '', 0, 0, "m=$match[1]-$match[2]");
			// โหลด calendar
			$calendar = array();
			$sql = "SELECT D.`id`,D.`color`,D.`topic`,D.`description`,TIME(D.`begin_date`) AS `t`";
			$sql .= " FROM `".DB_EVENTCALENDAR."` AS D";
			$sql .= " WHERE D.`module_id`='$index[module_id]' AND YEAR(D.`begin_date`)='$match[1]' AND MONTH(D.`begin_date`)='$match[2]' AND DAY(D.`begin_date`)='$match[3]'";
			$sql .= " AND D.`published`='1' AND D.`published_date`<='".date('Y-m-d', $mmktime)."'";
			$sql .= " ORDER BY D.`begin_date` ASC";
			$datas = $cache->get($sql);
			if (!$datas) {
				$datas = $db->customQuery($sql);
				$cache->save($sql, $datas);
			}
			$skin = gcms::loadtemplate($index['module'], 'event', 'dayitem');
			$patt = array('/{COLOR}/', '/{URL}/', '/{TOPIC}/', '/{DESCRIPTION}/', '/{TIME}/');
			foreach ($datas AS $item) {
				$replace = array();
				$replace[] = $item['color'];
				$replace[] = gcms::getUrl($index['module'], '', 0, 0, "id=$item[id]");
				$replace[] = $item['topic'];
				$replace[] = $item['description'];
				preg_match('/^(([0-9]+):([0-9]+)):[0-9]+$/', $item['t'], $m);
				$replace[] = $m[1];
				$calendar[] = preg_replace($patt, $replace, $skin);
			}
			// แสดงผล
			$patt = array('/{BREADCRUMS}/', '/{LIST}/', '/{TOPIC}/', '/{(LNG_[A-Z0-9_]+)}/e', '/{YEAR}/', '/{MONTH}/', '/{DATE}/', '/{URL}/');
			$replace = array();
			$replace[] = implode("\n", $breadcrumbs);
			$replace[] = implode('', $calendar);
			$replace[] = $index['topic'];
			$replace[] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
			$replace[] = (int)$match[1] + $lng['YEAR_OFFSET'];
			$replace[] = $lng['MONTH_SHORT'][(int)$match[2] - 1];
			$replace[] = (int)$match[3];
			$replace[] = $canonical;
			$content = gcms::pregReplace($patt, $replace, gcms::loadtemplate($index['module'], 'event', 'day'));
			// title,keywords,description
			$title = $index['topic'];
			$keywords = $index['keywords'];
			$description = $index['detail'];
		}
	} else {
		$title = $lng['LNG_DATA_NOT_FOUND'];
		$content = '<div class=error>'.$title.'</div>';
	}
