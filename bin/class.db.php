<?php
	/**
	 * bin/class.db.php
	 * สงวนลิขสิทธ์ ห้ามซื้อขาย ให้นำไปใช้ได้ฟรีเท่านั้น
	 *
	 * @package GCMS
	 * @subpackage Database
	 * @copyright http://www.goragod.com
	 * @author กรกฎ วิริยะ
	 * @version 09-06-58
	 */
	if (!defined('ROOT_PATH')) {
		exit('No direct script access allowed');
	}
	/**
	 *  Inint database driver
	 *
	 * @param string DNS string driver://username:password@hostname/database
	 */
	function &sql($params) {
		if (($dns = @parse_url($params)) === FALSE) {
			$msg = 'Invalid DB Connection String';
			if (class_exists('gcms')) {
				gcms::writeDebug($msg);
			} else {
				echo $msg;
			}
		} else {
			$params = array(
				'dbdriver' => $dns['scheme'],
				'hostname' => (isset($dns['host'])) ? rawurldecode($dns['host']) : '',
				'username' => (isset($dns['user'])) ? rawurldecode($dns['user']) : '',
				'password' => (isset($dns['pass'])) ? rawurldecode($dns['pass']) : '',
				'dbname' => (isset($dns['path'])) ? rawurldecode(substr($dns['path'], 1)) : ''
			);
			// inint database class
			require_once (ROOT_PATH.'bin/drivers/class.db.driver.php');
			// driver class
			if (require_once (ROOT_PATH.'bin/drivers/class.'.$params['dbdriver'].'_driver.php')) {
				// โหลดจาก driver ที่กำหนด
				require_once (ROOT_PATH.'bin/drivers/class.'.$params['dbdriver'].'_driver.php');
			} else {
				// ไม่พบ driver ใช้ pdo
				require_once (ROOT_PATH.'bin/drivers/class.pdo_driver.php');
			}
			// driver string
			$driver = strtoupper($params['dbdriver']).'_DB_driver';
			// parse query string
			if (isset($dns['query'])) {
				parse_str($dns['query'], $extra);
				foreach ($extra as $key => $val) {
					// booleans
					if (strtoupper($val) == "TRUE") {
						$params[$key] = TRUE;
					} elseif (strtoupper($val) == "FALSE") {
						$params[$key] = FALSE;
					} else {
						$params[$key] = $val;
					}
				}
			}
			// inint class
			$db = new $driver($params);
			// return class
			return $db;
		}
	}
