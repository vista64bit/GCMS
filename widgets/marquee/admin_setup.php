<?php
	// widgets/marquee/admin_setup.php
	if (MAIN_INIT == 'admin' && $isAdmin) {
		// title
		$title = $lng['LNG_WIDGETS_MARQUEE_SETTINGS'];
		$a = array();
		$a[] = '<span class=icon-widgets>{LNG_WIDGETS}</span>';
		$a[] = '{LNG_WIDGETS_MARQUEE}';
		// แสดงผล
		$content[] = '<div class=breadcrumbs><ul><li>'.implode('</li><li>', $a).'</li></ul></div>';
		$content[] = '<section>';
		$content[] = '<header><h1 class=icon-width>'.$title.'</h1></header>';
		$content[] = '<form id=setup_frm class=setup_frm method=post action=index.php>';
		$content[] = '<fieldset>';
		$content[] = '<legend><span>{LNG_WIDGETS_MARQUEE}</span></legend>';
		// marquee_speed
		$content[] = '<div class=item>';
		$content[] = '<label for=marquee_speed>{LNG_MARQUEE_SPEED}</label>';
		$content[] = '<span class="table g-input icon-config"><input type=number id=marquee_speed name=marquee_speed value="'.gcms::getVars($config, 'marquee_speed', '').'" title="{LNG_MARQUEE_SPEED_COMMENT}"></span>';
		$content[] = '<div class=comment id=result_marquee_speed>{LNG_MARQUEE_SPEED_COMMENT}</div>';
		$content[] = '</div>';
		// detail
		$content[] = '<div class=item>';
		$content[] = '<label for=marquee_text >{LNG_MARQUEE_DETAILS}</label>';
		$content[] = '<div><textarea name=marquee_text id=marquee_text>'.gcms::detail2TXT($config, 'marquee_text').'</textarea></div>';
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
		$_SESSION['CKEDITOR'] = $_SESSION['login']['id'];
		$content[] = 'CKEDITOR.replace("marquee_text", {';
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
		$content[] = 'new GForm("setup_frm","'.WEB_URL.'/widgets/marquee/admin_save.php").onsubmit(doFormSubmit);';
		$content[] = '});';
		$content[] = '</script>';
		// หน้านี้
		$url_query['module'] = 'marquee-setup';
	} else {
		$title = $lng['LNG_DATA_NOT_FOUND'];
		$content[] = '<aside class=error>'.$title.'</aside>';
	}
