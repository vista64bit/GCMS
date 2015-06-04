<?php
	if (INSTALL_INIT == 'upgrade') {
		$current_version = '10.1.2';
		// upgrade language
		include (ROOT_PATH.'admin/install/updatelanguages.php');
		// update index
		$db->query("ALTER TABLE `".DB_INDEX."` CHANGE `visited` `visited` INT(11) UNSIGNED NOT NULL");
		if (!$db->fieldExists(DB_INDEX, 'visited_today')) {
			$db->query("ALTER TABLE `".DB_INDEX."` ADD `visited_today` INT(11) UNSIGNED NOT NULL AFTER `visited`");
		}
		echo '<li class=correct>Update database <strong>'.DB_INDEX.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
		// update index_detail
		$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` ADD PRIMARY KEY (`id`, `module_id`, `language`)');
		echo '<li class=correct>Update database <strong>'.DB_INDEX_DETAIL.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
	}
