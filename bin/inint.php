<?php
	/**
	 * bin/inint.php
	 * จัดการ session ของ GCMS
	 * สงวนลิขสิทธ์ ห้ามซื้อขาย ให้นำไปใช้ได้ฟรีเท่านั้น
	 *
	 * @copyright http://www.goragod.com
	 * @author กรกฎ วิริยะ
	 * @version 21-05-58
	 */
	// session, cookie
	session_start();
	if (!ob_get_status()) {
		if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
			// เปิดใช้งานการบีบอัดหน้าเว็บไซต์
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}
	}
	// load
	include dirname(__FILE__).'/load.php';
