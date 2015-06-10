<?php
	if (INSTALL_INIT == 'upgrade') {
		$current_version = '10.1.2';
		// upgrade language
		include (ROOT_PATH.'admin/install/updatelanguages.php');
		// update index
		$sql = "ALTER TABLE `".DB_INDEX."`";
		$sql .= " MODIFY COLUMN `index`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0,";
		$sql .= " MODIFY COLUMN `can_reply`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0,";
		$sql .= " MODIFY COLUMN `published`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1,";
		$sql .= " MODIFY COLUMN `pin`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0,";
		$sql .= " MODIFY COLUMN `locked`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0";
		$db->query($sql);
		$db->query("ALTER TABLE `".DB_INDEX."` CHANGE `visited` `visited` INT(11) UNSIGNED NOT NULL");
		$db->query("ALTER TABLE `".DB_INDEX."` DROP COLUMN `related`");
		if (!$db->fieldExists(DB_INDEX, 'visited_today')) {
			$db->query("ALTER TABLE `".DB_INDEX."` ADD `visited_today` INT(11) UNSIGNED NOT NULL AFTER `visited`");
		}
		echo '<li class=correct>Update database <strong>'.DB_INDEX.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
		// update index_detail
		$search = $db->customQuery("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='".DB_INDEX_DETAIL."'  AND column_key='PRI') As HasPrimaryKey");
		if ($search[0]['HasPrimaryKey'] == 1) {
			$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` DROP PRIMARY KEY');
		}
		$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` ADD PRIMARY KEY (`id`, `module_id`, `language`)');
		echo '<li class=correct>Update database <strong>'.DB_INDEX_DETAIL.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
		// update board_q
		$sql = "ALTER TABLE `".DB_BOARD_Q."`";
		$sql .= " MODIFY COLUMN `can_reply`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1,";
		$sql .= " MODIFY COLUMN `published`  tinyint(1) UNSIGNED NOT NULL DEFAULT 1,";
		$sql .= " MODIFY COLUMN `pin`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0,";
		$sql .= " MODIFY COLUMN `locked`  tinyint(1) UNSIGNED NOT NULL DEFAULT 0";
		$db->query($sql);
		echo '<li class=correct>Update database <strong>'.DB_BOARD_Q.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
		// update email_template
		$db->query('ALTER TABLE `'.DB_EMAIL_TEMPLATE.'` CHANGE `last_update` `last_update` int(11) UNSIGNED  NOT NULL)');
		$db->query('ALTER TABLE `'.DB_EMAIL_TEMPLATE.'` CHANGE `last_send` `last_send` datetime  NOT NULL)');
		echo '<li class=correct>Update database <strong>'.DB_EMAIL_TEMPLATE.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
		// update menus
		$db->query("ALTER TABLE `".DB_MENUS."` MODIFY COLUMN `published`  enum('0','1','2','3') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' AFTER `alias`");
		echo '<li class=correct>Update database <strong>'.DB_MENUS.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
		// update user
		$sql = "ALTER TABLE `".DB_USER."`";
		$sql .= " MODIFY COLUMN `subscrib`  enum('1','0') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',";
		$sql .= " MODIFY COLUMN `fb`  enum('0','1') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',";
		$sql .= " MODIFY COLUMN `admin_access`  enum('0','1') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0'";
		echo '<li class=correct>Update database <strong>'.DB_USER.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
	}
