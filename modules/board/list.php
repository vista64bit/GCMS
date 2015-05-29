<?php
	// modules/board/list.php
	if (defined('MAIN_INIT')) {
		// ค่าที่ส่งมา
		$cat = gcms::getVars($_REQUEST, 'cat', 0);
		$page = gcms::getVars($_REQUEST, 'page', 0);
		$module_id = gcms::getVars($_REQUEST, 'mid', 0);
		// ตรวจสอบโมดูลที่เลือก และ จำนวนหมวดในโมดูล
		$sql = "SELECT M.`id`,M.`module`,D.`detail`,D.`keywords`";
		$sql .= ",(SELECT COUNT(*) FROM `".DB_CATEGORY."` WHERE `module_id`=M.`id`) AS `categories`";
		if ($cat == 0) {
			// ไม่ได้เลือกหมวดมา
			$sql .= ",D.`topic`,D.`description`,M.`config`";
			$sql .= " FROM `".DB_INDEX_DETAIL."` AS D";
		} else {
			// มีการเลือกหมวด
			$sql .= ",CASE WHEN ISNULL(C.`config`) THEN M.`config` ELSE CONCAT(M.`config` ,'\n' ,C.`config`) END AS `config`";
			$sql .= ",C.`category_id`,C.`topic`,C.`detail` AS `description`";
			$sql .= " FROM `".DB_INDEX_DETAIL."` AS D";
			$sql .= " INNER JOIN `".DB_CATEGORY."` AS C ON C.`category_id`='$cat' AND C.`module_id`=D.`module_id`";
		}
		if ($module_id > 0) {
			$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`='$module_id' AND I.`index`='1' AND I.`language` IN('".LANGUAGE."', '')";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`='$module_id' AND M.`owner`='board'";
		} else {
			$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`=D.`module_id` AND I.`index`='1' AND I.`language` IN('".LANGUAGE."', '')";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`owner`='board' AND M.`module`='$module'";
		}
		$sql .= " LIMIT 1";
		$index = $cache->get($sql);
		if (!$index) {
			$index = $db->customQuery($sql);
			if (sizeof($index) == 1) {
				$index = $index[0];
				$cache->save($sql, $index);
			} else {
				$index = false;
			}
		}
		if (!$index) {
			$title = $lng['LNG_DOCUMENT_NOT_FOUND'];
			$content = '<div class=error>'.$title.'</div>';
		} else {
			if ($cat > 0) {
				$index['topic'] = gcms::ser2Str($index, 'topic');
				$index['description'] = gcms::ser2Str($index, 'description');
				$index['icon'] = gcms::ser2Str($index, 'icon');
			}
			// อ่าน config
			gcms::r2config($index['config'], $index);
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
			// category
			if ($cat > 0 && $index['topic'] != '') {
				$breadcrumbs['CATEGORY'] = gcms::breadcrumb('', gcms::getURL($index['module'], '', (int)$index['category_id']), $index['description'], $index['topic'], $breadcrumb);
			}
			// ข้อมูลการ login
			$login = gcms::getVars($_SESSION, 'login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''));
			// guest มีสถานะเป็น -1
			$status = $isMember ? $login['status'] : -1;
			$list = array();
			$splitpage = '';
			if ($cat > 0 || $index['categories'] == 0 || $index['category_display'] == 0) {
				// เลือกหมวดมา หรือไม่มีหมวด หรือปิดการแสดงผลหมวดหมู่ แสดงรายการเรื่อง
				include (ROOT_PATH.'modules/board/stories.php');
				$template = 'list';
			} else {
				// ลิสต์รายชื่อหมวด
				include (ROOT_PATH.'modules/board/categories.php');
				$template = 'category';
			}
			if (sizeof($list) == 0) {
				$template = 'empty';
				$list = '';
			} elseif ($template == 'category') {
				$list = '<div class="row iconview">'.implode("\n", $list).'</div>';
			} else {
				$list = implode("\n", $list);
			}
			// แสดงผลหน้าเว็บ
			$patt = array('/{BREADCRUMS}/', '/{LIST}/', '/{NEWTOPIC}/', '/{CATEGORY}/', '/{TOPIC}/',
				'/{DETAIL}/', '/{SPLITPAGE}/', '/{MODULE}/');
			$replace = array();
			$replace[] = implode("\n", $breadcrumbs);
			$replace[] = $list;
			$replace[] = $isAdmin || in_array($status, explode(',', $index['can_post'])) ? '' : 'hidden';
			$replace[] = (int)$cat;
			$replace[] = $index['topic'];
			$replace[] = $index['detail'];
			$replace[] = $splitpage;
			$replace[] = $index['module'];
			$content = preg_replace($patt, $replace, gcms::loadtemplate($index['module'], 'board', $template));
			// title,keywords,description
			$title = $index['topic'];
			$keywords = $index['keywords'];
			$description = $index['description'];
			// เลือกเมนู
			$menu = empty($install_modules[$index['module']]['alias']) ? $index['module'] : $install_modules[$index['module']]['alias'];
		}
	}
