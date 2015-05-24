<?php
	// modules/document/view.php
	if (defined('MAIN_INIT')) {
		// ค่า ที่ส่งมา
		$id = gcms::getVars($_REQUEST, 'id', 0);
		$page = gcms::getVars($_REQUEST, 'page', 0);
		$cat = gcms::getVars($_REQUEST, 'cat', 0);
		$search = preg_replace('/[+\s]+/u', ' ', gcms::getVars($_REQUEST, 'q', ''));
		// query ข้อมูล
		$sql = "SELECT I.`id`,I.`module_id`,I.`category_id`,D.`topic`,I.`picture`,D.`description`,D.`detail`,I.`create_date`,I.`last_update`,I.`visited`,I.`visited_today`";
		$sql .= ",I.`comments`,I.`alias`,D.`keywords`,D.`relate`,I.`can_reply`,I.`published`,M.`module`,M.`config`,0 AS `vote`,0 AS `vote_count`";
		$sql .= ",C.`topic` AS `category`,C.`detail` AS `cat_tooltip`,U.`status`,U.`id` AS `member_id`,U.`displayname`,U.`email`";
		$sql .= " FROM `".DB_INDEX."` AS I";
		$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=I.`module_id` AND M.`owner`='document'";
		$sql .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`id`=I.`id` AND D.`module_id`=I.`module_id` AND D.`language` IN ('".LANGUAGE."','')";
		$sql .= " LEFT JOIN `".DB_USER."` AS U ON U.`id`=I.`member_id`";
		$sql .= " LEFT JOIN `".DB_CATEGORY."` AS C ON C.`category_id`=I.`category_id` AND C.`module_id`=I.`module_id`";
		$sql .= !empty($modules[4]) ? " WHERE I.`alias`='".addslashes($modules[4])."'" : " WHERE I.`id`='$id'";
		$sql .= " AND I.`index`='0' LIMIT 1";
		if (isset($_REQUEST['visited'])) {
			// มาจากการ post ไม่ต้องโหลดจากแคช
			$index = $db->customQuery($sql);
			$index = sizeof($index) == 0 ? false : $index[0];
		} else {
			$index = $cache->get($sql);
			if (!$index) {
				$index = $db->customQuery($sql);
				$index = sizeof($index) == 0 ? false : $index[0];
			}
		}
		if (!$index) {
			$title = $lng['PAGE_NOT_FOUND'];
			$content = '<div class=error>'.$title.'</div>';
		} else {
			// login
			$login = gcms::getVars($_SESSION, 'login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''));
			// config
			gcms::r2config($index['config'], $index, false);
			// แสดงความคิดเห็นได้
			$canReply = $index['can_reply'] == 1;
			// ผู้ดูแล,เจ้าของเรื่อง (ลบ-แก้ไข บทความ,ความคิดเห็นได้)
			$moderator = gcms::canConfig($index, 'moderator');
			$moderator = $isMember && ($moderator || $index['member_id'] == $login['id']);
			// guest มีสถานะเป็น -1
			$status = $isMember ? $login['status'] : -1;
			// สถานะสมาชิกที่สามารถเปิดดูกระทู้ได้
			$canview = in_array($status, explode(',', $index['can_view']));
			if ($canview || $index['viewing'] == 1) {
				// สามารถลบได้ (mod และ เจ้าของ=ลบ,สมาชิกทั่วไป=แจ้งลบ)
				$canDelete = $moderator || ($isMember && defined('DB_PM'));
				// อัปเดทการเปิดดู
				if (!isset($_REQUEST['visited'])) {
					$index['visited']++;
					$index['visited_today']++;
					$db->edit(DB_INDEX, $index['id'], array('visited' => $index['visited'], 'visited_today' => $index['visited_today']));
				}
				// บันทึก cache หลังจากอัปเดทการเปิดดูแล้ว
				$cache->save($sql, $index);
				// relate
				$relate = array();
				$patt = array('/{URL}/', '/{TOPIC}/');
				$skin = gcms::loadtemplate($index['module'], 'document', 'relate');
				foreach (explode(',', $index['relate']) AS $tag) {
					$relate[] = preg_replace($patt, array(gcms::getURL('tag', $tag), $tag), $skin);
				}
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
				if ($index['category_id'] > 0 && $index['category'] != '') {
					$breadcrumbs['CATEGORY'] = gcms::breadcrumb('', gcms::getURL($index['module'], '', $index['category_id']), gcms::ser2Str($index, 'cat_tooltip'), gcms::ser2Str($index, 'category'), $breadcrumb);
				}
				// ความคิดเห็น
				$comments = array();
				if ($canReply) {
					$patt = array('/(edit-{QID}-{RID}-{NO}-{MODULE})/', '/(delete-{QID}-{RID}-{NO}-{MODULE})/',
						'/{DETAIL}/', '/{UID}/', '/{DISPLAYNAME}/', '/{STATUS}/', '/{DATE}/', '/{DATEISO}/', '/{IP}/', '/{NO}/', '/{RID}/');
					$skin = gcms::loadtemplate($index['module'], 'document', 'commentitem');
					// query
					$sql = "SELECT C.*,U.`status`";
					$sql .= ",(CASE WHEN ISNULL(U.`id`) THEN C.`email` WHEN U.`displayname`='' THEN U.`email` ELSE U.`displayname` END) AS `displayname`";
					$sql .= " FROM `".DB_COMMENT."` AS C";
					$sql .= " LEFT JOIN `".DB_USER."` AS U ON U.`id`=C.`member_id`";
					$sql .= " WHERE C.`index_id`='$index[id]' AND C.`module_id`='$index[module_id]'";
					$sql .= " ORDER BY C.`id` ASC";
					if (isset($_REQUEST['visited'])) {
						$datas = $db->customQuery($sql);
						$cache->save($sql, $datas);
					} else {
						$datas = $cache->get($sql);
						if (!$datas) {
							$datas = $db->customQuery($sql);
							$cache->save($sql, $datas);
						}
					}
					foreach ($datas AS $i => $item) {
						// moderator และ เจ้าของ สามารถแก้ไขความคิดเห็นได้
						$canEdit = $moderator || ($isMember && $login['id'] == $item['member_id']);
						$replace = array();
						$replace[] = $canEdit ? '\\1' : 'hidden';
						$replace[] = $canDelete ? '\\1' : 'hidden';
						$replace[] = gcms::HighlightSearch(gcms::showDetail($item['detail'], $canview), $search);
						$replace[] = (int)$item['member_id'];
						$replace[] = $item['displayname'];
						$replace[] = $item['status'];
						$replace[] = gcms::mktime2date($item['last_update']);
						$replace[] = date(DATE_ISO8601, $item['last_update']);
						$replace[] = gcms::showip($item['ip']);
						$replace[] = $i + 1;
						$replace[] = $item['id'];
						$comments[] = preg_replace($patt, $replace, $skin);
					}
				}
				if ($canReply) {
					// antispam
					$register_antispamchar = gcms::rndname(32);
					$_SESSION[$register_antispamchar] = gcms::rndname(4);
				}
				// url ของหน้านี้
				if ($config['module_url'] == '1') {
					$canonical = gcms::getURL($index['module'], $index['alias']);
				} else {
					$canonical = gcms::getURL($index['module'], '', 0, $index['id']);
				}
				// แก้ไขบทความ เจ้าของหรือ mod
				$canEdit = is_file(ROOT_PATH.'modules/document/write.php') && ($moderator || ($isMember && $login['id'] == $index['member_id']));
				// แทนที่ลงใน template ของโมดูล
				$patt = array('/{BREADCRUMS}/', '/{COMMENTLIST}/', '/{REPLYFORM}/', '/{TOPIC}/', '/<MEMBER>(.*)<\/MEMBER>/s',
					'/(edit-{QID}-0-0-{MODULE})/', '/(delete-{QID}-0-0-{MODULE})/', '/(quote-{QID}-0-0-{MODULE})/', '/{DETAIL}/',
					'/{LANGUAGE}/', '/{UID}/', '/{DISPLAYNAME}/', '/{IMG}/', '/{STATUS}/', '/{DATE}/', '/{DATEISO}/',
					'/{VISITED}/', '/{TAGS}/', '/{COMMENTS}/', '/{QID}/', '/{LOGIN_PASSWORD}/', '/{LOGIN_EMAIL}/', '/{ANTISPAM}/',
					'/{ANTISPAMVAL}/', '/{DELETE}/', '/{MODULE}/', '/{MODULEID}/', '/{VOTE}/', '/{VOTE_COUNT}/', '/{CATID}/');
				$replace = array();
				$replace[] = implode("\n", $breadcrumbs);
				$replace[] = sizeof($comments) == 0 ? '' : implode("\n", $comments);
				$replace[] = $canReply ? gcms::loadtemplate($index['module'], 'document', 'reply') : '';
				$replace[] = $index['topic'];
				$replace[] = $isMember ? '' : '$1';
				$replace[] = $canEdit ? '\\1' : 'hidden';
				$replace[] = $canDelete ? '\\1' : 'hidden';
				$replace[] = $canReply ? '\\1' : 'hidden';
				$replace[] = gcms::HighlightSearch(gcms::showDetail($index['detail'], $canview, false), $search);
				$replace[] = LANGUAGE;
				$replace[] = (int)$index['member_id'];
				$replace[] = empty($index['displayname']) ? $index['email'] : $index['displayname'];
				if (is_file(DATA_PATH."document/$index[picture]")) {
					// รูปภาพของบทความ
					$image_src = DATA_URL."document/$index[picture]";
					$replace[] = '<figure><img src="'.$image_src.'" alt="'.$index['topic'].'"></figure>';
				} else {
					$image_src = WEB_URL."/$index[default_icon]";
					$replace[] = '';
				}
				$replace[] = $index['status'];
				$replace[] = gcms::mktime2date($index['create_date']);
				$replace[] = date(DATE_ISO8601, $index['create_date']);
				$replace[] = number_format($index['visited']);
				$replace[] = sizeof($relate) == 0 ? '' : implode('', $relate);
				$replace[] = number_format($index['comments']);
				$replace[] = $index['id'];
				$replace[] = $login['password'];
				$replace[] = $login['email'];
				if (isset($register_antispamchar)) {
					$replace[] = $register_antispamchar;
					$replace[] = $isAdmin ? $_SESSION[$register_antispamchar] : '';
				} else {
					$replace[] = '';
					$replace[] = '';
				}
				$replace[] = $moderator ? '{LNG_DELETE}' : '{LNG_SEND_DELETE}';
				$replace[] = $index['module'];
				$replace[] = $index['module_id'];
				$replace[] = (int)$index['vote'];
				$replace[] = (int)$index['vote_count'];
				$replace[] = $index['category_id'];
				$content = preg_replace($patt, $replace, gcms::loadtemplate($index['module'], 'document', 'view'));
				// ตัวแปรหลังจากแสดงผลแล้ว
				$custom_patt['/{TYPE}/'] = $index['img_typies'];
				// title,keywords,description
				$title = $index['topic'];
				$keywords = $index['keywords'];
				$description = $index['description'];
			} else {
				$title = $lng['LNG_NOT_LOGIN'];
				$content = '<div class=error>'.$title.'</div>';
			}
			// เลือกเมนู
			$menu = empty($install_modules[$index['module']]['alias']) ? $index['module'] : $install_modules[$index['module']]['alias'];
		}
	}
