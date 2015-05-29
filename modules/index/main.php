<?php
	// modules/index/main.php
	if (defined('MAIN_INIT')) {
		$id = gcms::getVars('REQUEST,REQUEST', 'mid,id', 0);
		// อ่านโมดูล ตามภาษา
		$sql = "SELECT M.`module`,I.`id`,D.`topic`,D.`description`,D.`keywords`,D.`detail`,I.`visited`";
		if ($id > 0) {
			$sql .= " FROM `".DB_INDEX."` AS I";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=I.`module_id`";
			$sql .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`id`=I.`id` AND D.`module_id`=I.`module_id` AND D.`language`=I.`language`";
			$sql .= " WHERE I.`id`='$id' AND I.`index`='1' AND I.`published`='1' AND I.`published_date`<='".date('Y-m-d', $mmktime)."' LIMIT 1";
		} else {
			$sql .= " FROM `".DB_INDEX_DETAIL."` AS D ";
			$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`index`='1' AND I.`published`='1' AND I.`published_date`<='".date('Y-m-d', $mmktime)."' AND I.`language`=D.`language`";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`module`='$module'";
			$sql .= " WHERE D.`language` IN ('".LANGUAGE."','') LIMIT 1";
		}
		// ตรวจสอบข้อมูลจาก cache
		$index = $cache->get($sql);
		if (!$index) {
			$index = $db->customQuery($sql);
			$index = sizeof($index) == 0 ? false : $index[0];
		}
		if (!$index) {
			$title = $lng['PAGE_NOT_FOUND'];
			$content = '<div class=error>'.$title.'</div>';
		} else {
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
			// อัปเดทการเปิดดู
			if (!isset($_REQUEST['visited'])) {
				$index['visited']++;
				$db->edit(DB_INDEX, $index['id'], array('visited' => $index['visited']));
			}
			$cache->save($sql, $index);
			// แทนที่ลงใน template ของโมดูล
			$patt = array('/{BREADCRUMS}/', '/{TOPIC}/', '/{DETAIL}/', '/{MODULE}/');
			$replace = array();
			$replace[] = implode("\n", $breadcrumbs);
			$replace[] = $index['topic'];
			$replace[] = gcms::showDetail($index['detail'], true, false);
			$replace[] = $module;
			$content = preg_replace($patt, $replace, gcms::loadtemplate($index['module'], '', 'main'));
			// title,keywords,description
			$title = $index['topic'];
			$keywords = $index['keywords'];
			$description = $index['description'];
			// เลือกเมนู
			$menu = empty($install_modules[$index['module']]['alias']) ? $index['module'] : $install_modules[$index['module']]['alias'];
		}
	}
