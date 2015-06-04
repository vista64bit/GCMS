<?php
	// admin/debug.php
	if (defined('MAIN_INIT') && $isAdmin) {
		// แสดงผล
		$content[] = '<div class=breadcrumbs><ul><li><span class=icon-home>{LNG_HOME}</span></li></ul></div>';
		$content[] = '<section>';
		$content[] = '<header><h1 class=icon-tools>{LNG_DEBUG}</h1></header>';
		$content[] = '<div class=setup_frm>';
		$content[] = '<div class=item>';
		$content[] = '<div id=debug_layer></div>';
		$content[] = '<div class="submit right"><a id=debug_clear class="button large red">{LNG_DELETE}</a></div>';
		$content[] = '</div>';
		$content[] = '</div>';
		$content[] = '</section>';
		$content[] = '<script>';
		$content[] = 'showDebug()';
		$content[] = '</script>';
		// หน้าปัจจุบัน
		$url_query['module'] = 'dashboard';
	} else {
		$title = $lng['LNG_DATA_NOT_FOUND'];
		$content[] = '<aside class=error>'.$title.'</aside>';
	}
