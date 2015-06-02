<?php
	if (INSTALL_INIT == 'upgrade') {
		$current_version = '10.1.1';
		// upgrade language
		include (ROOT_PATH.'admin/install/updatelanguages.php');
		// update index_detail
		$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` ADD PRIMARY KEY (`id`, `module_id`, `language`)');
		echo '<li class=correct>Update database <strong>'.DB_INDEX_DETAIL.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
	}
