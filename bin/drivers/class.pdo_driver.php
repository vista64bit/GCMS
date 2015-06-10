<?php
	/**
	 * bin/drivers/class.pdo_driver.php
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
	 * PDO Database Adapter Class
	 *
	 * @package GCMS
	 * @subpackage Database\Drivers
	 * @category Database
	 * @author กรกฎ วิริยะ
	 */
	class PDO_DB_driver extends DB_driver {
		/**
		 *
		 * @param string $params
		 * @return boolean
		 */
		function __construct($params) {
			parent::__construct($params);
			// pdo options
			$options = array();
			$options[PDO::ATTR_PERSISTENT] = true;
			$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
			if ($this->dbdriver == 'mysql') {
				$options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES '.$this->char_set;
			}
			// pdo connect
			try {
				// connection string
				$sql = $this->dbdriver.':host='.$this->hostname.($this->dbname == '' ? '' : ';dbname='.$this->dbname);
				// connect to database
				$this->connection = new PDO($sql, $this->username, $this->password, $options);
				return true;
			} catch (PDOException $e) {
				$this->debug('PDO_DB_driver', $e->getMessage());
				return false;
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
			$keys = array();
			$datas = array();
			if (is_array($fields)) {
				foreach ($fields AS $i => $field) {
					$keys[] = "`$field`=:$field";
					if (is_array($values)) {
						$datas[":$field"] = $values[$i];
					} else {
						$datas[":$field"] = $values;
					}
				}
			} else {
				if (is_array($values)) {
					$ks = array();
					foreach ($values AS $value) {
						$ks[] = '?';
						$datas[] = $value;
					}
					$keys[] = "`$fields` IN (".implode(',', $ks).")";
				} else {
					$keys[] = "`$fields`=:$fields";
					$datas[":$fields"] = $values;
				}
			}
			try {
				$sql = "SELECT * FROM `$table` WHERE ".implode(' OR ', $keys)." LIMIT 1";
				$query = $this->connection->prepare($sql);
				$query->execute($datas);
				$result = array();
				if ($query) {
					while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
						return $row;
					}
				}
				return false;
			} catch (PDOException $e) {
				$this->debug($sql, $e->getMessage());
				return false;
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
			try {
				$keys = array();
				$values = array();
				foreach ($recArr AS $key => $value) {
					$keys[] = $key;
					$values[":$key"] = $value;
				}
				$sql = 'INSERT INTO `'.$table.'` (`'.implode('`,`', $keys);
				$sql .= "`) VALUES (:".implode(",:", $keys).");";
				$query = $this->connection->prepare($sql);
				$query->execute($values);
				$this->time++;
				return $this->connection->lastInsertId();
			} catch (PDOException $e) {
				$this->debug($sql, $e->getMessage());
				return false;
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
			try {
				$keys = array();
				$values = array();
				foreach ($recArr AS $key => $value) {
					$keys[] = "`$key`=:$key";
					$values[":$key"] = $value;
				}
				if (is_array($idArr)) {
					$datas = array();
					foreach ($idArr AS $key => $value) {
						$datas[] = "`$key`=:$key";
						$values[":$key"] = $value;
					}
					$where = sizeof($datas) == 0 ? '' : implode(' AND ', $datas);
				} else {
					$id = (int)$idArr;
					$where = $id == 0 ? '' : '`id`=:id';
					$values[':id'] = $id;
				}
				if ($where == '' || sizeof($keys) == 0) {
					return false;
				} else {
					$sql = "UPDATE `$table` SET ".implode(",", $keys)." WHERE $where LIMIT 1";
					$query = $this->connection->prepare($sql);
					$query->execute($values);
					$this->time++;
					return true;
				}
			} catch (PDOException $e) {
				$this->debug($sql, $e->getMessage());
				return false;
			}
		}
		/**
		 * query ข้อมูล
		 *
		 * @param string $sql
		 * @return int|boolean สำเร็จ คืนค่าจำนวนแถวที่ทำรายการ มีข้อผิดพลาดคืนค่า false
		 */
		function _query($sql) {
			try {
				$query = $this->connection->query($sql);
				$this->time++;
				return $query->rowCount();
			} catch (PDOException $e) {
				$this->error_message = $e->getMessage();
				return false;
			}
		}
		/**
		 * query ข้อมูล ด้วย sql ที่กำหนดเอง
		 *
		 * @param string $sql query string
		 * @return array|boolean คืนค่าผลการทำงานเป็น record ของข้อมูลทั้งหมดที่ตรงตามเงื่อนไข ไม่พบข้อมูลคืนค่าเป็น array ว่างๆ ผิดพลาดคืนค่า false
		 */
		function _customQuery($sql) {
			try {
				$query = $this->connection->query($sql);
				$result = array();
				if ($query) {
					while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
						$result[] = $row;
					}
				}
				$this->time++;
				return $result;
			} catch (PDOException $e) {
				$this->error_message = $e->getMessage();
				return false;
			}
		}
		/**
		 * ยกเลิก mysql
		 */
		function _close() {
			$this->connection = null;
		}
	}