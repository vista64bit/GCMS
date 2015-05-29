<?php
	// widgets/relate/getnews.php
	header("content-type: text/html; charset=UTF-8");
	// inint
	include ('../../bin/inint.php');
	// ตรวจสอบ referer
	if (gcms::isReferer() && preg_match('/^widget_([0-9]+)_([0-9]+)_([0-9]+)_([0-9]+)_(list|icon|thumb)$/', $_POST['id'], $match)) {
		// วันนี้
		$c_date = date('Y-m-d', $mmktime);
		// อ่านโมดูล
		$sql = "SELECT M.`config`,M.`module`,D.`relate`,Q.`id`,Q.`module_id`";
		$sql .= " FROM `".DB_INDEX."` AS Q";
		$sql .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`id`=Q.`id` AND D.`module_id`=Q.`module_id` AND D.`language` IN ('".LANGUAGE."','')";
		$sql .= " INNER JOIN ".DB_MODULES." AS M ON M.`id`=D.`module_id`";
		$sql .= " WHERE D.`id`=".(int)$match[1]." AND M.`owner`='document' AND Q.`published`='1' AND Q.`published_date`<='$c_date' AND Q.`index` = '0' LIMIT 1";
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
		if ($index && $index['relate'] != '') {
			$qs = array();
			foreach (explode(',', $index['relate']) AS $q) {
				$qs[] = "D.`relate` LIKE '%$q%'";
			}
			// query
			$sql1 = "SELECT @row:=@row+1 AS `row`,Q3.* FROM (";
			$sql1 .= "SELECT * FROM (";
			$sql1 .= "SELECT  Q.`id`, D.`topic`, Q.`alias`, Q.`picture`, Q.`comment_date`, Q.`last_update`, Q.`create_date`";
			$sql1 .= ",D.`description`, Q.`comments`, Q.`visited`, U.`status`, U.`id` AS `member_id`, U.`displayname`, U.`email`";
			$sql1 .= " FROM `".DB_INDEX."` AS Q";
			$sql1 .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`id` = Q.`id` AND D.`module_id` = Q.`module_id` AND D.`language` IN ('th', '')";
			$sql1 .= " LEFT JOIN `".DB_USER."` AS U ON U.`id` = Q.`member_id`";
			$sql1 .= " WHERE Q.`module_id`=".$index['module_id']." AND Q.`published`='1' AND Q.`published_date`<='$c_date' AND Q.`index` = '0' AND Q.`id`>".$index['id']." AND (".implode(' OR ', $qs).") ORDER BY Q.`create_date` ASC";
			$sql1 .= ") AS Q2, (SELECT @row:=0) r";
			$sql1 .= ") AS Q3";
			$sql2 = "SELECT @row2:=@row2+1 AS `row2`,Q3.* FROM (";
			$sql2 .= "SELECT * FROM (";
			$sql2 .= "SELECT  Q.`id`, D.`topic`, Q.`alias`, Q.`picture`, Q.`comment_date`, Q.`last_update`, Q.`create_date`";
			$sql2 .= ",D.`description`, Q.`comments`, Q.`visited`, U.`status`, U.`id` AS `member_id`, U.`displayname`, U.`email`";
			$sql2 .= " FROM `".DB_INDEX."` AS Q";
			$sql2 .= " INNER JOIN `".DB_INDEX_DETAIL."` AS D ON D.`id` = Q.`id` AND D.`module_id` = Q.`module_id` AND D.`language` IN ('th', '')";
			$sql2 .= " LEFT JOIN `".DB_USER."` AS U ON U.`id` = Q.`member_id`";
			$sql2 .= " WHERE Q.`module_id`=".$index['module_id']." AND Q.`published` = '1' AND Q.`published_date`<='$c_date' AND Q.`index` = '0' AND Q.`id`<".$index['id']." AND (".implode(' OR ', $qs).") ORDER BY Q.`create_date` DESC";
			$sql2 .= ") AS Q2, (SELECT @row2:=0) r";
			$sql2 .= ") AS Q3";
			$sql = "SELECT * FROM (SELECT * FROM (($sql1) UNION ($sql2)) AS X ORDER BY X.`row` LIMIT ".$match[2] * $match[3].") AS Y ORDER BY `create_date` DESC";
			$datas = $cache->get($sql);
			if (!$datas) {
				$datas = $db->customQuery($sql);
				$cache->save($sql, $datas);
			}
			// styles
			$styles = in_array($match[5], array('list', 'icon', 'thumb')) ? $match[5] : 'list';
			// เครื่องหมาย new
			$valid_date = $mmktime - $index['new_date'];
			// template
			$skin = gcms::loadfile(ROOT_PATH.'widgets/relate/widgetitem.html');
			$patt = array('/{BG}/', '/{URL}/', '/{TOPIC}/', '/{DETAIL}/', '/{CATEGORY}/', '/{LASTUPDATE}/', '/{UID}/',
				'/{SENDER}/', '/{STATUS}/', '/{COMMENTS}/', '/{VISITED}/', '/{THUMB}/', '/{ICON}/');
			$widget = array();
			$bg = 'bg2';
			foreach ($datas AS $i => $item) {
				if ($i > 0 && $i % $match[2] == 0) {
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
				$replace[] = $match[2];
				$replace[] = OLD_PHP ? '$lng[\'$1\']' : 'gcms::getLng';
				echo gcms::pregReplace($patt, $replace, '<div class="row '.$styles.'view">'.implode('', $widget).'</div>');
			} else {
				$widget = '';
			}
		}
	}
