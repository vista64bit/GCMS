<?php
	// modules/edocument/wirte.php
	if (defined('MAIN_INIT') && $isMember) {
		// id ของรายการที่เลือก
		$id = gcms::getVars($_REQUEST, 'id', 0);
		if ($id > 0) {
			// แก้ไข
			$sql = "SELECT C.*,D.`topic` AS `title`,D.`description`,D.`keywords`,M.`module`";
			$sql .= " FROM `".DB_EDOCUMENT."` AS C";
			$sql .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`module_id`=C.`module_id` AND D.`language` IN ('".LANGUAGE."','')";
			$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`=D.`module_id` AND I.`index`='1' AND I.`language`=D.`language`";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`owner`='edocument'";
			$sql .= " WHERE C.`id`=$id LIMIT 1";
		} else {
			// ใหม่
			$sql = "SELECT D.`topic` AS `title`,D.`description`,D.`keywords`,M.`module`";
			$sql .= ",(SELECT MAX(`id`) FROM `".DB_EDOCUMENT."` WHERE `module_id`=M.`id`) AS `document_no`";
			$sql .= " FROM `".DB_INDEX_DETAIL."` AS D";
			$sql .= " INNER JOIN `".DB_INDEX."` AS I ON I.`id`=D.`id` AND I.`module_id`=D.`module_id` AND I.`index`='1' AND I.`language`=D.`language`";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`id`=D.`module_id` AND M.`owner`='edocument'";
			$sql .= " WHERE D.`language` IN ('".LANGUAGE."','') LIMIT 1";
		}
		$index = $db->customQuery($sql);
		if (sizeof($index) == 0) {
			// ไม่พบรายการหรือยังไม่ได้ติดตั้ง
			$title = $lng['LNG_DATA_NOT_FOUND'];
			$content = '<div class=error>'.$title.'</div>';
		} elseif (empty($config['edocument_can_upload']) || !gcms::canConfig($config, 'edocument_can_upload')) {
			// ไม่สามารถอัปโหลดได้
			$title = $lng['ACTION_FORBIDDEN'];
			$content = '<div class=error>'.$title.'</div>';
		} else {
			$index = $index[0];
			// login
			$login = gcms::getVars($_SESSION, 'login', array('id' => 0, 'status' => -1, 'email' => '', 'password' => ''));
			if ($id > 0) {
				// เจ้าของ
				$canEdit = $index['sender_id'] == $login['id'];
				// ผู้ดูแล
				$moderator = gcms::canConfig($config, 'edocument_moderator');
				// เจ้าของหรือผู้ดูแล แก้ไขได้
				$canEdit = $canEdit || $moderator;
				// ผู้รับเอกสาร
				$reciever = explode(',', $index['reciever']);
			} else {
				$index['document_no'] = sprintf($config['edocument_format_no'], (int)$index['document_no'] + 1);
				$index['topic'] = '';
				$index['detail'] = '';
				$index['ext'] = '';
				$index['id'] = 0;
				$reciever = array();
			}
			if ($id == 0 || $canEdit) {
				// antispam
				$antispam = gcms::rndname(32);
				$_SESSION[$antispam] = gcms::rndname(4);
				// breadcrumbs
				$breadcrumb = gcms::loadtemplate($index['module'], '', 'breadcrumb');
				$breadcrumbs = array();
				// หน้าหลัก
				$breadcrumbs['HOME'] = gcms::breadcrumb('icon-home', $canonical, $install_modules[$module_list[0]]['menu_tooltip'], $install_modules[$module_list[0]]['menu_text'], $breadcrumb);
				// url ของหน้านี้
				$canonical = gcms::getURL($index['module']);
				// module
				$breadcrumbs['MODULE'] = gcms::breadcrumb('', $canonical, $index['title'], $index['title'], $breadcrumb);
				// สถานะ (กลุ่ม) ของสมาชิกทั้งหมด
				$status = array();
				$sel = in_array(-1, $reciever) ? ' selected' : '';
				$status[] = '<option value=-1'.$sel.'>{LNG_GUEST}</option>';
				foreach ($config['member_status'] AS $i => $item) {
					$sel = in_array($i, $reciever) ? ' selected' : '';
					$status[] = '<option value='.$i.$sel.'>'.$item.'</option>';
				}
				// form
				$patt = array('/{BREADCRUMS}/', '/{GROUPS}/',
					'/{(LNG_[A-Z0-9_]+)}/e', '/{TYPE}/', '/{SIZE}/', '/{ANTISPAM}/', '/{ANTISPAMVAL}/',
					'/{ID}/', '/{NO}/', '/{TOPIC}/', '/{DETAIL}/', '/{ICON}/', '/{ACTION}/');
				$replace = array();
				$replace[] = implode("\n", $breadcrumbs);
				$replace[] = implode('', $status);
				$replace[] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
				$replace[] = implode(', ', $config['edocument_file_typies']);
				$replace[] = gcms::formatFileSize($config['edocument_upload_size']);
				$replace[] = $antispam;
				$replace[] = $isAdmin ? $_SESSION[$antispam] : '';
				$replace[] = $index['id'];
				$replace[] = $index['document_no'];
				$replace[] = $index['topic'];
				$replace[] = $index['detail'];
				$replace[] = is_file(ROOT_PATH."skin/ext/$index[ext].png") ? $index['ext'] : 'file';
				$replace[] = $id > 0 ? $lng['LNG_EDIT'] : $lng['LNG_ADD'];
				$content = gcms::pregReplace($patt, $replace, gcms::loadtemplate($index['module'], 'edocument', 'write'));
				// title,description, keywords
				$title = $index['title'];
				$keywords = $index['keywords'];
				$description = $index['description'];
			} else {
				// ไม่พบหรือไม่มีสิทธิ์
				$title = $lng['LNG_DATA_NOT_FOUND'];
				$content = '<div class=error>'.$title.'</div>';
			}
			// เลือกเมนู
			$menu = empty($install_modules[$index['module']]['alias']) ? $index['module'] : $install_modules[$index['module']]['alias'];
		}
	} else {
		// ไม่ได้ login
		$title = $lng['LNG_NOT_LOGIN'];
		$content = '<div class=error>'.$title.'</div>';
	}
