<?php
	// modules/edocument/report.php
	if (defined('MAIN_INIT') && $isMember) {
		// id ของรายการที่เลือก
		$id = gcms::getVars($_REQUEST, 'id', 0);
		// ตรวจสอบโมดูล
		$sql = "SELECT E.*,M.`module`,D.`topic` AS `title`,D.`description`,D.`keywords`";
		$sql .= " FROM `".DB_INDEX_DETAIL."` AS D";
		$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`=D.`module_id` AND I.`index`='1' AND I.`language`=D.`language`";
		$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`owner`='edocument'";
		$sql .= " INNER JOIN `".DB_EDOCUMENT."` AS E ON E.`id`='$id' AND E.`module_id`=M.`id`";
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
			// url ของหน้านี้
			$canonical = gcms::getURL($index['module']);
			// โมดูล
			if ($index['module'] != $module_list[0]) {
				if (isset($install_modules[$index['module']]['menu_text'])) {
					$m = $install_modules[$index['module']]['menu_text'];
					$t = $install_modules[$index['module']]['menu_tooltip'];
				} else {
					$m = ucwords($index['module']);
					$t = $m;
				}
				$breadcrumbs['MODULE'] = gcms::breadcrumb('', $canonical, $t, $m, $breadcrumb);
			}
			// แก้ไข
			$breadcrumbs['EDIT'] = gcms::breadcrumb('', WEB_URL."/index.php?module=$index[module]-write&amp;id=$index[id]", "$index[topic].$index[ext]", "$index[topic].$index[ext]", $breadcrumb);
			// default query
			$where = " WHERE D.`module_id`='$index[module_id]' AND D.`document_id`='$index[id]'";
			// จำนวนทั้งหมด
			$sql = "SELECT COUNT(*) AS `count` FROM `".DB_EDOCUMENT_DOWNLOAD."` AS D $where";
			$count = $cache->get($sql);
			if (!$count) {
				$count = $db->customQuery($sql);
				$count = $count[0];
				$cache->save($sql, $count);
			}
			// หน้าที่เรียก
			$page = gcms::getVars($_REQUEST, 'page', 0);
			$totalpage = round($count['count'] / $config['edocument_listperpage']);
			$totalpage += ($totalpage * $config['edocument_listperpage'] < $count['count']) ? 1 : 0;
			$page = $page > $totalpage ? $totalpage : $page;
			$page = $page < 1 ? 1 : $page;
			$start = $config['edocument_listperpage'] * ($page - 1);
			// list รายการ
			$sql = "SELECT D.*,U.`fname`,U.`lname`,U.`email`,U.`status` FROM `".DB_EDOCUMENT_DOWNLOAD."` AS D";
			$sql .= " LEFT JOIN `".DB_USER."` AS U ON U.`id`=D.`member_id`";
			$sql .= " $where ORDER BY D.`last_update` DESC LIMIT $start,$config[edocument_listperpage]";
			$datas = $cache->get($sql);
			if (!$datas) {
				$datas = $db->customQuery($sql);
				$cache->save($sql, $datas);
			}
			// อ่านรายการลงใน $list
			$list = array();
			$patt = array('/{DATE}/', '/{DOWNLOADS}/', '/{NAME}/', '/{GROUP}/', '/{STATUS}/', '/{UID}/');
			$listitem = gcms::loadtemplate($index['module'], 'edocument', 'reportitem');
			foreach ($datas AS $item) {
				$replace = array();
				$replace[] = gcms::mktime2date($item['last_update']);
				$replace[] = $item['downloads'];
				if ($item['member_id'] == 0) {
					$replace[] = '&nbsp;';
					$replace[] = '{LNG_GUEST}';
				} else {
					$sender = trim("$item[fname] $item[lname]");
					$replace[] = $sender == '' ? $item['email'] : $sender;
					$replace[] = $config['member_status'][$item['status']];
				}
				$replace[] = $item['status'];
				$replace[] = $item['member_id'];
				$list[] = preg_replace($patt, $replace, $listitem);
			}
			// แบ่งหน้า
			$maxlink = 9;
			// query สำหรับ URL
			$url = '<a href="'.gcms::getURL($index['module'], '', 0, 0, 'page=%1').'">%1</a>';
			if ($totalpage > $maxlink) {
				$start = $page - floor($maxlink / 2);
				if ($start < 1) {
					$start = 1;
				} elseif ($start + $maxlink > $totalpage) {
					$start = $totalpage - $maxlink + 1;
				}
			} else {
				$start = 1;
			}
			$splitpage = ($start > 2) ? str_replace('%1', 1, $url) : '';
			for ($i = $start; $i <= $totalpage && $maxlink > 0; $i++) {
				$splitpage .= ($i == $page) ? '<strong>'.$i.'</strong>' : str_replace('%1', $i, $url);
				$maxlink--;
			}
			$splitpage .= ($i < $totalpage) ? str_replace('%1', $totalpage, $url) : '';
			$splitpage = $splitpage == '' ? '<strong>1</strong>' : $splitpage;
			// แสดงผล list รายการ
			$patt = array('/{BREADCRUMS}/', '/{LIST}/', '/{TOPIC}/', '/{SPLITPAGE}/', '/{(LNG_[A-Z0-9_]+)}/e');
			$replace = array();
			$replace[] = implode("\n", $breadcrumbs);
			$replace[] = implode("\n", $list);
			$replace[] = "$index[topic].$index[ext]";
			$replace[] = $splitpage;
			$replace[] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
			$content = gcms::pregReplace($patt, $replace, gcms::loadtemplate($index['module'], 'edocument', 'report'));
			// title,keywords,description
			$title = $index['title'];
			$keywords = $index['keywords'];
			$description = $index['description'];
		}
	} else {
		$title = $lng['LNG_NOT_LOGIN'];
		$content = '<div class=error>'.$title.'</div>';
	}
