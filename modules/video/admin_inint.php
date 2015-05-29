<?php
	// modules/video/admin_inint.php
	if (MAIN_INIT == 'admin' && $isAdmin && (sizeof($install_owners['video']) == 0 || !defined('DB_VIDEO'))) {
		// เมนูติดตั้ง
		$admin_menus['tools']['install']['video'] = '<a href="index.php?module=install&amp;modules=video"><span>Video</span></a>';
	} else {
		// เมนูแอดมิน
		if (!gcms::canConfig($config, 'video_can_config')) {
			unset($admin_menus['modules']['video']['config']);
		}
		if (gcms::canConfig($config, 'video_can_write')) {
			$admin_menus['modules']['video']['setup'] = '<a href="index.php?module=video-setup"><span>{LNG_VIDEO_LIST}</span></a>';
			$admin_menus['modules']['video']['write'] = '<a href="index.php?module=video-write"><span>{LNG_ADD_NEW} {LNG_VIDEO}</span></a>';
		} else {
			unset($admin_menus['modules']['video']['setup']);
		}
	}