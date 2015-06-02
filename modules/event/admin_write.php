<?php
	// modules/event/admin_write.php
	if (MAIN_INIT == 'admin' && gcms::canConfig($config, 'event_can_write')) {
		// รายการที่แก้ไข
		$id = gcms::getVars($_GET, 'id', 0);
		// หมวดที่เลือก
		$cat = gcms::getVars($_GET, 'cat', 0);
		if ($id > 0) {
			// แก้ไข
			$sql = "SELECT D.*,M.`owner`,M.`module`";
			$sql .= " FROM `".DB_EVENTCALENDAR."` AS D";
			$sql .= " INNER JOIN `".DB_MODULES."` AS M ON M.`owner`='event' AND M.`id`=D.`module_id`";
			$sql .= " WHERE D.`id`='$id' LIMIT 1";
		} else {
			// ใหม่
			$sql = "SELECT M.`id` AS `module_id`,M.`module`,M.`owner`,$cat AS `category_id`,1 AS `published`";
			$sql .= " FROM `".DB_MODULES."` AS M";
			$sql .= " WHERE M.`owner`='event' LIMIT 1";
		}
		$index = $db->customQuery($sql);
		if (sizeof($index) == 1) {
			$index = $index[0];
			// title
			$a = array();
			$a[] = '<span class=icon-event>{LNG_MODULES}</span>';
			$a[] = '<a href="{URLQUERY?module=event-config}">'.ucwords($index['module']).'</a>';
			$a[] = '<a href="{URLQUERY?module=event-setup}">{LNG_ALL_ITEMS}</a>';
			if ($id > 0) {
				$a[] = '{LNG_EDIT}';
				$title = "$lng[LNG_EDIT] $lng[LNG_EVENT] $index[topic]";
			} else {
				$a[] = '{LNG_ADD}';
				$title = "$lng[LNG_ADD] $lng[LNG_EVENT]";
				$index['id'] = 0;
				$index['topic'] = '';
				$index['color'] = '';
				$index['begin_date'] = date('Y-m-d H:i:s');
				$index['published_date'] = $index['begin_date'];
			}
			$title .= ' ('.ucwords($index['owner']).')';
			// แสดงผล
			$content[] = '<div class=breadcrumbs><ul><li>'.implode('</li><li>', $a).'</li></ul></div>';
			$content[] = '<section>';
			$content[] = '<header><h1 class=icon-write>'.$title.'</h1></header>';
			// form
			$content[] = '<form id=setup_frm class=setup_frm method=post action=index.php>';
			$content[] = '<fieldset>';
			$content[] = '<legend><span>'.$a[3].'</span></legend>';
			// topic
			$content[] = '<div class=item>';
			$content[] = '<label for=write_topic>{LNG_TOPIC}</label>';
			$content[] = '<span class="g-input icon-edit"><input type=text name=write_topic id=write_topic value="'.$index['topic'].'" maxlength=64 title="{LNG_TOPIC_COMMENT}"></span>';
			$content[] = '<div class=comment id=result_write_topic>{LNG_TOPIC_COMMENT}</div>';
			$content[] = '</div>';
			// color
			$content[] = '<div class=item>';
			$content[] = '<label for=write_color>{LNG_EVENT_COLOR}</label>';
			$content[] = '<span class="g-input icon-color"><input type=text class=color name=write_color id=write_color value="'.$index['color'].'" title="{LNG_EVENT_COLOR_COMMENT}"></span>';
			$content[] = '<div class=comment id=result_write_color>{LNG_EVENT_COLOR_COMMENT}</div>';
			$content[] = '</div>';
			// keywords,tags
			$content[] = '<div class=item>';
			$content[] = '<label for=write_keywords>{LNG_TAGS}</label>';
			$content[] = '<span class="g-input icon-tags"><textarea name=write_keywords id=write_keywords rows=3 maxlength=149 title="{LNG_TAGS_COMMENT}">'.gcms::detail2TXT($index, 'keywords').'</textarea></span>';
			$content[] = '<div class=comment id=result_write_keywords>{LNG_TAGS_COMMENT}</div>';
			$content[] = '</div>';
			// event_d,event_h,event_m
			$content[] = '<div class=item>';
			if (preg_match('/^([0-9\-\/]+)\s([0-9]{2,2})\:([0-9]{2,2}).*$/', $index['begin_date'], $match)) {
				$d = $match[1];
				$h = (int)$match[2];
				$m = (int)$match[3];
			}
			$content[] = '<div class=input-groups-table>';
			$content[] = '<label class=width for=write_d>{LNG_DATE}</label>';
			$content[] = '<span class="width g-input icon-calendar"><input type=date id=write_d name=write_d value="'.$d.'" title="{LNG_EVENT_DATE_COMMENT}"></span>';
			$content[] = '<label class="width g-input"><select name=write_h title="{LNG_HOUR}">';
			for ($i = 0; $i < 24; $i++) {
				$sel = $i == $h ? ' selected' : '';
				$content[] = '<option value='.$i.$sel.'>'.sprintf('%02d', $i).'</option>';
			}
			$content[] = '</select></label>';
			$content[] = '<span class="label width">:</span>';
			$content[] = '<label class="width g-input"><select name=write_m title="{LNG_MINUTE}">';
			for ($i = 0; $i < 60; $i++) {
				$sel = $i == $m ? ' selected' : '';
				$content[] = '<option value='.$i.$sel.'>'.sprintf('%02d', $i).'</option>';
			}
			$content[] = '</select></label>';
			$content[] = '</div>';
			$content[] = '<div class=comment id=result_begin_date>{LNG_EVENT_DATE_COMMENT}</div>';
			$content[] = '</div>';
			// description
			$content[] = '<div class=item>';
			$content[] = '<label for=write_description>{LNG_DESCRIPTION}</label>';
			$content[] = '<span class="g-input icon-file"><textarea name=write_description id=write_description rows=3 maxlength=149 title="{LNG_DESCRIPTION_COMMENT}">'.gcms::detail2TXT($index, 'description').'</textarea></span>';
			$content[] = '<div class=comment id=result_write_description>{LNG_DESCRIPTION_COMMENT}</div>';
			$content[] = '</div>';
			// detail
			$content[] = '<div class=item>';
			$content[] = '<label for=write_detail>{LNG_DETAIL}</label>';
			$content[] = '<div><textarea name=write_detail id=write_detail>'.gcms::detail2TXT($index, 'detail').'</textarea></div>';
			$content[] = '</div>';
			// published date
			$content[] = '<div class=item>';
			$content[] = '<label for=write_published_date>{LNG_PUBLISHED_DATE}</label>';
			$content[] = '<span class="g-input icon-calendar"><input type=date id=write_published_date name=write_published_date value="'.$index['published_date'].'" title="{LNG_PUBLISHED_DATE_COMMENT}"></span>';
			$content[] = '<div class=comment>{LNG_PUBLISHED_DATE_COMMENT}</div>';
			$content[] = '</div>';
			// published
			$content[] = '<div class=item>';
			$content[] = '<label for=write_published>{LNG_PUBLISHED}</label>';
			$content[] = '<span class="g-input icon-published1"><select id=write_published name=write_published title="{LNG_PUBLISHED_SETTING}">';
			foreach ($lng['LNG_PUBLISHEDS'] AS $i => $item) {
				$sel = $index['published'] == $i ? ' selected' : '';
				$content[] = '<option value='.$i.$sel.'>'.$item.'</option>';
			}
			$content[] = '</select></span>';
			$content[] = '<div class=comment>{LNG_PUBLISHED_SETTING}</div>';
			$content[] = '</div>';
			$content[] = '</fieldset>';
			// submit
			$content[] = '<fieldset class=submit>';
			$content[] = '<input type=submit class="button large save" value="{LNG_SAVE}">';
			$content[] = '<input type=hidden id=write_id name=write_id value='.(int)$index['id'].'>';
			$content[] = '</fieldset>';
			$lastupdate = empty($index['last_update']) ? '-' : gcms::mktime2date($index['last_update']);
			$content[] = '<div class=lastupdate><span class=comment>{LNG_WRITE_COMMENT}</span>{LNG_LAST_UPDATE}<span id=lastupdate>'.$lastupdate.'</span></div>';
			$content[] = '</form>';
			$content[] = '</section>';
			$content[] = '<script>';
			$content[] = 'CKEDITOR.replace("write_detail", {';
			$content[] = 'toolbar:"Document",';
			$content[] = 'language:"'.LANGUAGE.'",';
			$content[] = 'height:300,';
			if (is_dir(ROOT_PATH.'ckfinder')) {
				$content[] = 'filebrowserBrowseUrl:"'.WEB_URL.'/ckfinder/ckfinder.html",';
				$content[] = 'filebrowserImageBrowseUrl:"'.WEB_URL.'/ckfinder/ckfinder.html?Type=Images",';
				$content[] = 'filebrowserFlashBrowseUrl:"'.WEB_URL.'/ckfinder/ckfinder.html?Type=Flash",';
				$content[] = 'filebrowserUploadUrl:"'.WEB_URL.'/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files",';
				$content[] = 'filebrowserImageUploadUrl:"'.WEB_URL.'/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images",';
				$content[] = 'filebrowserFlashUploadUrl:"'.WEB_URL.'/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash"';
			} else {
				$connector = urlencode(WEB_URL.'/ckeditor/filemanager/connectors/php/connector.php');
				$content[] = 'filebrowserBrowseUrl:"'.WEB_URL.'/ckeditor/filemanager/browser/default/browser.html?Connector='.$connector.'",';
				$content[] = 'filebrowserImageBrowseUrl:"'.WEB_URL.'/ckeditor/filemanager/browser/default/browser.html?Type=Image&Connector='.$connector.'",';
				$content[] = 'filebrowserFlashBrowseUrl:"'.WEB_URL.'/ckeditor/filemanager/browser/default/browser.html?Type=Flash&Connector='.$connector.'",';
				$content[] = 'filebrowserUploadUrl:"'.WEB_URL.'/ckeditor/filemanager/connectors/php/upload.php",';
				$content[] = 'filebrowserImageUploadUrl:"'.WEB_URL.'/ckeditor/filemanager/connectors/php/upload.php?Type=Image",';
				$content[] = 'filebrowserFlashUploadUrl:"'.WEB_URL.'/ckeditor/filemanager/connectors/php/upload.phpType=Flash"';
			}
			$content[] = '});';
			$content[] = '$G(window).Ready(function(){';
			$content[] = 'new GForm("setup_frm","'.WEB_URL.'/modules/event/admin_write_save.php").onsubmit(doFormSubmit);';
			$content[] = '});';
			$content[] = '</script>';
			// หน้านี้
			$url_query['module'] = 'event-write';
		} else {
			$title = $lng['LNG_DATA_NOT_FOUND'];
			$content[] = '<aside class=error>'.$title.'</aside>';
		}
	} else {
		$title = $lng['LNG_DATA_NOT_FOUND'];
		$content[] = '<aside class=error>'.$title.'</aside>';
	}
