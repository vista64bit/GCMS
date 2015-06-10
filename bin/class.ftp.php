<?php
	/**
	 * bin/class.ftp.php
	 * สงวนลิขสิทธ์ ห้ามซื้อขาย ให้นำไปใช้ได้ฟรีเท่านั้น
	 *
	 * @copyright http://www.goragod.com
	 * @author กรกฎ วิริยะ
	 * @version 21-05-58
	 */
	/**
	 * FTP Class
	 *
	 * @package GCMS
	 * @subpackage FTP
	 */
	class ftp {
		/**
		 * FTP connection
		 *
		 * @var resource
		 */
		protected $connection;
		/**
		 * FTP Host name
		 *
		 * @var string
		 */
		protected $host;
		/**
		 * FTP username
		 *
		 * @var string
		 */
		protected $username;
		/**
		 * FTP password
		 *
		 * @var string
		 */
		protected $password;
		/**
		 * FTP port
		 *
		 * @var int
		 */
		protected $port;
		/**
		 * root ของ FTP
		 *
		 * @var string
		 */
		protected $ftp_absolute_path;
		/**
		 * inintial class
		 *
		 * @param string $host  FTP Host
		 * @param string $username  FTP Username
		 * @param string $password  FTP Password
		 * @param string $ftproot  root ของ FTP
		 * @param int $port (optional) FTP Port (default 21)
		 */
		public function __construct($host, $username, $password, $ftproot, $port = 21) {
			$this->host = $host;
			$this->ftp_absolute_path = $ftproot;
			$this->username = $username;
			$this->password = $password;
			$this->port = $port;
			$this->connection = false;
		}
		/**
		 * ฟังก์ชั่น login และคืนค่า resource ของ FTP
		 *
		 * @return resource คืนค่า connection ถ้าสำเร็จ
		 * @return boolean คืนค่า false ถ้าไม่สำเร็จ
		 */
		public function connect() {
			if ($this->login()) {
				return $this->connection;
			} else {
				return false;
			}
		}
		/**
		 * destroy class
		 */
		public function __destruct() {
			@ftp_close($this->connection);
		}
		/**
		 * Tempraly close ftp
		 */
		public function close() {
			@ftp_close($this->connection);
			$this->connection = false;
		}
		/**
		 * FTP login
		 *
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		public function login() {
			if (function_exists('ftp_connect')) {
				if (!$this->connection) {
					$stream = @ftp_connect($this->host, $this->port, 10);
					if ($stream) {
						$login = @ftp_login($stream, $this->username, $this->password);
						if ($login) {
							$this->connection = $stream;
							return true;
						} else {
							ftp_close($stream);
						}
					}
				} else {
					return true;
				}
			}
			return false;
		}
		/**
		 * ย้ายไฟล์
		 *
		 * @param string $source ไฟล์ต้นฉบับ
		 * @param string $dest ไฟล์ปลายทาง
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		public function move_uploaded_file($source, $dest) {
			return $this->rename($source, $dest);
		}
		/**
		 * สำเนาไฟล์
		 *
		 * @param string $source ไฟล์ต้นฉบับ
		 * @param string $dest ไฟล์ปลายทาง
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		public function copy($source, $dest) {
			if (!is_file($dest)) {
				$chk = dirname($dest);
				if (!is_writable($chk)) {
					$chk = '';
				}
			} elseif (!is_writable($dest)) {
				$chk = $dest;
			}
			if (!empty($chk)) {
				$chmod = fileperms($chk);
				$this->chmod($chk, 0757);
			}
			$f = @copy($source, $dest);
			if (!empty($chk)) {
				$this->chmod($chk, $chmod);
			}
			return $f;
		}
		/**
		 * ดาวน์โหลดไฟล์จาก FTP
		 *
		 * @param string $remote_file ที่อยู่ไฟล์ต้นทางบน FTP
		 * @param string $local_file ที่อยู่ไฟล์ปลายทาง
		 * @param int $mode (optional) transfer mode เช่น FTP_ASCII หรือ FTP_BINARY (default)
		 * @return boolean สำเร็จ คืนค่า true
		 */
		public function download($remote_file, $local_file, $mode = FTP_BINARY) {
			if ($this->login()) {
				return ftp_get($this->connection, $local_file, $remote_file, $mode);
			}
			return false;
		}
		/**
		 * สำเนาไฟล์บน FTP
		 *
		 * @param string $remote_file ที่อยู่ไฟล์ปลายทาง
		 * @param string $local_file  ที่อยู่ไฟล์ต้นทาง
		 * @param int $mode (optional) transfer mode เช่น FTP_ASCII หรือ FTP_BINARY (default)
		 * @return boolean สำเร็จ คืนค่า true
		 */
		public function put($remote_file, $local_file, $mode = FTP_BINARY) {
			if ($this->login()) {
				return ftp_put($this->connection, $remote_file, $local_file, $mode);
			}
			return false;
		}
		/**
		 * เขียนไฟล์
		 * @param string $file ไฟล์พาธ
		 * @param string $mode mode การเขียน
		 * @param string $string รายละเอียด
		 * @return boolean สำเร็จ คืนค่า true
		 */
		public function fwrite($file, $mode, $string) {
			if (!is_file($file)) {
				$chk = dirname($file);
				if (is_writable($chk)) {
					$chk = '';
				}
			} elseif (!is_writable($file)) {
				$chk = $file;
			}
			if (!empty($chk)) {
				$chmod = fileperms($chk);
				$this->chmod($chk, 0757);
			}
			$f = @fopen($file, $mode);
			if ($f) {
				fwrite($f, $string);
				fclose($f);
			}
			if (!empty($chk)) {
				$this->chmod($chk, $chmod);
			}
			return $f;
		}
		/**
		 * อ่านรายละเอียดของไดเรคทอรี่
		 *
		 * @param string $dir ไดเรคทอรี่
		 * @return array คืนค่ารายละเอียดของไฟล์หรือโฟลเดอร์ในไดเรคทอรี่
		 * @return boolean คืนค่า false ถ้าไม่สามารถอ่านได้
		 */
		public function readdir($dir = '.') {
			if ($this->login()) {
				return ftp_nlist($this->connection, $dir);
			}
			return false;
		}
		/**
		 * อ่านค่าไดเร็คทอรี่ปัจจุบันของ FTP
		 *
		 * @return string คืนค่า full path
		 * @return boolean คืนค่า false ถ้าไม่สามารถอ่านได้
		 */
		public function getcwd() {
			if ($this->login()) {
				return ftp_pwd($this->connection);
			}
			return false;
		}
		/**
		 * สร้างไดเรคทอรี่
		 *
		 * @param string $dir full path
		 * @param mixed $mode chmod value
		 * @return boolean สำเร็จ คืนค่า true
		 */
		function mkdir($dir, $mode = 0755) {
			if (!is_dir($dir)) {
				$pdir = dirname($dir);
				if (!is_writeable($pdir)) {
					$chmod = @fileperms($pdir);
					$this->chmod($pdir, 0757);
				} else {
					$chmod = 0;
				}
				$f = @mkdir($dir, $mode);
				if ($chmod > 0) {
					$this->chmod($pdir, $chmod);
				}
				return $f;
			} else {
				return $this->chmod($dir, $mode);
			}
		}
		/**
		 * ตรวจสอบว่าเป็นไดเรคทอรี่หรือไม่
		 *
		 * @param string $dir full path
		 * @return boolean คืนค่า true ถ้าเป็นไดเรคทอรี่
		 */
		function is_dir($dir) {
			if ($this->login() && @ftp_chdir($this->connection, $dir)) {
				ftp_chdir($this->connection, '/../');
				return true;
			} else {
				return false;
			}
		}
		/**
		 * ตรวจสอบว่าเขียนได้หรือไม่
		 *
		 * @param string $dir ไฟล์หรือโฟลเดอร์
		 * @return boolean คืนค่า true ถ้าเขียนได้
		 */
		function is_writeable($dir) {
			if (is_writeable($dir)) {
				return true;
			} else {
				$this->chmod($dir, 0755);
				return is_writeable($dir);
			}
		}
		/**
		 * เปลี่ยนชื่อไฟล์หรือโฟลเดอร์
		 *
		 * @param string $old_file ชื่อไฟล์หรือโฟลเดอร์
		 * @param string $new_file ชื่อใหม่
		 * @return boolean สำเร็จคืนค่า true
		 */
		function rename($old_file, $new_file) {
			if (!is_file($new_file)) {
				$chk = dirname($new_file);
				if (!is_writable($chk)) {
					$chk = '';
				}
			} elseif (!is_writable($new_file)) {
				$chk = $new_file;
			}
			if (!empty($chk)) {
				$chmod = fileperms($chk);
				$this->chmod($chk, 0757);
			}
			$f = @rename($old_file, $new_file);
			if (!$f && $this->login()) {
				$f = @ftp_rename($this->connection, $old_file, $new_file);
			}
			if (!empty($chk)) {
				$this->chmod($chk, $chmod);
			}
			return $f;
		}
		/**
		 * อ่านขนาดของไฟล์
		 *
		 * @param string $file
		 * @return float คืนค่าขนาดของไฟล์ หรือ -1 หากไม่พบไฟล์
		 */
		function filesize($file) {
			$socket = fsockopen($this->host, $this->port);
			$t = fgets($socket, 128);
			fwrite($socket, "USER $this->username\r\n");
			$t = fgets($socket, 128);
			fwrite($socket, "PASS $this->password\r\n");
			$t = fgets($socket, 128);
			fwrite($socket, "SIZE $file\r\n");
			$t = fgets($socket, 128);
			if (preg_match('/^213\s(.*)$/', $t, $match)) {
				$size = floatval($match[1]);
			} else {
				$size = -1;
			}
			fwrite($socket, "QUIT\r\n");
			fclose($socket);
			return $size;
		}
		/**
		 * ปรับ chmod
		 *
		 * @param string $file ไฟล์หรือโฟลเดอร์ที่ต้องการปรับ chmod
		 * @param mixed $mode chmod value
		 * @return boolean สำเร็จคืนค่า true
		 */
		function chmod($file, $mode) {
			if (!@chmod($file, $mode)) {
				if ($this->login()) {
					return @ftp_chmod($this->connection, $mode, $this->ftp_file($file));
				}
				return false;
			}
			return true;
		}
		/**
		 * ลบไฟล์ (FTP)
		 *
		 * @param string $path ไฟล์ที่ต้องการลบ
		 * @return boolean สำเร็จคืนค่า true
		 */
		function unlink($path) {
			if (is_file($path)) {
				if (!@unlink($path)) {
					if ($this->login()) {
						return ftp_delete($this->connection, $this->ftp_file($path));
					}
					return false;
				}
			}
			return true;
		}
		/**
		 * ลบไดเรคทอรี่ (FTP)
		 *
		 * @param string $dir ไดเรคทอรี่ที่ต้องการลบ
		 * @return boolean สำเร็จคืนค่า true
		 */
		function _rmdir($dir) {
			if (is_dir($dir)) {
				if (!@rmdir($dir)) {
					if ($this->login()) {
						return @ftp_rmdir($this->connection, $this->ftp_file($dir));
					}
					return false;
				}
			}
			return true;
		}
		/**
		 * ลบไดเรคทอรี่และไฟล์ หรือ ไดเร็คทอรี่ในนั้นทั้งหมด
		 *
		 * @param string $dir ไดเรคทอรี่ที่ต้องการลบ
		 */
		function rmdir($dir) {
			if (is_dir($dir)) {
				$f = opendir($dir);
				while (false !== ($text = readdir($f))) {
					if ($text != '.' && $text != '..') {
						if (is_dir($dir.$text)) {
							$this->rmdir($dir.$text.'/');
							$this->_rmdir($dir.$text.'/');
						} else {
							$this->unlink($dir.$text);
						}
					}
				}
				closedir($f);
				$this->_rmdir($dir);
			}
		}
		/**
		 * get ftp path
		 *
		 * @param string $file ไฟล์ path
		 * @return string คืนค่า ไฟล์ path ของ FTP
		 */
		function ftp_file($file) {
			list($a, $b) = explode($this->ftp_absolute_path, $file);
			return $this->ftp_absolute_path.$b;
		}
	}