<?php
	// modules/video/list.php
	if (defined('MAIN_INIT')) {
		// ตรวจสอบโมดูล
		$sql = "SELECT I.`module_id`,M.`module`,D.`detail`,D.`topic`,D.`description`,D.`keywords`";
		$sql .= " FROM `".DB_INDEX_DETAIL."` AS D";
		$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`=D.`module_id` AND I.`index`='1' AND I.`language`=D.`language`";
		$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`owner`='video'";
		$sql .= " WHERE D.`language` IN ('".LANGUAGE."','') LIMIT 1";
		$index = $cache->get($sql);
		if (!$index) {
			$index = $db->customQuery($sql);
			$cache->save($sql, $index);
		}
		if (sizeof($index) == 1) {
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
			// ทั้งหมด
			$sql = "SELECT COUNT(*) AS `count` FROM `".DB_VIDEO."`";
			$sql .= " WHERE `module_id`='$index[module_id]'";
			$count = $cache->get($sql);
			if (!$count) {
				$count = $db->customQuery($sql);
				$cache->save($sql, $count);
			}
			if ($count[0]['count'] == 0) {
				$content = '<div class=error>'.$lng['LNG_LIST_EMPTY'].'</div>';
			} else {
				// จำนวนที่ต้องการ
				$config['video_rows'] = max(1, $config['video_rows']);
				$config['video_cols'] = max(1, $config['video_cols']);
				$list_per_page = $config['video_rows'] * $config['video_cols'];
				// หน้าที่เรียก
				$page = gcms::getVars($_REQUEST, 'page', 0);
				$totalpage = round($count[0]['count'] / $list_per_page);
				$totalpage += ($totalpage * $list_per_page < $count[0]['count']) ? 1 : 0;
				$page = $page > $totalpage ? $totalpage : $page;
				$page = $page < 1 ? 1 : $page;
				$start = $list_per_page * ($page - 1);
				// query
				$sql = "SELECT * FROM `".DB_VIDEO."`";
				$sql .= " WHERE `module_id`='$index[module_id]'";
				$sql .= " ORDER BY `id` DESC LIMIT $start,$list_per_page";
				$list = $cache->get($sql);
				if (!$list) {
					$list = $db->customQuery($sql);
					$cache->save($sql, $list);
				}
				$items = array();
				$patt = array('/{ID}/', '/{THUMB}/', '/{YOUTUBE}/', '/{TOPIC}/', '/{DESCRIPTION}/', '/{VIEWS}/');
				$skin = gcms::loadtemplate($index['module'], 'video', 'listitem');
				foreach ($list AS $i => $item) {
					$replace = array();
					$replace[] = $item['id'];
					$replace[] = is_file(DATA_PATH."video/$item[youtube].jpg") ? DATA_URL."video/$item[youtube].jpg" : WEB_URL.'/modules/video/img/nopicture.jpg';
					$replace[] = $item['youtube'];
					$replace[] = $item['topic'];
					$replace[] = $item['description'];
					$replace[] = $item['views'];
					$items[] = preg_replace($patt, $replace, $skin);
				}
				// แบ่งหน้า
				$maxlink = 9;
				$url = '<a href="'.gcms::getURL($index['module'], '', 0, 0, "page=%1").'">%1</a>';
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
				$patt = array('/{BREADCRUMS}/', '/{TOPIC}/', '/{DETAIL}/', '/{LIST}/', '/{SPLITPAGE}/', '/{COLS}/', '/{ID}/');
				$replace = array();
				$replace[] = implode("\n", $breadcrumbs);
				$replace[] = $index['topic'];
				$replace[] = nl2br($index['detail']);
				$replace[] = implode("\n", $items);
				$replace[] = $splitpage;
				$replace[] = $config['video_cols'];
				$replace[] = $index['module_id'];
				$content = preg_replace($patt, $replace, gcms::loadtemplate($index['module'], 'video', 'list'));
			}
			// title,keywords,description
			$title = $index['topic'];
			$keywords = $index['keywords'];
			$description = $index['description'];
			// เลือกเมนู
			$menu = empty($install_modules[$index['module']]['alias']) ? $index['module'] : $install_modules[$index['module']]['alias'];
		} else {
			$title = $lng['LNG_DATA_NOT_FOUND'];
			$content = '<div class=error>'.$title.'</div>';
		}
	}
