<?php
	/**
	 * bin/class.mysql.php
	 * สงวนลิขสิทธ์ ห้ามซื้อขาย ให้นำไปใช้ได้ฟรีเท่านั้น
	 *
	 * @copyright http://www.goragod.com
	 * @author กรกฎ วิริยะ
	 * @version 21-05-58
	 */
	/**
	 * MySQL Class
	 */
	class sql {
		/**
		 * ตัวแปรเก็บจำนวน query
		 *
		 * @var int
		 */
		protected $time = 0;
		/**
		 * MySQL instance
		 *
		 * @var resource
		 */
		protected $connection = false;
		/**
		 * 1=develop mode (ใช้ตอนทดสอบเพื่อแสดง error), 0=production mode
		 *
		 * @var int
		 */
		var $debug = 0;
		/**
		 * inintial database class
		 *
		 * @param string $server Database server
		 * @param string $username Database username
		 * @param string $password Database password
		 * @param string $dbname Database name
		 * @param boolean $new (optional) true=new connection (default true)
		 * @return boolean สำเร็จคืนค่า true
		 */
		public function __construct($server, $username, $password, $dbname, $new = true) {
			$conn = @mysql_connect($server, $username, $password, $new);
			if ($conn != false) {
				$db = @mysql_select_db($dbname, $conn);
				@mysql_query('SET NAMES UTF8', $conn);
			}
			if ($conn == false || $db == false) {
				$this->debug('mysql_connect()');
				return false;
			} else {
				$this->connection = $conn;
				return true;
			}
		}
		/**
		 * อ่านค่า resource connection
		 *
		 * @return resource คืนค่า resource ของ DB
		 */
		public function connection() {
			return $this->connection;
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบว่ามีตาราง $table อยู่หรือไม่
		 *
		 * @param string $table ชื่อตาราง
		 * @return boolean คืนค่า true หากมีตารางนี้อยู่ ไม่พบคืนค่า false
		 */
		public function tableExists($table) {
			$result = @mysql_query("SELECT 1 FROM `$table`", $this->connection);
			$this->time++;
			if (!$result) {
				return false;
			} else {
				return true;
			}
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบฟิลด์ในตาราง
		 *
		 * @param string $table ชื่อตาราง
		 * @param string $field ชื่อฟิลด์
		 * @return boolean คืนค่า true หากมีฟิลด์นี้อยู่ ไม่พบคืนค่า false
		 */
		public function fieldExists($table, $field) {
			$result = @mysql_query("SHOW COLUMNS FROM `$table`", $this->connection);
			if (!$result) {
				$this->debug("fieldexists($table, $field)");
			} elseif (mysql_num_rows($result) > 0) {
				$this->time++;
				$field = strtolower($field);
				while ($row = mysql_fetch_assoc($result)) {
					if (strtolower($row['Field']) == $field) {
						return true;
					}
				}
			}
			return false;
		}
		/**
		 * ค้นหา $values ที่ $fields บนตาราง $table
		 *
		 * @param string $table ชื่อตาราง
		 * @param array|string $fields ชื่อฟิลด์
		 * @param array|string $values ข้อความค้นหาในฟิลด์ที่กำหนด ประเภทเดียวกันกับ $fields
		 * @return array|boolean พบคืนค่ารายการที่พบเพียงรายการเดียว มีข้อผิดพลาดคืนค่า false
		 */
		public function basicSearch($table, $fields, $values) {
			if (is_array($fields)) {
				foreach ($fields AS $i => $field) {
					if (is_array($values)) {
						$search[] = "`$field`='$values[$i]'";
					} else {
						$search[] = "`$field`='$values'";
					}
				}
			} else {
				if (is_array($values)) {
					$search[] = "`$fields` IN ('".implode("','", $values)."')";
				} else {
					$search[] = "`$fields`='$values'";
				}
			}
			$sql = "SELECT * FROM `$table` WHERE ".implode(' OR ', $search)." LIMIT 1;";
			$query = @mysql_query($sql, $this->connection);
			if ($query == false) {
				$this->debug("basicSearch($table)");
				return false;
			} else {
				$this->time++;
				if (mysql_num_rows($query) == 0) {
					return false;
				} else {
					$result = mysql_fetch_array($query, MYSQL_ASSOC);
					mysql_free_result($query);
					return $result;
				}
			}
		}
		/**
		 * อ่านค่า record ที่ id=$id
		 *
		 * @param string $table ชื่อตาราง
		 * @param int $id id ที่ต้องการอ่าน
		 * @return array|boolean พบคืนค่ารายการที่พบเพียงรายการเดียว ไม่พบคืนค่า false
		 */
		public function getRec($table, $id) {
			$sql = "SELECT * FROM `$table` WHERE `id`=".(int)$id." LIMIT 1";
			$query = @mysql_query($sql, $this->connection);
			if ($query == false) {
				$this->debug("getRec($table, $id)");
				return false;
			} else {
				$this->time++;
				if (mysql_num_rows($query) == 0) {
					return false;
				} else {
					$result = mysql_fetch_array($query, MYSQL_ASSOC);
					mysql_free_result($query);
					return $result;
				}
			}
		}
		/**
		 * เพิ่มข้อมูลลงบน $table
		 *
		 * @param string $table ชื่อตาราง
		 * @param array $recArr ข้อมูลที่ต้องการบันทึก
		 * @return int|boolean สำเร็จ คืนค่า id ที่เพิ่ม ผิดพลาด คืนค่า false
		 */
		public function add($table, $recArr) {
			$keys = array();
			$values = array();
			foreach ($recArr AS $key => $value) {
				$keys[] = $key;
				$values[] = $value;
			}
			$sql = 'INSERT INTO `'.$table.'` (`'.implode('`,`', $keys);
			$sql .= "`) VALUES ('".implode("','", $values);
			$sql .= "');";
			$query = @mysql_query($sql, $this->connection);
			if ($query == false) {
				$this->debug("add($table)");
				return false;
			} else {
				$this->time++;
				return mysql_insert_id($this->connection);
			}
		}
		/**
		 * แก้ไขข้อมูล
		 *
		 * @param string $table ชื่อตาราง
		 * @param array|string $idArr id ที่ต้องการแก้ไข หรือข้อความค้นหารูปแอเรย์ [filed=>value]
		 * @param array $recArr ข้อมูลที่ต้องการบันทึก
		 * @return boolean สำเร็จ คืนค่า true
		 */
		public function edit($table, $idArr, $recArr) {
			if (is_array($idArr)) {
				$datas = array();
				foreach ($idArr AS $key => $value) {
					$datas[] = "`$key`='$value'";
				}
				$id = implode(' AND ', $datas);
			} else {
				$id = (int)$idArr;
				$id = $id == 0 ? '' : "`id`='$id'";
			}
			if ($id == '') {
				return false;
			} else {
				$datas = array();
				foreach ($recArr AS $key => $value) {
					$datas[] = "`$key`='$value'";
				}
				$sql = "UPDATE `$table` SET ".implode(",", $datas)." WHERE $id LIMIT 1";
				$query = @mysql_query($sql, $this->connection);
				if ($query == false) {
					$this->debug("edit($table, $id)");
					return false;
				} else {
					$this->time++;
					return true;
				}
			}
		}
		/**
		 * ลบ เร็คคอร์ดรายการที่ $id
		 *
		 * @param string $table ชื่อตาราง
		 * @param int $id id ที่ต้องการลบ
		 * @return string  สำเร็จ คืนค่าว่าง ไม่สำเร็จคืนค่าข้อความผิดพลาด
		 */
		public function delete($table, $id) {
			$sql = "DELETE FROM `$table` WHERE `id`=".(int)$id." LIMIT 1;";
			$query = @mysql_query($sql, $this->connection);
			$this->time++;
			return ($query == false) ? mysql_error($this->connection) : '';
		}
		/**
		 * query ข้อมูล แบบไม่ต้องการผลตอบกลับ
		 *
		 * @param string $sql query string
		 * @return boolean สำเร็จ คืนค่า true
		 */
		public function query($sql) {
			$query = @mysql_query($sql, $this->connection);
			if ($query == false) {
				$this->debug("query($sql)");
				return false;
			} else {
				$this->time++;
				return true;
			}
		}
		/**
		 * query ข้อมูล ด้วย sql ที่กำหนดเอง
		 *
		 * @param string $sql query string
		 * @return array คืนค่าผลการทำงานเป็น record ของข้อมูลทั้งหมดที่ตรงตามเงื่อนไข ไม่พบข้อมูลคืนค่าเป็น array ว่างๆ
		 */
		public function customQuery($sql) {
			$recArr = array();
			$query = @mysql_query($sql, $this->connection);
			if ($query == false) {
				$this->debug("customQuery($sql)");
			} else {
				$this->time++;
				while ($row = mysql_fetch_array($query, MYSQL_ASSOC)) {
					$recArr[] = $row;
				}
				mysql_free_result($query);
			}
			return $recArr;
		}
		/**
		 * อ่าน id ล่าสุดของตาราง
		 *
		 * @param string $table ชื่อตาราง
		 * @return int คืนค่า id ล่าสุดของตาราง
		 */
		function lastId($table) {
			$sql = "SHOW TABLE STATUS LIKE '$table'";
			$query = @mysql_query($sql, $this->connection);
			if ($query == false) {
				$this->debug("lastId($table)");
				return false;
			} else {
				$row = @mysql_fetch_assoc($query);
				$this->time++;
				return (int)$row['Auto_increment'];
			}
		}
		/**
		 * ยกเลิกการล๊อคตารางทั้งหมดที่ล๊อคอยู่
		 *
		 * @return boolean สำเร็จ คืนค่า true
		 */
		function unlock() {
			return $this->query('UNLOCK TABLES');
		}
		/**
		 * ล๊อคตาราง
		 *
		 * @param string $table ชื่อตาราง
		 * @return boolean สำเร็จ คืนค่า true
		 */
		function lock($table) {
			return $this->query("LOCK TABLES $table");
		}
		/**
		 * ล๊อคตารางสำหรับอ่าน
		 *
		 * @param string $table ชื่อตาราง
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		function setReadLock($table) {
			return $this->lock("`$table` READ");
		}
		/**
		 * ล๊อคตารางสำหรับเขียน
		 *
		 * @param string $table ชื่อตาราง
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		function setWriteLock($table) {
			return $this->lock("`$table` WRITE");
		}
		/**
		 * ตรวจสอบและลบข้อความที่ไม่ต้องการของ mysql
		 *
		 * @param string $value ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public function sql_clean($value) {
			if ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || ini_get('magic_quotes_sybase')) {
				$value = stripslashes($value);
			}
			if (function_exists("mysql_real_escape_string")) {
				$value = mysql_real_escape_string($value);
			} else {
				// PHP version < 4.3.0 use addslashes
				$value = addslashes($value);
			}
			return $value;
		}
		/**
		 * เติม string ด้วย /
		 *
		 * @param string $value ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public function sql_quote($value) {
			return str_replace('\\\\', '&#92;', $this->sql_clean($value));
		}
		/**
		 * ลบช่องว่างหัวท้ายออกจากข้อความ และ เติม string ด้วย /
		 *
		 * @param mixed $array ตัวแปรเก็บข้อความ
		 * @param string $key key ของ $array เช่น $array[$key]
		 * @return string คืนค่าข้อความ
		 */
		public function sql_trim($array, $key = '') {
			if (is_array($array)) {
				if (!isset($array[$key])) {
					return '';
				} else {
					return $this->sql_quote(trim($array[$key]));
				}
			} else {
				return $this->sql_quote(trim($array));
			}
		}
		/**
		 * ลบช่องว่างหัวท้ายออกจากข้อความ และ เติม string ด้วย / และ แปลงอักขระ HTML
		 *
		 * @param mixed $array ตัวแปรเก็บข้อความ
		 * @param string $key key ของ $array เช่น $array[$key]
		 * @return string คืนค่าข้อความ
		 */
		public function sql_trim_str($array, $key = '') {
			if (is_array($array)) {
				if (!isset($array[$key])) {
					return '';
				} else {
					return $this->sql_quote(htmlspecialchars(trim($array[$key])));
				}
			} else {
				return $this->sql_quote(htmlspecialchars(trim($array)));
			}
		}
		/**
		 * แปลงวันที่ ในรูป mktime เป้นวันที่ของ mysql ในรูป Y-m-d
		 *
		 * @param int $mktime วันที่ในรูป mktime
		 * @return string คืนค่าวันที่รูป Y-m-d
		 */
		public function sql_mktimetodate($mktime) {
			return date("Y-m-d", $mktime);
		}
		/**
		 * แปลงวันที่ ในรูป mktime เป้นวันที่และเวลาของ mysql เช่น Y-m-d H:i:s
		 *
		 * @param int $mktime วันที่ในรูป mktime
		 * @return string คืนค่า วันที่และเวลาของ mysql เช่น Y-m-d H:i:s
		 */
		public function sql_mktimetodatetime($mktime) {
			return date("Y-m-d H:i:s", $mktime);
		}
		/**
		 * แปลงวันที่ในรูป Y-m-d เป็นวันที่และเวลา เช่น 1 มค. 2555 12:00:00
		 *
		 * @global array $lng ตัวแปรภาษา
		 * @param string $date วันที่ในรูป Y-m-d หรือ Y-m-d h:i:s
		 * @param boolean $short (optional) true=เดือนแบบสั้น, false=เดือนแบบยาว (default true)
		 * @param boolean $time (optional) true=คืนค่าเวลาด้วยถ้ามี, false=ไม่ต้องคืนค่าเวลา (default true)
		 * @return string คืนค่า วันที่และเวลา
		 */
		public function sql_date2date($date, $short = true, $time = true) {
			global $lng;
			preg_match('/([0-9]+){0,4}-([0-9]+){0,2}-([0-9]+){0,2}(\s([0-9]+){0,2}:([0-9]+){0,2}:([0-9]+){0,2})?/', $date, $match);
			if (empty($match[1])) {
				return '';
			} else {
				$month = $short ? $lng['MONTH_SHORT'] : $lng['MONTH_LONG'];
				return $match[3].' '.$month[(int)$match[2] - 1].' '.((int)$match[1] + $lng['YEAR_OFFSET']).($time && isset($match[4]) ? $match[4] : '');
			}
		}
		/**
		 * ฟังก์ชั่น แปลงวันที่และเวลาของ sql เป็น mktime
		 *
		 * @param string $date วันที่ในรูป Y-m-d H:i:s
		 * @return int คืนค่าเวลาในรูป mktime
		 */
		function sql_datetime2mktime($date) {
			preg_match('/([0-9]+){0,4}-([0-9]+){0,2}-([0-9]+){0,2}\s([0-9]+){0,2}:([0-9]+){0,2}:([0-9]+){0,2}/', $date, $match);
			return mktime($match[4], $match[5], $match[6], $match[2], $match[3], $match[1]);
		}
		/**
		 * ฟังก์ชั่น เริ่มต้นจับเวลาการประมวลผล
		 */
		public function timer_start() {
			$mtime = microtime();
			$mtime = explode(' ', $mtime);
			$this->time_start = $mtime[1] + $mtime[0];
			$this->time = 0;
		}
		/**
		 * ฟังก์ชั่น จบการจับเวลา
		 *
		 * @return int คืนค่าเวลาที่ใช้ไป (msec)
		 */
		public function timer_stop() {
			$mtime = microtime();
			$mtime = explode(' ', $mtime);
			$time_end = $mtime[1] + $mtime[0];
			$time_total = $time_end - $this->time_start;
			return round($time_total, 10);
		}
		/**
		 * ฟังก์ชั่น อ่านจำนวน query ทั้งหมดที่ทำงาน
		 *
		 * @return int
		 */
		public function query_count() {
			return $this->time;
		}
		/**
		 * ฟังก์ชั่น แสดงผล error เมื่ออยู่ใน develop mode
		 *
		 * @param string $text ข้อความที่จะแสดง (error)
		 */
		private function debug($text) {
			if ($this->debug == 1) {
				echo preg_replace(array('/\r/', '/\n/', '/\t/'), array('', ' ', ' '), $text);
			}
		}
		/**
		 * ยกเลิก mysql
		 */
		public function close() {
			@mysql_close($this->connection) === false ? false : true;
		}
		/**
		 * ฟังก์ชั่น จบ class
		 */
		public function __destruct() {
			$this->connection = null;
		}
	}