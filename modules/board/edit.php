<?php
	// modules/board/edit.php
	if (defined('MAIN_INIT')) {
		// ค่า ที่ส่งมา
		$rid = gcms::getVars($_REQUEST, 'rid', 0);
		$qid = gcms::getVars($_REQUEST, 'qid', 0);
		if ($rid > 0) {
			// คำตอบ
			$sql = "SELECT R.`id` AS `comment_id`,R.`index_id`,R.`detail`,M.`config`";
			$sql .= ",R.`module_id`,M.`module`,Q.`topic`,U.`id` AS `member_id`,U.`status`";
			$sql .= " FROM `".DB_BOARD_R."` AS R";
			$sql .= " INNER JOIN `".DB_BOARD_Q."` AS Q ON Q.`id`=R.`index_id`";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=Q.`module_id`";
			$sql .= " LEFT JOIN `".DB_USER."` AS U ON U.`id`=R.`member_id`";
			$sql .= " WHERE R.`id`='$rid' LIMIT 1";
			$form = 'reply';
		} else {
			// คำถาม
			$sql = "SELECT I.`id` AS `index_id`,I.`topic`,I.`detail`,I.`module_id`,I.`category_id`,I.`comments`,0 AS `comment_id`";
			$sql .= ",I.`create_date`,M.`module`,U.`id` AS `member_id`,U.`status`,M.`config`";
			$sql .= " FROM `".DB_BOARD_Q."` AS I";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=I.`module_id`";
			$sql .= " LEFT JOIN `".DB_USER."` AS U ON U.`id`=I.`member_id`";
			$sql .= " WHERE I.`id`='$qid' LIMIT 1";
			$form = 'post';
		}
		$index = $db->customQuery($sql);
		if (sizeof($index) == 1) {
			$index = $index[0];
			// config
			gcms::r2config($index['config'], $index);
			// ข้อมูลการ login
			$login = gcms::getVars($_SESSION, 'login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''));
			// เจ้าของ
			$canEdit = $isMember && $index['member_id'] == $login['id'];
			// ผู้ดูแล
			$moderator = gcms::canConfig($index, 'moderator');
			// เจ้าของหรือผู้ดูแล แก้ไขได้
			$canEdit = $canEdit || $moderator;
			// เลือกเมนู
			$menu = empty($install_modules[$index['module']]['alias']) ? $index['module'] : $install_modules[$index['module']]['alias'];
		} else {
			$index = false;
		}
		// แก้ไขคำถาม อ่านหมวด
		$categories = array();
		if ($index && $canEdit) {
			// breadcrumbs
			$breadcrumb = gcms::loadtemplate($index['module'], '', 'breadcrumb');
			$breadcrumbs = array();
			// หน้าหลัก
			$breadcrumbs['HOME'] = gcms::breadcrumb('icon-home', $canonical, $install_modules[$module_list[0]]['menu_tooltip'], $install_modules[$module_list[0]]['menu_text'], $breadcrumb);
			// โมดูล
			if (isset($install_modules[$index['module']]['menu_text'])) {
				$m = $install_modules[$index['module']]['menu_text'];
				$t = $install_modules[$index['module']]['menu_tooltip'];
			} else {
				$m = ucwords($index['module']);
				$t = $m;
			}
			$breadcrumbs['MODULE'] = gcms::breadcrumb('', gcms::getURL($index['module']), $t, $m, $breadcrumb);
			if ($rid == 0) {
				$categories[0] = '<option value=0>{LNG_NO_CATEGORY}</option>';
				$sql = "SELECT `category_id`,`topic` FROM `".DB_CATEGORY."` WHERE `module_id`='$index[module_id]' ORDER BY `category_id`";
				foreach ($db->customQuery($sql) AS $item) {
					if ($moderator || $index['category_id'] == $item['category_id']) {
						$sel = $index['category_id'] == $item['category_id'] ? ' selected' : '';
						$categories[$item['category_id']] = "<option value=$item[category_id]$sel>".gcms::ser2Str($item, 'topic')."</option>";
					}
				}
				if (sizeof($categories) > 1) {
					unset($categories[0]);
				}
			}
			// antispam
			$register_antispamchar = gcms::rndname(32);
			$_SESSION[$register_antispamchar] = gcms::rndname(4);
			$patt = array('/{BREADCRUMS}/', '/<UPLOAD>(.*)<\/UPLOAD>/s', '/<ADMIN>(.*)<\/ADMIN>/s', '/{CATEGORIES}/', '/{ANTISPAM}/',
				'/{ANTISPAMVAL}/', '/{QID}/', '/{RID}/', '/{TOPIC}/', '/{DETAIL}/', '/{DATE}/', '/{HOUR}/', '/{MINUTE}/');
			$replace = array();
			$replace[] = implode("\n", $breadcrumbs);
			$replace[] = $index['img_upload_type'] == '' ? '' : '$1';
			$replace[] = $moderator ? '$1' : '';
			$replace[] = implode("\n", $categories);
			$replace[] = $register_antispamchar;
			$replace[] = $isAdmin ? $_SESSION[$register_antispamchar] : '';
			$replace[] = (int)$index['index_id'];
			$replace[] = (int)$index['comment_id'];
			$replace[] = $index['topic'];
			$replace[] = gcms::txtQuote($index['detail']);
			if ($rid == 0) {
				preg_match('/([0-9]{4,4}\-[0-9]{2,2}\-[0-9]{2,2})\s([0-9]+):([0-9]+)/', date('Y-m-d H:i', $index['create_date']), $match);
				// วันที่ของบอร์ด
				$replace[] = $match[1];
				// hour
				$datas = array();
				for ($i = 0; $i < 24; $i++) {
					$d = sprintf('%02d', $i);
					$sel = $d == $match[2] ? ' selected' : '';
					$datas[] = '<option value='.$d.$sel.'>'.$d.'</option>';
				}
				$replace[] = implode('', $datas);
				// minute
				$datas = array();
				for ($i = 0; $i < 60; $i++) {
					$d = sprintf('%02d', $i);
					$sel = $d == $match[3] ? ' selected' : '';
					$datas[] = '<option value='.$d.$sel.'>'.$d.'</option>';
				}
				$replace[] = implode('', $datas);
			} else {
				$replace[] = '';
				$replace[] = '';
				$replace[] = '';
			}
			$content = preg_replace($patt, $replace, gcms::loadtemplate($index['module'], 'board', "edit$form"));
			// ตัวแปรหลังจากแสดงผลแล้ว
			$custom_patt['/{MODULE}/'] = $index['module'];
			$custom_patt['/{MODULEID}/'] = $index['module_id'];
			$custom_patt['/{SIZE}/'] = $index['img_upload_size'];
			$custom_patt['/{TYPE}/'] = $index['img_upload_type'];
			// title,keywords,description
			$title = "$lng[LNG_EDIT] $index[topic]";
			$keywords = $title;
			$description = $title;
		} else {
			$title = $lng['LNG_DATA_NOT_FOUND'];
			$content = '<div class=error>'.$title.'</div>';
		}
	}
