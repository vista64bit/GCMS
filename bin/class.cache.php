<?php
	/**
	 * bin/class.cache.php
	 * สงวนลิขสิทธ์ ห้ามซื้อขาย ให้นำไปใช้ได้ฟรีเท่านั้น
	 *
	 * @copyright http://www.goragod.com
	 * @author กรกฎ วิริยะ
	 * @version 21-05-58
	 */
	/**
	 * Cache Class
	 *
	 * @package GCMS
	 * @subpackage Cache
	 */
	class gcmsCache {
		/**
		 *
		 * @var boolean true ถ้าอ่านข้อมูลจากแคช
		 */
		protected $cache_used = false;
		/**
		 *
		 * @var string ไดเร็คทอรี่แคช
		 */
		protected $cache_dir;
		/**
		 *
		 * @var int อายุของแคช
		 */
		protected $cache_expire;
		/**
		 *
		 * @var string ข้อผิดพลาดของแคช
		 */
		protected $cache_error;
		/**
		 * inint class
		 *
		 * @global resource $ftp FTP resource
		 * @param string $dir
		 * @param int $expire  (optional) อายุของ cache เป็นวินาที (default 10 วินาที)
		 */
		public function __construct($dir, $expire = 10) {
			global $ftp;
			if ($ftp->mkdir($dir)) {
				$this->cache_dir = $dir;
				$this->cache_expire = (int)$expire;
			} else {
				$this->cache_dir = false;
				$this->cache_expire = 0;
			}
			// clear old cache every day
			$d = is_file($dir.'index.php') ? file_get_contents($dir.'index.php') : 0;
			if ($d != date('d')) {
				$this->clear();
				$f = @fopen($dir.'index.php', 'wb');
				if ($f) {
					fwrite($f, date('d'));
					fclose($f);
				} else {
					$this->cache_error = 'CACHE_INDEX_READONLY';
				}
			}
		}
		/**
		 * อ่านข้อมูลแคชที่บันทึกไว้
		 *
		 * @param string $key ชื่อของ cache
		 * @return array ข้อมูลที่อ่านจาก cache
		 * @return boolean false ถ้าไม่มีข้อมูลในแคช
		 */
		public function get($key) {
			if ($this->cache_dir == false || $this->cache_expire == 0) {
				return false;
			} else {
				$file = $this->cache_dir.md5($key).'.php';
				if (file_exists($file) && filemtime($file) > (time() - $this->cache_expire)) {
					$this->cache_used = true;
					return unserialize(preg_replace('/^<\?php\sexit\?>(.*)$/isu', '\\1', file_get_contents($file)));
				} else {
					return false;
				}
			}
		}
		/**
		 * กำหนดเวลาหมดอายุของ cache
		 *
		 * @param int $value อายุของ cache (วินาที)
		 */
		public function set_expire($value) {
			$this->cache_expire = $value;
		}
		/**
		 * ฟังก์ชั่นตรวจสอบว่าข้อมูลอ่านมาจาก cache หรือไม่
		 *
		 * @return boolean คืนค่า true  ถ้ากำลังใช้งาน cache อยู่
		 */
		public function is_cache() {
			return $this->cache_used;
		}
		/**
		 * บันทึก cache
		 *
		 * @param string $key ชื่อของ cache
		 * @param array $datas ข้อมูล
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		public function save($key, $datas) {
			if ($this->cache_dir == false || $this->cache_expire == 0) {
				return false;
			} else {
				$file = $this->cache_dir.md5($key).'.php';
				$f = @fopen($file, 'wb');
				if ($f) {
					fwrite($f, '<?php exit?>'.serialize($datas));
					fclose($f);
					return true;
				} else {
					$this->cache_error = 'CACHE_DIRECTORY_PREMISSION';
					return false;
				}
			}
		}
		/**
		 * ลบแคช
		 *
		 * @param string $key แคชที่ต้องการลบ
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		public function remove($key) {
			if ($this->cache_dir == false || $this->cache_expire == 0) {
				return false;
			} else {
				$file = $this->cache_dir.md5($key).'.php';
				if (is_file($file)) {
					return @unlink($file);
				} else {
					return true;
				}
			}
		}
		/**
		 * ลบไฟล์ทั้งหมดในไดเร็คทอรี่ (cache)
		 *
		 * @param string $dir ไดเรคทอรี่ที่ต้องการลบ
		 * @param array $error ตัวแปร array เก็บรายการที่ไม่สามารถลบได้
		 */
		private function _clear($dir, &$error) {
			$f = @opendir($dir);
			if ($f) {
				while (false !== ($text = readdir($f))) {
					if ($text != "." && $text != ".." && $text != 'index.php') {
						if (is_dir($dir.$text)) {
							$this->_clear($dir.$text.'/', $error);
						} elseif (!@unlink($dir.$text)) {
							$error[] = $dir.$text;
						}
					}
				}
				closedir($f);
			}
		}
		/**
		 * ลบแคชทั้งไดเร็คทอรี่
		 *
		 * @return boolean true ถ้าสำเร็จ
		 * @return array ไม่สำเร็จ คืนค่าแอเรย์ของไฟล์ที่ไม่สามารถลบได้
		 */
		public function clear() {
			if ($this->cache_dir == false || $this->cache_expire == 0) {
				return false;
			} else {
				$error = array();
				$this->_clear($this->cache_dir, $error);
				return sizeof($error) == 0 ? true : $error;
			}
		}
		/**
		 * ข้อผิดพลาดของ cache
		 *
		 * @return string
		 */
		public function Error() {
			return $this->cache_error;
		}
	}