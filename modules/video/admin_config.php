<?php
	// modules/video/admin_config.php
	if (MAIN_INIT == 'admin' && gcms::canConfig($config, 'video_can_config')) {
		// ตรวจสอบโมดูลที่เรียก
		$sql = "SELECT `id` FROM `".DB_MODULES."` WHERE `owner`='video' LIMIT 1";
		$index = $db->customQuery($sql);
		if (sizeof($index) == 0) {
			$title = $lng['LNG_DATA_NOT_FOUND'];
			$content[] = '<aside class=error>'.$title.'</aside>';
		} else {
			$index = $index[0];
			// title
			$title = "$lng[LNG_CONFIG] $lng[LNG_VIDEO]";
			$a = array();
			$a[] = '<span class=icon-video>{LNG_MODULES}</span>';
			$a[] = '{LNG_VIDEO}';
			$a[] = '{LNG_CONFIG}';
			// แสดงผล
			$content[] = '<div class=breadcrumbs><ul><li>'.implode('</li><li>', $a).'</li></ul></div>';
			$content[] = '<section>';
			$content[] = '<header><h1 class=icon-config>'.$title.'</h1></header>';
			// form
			$content[] = '<form id=setup_frm class=setup_frm method=post action=index.php autocomplete=off>';
			$content[] = '<fieldset>';
			$content[] = '<legend><span>{LNG_MAIN_CONFIG}</span></legend>';
			$content[] = '<div class=item>';
			$content[] = '<label for=google_api_key>{LNG_GOOGLE_API_KEY}</label>';
			$content[] = '<span class="g-input icon-google"><input id=google_api_key name=google_api_key type=text value="'.gcms::getVars($config, 'google_api_key', '').'"></span>';
			$content[] = '<div class=comment>{LNG_GOOGLE_API_KEY_COMMENT}</div>';
			$content[] = '</div>';
			$content[] = '</fieldset>';
			// การแสดงผล
			$content[] = '<fieldset>';
			$content[] = '<legend><span>{LNG_DISPLAY}</span></legend>';
			// video_cols,video_rows
			$content[] = '<div class=item>';
			$content[] = '<label for=config_cols>{LNG_QUANTITY}</label>';
			$content[] = '<div class=input-groups-table>';
			$content[] = '<label class=width for=config_cols>{LNG_COLS}</label>';
			$content[] = '<span class="width g-input icon-height"><select name=config_cols id=config_cols>';
			for ($i = 1; $i < 7; $i++) {
				$sel = $i == $config['video_cols'] ? ' selected' : '';
				$content[] = '<option value='.$i.$sel.'>'.$i.'</option>';
			}
			$content[] = '</select></span>';
			$content[] = '<label class=width for=config_rows>{LNG_ROWS}</label>';
			$content[] = '<span class="width g-input icon-width"><select name=config_rows id=config_rows>';
			for ($i = 1; $i < 20; $i++) {
				$sel = $i == $config['video_rows'] ? ' selected' : '';
				$content[] = '<option value='.$i.$sel.'>'.$i.'</option>';
			}
			$content[] = '</select></span>';
			$content[] = '</div>';
			$content[] = '<div class=comment>{LNG_DISPLAY_ROWS_COLS_COMMENT}</div>';
			$content[] = '</div>';
			$content[] = '</fieldset>';
			// กำหนดความสามารถของสมาชิกแต่ละระดับ
			$content[] = '<fieldset>';
			$content[] = '<legend><span>{LNG_MEMBER_ROLE_SETTINGS}</span></legend>';
			$content[] = '<div class=item>';
			$content[] = '<table class="responsive config_table">';
			$content[] = '<thead>';
			$content[] = '<tr>';
			$content[] = '<th>&nbsp;</th>';
			$content[] = '<th scope=col>{LNG_CAN_WRITE}</th>';
			$content[] = '<th scope=col>{LNG_CAN_CONFIG}</th>';
			$content[] = '</tr>';
			$content[] = '</thead>';
			$content[] = '<tbody>';
			// สถานะสมาชิก
			$bg = 'bg2';
			foreach ($config['member_status'] AS $i => $item) {
				if ($i > 1) {
					$bg = $bg == 'bg1' ? 'bg2' : 'bg1';
					$tr = '<tr class="'.$bg.' status'.$i.'">';
					$tr .= '<th>'.$item.'</th>';
					// can_write
					$tr .= '<td><label data-text="{LNG_CAN_WRITE}" ><input type=checkbox name=config_can_write[]'.(is_array($config['video_can_write']) && in_array($i, $config['video_can_write']) ? ' checked' : '').' value='.$i.' title="{LNG_CAN_WRITE_COMMENT}"></label></td>';
					// can_config
					$tr .= '<td><label data-text="{LNG_CAN_CONFIG}" ><input type=checkbox name=config_can_config[]'.(is_array($config['video_can_config']) && in_array($i, $config['video_can_config']) ? ' checked' : '').' value='.$i.' title="{LNG_CAN_CONFIG_COMMENT}"></label></td>';
					$tr .= '</tr>';
					$content[] = $tr;
				}
			}
			$content[] = '</tbody>';
			$content[] = '</table>';
			$content[] = '</div>';
			$content[] = '</fieldset>';
			// submit
			$content[] = '<fieldset class=submit>';
			$content[] = '<input type=submit class="button large save" value="{LNG_SAVE}">';
			$content[] = '</fieldset>';
			$content[] = '</form>';
			$content[] = '</section>';
			$content[] = '<script>';
			$content[] = '$G(window).Ready(function(){';
			$content[] = 'new GForm("setup_frm", "'.WEB_URL.'/modules/video/admin_config_save.php").onsubmit(doFormSubmit);';
			$content[] = '});';
			$content[] = '</script>';
			// หน้านี้
			$url_query['module'] = 'video-config';
		}
	} else {
		$title = $lng['LNG_DATA_NOT_FOUND'];
		$content[] = '<aside class=error>'.$title.'</aside>';
	}
