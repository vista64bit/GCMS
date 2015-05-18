<?php
	if (INSTALL_INIT == 'upgrade') {
		$current_version = '10.1.0';
		// upgrade language
		include(ROOT_PATH.'admin/install/updatelanguages.php');
		// อัปเดทความยาวของ topic,heywords,description,relate
		$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` CHANGE `topic` `topic` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL');
		$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` CHANGE `keywords` `keywords` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL');
		$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` CHANGE `description` `description` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL');
		$db->query('ALTER TABLE `'.DB_INDEX_DETAIL.'` CHANGE `relate` `relate` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL');
		echo '<li class=correct>Update database <strong>'.DB_INDEX_DETAIL.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
		if (defined('DB_TEXTLINK')) {
			$db->query("ALTER TABLE `".DB_TEXTLINK."` CHANGE `type` `type` VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
			if (!$db->fieldExists(DB_TEXTLINK, 'template')) {
				$db->query("ALTER TABLE `".DB_TEXTLINK."` ADD `template` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
			}
			if (!$db->fieldExists(DB_TEXTLINK, 'name')) {
				$db->query('ALTER TABLE `'.DB_TEXTLINK.'` ADD `name` VARCHAR(11) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `type`');
				$sql = "SELECT `id`,`type` FROM `".DB_TEXTLINK."`";
				foreach ($db->customQuery($sql) AS $item) {
					if (!preg_match('/([a-z]+)([0-9]{0,2})/', $item['type'], $match)) {
						$match = array(1 => '', 2 => '');
					}
					$db->edit(DB_TEXTLINK, $item['id'], array('name' => $item['type'], 'type' => $match['1']));
				}
			}
			if (!$db->fieldExists(DB_TEXTLINK, 'target')) {
				$db->query('ALTER TABLE `'.DB_TEXTLINK.'` ADD `target` VARCHAR( 6 ) NOT NULL');
			}
			echo '<li class=correct>Update database <strong>'.DB_TEXTLINK.'</strong> <i>complete...</i></li>';
			ob_flush();
			flush();
		}
		if (!$db->fieldExists(DB_INDEX, 'show_news')) {
			$db->query("ALTER TABLE `".DB_INDEX."` ADD `show_news` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `can_reply`");
		} else {
			$db->query("ALTER TABLE `".DB_INDEX."` CHANGE `show_news` `show_news` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
		}
		$db->query("UPDATE `".DB_INDEX."` SET `show_news`='news=1'");
		$db->query("ALTER TABLE `".DB_INDEX."` ADD `visited_today` INT(11) UNSIGNED NOT NULL AFTER `visited`");
		$db->query("ALTER TABLE `".DB_INDEX."` CHANGE `visited` `visited` INT(11) UNSIGNED NOT NULL");
		echo '<li class=correct>Update database <strong>'.DB_INDEX.'</strong> <i>complete...</i></li>';
		if (!$db->fieldExists(DB_CATEGORY, 'published')) {
			$db->query("ALTER TABLE `".DB_CATEGORY."` ADD `published` ENUM('1','0') NOT NULL DEFAULT '1'");
			$db->query("UPDATE `".DB_CATEGORY."` SET `published`='1'");
			echo '<li class=correct>Update database <strong>'.DB_CATEGORY.'</strong> <i>complete...</i></li>';
		}
		ob_flush();
		flush();
		$db->query("DROP TABLE `".DB_USERONLINE."`");
		$db->query("CREATE TABLE IF NOT EXISTS `".DB_USERONLINE."` (`id` int(11) NOT NULL auto_increment,`member_id` int(11) NOT NULL,`displayname` text collate utf8_unicode_ci NOT NULL,`icon` text collate utf8_unicode_ci NOT NULL,`time` int(11) NOT NULL,`session` varchar(32) collate utf8_unicode_ci NOT NULL,`ip` varchar(50) collate utf8_unicode_ci NOT NULL,PRIMARY KEY (`id`,`session`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
		echo '<li class=correct>Update database <strong>'.DB_USERONLINE.'</strong> <i>complete...</i></li>';
		ob_flush();
		flush();
	}