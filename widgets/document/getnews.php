<?php
	// widgets/document/getnews.php
	header("content-type: text/html; charset=UTF-8");
	// inint
	include ('../../bin/inint.php');
	// ตรวจสอบ referer
	if (gcms::isReferer() && preg_match('/^widget_([a-z0-9]+)_([0-9]+)_([0-9,]+)_([0-9]+)_([0-9]+)_([0-9]+)_([0-9]+)_(list|icon|thumb)_([a-z0-9]+)?$/', $_POST['id'], $match)) {
		// อ่านโมดูล
		$sql = "SELECT `id`,`config`,`module` FROM `".DB_MODULES."` WHERE `id`=".(int)$match[2]." AND `owner`='document' LIMIT 1";
		$index = $cache->get($sql);
		if (!$index) {
			$index = $db->customQuery($sql);
			if (sizeof($index) == 1) {
				$index = $index[0];
				// อ่าน config
				gcms::r2config($index['config'], $index);
				unset($index['config']);
				// save cached
				$cache->save($sql, $index);
			} else {
				$index = false;
			}
		}
		if ($index && $match[4] > 0) {
			// เรียงลำดับ
			$sorts = array('Q.`last_update` DESC,Q.`id` DESC', 'Q.`create_date` DESC,Q.`id` DESC', 'Q.`published_date` DESC,Q.`last_update` DESC', 'Q.`id` DESC');
			// query
			$sql = "SELECT Q.`id`,D.`topic`,Q.`alias`,Q.`picture`,Q.`comment_date`,Q.`last_update`,Q.`create_date`";
			$sql .= ",D.`description`,Q.`comments`,Q.`visited`,U.`status`,U.`id` AS `member_id`,U.`displayname`,U.`email`,C.`topic` AS `category`";
			$sql .= " FROM `".DB_INDEX."` AS Q";
			$sql .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`id`=Q.`id` AND D.`module_id`=Q.`module_id` AND D.`language` IN ('".LANGUAGE."','')";
			$sql .= " LEFT JOIN `".DB_USER."` AS U ON U.`id`=Q.`member_id`";
			$sql .= " LEFT JOIN `".DB_CATEGORY."` AS C ON C.`category_id`=Q.`category_id` AND C.`module_id`=Q.`module_id`";
			$sql .= " WHERE Q.`module_id`=$index[id]";
			if ($match[3] != '0') {
				$sql .= " AND Q.`category_id` IN ($match[3])";
			}
			if (!empty($match[9]) && preg_match('/^[a-z0-9]+$/', $match[9])) {
				$sql .= " AND Q.`show_news` LIKE '%".$match[9]."=1%'";
			}
			$sql .= " AND Q.`published`='1' AND Q.`published_date`<='".date('Y-m-d', $mmktime)."' AND Q.`index`='0'";
			$sql .= " ORDER BY ".$sorts[$match[6]]." LIMIT $match[4]";
			$datas = $cache->get($sql);
			if (!$datas) {
				$datas = $db->customQuery($sql);
				$cache->save($sql, $datas);
			}
			// styles
			$styles = in_array($match[8], array('list', 'icon', 'thumb')) ? $match[8] : 'list';
			// เครื่องหมาย new
			$valid_date = $mmktime - $match[5];
			// template
			$skin = gcms::loadtemplate($index['module'], 'document', 'widgetitem');
			$patt = array('/{BG}/', '/{URL}/', '/{TOPIC}/', '/{DETAIL}/', '/{CATEGORY}/', '/{DATE}/', '/{UID}/',
				'/{SENDER}/', '/{STATUS}/', '/{COMMENTS}/', '/{VISITED}/', '/{THUMB}/', '/{ICON}/');
			$widget = array();
			$bg = 'bg2';
			foreach ($datas AS $i => $item) {
				if ($i > 0 && $i % $match[7] == 0) {
					$widget[] = '</div><div class="row '.$styles.'view">';
				}
				$bg = $bg == 'bg1' ? 'bg2' : 'bg1';
				$replace = array();
				$replace[] = "$bg background".rand(0, 5);
				if ($config['module_url'] == '1') {
					$replace[] = gcms::getURL($index['module'], $item['alias']);
				} else {
					$replace[] = gcms::getURL($index['module'], '', 0, $item['id']);
				}
				$replace[] = $item['topic'];
				$replace[] = $item['description'];
				$replace[] = gcms::ser2Str($item, 'category');
				$replace[] = gcms::mktime2date($item['create_date'], 'd M Y');
				$replace[] = $item['member_id'];
				$replace[] = $item['displayname'] == '' ? $item['email'] : $item['displayname'];
				$replace[] = $item['status'];
				$replace[] = number_format($item['comments']);
				$replace[] = number_format($item['visited']);
				if ($item['picture'] != '' && is_file(DATA_PATH."document/$item[picture]")) {
					$replace[] = DATA_URL."document/$item[picture]";
				} else {
					$replace[] = WEB_URL."/$index[default_icon]";
				}
				if ($item['create_date'] > $valid_date && $item['comment_date'] == 0) {
					$replace[] = 'new';
				} elseif ($item['last_update'] > $valid_date || $item['comment_date'] > $valid_date) {
					$replace[] = 'update';
				} else {
					$replace[] = '';
				}
				$widget[] = preg_replace($patt, $replace, $skin);
			}
			if (sizeof($widget) > 0) {
				$patt = array('/{COLS}/', '/{(LNG_[A-Z0-9_]+)}/e');
				$replace = array();
				$replace[] = $match[7];
				$replace[] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
				echo gcms::pregReplace($patt, $replace, '<div class="row '.$styles.'view">'.implode('', $widget).'</div>');
			} else {
				$widget = '';
			}
		}
	}
