<?php
	/**
	 * bin/drivers/class.mysqli_driver.php
	 * สงวนลิขสิทธ์ ห้ามซื้อขาย ให้นำไปใช้ได้ฟรีเท่านั้น
	 *
	 * @package GCMS
	 * @copyright http://www.goragod.com
	 * @author กรกฎ วิริยะ
	 * @version 09-06-58
	 */
	if (!defined('ROOT_PATH')) {
		exit('No direct script access allowed');
	}
	/**
	 * MySQLI Database Adapter Class
	 *
	 * @package GCMS
	 * @subpackage Database\Drivers
	 * @category Database
	 * @author กรกฎ วิริยะ
	 */
	class MYSQLI_DB_driver extends DB_driver {
		/**
		 *
		 * @param string $params
		 * @return boolean
		 */
		function __construct($params) {
			parent::__construct($params);
			// mysqli connect
			if ($this->port == '') {
				$this->connection = new mysqli($this->hostname, $this->username, $this->password, $this->dbname);
			} else {
				$this->connection = new mysqli($this->hostname, $this->username, $this->password, $this->dbname, $this->port);
			}
			if ($this->connection->connect_error) {
				$this->debug('MYSQLI_DB_driver', $this->connection->connect_error);
				return false;
			} else {
				$this->connection->set_charset($this->char_set);
				return true;
			}
		}
		/**
		 * ค้นหา $values ที่ $fields บนตาราง $table
		 *
		 * @param string $table ชื่อตาราง
		 * @param array|string $fields ชื่อฟิลด์
		 * @param array|string $values ข้อความค้นหาในฟิลด์ที่กำหนด ประเภทเดียวกันกับ $fields
		 * @return array|boolean พบคืนค่ารายการที่พบเพียงรายการเดียว ไม่พบหรือมีข้อผิดพลาดคืนค่า false
		 */
		function basicSearch($table, $fields, $values) {
			$search = array();
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
			$sql = "SELECT * FROM `$table` WHERE ".implode(' OR ', $search)." LIMIT 1";
			$query = $this->connection->query($sql);
			if ($query == false) {
				$this->debug($sql, $this->connection->error);
				return false;
			} else {
				$this->time++;
				if ($query->num_rows == 1) {
					$result = $query->fetch_assoc();
					$query->free();
					return $result;
				} else {
					return false;
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
		function add($table, $recArr) {
			$keys = array();
			$values = array();
			foreach ($recArr AS $key => $value) {
				$keys[] = $key;
				$values[] = $value;
			}
			$sql = 'INSERT INTO `'.$table.'` (`'.implode('`,`', $keys);
			$sql .= "`) VALUES ('".implode("','", $values);
			$sql .= "');";
			$result = $this->connection->query($sql);
			if ($query == false) {
				$this->debug($sql, $this->connection->error);
				return false;
			} else {
				$this->time++;
				return $this->connection->insert_id;
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
		function edit($table, $idArr, $recArr) {
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
				$query = $this->connection->query($sql);
				if ($query == false) {
					$this->debug($sql, $this->connection->error);
					return false;
				} else {
					$this->time++;
					return true;
				}
			}
		}
		/**
		 * query ข้อมูล
		 *
		 * @param string $sql
		 * @return int|boolean สำเร็จ คืนค่าจำนวนแถวที่ทำรายการ มีข้อผิดพลาดคืนค่า false
		 */
		function _query($sql) {
			$query = $this->connection->query($sql);
			if ($query == false) {
				$this->error_message = $this->connection->error;
				return false;
			} else {
				$this->time++;
				return $this->connection->affected_rows;
			}
		}
		/**
		 * query ข้อมูล ด้วย sql ที่กำหนดเอง
		 *
		 * @param string $sql query string
		 * @return array|boolean คืนค่าผลการทำงานเป็น record ของข้อมูลทั้งหมดที่ตรงตามเงื่อนไข ไม่พบข้อมูลคืนค่าเป็น array ว่างๆ ผิดพลาดคืนค่า false
		 */
		function _customQuery($sql) {
			$result = array();
			$query = $this->connection->query($sql);
			if ($query == false) {
				$this->error_message = $this->connection->error;
				return false;
			} else {
				$this->time++;
				while ($row = $query->fetch_assoc()) {
					$result[] = $row;
				}
				$query->free();
			}
			return $result;
		}
		/**
		 * ตรวจสอบและลบข้อความที่ไม่ต้องการของ mysql
		 *
		 * @param string $value ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		function sql_clean($value) {
			if ((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || ini_get('magic_quotes_sybase')) {
				$value = stripslashes($value);
			}
			return $this->connection->real_escape_string($value);
		}
		/**
		 * เติม string ด้วย /
		 *
		 * @param string $value ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		function sql_quote($value) {
			return str_replace('\\\\', '&#92;', $this->sql_clean($value));
		}
		/**
		 * ยกเลิก mysql
		 */
		function _close() {
			$this->connection->close();
		}
	}