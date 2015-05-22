<?php
	/**
	 * bin/class.gcms.php
	 * สงวนลิขสิทธ์ ห้ามซื้อขาย ให้นำไปใช้ได้ฟรีเท่านั้น
	 *
	 * @copyright http://www.goragod.com
	 * @author กรกฎ วิริยะ
	 * @version 21-05-58
	 */
	mb_internal_encoding('utf-8');
	date_default_timezone_set('Asia/Bangkok');
	/**
	 * @var int $mmktime เวลาปัจจุบันในรูป mktime
	 * @var int $myear ปี คศ. ปัจจุบัน
	 * @var int $mmonth เดือนนี้
	 * @var int $mtoday วันนี้
	 */
	$mmktime = mktime(date("H") + $config['hour']);
	$myear = (int)date('Y', $mmktime);
	$mmonth = (int)date('m', $mmktime);
	$mtoday = (int)date('d', $mmktime);
	/**
	 * GCMS Frameworks Class
	 */
	class gcms {
		/**
		 * ฟังก์ชั่น แปลงเวลา (mktime) เป็นวันที่ตามรูปแบบที่กำหนด
		 *
		 * @global array $lng ตัวแปรภาษา
		 * @param int $mmktime เวลาในรูป mktime
		 * @param string $format (optional) รูปแบบของวันที่ที่ต้องการ (default DATE_FORMAT)
		 * @return string วันที่และเวลาตามรูปแบบที่กำหนดโดย $format
		 */
		public static function mktime2date($mmktime, $format = '') {
			global $lng;
			if (preg_match_all('/(.)/u', $format == '' ? $lng['DATE_FORMAT'] : $format, $match)) {
				$ret = '';
				foreach ($match[0] AS $item) {
					switch ($item) {
						case 'l':
							$ret .= $lng['DATE_SHORT'][date('w', $mmktime)];
							break;
						case 'L':
							$ret .= $lng['DATE_LONG'][date('w', $mmktime)];
							break;
						case 'M':
							$ret .= $lng['MONTH_SHORT'][date('n', $mmktime) - 1];
							break;
						case 'F':
							$ret .= $lng['MONTH_LONG'][date('n', $mmktime) - 1];
							break;
						case 'Y':
							$ret .= date('Y', $mmktime) + $lng['YEAR_OFFSET'];
							break;
						default:
							$ret .= date($item, $mmktime);
					}
				}
				return $ret;
			} else {
				return $format == false ? $lng['DATE_FORMAT'] : $format;
			}
		}
		/**
		 * ฟังก์ชั่น สุ่มตัวอักษร
		 *
		 * @param int $count จำนวนหลักที่ต้องการ
		 * @param string $chars (optional) ตัวอักษรที่ใช้ในการสุ่ม default abcdefghjkmnpqrstuvwxyz
		 * @return string คืนค่าข้อความ
		 */
		public static function rndname($count, $chars = 'abcdefghjkmnpqrstuvwxyz') {
			srand((double)microtime() * 10000000);
			$ret = "";
			$num = strlen($chars);
			for ($i = 0; $i < $count; $i++) {
				$ret .= $chars[rand() % $num];
			}
			return $ret;
		}
		/**
		 * ฟังก์ชั่น ตัดสตริงค์ตามความยาวที่กำหนด
		 * หากข้อความที่นำมาตัดยาวกว่าที่กำหนด จะตัดข้อความที่เกินออก และเติม .. ข้างท้าย
		 *
		 * @param string $str ข้อความที่ต้องการตัด
		 * @param int $len ความยาวของข้อความที่ต้องการ  (จำนวนตัวอักษรรวมจุด)
		 * @return string คืนค่าข้อความ
		 */
		public static function cutstring($str, $len) {
			$len = (int)$len;
			if ($len == 0) {
				return $str;
			} else {
				return (mb_strlen($str) <= $len || $len < 3) ? $str : mb_substr($str, 0, $len - 2)."..";
			}
		}
		/**
		 * ฟังก์ชั่น อ่าน mimetype จาก file type แบบ ออนไลน์
		 *
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @param array $typies ชนิดของไฟล์ที่ต้องการอ่าน mimetype เช่น jpg gif png
		 * @return array คืนค่า mimetype ที่พบ เช่น 'php'=>'text/html'
		 */
		public static function getMimeTypies($typies) {
			global $config;
			$s = array();
			$es = array();
			if (is_array($config['mimeTypes'])) {
				foreach ($typies AS $ext) {
					if (!empty($config['mimeTypes'][$ext])) {
						$s[$ext] = $config['mimeTypes'][$ext];
					} else {
						$es[] = $ext;
					}
				}
			} else {
				$es = $typies;
			}
			if (sizeof($es) > 0) {
				$content = '';
				if (is_file(DATA_PATH.'cache/mime.types')) {
					$content = trim(@file_get_contents(DATA_PATH.'cache/mime.types'));
				}
				if ($content == '') {
					// ตรวจสอบ mimetype ออนไลน์
					$content = trim(@file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'));
					if ($content != '') {
						// cache
						$f = @fopen(DATA_PATH.'cache/mime.types', 'wb');
						if ($f) {
							fwrite($f, $content);
							fclose($f);
						}
					}
				}
				if ($content != '') {
					foreach (explode("\n", $content) AS $x) {
						if (isset($x[0]) && $x[0] !== '#' && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = sizeof($out[1])) > 1) {
							for ($i = 1; $i < $c; $i++) {
								if (in_array($out[1][$i], $typies)) {
									$s[$out[1][$i]] = $out[1][0];
								}
							}
						}
					}
				}
			}
			return $s;
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบ mimetype ที่ต้องการ
		 *
		 * @param array $typies ชนิดของไฟล์ที่ยอมรับ เช่น jpg gif png
		 * @param string $mime ชนิดของไฟล์ที่ต้องการตรวจสอบ เช่น image/png ซึ่งปกติจะได้จากการอัปโหลด เช่น $file[mime]
		 * @return boolean  คืนค่า true ถ้าพบ $mime ใน $typies
		 */
		public static function checkMIMEType($typies, $mime) {
			global $config;
			foreach ($typies AS $t) {
				if ($mime == $config['mimeTypes'][$t]) {
					return true;
				}
			}
			return false;
		}
		/**
		 * ฟังก์ชั่น อ่าน mimetype ของไฟล์ สำหรับส่งให้ input ชนิด file
		 *
		 * @param array $typies ชนิดของไฟล์ เช่น jpg gif png
		 * @return string คืนค่า mimetype ของไฟล์ คั่นแต่ละรายการด้วย , เช่น image/jpeg,image/png,image/gif
		 */
		public static function getEccept($typies) {
			global $config;
			$accept = array();
			foreach ($typies AS $ext) {
				if (isset($config['mimeTypes'][$ext])) {
					$accept[] = $config['mimeTypes'][$ext];
				}
			}
			return implode(',', $accept);
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบไฟล์อัปโหลดว่าเป็นรูปภาพหรือไม่
		 *
		 * @param array $typies ชนิดของไฟล์รูปภาพ ได้แก่ jpg gif png
		 * @param array $files ตัวแปรที่ได้จาก $_FILES
		 * @return array|boolean คืนค่าแอเรย์ [width, height, mime] ของรูปภาพ หรือ  false ถ้าไม่ใช่รูปภาพ
		 */
		public static function isValidImage($typies, $files) {
			// ext
			$imageinfo = explode('.', $files['name']);
			$imageinfo = array('ext' => strtolower(end($imageinfo)));
			if (!in_array($imageinfo['ext'], $typies)) {
				return false;
			} else {
				// Exif
				$info = getImageSize($files['tmp_name']);
				if ($info[0] == 0 || $info[1] == 0 || !gcms::checkMIMEType($typies, $info['mime'])) {
					return false;
				} else {
					$imageinfo['width'] = $info[0];
					$imageinfo['height'] = $info[1];
					$imageinfo['mime'] = $info['mime'];
					return $imageinfo;
				}
			}
		}
		/**
		 * ฟังก์ชั่น อ่านข้อมูลรูปภาพ
		 *
		 * @param string $img path ของไฟล์รูปภาพ
		 * @return array [width, height, mime] ของรูปภาพ
		 */
		public static function imageInfo($img) {
			// Exif
			$info = getImageSize($img);
			$imageinfo['width'] = $info[0];
			$imageinfo['height'] = $info[1];
			$imageinfo['mime'] = $info['mime'];
			return $imageinfo;
		}
		/**
		 * ฟังก์ชั่น ตัดรูปภาพ ตามขนาดที่กำหนด
		 * รูปภาพปลายทางจะมีขนาดเท่าที่กำหนด หากรูปภาพต้นฉบับมีขนาดหรืออัตราส่วนไม่พอดีกับขนาดของภาพปลายทาง
		 * รูปภาพจะถูกตัดขอบออกหรือจะถูกขยาย เพื่อให้พอดีกับรูปภาพปลายทางที่ต้องการ
		 *
		 * @param string $source path และชื่อไฟล์ของไฟล์รูปภาพต้นฉบับ
		 * @param string $target path และชื่อไฟล์ของไฟล์รูปภาพปลายทาง
		 * @param array $info [width, height, mime] ของรูปภาพ
		 * @param int $thumbwidth ความกว้างของรูปภาพที่ต้องการ
		 * @param int $thumbheight ความสูงของรูปภาพที่ต้องการ
		 * @param string $watermark (optional) ข้อความลายน้ำ
		 * @return boolean สำเร็จคืนค่า true
		 */
		public static function cropImage($source, $target, $info, $thumbwidth, $thumbheight, $watermark = '') {
			switch ($info['mime']) {
				case 'image/gif':
					$o_im = imageCreateFromGIF($source);
					break;
				case 'image/jpg':
				case 'image/jpeg':
				case 'image/pjpeg':
					$o_im = gcms::orientImage($source);
					break;
				case 'image/png':
				case 'image/x-png':
					$o_im = imageCreateFromPNG($source);
					break;
				default:
					return false;
			}
			$wm = $info['width'] / $thumbwidth;
			$hm = $info['height'] / $thumbheight;
			$h_height = $thumbheight / 2;
			$w_height = $thumbwidth / 2;
			$t_im = ImageCreateTrueColor($thumbwidth, $thumbheight);
			$int_width = 0;
			$int_height = 0;
			$adjusted_width = $thumbwidth;
			$adjusted_height = $thumbheight;
			if ($info['width'] > $info['height']) {
				$adjusted_width = ceil($info['width'] / $hm);
				$half_width = $adjusted_width / 2;
				$int_width = $half_width - $w_height;
				if ($adjusted_width < $thumbwidth) {
					$adjusted_height = ceil($info['height'] / $wm);
					$half_height = $adjusted_height / 2;
					$int_height = $half_height - $h_height;
					$adjusted_width = $thumbwidth;
					$int_width = 0;
				}
			} elseif (($info['width'] < $info['height']) || ($info['width'] == $info['height'])) {
				$adjusted_height = ceil($info['height'] / $wm);
				$half_height = $adjusted_height / 2;
				$int_height = $half_height - $h_height;
				if ($adjusted_height < $thumbheight) {
					$adjusted_width = ceil($info['width'] / $hm);
					$half_width = $adjusted_width / 2;
					$int_width = $half_width - $w_height;
					$adjusted_height = $thumbheight;
					$int_height = 0;
				}
			}
			ImageCopyResampled($t_im, $o_im, -$int_width, -$int_height, 0, 0, $adjusted_width, $adjusted_height, $info['width'], $info['height']);
			if ($watermark != '') {
				$t_im = gcms::watermarkText($t_im, $watermark);
			}
			$ret = @ImageJPEG($t_im, $target);
			imageDestroy($o_im);
			imageDestroy($t_im);
			return $ret;
		}
		/**
		 * ฟังก์ชั่นปรับขนาดของภาพ โดยรักษาอัตราส่วนของภาพตามความกว้างที่ต้องการ
		 * หากรูปภาพมีขนาดเล็กกว่าที่กำหนด จะเป็นการ copy file
		 * หากรูปภาพมาความสูง หรือความกว้างมากกว่า $width
		 * จะถูกปรับขนาดให้มีขนาดไม่เกิน $width (ทั้งความสูงและความกว้าง)
		 * และเปลี่ยนชนิดของภาพเป็น jpg
		 *
		 * @param string $source path และชื่อไฟล์ของไฟล์รูปภาพต้นฉบับ
		 * @param string $target path ของไฟล์รูปภาพปลายทาง
		 * @param string $name ชื่อไฟล์ของรูปภาพปลายทาง
		 * @param array $info [width, height, mime] ของรูปภาพ
		 * @param int $width ขนาดสูงสุดของรูปภาพที่ต้องการ
		 * @param string $watermark (optional) ข้อความลายน้ำ
		 * @return array|boolean คืนค่าแอเรย์ [name, width, height, mime] ของรูปภาพปลายทาง หรือ false ถ้าไม่สามารถดำเนินการได้
		 */
		public static function resizeImage($source, $target, $name, $info, $width, $watermark = '') {
			if ($info['width'] > $width || $info['height'] > $width) {
				if ($info['width'] <= $info['height']) {
					$h = $width;
					$w = round($h * $info['width'] / $info['height']);
				} else {
					$w = $width;
					$h = round($w * $info['height'] / $info['width']);
				}
				switch ($info['mime']) {
					case 'image/gif':
						$o_im = imageCreateFromGIF($source);
						break;
					case 'image/jpg':
					case 'image/jpeg':
					case 'image/pjpeg':
						$o_im = gcms::orientImage($source);
						break;
					case 'image/png':
					case 'image/x-png':
						$o_im = imageCreateFromPNG($source);
						break;
				}
				$o_wd = @imagesx($o_im);
				$o_ht = @imagesy($o_im);
				$t_im = @ImageCreateTrueColor($w, $h);
				@ImageCopyResampled($t_im, $o_im, 0, 0, 0, 0, $w + 1, $h + 1, $o_wd, $o_ht);
				if ($watermark != '') {
					$t_im = gcms::watermarkText($t_im, $watermark);
				}
				$newname = substr($name, 0, strrpos($name, '.')).'.jpg';
				if (!@ImageJPEG($t_im, $target.$newname)) {
					$ret = false;
				} else {
					$ret['name'] = $newname;
					$ret['width'] = $w;
					$ret['height'] = $h;
					$ret['mime'] = 'image/jpeg';
				}
				@imageDestroy($o_im);
				@imageDestroy($t_im);
				return $ret;
			} elseif (@copy($source, $target.$name)) {
				$ret['name'] = $name;
				$ret['width'] = $info['width'];
				$ret['height'] = $info['height'];
				$ret['mime'] = $info['mime'];
				return $ret;
			}
			return false;
		}
		/**
		 * ฟังก์ชั่น โหลดภาพ jpg และหมุนภาพอัตโนมัติจากข้อมูลของ Exif
		 *
		 * @param resource $source resource ของรูปภาพต้นฉบับ
		 * @return resource คืนค่า resource ของรูปภาพหลังจากหมุนแล้ว ถ้าไม่สนับสนุนคืนค่า resource เดิม
		 */
		public static function orientImage($source) {
			$imgsrc = imageCreateFromJPEG($source);
			if (function_exists('exif_read_data')) {
				// read image exif and rotate
				$exif = exif_read_data($source);
				if (!isset($exif['Orientation'])) {
					return $imgsrc;
				} elseif ($exif['Orientation'] == 2) {
					// horizontal flip
					$imgsrc = gcms::flipImage($imgsrc);
				} elseif ($exif['Orientation'] == 3) {
					// 180 rotate left
					$imgsrc = imagerotate($imgsrc, 180, 0);
				} elseif ($exif['Orientation'] == 4) {
					// vertical flip
					$imgsrc = gcms::flipImage($imgsrc);
				} elseif ($exif['Orientation'] == 5) {
					// vertical flip + 90 rotate right
					$imgsrc = imagerotate($imgsrc, 270, 0);
					$imgsrc = gcms::flipImage($imgsrc);
				} elseif ($exif['Orientation'] == 6) {
					// 90 rotate right
					$imgsrc = imagerotate($imgsrc, 270, 0);
				} elseif ($exif['Orientation'] == 7) {
					// horizontal flip + 90 rotate right
					$imgsrc = imagerotate($imgsrc, 90, 0);
					$imgsrc = gcms::flipImage($imgsrc);
				} elseif ($exif['Orientation'] == 8) {
					// 90 rotate left
					$imgsrc = imagerotate($imgsrc, 90, 0);
				}
			}
			return $imgsrc;
		}
		/**
		 * ฟังก์ชั่น พลิกรูปภาพ (ซ้าย-ขวา คล้ายกระจกเงา)
		 *
		 * @param resource $imgsrc resource ของรูปภาพต้นฉบับ
		 * @return resource คืนค่า resource ของรูปภาพหลังจากพลิกรูปภาพแล้ว ไม่สำเร็จคืนค่า resource ของรูปภาพต้นฉบับ
		 */
		public static function flipImage($imgsrc) {
			$width = imagesx($imgsrc);
			$height = imagesy($imgsrc);
			$src_x = $width - 1;
			$src_y = 0;
			$src_width = -$width;
			$src_height = $height;
			$imgdest = imagecreatetruecolor($width, $height);
			if (imagecopyresampled($imgdest, $imgsrc, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height)) {
				return $imgdest;
			}
			return $imgsrc;
		}
		/**
		 * ฟังก์ชั่น วาดลายน้ำที่เป็นตัวอักษรลงบนรูปภาพ
		 *
		 * @param resource $imgsrc resource ของรูปภาพต้นฉบับ
		 * @param string $text ข้อความที่จะใช้เป็นลายน้ำ
		 * @param string $pos (optional) ตำแหน่งของลายน้ำเช่น center top bottom right left (default 'top left')
		 * @param string $color (optional) สีของตัวอักษร เป็น hex เท่านั้น ไม่ต้องมี # (default CCCCCC)
		 * @param int $font_size (optional) ขนาดตัวอักษรของลายน้ำเป็นพิกเซล (default 20px)
		 * @param int $opacity (optional) กำหนดค่าตัวอักษรโปร่งใส 0-50 (default 50)
		 * @return resource ของรูปภาพต้นฉบับ
		 */
		public static function watermarkText($imgsrc, $text, $pos = '', $color = 'CCCCCC', $font_size = 20, $opacity = 50) {
			$font = ROOT_PATH.'skin/fonts/leelawad.ttf';
			$offset = 5;
			$alpha_color = imagecolorallocatealpha($imgsrc, hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)), 127 * (100 - $opacity) / 100);
			$box = imagettfbbox($font_size, 0, $font, $text);
			if (preg_match('/center/i', $pos)) {
				$y = $box[1] + (imagesy($imgsrc) / 2) - ($box[5] / 2);
			} elseif (preg_match('/bottom/i', $pos)) {
				$y = imagesy($imgsrc) - $offset;
			} else {
				$y = $box[1] - $box[5] + $offset;
			}
			if (preg_match('/center/i', $pos)) {
				$x = $box[0] + (imagesx($imgsrc) / 2) - ($box[4] / 2);
			} elseif (preg_match('/right/i', $pos)) {
				$x = $box[0] - $box[4] + imagesx($imgsrc) - $offset;
			} else {
				$x = $offset;
			}
			imagettftext($imgsrc, $font_size, 0, $x, $y, $alpha_color, $font, $text);
			return $imgsrc;
		}
		/**
		 * ฟังก์ชั่น แปลงข้อความภาษาไทยเป็น HTML เช่น ก แปลงเป้น &#3585;
		 *
		 * @param string $utf8 ข้อความต้นฉบับ
		 * @param boolean $encodeTags true แปลงข้อความภาษาอังกฤษด้วย
		 * @return string คืนค่าข้อความ
		 */
		public static function text2HTML($utf8, $encodeTags) {
			$result = '';
			for ($i = 0; $i < strlen($utf8); $i++) {
				$char = $utf8[$i];
				$ascii = ord($char);
				if ($ascii < 128) {
					// one-byte character
					$result .= $encodeTags ? htmlentities($char) : $char;
				} else if ($ascii < 192) {
					// non-utf8 character or not a start byte
				} else if ($ascii < 224) {
					// two-byte character
					$result .= htmlentities(substr($utf8, $i, 2), ENT_QUOTES, 'UTF-8');
					$i++;
				} else if ($ascii < 240) {
					// three-byte character
					$ascii1 = ord($utf8[$i + 1]);
					$ascii2 = ord($utf8[$i + 2]);
					$unicode = (15 & $ascii) * 4096 + (63 & $ascii1) * 64 + (63 & $ascii2);
					$result .= "&#$unicode;";
					$i += 2;
				} else if ($ascii < 248) {
					// four-byte character
					$ascii1 = ord($utf8[$i + 1]);
					$ascii2 = ord($utf8[$i + 2]);
					$ascii3 = ord($utf8[$i + 3]);
					$unicode = (15 & $ascii) * 262144 + (63 & $ascii1) * 4096 + (63 & $ascii2) * 64 + (63 & $ascii3);
					$result .= "&#$unicode;";
					$i += 3;
				}
			}
			return $result;
		}
		/**
		 * ฟังก์ชั่นส่งเมล์จากแม่แบบจดหมาย
		 *
		 * @global resource $db database resource
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @global int $mmktime เวลาปัจจุบัน (mktime)
		 * @param int $id ID ของจดหมายที่ต้องการส่ง
		 * @param string $module ชื่อโมดูลของจดหมายที่ต้องการส่ง
		 * @param array $datas ข้อมูลที่จะถูกแทนที่ลงในจดหมาย ในรูป 'ตัวแปร'=>'ข้อความ'
		 * @param string $to ที่อยู่อีเมล์ผู้รับ  คั่นแต่ละรายชื่อด้วย ,
		 * @return string สำเร็จคืนค่าว่าง ไม่สำเร็จ คืนค่าข้อความผิดพลาด
		 */
		public static function sendMail($id, $module, $datas, $to) {
			global $db, $config, $mmktime;
			$sql = "SELECT * FROM `".DB_EMAIL_TEMPLATE."`";
			$sql .= " WHERE `module`='$module' AND `email_id`='$id' AND `language` IN ('".LANGUAGE."','th')";
			$sql .= " LIMIT 1";
			$email = $db->customQuery($sql);
			if (sizeof($email) == 0) {
				return 'Error : email template not found.';
			} else {
				$email = $email[0];
				// ข้อความในอีเมล์
				$replace = array();
				$replace['/%WEBTITLE%/'] = strip_tags($config['web_title']);
				$replace['/%WEBURL%/'] = WEB_URL;
				$replace['/%EMAIL%/'] = $to;
				$replace['/%ADMINEMAIL%/'] = empty($email['from_email']) ? $config['noreply_email'] : $email['from_email'];
				$replace['/%TIME%/'] = gcms::mktime2date($mmktime);
				$replace = array_merge($replace, $datas);
				$patt = array_keys($replace);
				$replace = array_values($replace);
				$msg = preg_replace($patt, $replace, $email['detail']);
				$subject = preg_replace($patt, $replace, $email['subject']);
				// ส่งอีเมล์
				return gcms::customMail($to.(!empty($email['copy_to']) ? ",$email[copy_to]" : ''), $email['from_email'], $subject, $msg);
			}
		}
		/**
		 * ฟังก์ชั่นส่งเมล์แบบกำหนดรายละเอียดเอง
		 *
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @param string $mailto ที่อยู่อีเมล์ผู้รับ  คั่นแต่ละรายชื่อด้วย ,
		 * @param string $replyto ที่อยู่อีเมล์สำหรับการตอบกลับจดหมาย ถ้าระบุเป็นค่าว่างจะใช้ที่อยู่อีเมล์จาก noreply_email
		 * @param string $subject หัวข้อจดหมาย
		 * @param string $msg รายละเอียดของจดหมาย (รองรับ HTML)
		 * @return string สำเร็จคืนค่าว่าง ไม่สำเร็จ คืนค่าข้อความผิดพลาด
		 */
		public static function customMail($mailto, $replyto, $subject, $msg) {
			global $config;
			$charset = empty($config['email_charset']) ? 'utf-8' : $config['email_charset'];
			if ($replyto == '') {
				$replyto = array($config['noreply_email'], strip_tags($config['web_title']));
			} elseif (preg_match('/^(.*)<(.*?)>$/', $replyto, $match)) {
				$replyto = array($match[1], (empty($match[2]) ? $match[1] : $match[2]));
			} else {
				$replyto = array($replyto, $replyto);
			}
			if (strtolower($charset) !== 'utf-8') {
				$subject = iconv('utf-8', $config['email_charset'], $subject);
				$msg = iconv('utf-8', $config['email_charset'], $msg);
				$replyto[1] = iconv('utf-8', $config['email_charset'], $replyto[1]);
			}
			if (isset($config['email_use_phpMailer']) && $config['email_use_phpMailer'] !== 1) {
				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/html; charset=$charset\r\n";
				$headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
				$headers .= "To: $mailto\r\n";
				$headers .= "From: $replyto[1]\r\n";
				$headers .= "Reply-to: $replyto[0]\r\n";
				$headers .= "X-Mailer: PHP mailer\r\n";
				if (function_exists('imap_8bit')) {
					$subject = "=?$charset?Q?".imap_8bit($subject)."?=";
					$msg = imap_8bit($msg);
				}
				if (@mail($mailto, $subject, $msg, $headers)) {
					return '';
				} else {
					return 'Send Mail Error.';
				}
			} else {
				include_once (str_replace('class.gcms.php', 'class.phpmailer.php', __FILE__));
				$mail = new PHPMailer(true);
				// use SMTP
				$mail->IsSMTP();
				$mail->Encoding = "quoted-printable";
				// charset
				$mail->CharSet = $charset;
				// use html
				$mail->IsHTML();
				if ($config['email_SMTPAuth'] == 1) {
					$mail->SMTPAuth = true;
					$mail->Username = $config['email_Username'];
					$mail->Password = $config['email_Password'];
					$mail->SMTPSecure = $config['email_SMTPSecure'];
				} else {
					$mail->SMTPAuth = false;
				}
				if (!empty($config['email_Host'])) {
					$mail->Host = $config['email_Host'];
				}
				if (!empty($config['email_Port'])) {
					$mail->Port = $config['email_Port'];
				}
				try {
					$mail->AddReplyTo($replyto[0], $replyto[1]);
					foreach (explode(',', $mailto) AS $email) {
						if (preg_match('/^(.*)<(.*)>$/', $email, $match)) {
							if ($mail->ValidateAddress($match[1])) {
								$mail->AddAddress($match[1], $match[2]);
							}
						} else {
							if ($mail->ValidateAddress($email)) {
								$mail->AddAddress($email, $email);
							}
						}
					}
					$mail->SetFrom($config['noreply_email'], $replyto[1]);
					$mail->Subject = $subject;
					$mail->MsgHTML(preg_replace('/(<br([\s\/]{0,})>)/', "$1\r\n", stripslashes($msg)));
					$mail->Send();
					return '';
				} catch (phpmailerException $e) {
					// Pretty error messages from PHPMailer
					return strip_tags($e->errorMessage());
				} catch (exception $e) {
					// Boring error messages from anything else!
					return strip_tags($e->getMessage());
				}
			}
		}
		/**
		 * ฟังก์ชั่น เข้ารหัสข้อความ
		 *
		 * @param string $string ข้อความที่ต้องการเข้ารหัส
		 * @return string ข้อความที่เข้ารหัสแล้ว
		 */
		public static function encode($string) {
			$en_key = (string)EN_KEY;
			$j = 0;
			for ($i = 0; $i < mb_strlen($string); $i++) {
				$string[$i] = $string[$i] ^ $en_key[$j];
				if ($j < (mb_strlen($en_key) - 1)) {
					$j++;
				} else {
					$j = 0;
				}
			}
			return base64_encode($string);
		}
		/**
		 * ฟังก์ชั่น ถอดรหัสข้อความ
		 *
		 * @param string $string ข้อความที่เข้ารหัสจาก gcms::encode()
		 * @return string ข้อความที่ถอดรหัสแล้ว
		 */
		public static function decode($string) {
			$en_key = (string)EN_KEY;
			$encode = base64_decode($string);
			$j = 0;
			for ($i = 0; $i < mb_strlen($encode); $i++) {
				$encode[$i] = $en_key[$j] ^ $encode[$i];
				if ($j < (mb_strlen($en_key) - 1)) {
					$j++;
				} else {
					$j = 0;
				}
			}
			return $encode;
		}
		/**
		 * ฟังก์ชั่น อ่าน ip ของ client
		 *
		 * @return string IP ที่อ่านได้
		 */
		public static function getip() {
			if (isset($_SERVER)) {
				if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
					$realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
				} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
					$realip = $_SERVER["HTTP_CLIENT_IP"];
				} else {
					$realip = $_SERVER["REMOTE_ADDR"];
				}
			} else {
				if (getenv('HTTP_X_FORWARDED_FOR')) {
					$realip = getenv('HTTP_X_FORWARDED_FOR');
				} elseif (getenv('HTTP_CLIENT_IP')) {
					$realip = getenv('HTTP_CLIENT_IP');
				} else {
					$realip = getenv('REMOTE_ADDR');
				}
			}
			return $realip;
		}
		/**
		 * ฟังก์ชั่น แสดง ip แบบซ่อนหลักหลัง
		 *
		 * @param string $ip ที่อยู่ IP ที่ต้องการแปลง (IPV4)
		 * @return string ที่อยู่ IP ที่แปลงแล้ว
		 */
		public static function showip($ip) {
			preg_match('/([0-9]+\.[0-9]+\.)([0-9\.]+)/', $ip, $ips);
			return $ips[1].preg_replace('/[0-9]/', 'x', $ips[2]);
		}
		/**
		 * ฟังก์ชั่น preg_replace ของ gcms
		 *
		 * @param array $patt คีย์ใน template
		 * @param array $replace ข้อความที่จะถูกแทนที่ลงในคีย์
		 * @param string $skin template
		 * @return string คืนค่า HTML template
		 */
		public static function pregReplace($patt, $replace, $skin) {
			if (OLD_PHP) {
				return preg_replace($patt, $replace, $skin);
			} else {
				if (!is_array($patt)) {
					$patt = array($patt);
				}
				if (!is_array($replace)) {
					$replace = array($replace);
				}
				foreach ($patt AS $i => $item) {
					if (strpos($item, '/e') === FALSE) {
						$skin = preg_replace($item, $replace[$i], $skin);
					} else {
						$skin = preg_replace_callback(str_replace('/e', '/', $item), $replace[$i], $skin);
					}
				}
				return $skin;
			}
		}
		/**
		 * ฟังก์ชั่น HTML highlighter
		 * ทำ highlight ข้อความส่วนที่เป็นโค้ด
		 * จัดการแปลง BBCode
		 * แปลงข้อความ http เป็นลิงค์
		 *
		 * @param string $text ข้อความ
		 * @param boolean $canview true จะแสดงข้อความเตือน 'ยังไม่ได้เข้าระบบ' หากไม่ได้เข้าระบบ สำหรับส่วนที่อยู่ในกรอบ code
		 * @return string คืนค่าข้อความ
		 */
		public static function htmlhighlighter($text, $canview) {
			$patt[] = '/\[(\/)?(i|dfn|b|strong|u|em|ins|del|sub|sup|small|big|ul|ol|li)\]/isu';
			$replace[] = '<\\1\\2>';
			$patt[] = '/\[color=([#a-z0-9]+)\]/isu';
			$replace[] = '<span style="color:\\1">';
			$patt[] = '/\[size=([0-9]+)(px|pt|em|\%)\]/isu';
			$replace[] = '<span style="font-size:\\1\\2">';
			$patt[] = '/\[\/(color|size)\]/isu';
			$replace[] = '</span>';
			$patt[] = '/\[url\](.*)\[\/url\]/U';
			$replace[] = '<a href="\\1" target="_blank" rel="nofollow">\\1</a>';
			$patt[] = '/\[url=(ftp|http)(s)?:\/\/(.*)\](.*)\[\/url\]/U';
			$replace[] = '<a href="\\1\\2://\\3" target="_blank" rel="nofollow">\\4</a>';
			$patt[] = '/\[url=(\/)?(.*)\](.*)\[\/url\]/U';
			$replace[] = '<a href="'.WEB_URL.'/\\2" target="_blank" rel="nofollow">\\3</a>';
			$patt[] = '/(\[code=([a-z]{1,})\](.*?)\[\/code\])/uis';
			$replace[] = $canview ? '<code class="content-code \\2">\\3[/code]' : '<code class="content-code">{LNG_NOT_LOGIN}[/code]';
			$patt[] = '/(\[code\](.*?)\[\/code\])/uis';
			$replace[] = $canview ? '<code class="content-code">\\2[/code]' : '<code class="content-code">{LNG_NOT_LOGIN}[/code]';
			$patt[] = '/\[\/code\]/usi';
			$replace[] = '</code>';
			$patt[] = '/\[\/quote\]/usi';
			$replace[] = '</blockquote>';
			$patt[] = '/\[quote( q=[0-9]+)?\]/usi';
			$replace[] = '<blockquote><b>{LNG_Q_QUOTE}</b>';
			$patt[] = '/\[quote r=([0-9]+)\]/usi';
			$replace[] = '<blockquote><b>{LNG_R_QUOTE} <em>#\\1</em></b>';
			$patt[] = '/\[google\](.*?)\[\/google\]/usi';
			$replace[] = '<a class="googlesearch" href="http://www.google.co.th/search?q=\\1&amp;&meta=lr%3Dlang_th" target="_blank" rel="nofollow">\\1</a>';
			$patt[] = '/([^["]]|\r|\n|\s|\t|^)(https?:\/\/([^\s<>\"\']+))/';
			$replace[] = '\\1<a href="\\2" target="_blank" rel="nofollow">\\2</a>';
			$patt[] = '/\[WEBURL\]/isu';
			$replace[] = WEB_URL;
			$patt[] = '/\[youtube\]([a-z0-9-_]+)\[\/youtube\]/i';
			$replace[] = '<div class="youtube"><iframe src="//www.youtube.com/embed/\\1?wmode=transparent"></iframe></div>';
			return preg_replace($patt, $replace, $text);
		}
		/**
		 * ฟังก์ชั่น แปลง html เป็น text
		 * สำหรับตัด tag หรือเอา BBCode ออกจากเนื้อหาที่เป็น HTML ให้เหลือแต่ข้อความล้วน
		 *
		 * @param string $text ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function html2txt($text) {
			$patt = array();
			$replace = array();
			// ตัด style
			$patt[] = '@<style[^>]*?>.*?</style>@siu';
			$replace[] = '';
			// ตัด comment
			$patt[] = '@<![\s\S]*?--[ \t\n\r]*>@u';
			$replace[] = '';
			// ตัด tag
			$patt[] = '@<[\/\!]*?[^<>]*?>@iu';
			$replace[] = '';
			// ตัด keywords
			$patt[] = '/{(WIDGET|LNG)_[a-zA-Z0-9_]+}/su';
			$replace[] = '';
			// ลบ BBCode
			$patt[] = '/(\[code(.+)?\]|\[\/code\]|\[ex(.+)?\])/ui';
			$replace[] = '';
			// ลบ BBCode ทั่วไป [b],[i]
			$patt[] = '/\[([a-z]+)([\s=].*)?\](.*?)\[\/\\1\]/ui';
			$replace[] = '\\3';
			$replace[] = ' ';
			// ตัดตัวอักษรที่ไม่ต้องการออก
			$patt[] = '/(&amp;|&quot;|&nbsp;|[_\(\)\-\+\r\n\s\"\'<>\.\/\\\?&\{\}]){1,}/isu';
			$replace[] = ' ';
			return trim(preg_replace($patt, $replace, $text));
		}
		/**
		 * ฟังก์ชั่นแทนที่คำหยาบ
		 *
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @param string $text ข้อความ
		 * @return string คืนค่าข้อความที่ แปลงคำหยาบให้เป็น <em>xxx</em>
		 */
		public static function CheckRude($text) {
			global $config;
			if (is_array($config['wordrude'])) {
				return preg_replace("/(".implode('|', $config['wordrude']).")/usi", '<em>'.$config['wordrude_replace'].'</em>', $text);
			} else {
				return $text;
			}
		}
		/**
		 * ฟังก์ชั่นแสดงเนื้อหา
		 *
		 * @param string $detail ข้อความ
		 * @param boolean $canview true จะแสดงข้อความเตือน 'ยังไม่ได้เข้าระบบ' หากไม่ได้เข้าระบบ สำหรับส่วนที่อยู่ในกรอบ code
		 * @param [boolean] $rude (optional) true=ตรวจสอบคำหยาบด้วย (default true)
		 * @param [boolean] $txt (optional) true=เปลี่ยน tab เป็นช่องว่าง 4 ตัวอักษร (default false)
		 * @return string คืนค่าข้อความ
		 */
		public static function showDetail($detail, $canview, $rude = true, $txt = false) {
			if ($txt) {
				$detail = preg_replace('/[\t]/', '&nbsp;&nbsp;&nbsp;&nbsp;', $detail);
			}
			if ($rude) {
				return gcms::htmlhighlighter(gcms::CheckRude($detail), $canview);
			} else {
				return gcms::htmlhighlighter($detail, $canview);
			}
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบการ login
		 *
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @global resource $db database resource
		 * @global int $mmktime เวลาปัจจุบัน
		 * @global int $mtoday วันที่วันนี้
		 * @param string $email อีเมล์ที่ต้องการตรวจสอบ
		 * @param string $password รหัสผ่านที่ต้องการตรวจสอบ
		 * @return array|int ข้อมูลของสมาชิก หาก emailและ password ถูกต้อง หรือ 0 ไม่พบอีเมล์, 1 ยังไม่ได้ activate, 2 ติดแบน, 3 รหัสผ่านผิด, 4 login ต่าง ip กัน
		 */
		public static function CheckLogin($email, $password) {
			global $config, $db, $mmktime, $mtoday;
			if ($email == '') {
				// ไม่กรอก email มา
				return 0;
			} elseif (isset($config['demo_mode']) && $config['demo_mode'] === 1 && $email == 'demo' && $password == 'demo') {
				$login_result = array();
				$login_result['id'] = 0;
				$login_result['email'] = 'demo';
				$login_result['password'] = 'demo';
				$login_result['displayname'] = 'demo';
				$login_result['pname'] = '';
				$login_result['fname'] = '';
				$login_result['lname'] = '';
				$login_result['status'] = 1;
				$login_result['admin_access'] = 1;
				$login_result['account'] = 'demo';
				return $login_result;
			} else {
				$userupdate = false;
				$login_result = false;
				if ($config['member_login_phone'] == 1) {
					$sql = "SELECT * FROM `".DB_USER."` WHERE `email`='$email' OR `phone1`='$email'";
				} else {
					$sql = "SELECT * FROM `".DB_USER."` WHERE `email`='$email'";
				}
				foreach ($db->customQuery($sql) AS $item) {
					if ($item['password'] == md5($password.$item['email'])) {
						$login_result = $item;
						break;
					}
				}
				if (!$login_result) {
					// ไม่พบ email คืนค่า 0
					// รหัสผ่านผิด คืนค่า 3
					return isset($item) && is_array($item) ? 3 : 0;
				} elseif (trim($login_result['activatecode']) != '') {
					// ยังไม่ได้ activate
					return 1;
				} else {
					// ตรวจสอบการแบน
					if ($login_result['ban_date'] > 0 && $mmktime > $login_result['ban_date'] + ($login_result['ban_count'] * 86400)) {
						// ครบกำหนดการแบนแล้ว เคลียร์การแบน
						$login_result['ban_date'] = 0;
						$login_result['ban_count'] = 0;
						$userupdate = true;
					}
					if ($login_result['ban_date'] > 0) {
						// ติดแบน
						return 2;
					} else {
						$session_id = session_id();
						// ตรวจสอบการ login มากกว่า 1 ip
						$ip = gcms::getip();
						if ($config['member_only_ip'] == 1 && $ip != '') {
							$sql = "SELECT * FROM `".DB_USERONLINE."`";
							$sql .= " WHERE `member_id`='$login_result[id]' AND `ip`!='$ip' AND `ip`!=''";
							$sql .= " ORDER BY `time` DESC LIMIT 1";
							$online = $db->customQuery($sql);
							if (sizeof($online) == 1 && $mmktime - $online[0]['time'] < COUNTER_GAP) {
								// login ต่าง ip กัน
								return 4;
							}
						}
						// อัปเดทการเยี่ยมชม
						if ($session_id != $login_result['session_id']) {
							$login_result['visited']++;
							$userupdate = true;
						}
						if ($userupdate) {
							$db->edit(DB_USER, $login_result['id'], array('session_id' => $session_id, 'visited' => $login_result['visited'], 'lastvisited' => $mmktime, 'ip' => $ip));
						}
						return $login_result;
					}
				}
			}
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบการ login
		 *
		 * @return boolean คืนค่า true ถ้า Login อยู่
		 */
		public static function isMember() {
			return isset($_SESSION['login']);
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบสถานะแอดมิน (สูงสุด)
		 *
		 * @return boolean คืนค่า true ถ้าเป็นแอดมินระดับสูงสุด
		 */
		public static function isAdmin() {
			return isset($_SESSION['login']) && $_SESSION['login']['status'] == 1;
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบสถานะที่กำหนด
		 *
		 * @param array $cfg ตัวแปรแอเรย์ที่มีคีย์ที่ต้องการตรวจสอบเช่น $config
		 * @param string $key คีย์ของ $cfg ที่ต้องการตรวจสอบ
		 * @return boolean คืนค่า true ถ้าสมาชิกที่ login มีสถานะที่กำหนดอยู่ใน $cfg[$key]
		 */
		public static function canConfig($cfg, $key) {
			if (isset($_SESSION['login']['status'])) {
				$status = $_SESSION['login']['status'];
				if ($status == 1) {
					return true;
				} elseif (isset($cfg[$key])) {
					if (is_array($cfg[$key])) {
						return in_array($status, $cfg[$key]);
					} else {
						return in_array($status, explode(',', $cfg[$key]));
					}
				}
			} else {
				return false;
			}
		}
		/**
		 * ฟังชั่นคืนค่ารูปแบบ url ที่ใช้ได้บนเว็บไซต์
		 *
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @param string $module ชื่อโมดูล
		 * @param string $document alias ของบทความ (โมดูล document เท่านั้น) (default ค่าว่าง)
		 * @param int $catid id ของหมวดหมู่ (default 0)
		 * @param int $id id ของข้อมูล (default 0)
		 * @param string $query query string อื่นๆ (default ค่าว่าง)
		 * @param [boolean] $encode true=เข้ารหัสด้วย rawurlencode ด้วย (default true)
		 * @return string คืนค่า full URL
		 */
		public static function getURL($module, $document = '', $catid = 0, $id = 0, $query = '', $encode = true) {
			$urls = array();
			$patt = array();
			$replace = array();
			global $config;
			$urls['0'] = 'index.php?module={module}-{document}&amp;cat={catid}&amp;id={id}';
			$urls['1'] = '{module}/{catid}/{id}/{document}.html';
			if ($document == '') {
				$patt[] = '/[\/-]{document}/u';
				$replace[] = '';
			} else {
				$patt[] = '/{document}/u';
				$replace[] = $encode ? rawurlencode($document) : $document;
			}
			$patt[] = '/{module}/u';
			$replace[] = $encode ? rawurlencode($module) : $module;
			if ($catid == 0) {
				$patt[] = '/((cat={catid}&amp;)|([\/-]{catid}))/u';
				$replace[] = '';
			} else {
				$patt[] = '/{catid}/u';
				$replace[] = (int)$catid;
			}
			if ((int)$id == 0) {
				$patt[] = '/(((&amp;|\?)id={id})|([\/-]{id}))/u';
				$replace[] = '';
			} else {
				$patt[] = '/{id}/u';
				$replace[] = (int)$id;
			}
			$link = preg_replace($patt, $replace, $urls[$config['module_url']]);
			if ($query != '') {
				$link = preg_match('/[\?]/u', $link) ? $link.'&amp;'.$query : $link.'?'.$query;
			}
			return WEB_URL.'/'.$link;
		}
		/**
		 * ฟังก์ชั่น โหลด widget
		 *
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @global array $lng ตัวแปรภาษา
		 * @global resource $db database resource
		 * @global resource $cache cache resource
		 * @global int $mmktime เวลาปัจจุบัน (mktime)
		 * @global array $install_modules แอเรย์รายการโมดูลที่ติดตั้งแล้ว
		 * @global array $install_owners แอเรย์รายการโฟลเดอร์ใน modules
		 * @global array $module_list แอเรย์รายชื่อโมดูลที่ติดตั้งแล้ว
		 * @param array $matches ตัวแปร array ที่ได้จากการอ่าน widget จาก template
		 * @return string คืนค่าโค้ด HTML ของ widget
		 */
		public static function getWidgets($matches) {
			global $config, $lng, $db, $cache, $mmktime, $install_modules, $install_owners, $module_list;
			$owner = strtolower($matches[1]);
			$module = isset($matches[4]) ? $matches[4] : '';
			if (isset($matches[3]) && $matches[3] == ' ') {
				foreach (explode(';', $module) AS $item) {
					list($key, $value) = explode('=', $item);
					$$key = $value;
				}
			}
			if (is_file(ROOT_PATH."widgets/$owner/index.php")) {
				$widget = array();
				// load widget
				include ROOT_PATH."widgets/$owner/index.php";
				return $widget;
			}
			return '';
		}
		/**
		 * ฟังก์ชั่น อ่านภาษา
		 *
		 * @global array $lng ตัวแปรภาษา
		 * @param array $matches  ตัวแปร array ที่ได้จากการอ่านจาก template
		 * @return string คืนค่าข้อความ $lng[$matches[1]]
		 */
		public static function getLng($matches) {
			global $lng;
			return empty($lng[$matches[1]]) ? '' : $lng[$matches[1]];
		}
		/**
		 * ฟังก์ชั่น แปลง array เป็น json
		 *
		 * @param array $array แอเรย์ของข้อมูล
		 * @return string คืนค่าข้อความที่เป็น JSON หรือ คืนค่าว่างหาก $array ว่างเปล่า
		 */
		public static function array2json($array) {
			if (is_array($array) && count($array) > 0) {
				$ret = array();
				foreach ($array AS $key => $value) {
					$ret[] = $key."':'".$value;
				}
				return "[{'".implode("','", $ret)."'}]";
			} else {
				return '';
			}
		}
		/**
		 *  ฟังก์ชั่น แปลงข้อความสำหรับการ quote
		 *
		 * @param string $text ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function sql_trim_str_decode($text) {
			return str_replace('&#92;', '\\', htmlspecialchars_decode($text));
		}
		/**
		 * ฟังก์ชั่น แปลงข้อความสำหรับการ quote
		 *
		 * @param string $text ข้อความ
		 * @param [boolean] $u true=ถอดรหัสอักขระพิเศษด้วย (default false)
		 * @return string คืนค่าข้อความ
		 */
		public static function txtQuote($text, $u = false) {
			$text = preg_replace('/<br(\s\/)?>/isu', '', $text);
			if ($u) {
				$text = str_replace(array('&lt;', '&gt;', '&#92;', '&nbsp;'), array('<', '>', '\\', ' '), $text);
			}
			return $text;
		}
		/**
		 * ฟังก์ชั่น ตัดข้อความที่ไม่พึงประสงค์ก่อนบันทึกลง db ที่มาจาก textarea
		 *
		 * @param string $text ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function txtClean($text) {
			$patt = array();
			$replace = array();
			$patt[] = '/</u';
			$replace[] = '&lt;';
			$patt[] = '/>/u';
			$replace[] = '&gt;';
			$patt[] = '/\\\\\\\\/u';
			$replace[] = '&#92;';
			$text = nl2br(preg_replace($patt, $replace, $text));
			return defined('DATABASE_DRIVER') ? stripslashes($text) : $text;
		}
		/**
		 * ฟังก์ชั่น ตัดข้อความที่ไม่พึงประสงค์ก่อนที่มาจาก ckeditor
		 *
		 * @param string $text ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function ckClean($text) {
			$patt = array();
			$replace = array();
			$patt[] = "/<\?(.*?)\?>/su";
			$replace[] = '';
			$patt[] = '@<script[^>]*?>.*?</script>@siu';
			$replace[] = '';
			$patt[] = '@<style[^>]*?>.*?</style>@siu';
			$replace[] = '';
			$patt[] = '/\\\\\\\\/u';
			$replace[] = '&#92;';
			$patt[] = '/^[\r\n\s]{0,}<br \/>[\r\n\s]{0,}$/';
			$replace[] = '';
			$text = preg_replace($patt, $replace, $text);
			return defined('DATABASE_DRIVER') ? stripslashes($text) : $text;
		}
		/**
		 * ฟังก์ชั่น ตัดข้อความที่ไม่พึงประสงค์ก่อนบันทึกลง db ที่มาจาก ckeditor
		 *
		 * @global resource $db database resource
		 * @param string $text ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function ckDetail($text) {
			global $db;
			$patt = array();
			$replace = array();
			$patt[] = '/^(&nbsp;|\s){0,}<br[\s\/]+?>(&nbsp;|\s){0,}$/iu';
			$replace[] = '';
			$patt[] = '/<\?(.*?)\?>/su';
			$replace[] = '';
			$patt[] = '/\\\\\\\\/u';
			$replace[] = '&#92;';
			return $db->sql_clean(preg_replace($patt, $replace, $text));
		}
		/**
		 * ฟังก์ชั่น เข้ารหัส อักขระพิเศษ และ {} ก่อนจะส่งให้กับ CKEditor
		 *
		 * @param mixed $array array หรือ string
		 * @param string $key (optional) key ของ $array  เช่น $array[$key]
		 * @return string คืนค่าข้อความ
		 */
		public static function detail2TXT($array, $key = null) {
			if ($key === null) {
				$value = $array;
			} elseif (isset($array[$key])) {
				$value = $array[$key];
			} else {
				$value = '';
			}
			return $value == '' ? '' : str_replace(array('{', '}'), array('&#x007B;', '&#x007D;'), htmlspecialchars($value));
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบ referer
		 *
		 * @return boolean คืนค่า true ถ้า referer มาจากเว็บไซต์นี้
		 */
		public static function isReferer() {
			$server = empty($_SERVER["HTTP_HOST"]) ? $_SERVER["SERVER_NAME"] : $_SERVER["HTTP_HOST"];
			$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			if (preg_match("/$server/ui", $referer)) {
				return true;
			} elseif (preg_match('/^(http(s)?:\/\/)(.*)(\/.*){0,}$/U', WEB_URL, $match)) {
				return preg_match("/$match[3]/ui", $referer);
			} else {
				return false;
			}
		}
		/**
		 * ฟังก์ชั่น อ่าน config จากข้อมูล
		 *
		 * @param string $data ข้อมูลที่ต้องการอ่าน รูปแบบ key=value คั่นแต่ละรายการด้วย \n
		 * @param pointer $config ตัวแปรแอเรย์ที่ต้องการรับค่าหลังจากอ่านข้อมูลแล้ว
		 * @param boolean $replace false=เก็บข้อมูลก่อนหน้าไว้ หากมีข้อมูลอยู่ก่อนแล้ว,true=แทนที่ด้วยข้อมูลใหม่ (default=true)
		 */
		public static function r2config($data, &$config, $replace = true) {
			foreach (explode("\n", $data) As $item) {
				if ($item != '') {
					if (preg_match('/^(.*)=(.*)$/U', $item, $match)) {
						if ($replace || !isset($config[$match[1]])) {
							$config[$match[1]] = trim($match[2]);
						}
					}
				}
			}
		}
		/**
		 * ฟังก์ชั่น เรียงลำดับ array ตามชื่อฟิลด์
		 *
		 * @param pointer $array แอเรย์ที่ต้องการเรียงลำดับ
		 * @param string $subkey (optional) คืย์ของ $array ที่ต้องการในการเรียง (default id)
		 * @param boolean $sort_desc true=เรียงจากมากไปหาน้อย, false=เรียงจากน้อยไปหามาก (default false)
		 */
		public static function sortby(&$array, $subkey = 'id', $sort_desc = false) {
			if (count($array)) {
				$temp_array[key($array)] = array_shift($array);
			}
			foreach ($array AS $key => $val) {
				$offset = 0;
				$found = false;
				foreach ($temp_array AS $tmp_key => $tmp_val) {
					if (!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
						$temp_array = array_merge((array)array_slice($temp_array, 0, $offset), array($key => $val), array_slice($temp_array, $offset));
						$found = true;
					}
					$offset++;
				}
				if (!$found) {
					$temp_array = array_merge($temp_array, array($key => $val));
				}
			}
			if ($sort_desc) {
				$array = array_reverse($temp_array);
			} else {
				$array = $temp_array;
			}
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบไดเร็คทอรี่ว่าเขียนได้หรือไม่
		 *
		 * @global resource $ftp FTP resource
		 * @param string $dir โฟลเดอร์+path ที่ต้องการทดสอบ
		 * @return boolean คืนค่า true ถ้าเขียนได้
		 */
		public static function testDir($dir) {
			global $ftp;
			return $ftp->mkdir($dir);
		}
		/**
		 * ฟังก์ชั่น ลบ directory (และไฟล์หรือไดเรคทอรี่ในนั้นทั้งหมด)
		 *
		 * @global resource $ftp FTP resource
		 * @param string $dir full path ไดเร็คทอรี่ที่ต้องการลบ
		 * @return boolean คืนค่า true ถ้าสำเร็จ
		 */
		public static function rm_dir($dir) {
			global $ftp;
			return $ftp->rmdir($dir);
		}
		/**
		 *  ฟังก์ชั่น แปลงอักขระ HTML กลับเป็นตัวอักษร สำหรับใส่ใน textarea
		 *
		 * @param string $value ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function unhtmlentities($value) {
			$patt = array('/&amp;/', '/&#39;/', '/&quot;/', '/&nbsp;/');
			$replace = array('&', "'", '"', ' ');
			return preg_replace($patt, $replace, $value);
		}
		/**
		 * ฟังก์ชั่น โหลด template ของโมดูลที่เลือก
		 *
		 * @param string $module ชื่อโมดูล
		 * @param string $owner ชื่อโฟลเดอร์ ตามใน modules/
		 * @param string $file ชื่อไฟล์ ไม่ต้องระบุนามสกุล html
		 * @return string คืนค่า template จาก $module ถ้าไม่มีจะโหลดจาก $owner ถ้าไม่พบคืนค่าว่าง
		 */
		public static function loadtemplate($module, $owner, $file) {
			$template = is_file(ROOT_PATH.SKIN."$module/$file.html") ? $module : $owner;
			return gcms::loadfile(ROOT_PATH.SKIN.str_replace('//', '/', "$template/$file.html"));
		}
		/**
		 * ฟังก์ชั่น โหลดไฟล์ ตัด \t และ \r ออก
		 *
		 * @param string $file ชื่อไฟล์รวม path
		 * @return string คืนค่าข้อมูลในไฟล์ ถ้าไม่พบคืนค่าว่าง
		 */
		public static function loadfile($file) {
			return is_file($file) ? preg_replace('/[\t\r]/', '', file_get_contents($file)) : '';
		}
		/**
		 * ฟังก์ชั่น อ่าน info ของ theme
		 *
		 * @param string $theme ชื่อไฟล์ css ของ theme รวม full path
		 * @return array คืนค่า แอเรย์ข้อมูลส่วน header ของ css
		 */
		public static function parse_theme($theme) {
			$result = array();
			if (is_file($theme) && preg_match('/^[\s]{0,}\/\*(.*?)\*\//is', file_get_contents($theme), $match)) {
				if (preg_match_all('/([a-zA-Z]+)[\s:]{0,}(.*)?[\r\n]+/i', $match[1], $datas)) {
					foreach ($datas[1] AS $i => $v) {
						$result[strtolower($v)] = $datas[2][$i];
					}
				}
			}
			return $result;
		}
		/**
		 * ฟังก์ชั่น highlight ข้อความค้นหา
		 *
		 * @param string $text ข้อความ
		 * @param string $search ข้อความค้นหา แยกแต่ละคำด้วย ,
		 * @return string คืนค่าข้อความ
		 */
		public static function HighlightSearch($text, $search) {
			foreach (explode(' ', $search) AS $i => $q) {
				if ($q != '') {
					$text = gcms::doHighlight($text, $q);
				}
			}
			return $text;
		}
		/**
		 * ฟังก์ชั่น ทำ highlight ข้อความ
		 *
		 * @param string $text ข้อความ
		 * @param string $needle ข้อความที่ต้องการทำ highlight
		 * @return string คืนค่าข้อความ ข้อความที่ highlight จะอยู่ภายใต้ tag mark
		 */
		public static function doHighlight($text, $needle) {
			$newtext = '';
			$i = -1;
			$len_needle = mb_strlen($needle);
			while (mb_strlen($text) > 0) {
				$i = mb_stripos($text, $needle, $i + 1);
				if ($i == false) {
					$newtext .= $text;
					$text = '';
				} else {
					$a = gcms::lastIndexOf($text, '>', $i) >= gcms::lastIndexOf($text, '<', $i);
					$a = $a && (gcms::lastIndexOf($text, '}', $i) >= gcms::lastIndexOf($text, '{LNG_', $i));
					$a = $a && (gcms::lastIndexOf($text, '/script>', $i) >= gcms::lastIndexOf($text, '<script', $i));
					$a = $a && (gcms::lastIndexOf($text, '/style>', $i) >= gcms::lastIndexOf($text, '<style', $i));
					if ($a) {
						$newtext .= mb_substr($text, 0, $i).'<mark>'.mb_substr($text, $i, $len_needle).'</mark>';
						$text = mb_substr($text, $i + $len_needle);
						$i = -1;
					}
				}
			}
			return $newtext;
		}
		/**
		 * ฟังก์ชั่น ค้นหาข้อความย้อนหลัง
		 *
		 * @param string $text ข้อความ
		 * @param string $needle ข้อความค้นหา
		 * @param int $offset ตำแหน่งเริ่มต้นที่ต้องการค้นหา
		 * @return int คืนค่าตำแหน่งของตัวอักษรที่พบ ตัวแรกคือ 0 หากไม่พบคืนค่า -1
		 */
		public static function lastIndexOf($text, $needle, $offset) {
			$pos = mb_strripos(mb_substr($text, 0, $offset), $needle);
			return $pos == false ? -1 : $pos;
		}
		/**
		 * ฟังก์ชั่น อ่านสถานะของสมาชิกเป็นข้อความ
		 *
		 * @global array $lng ตัวแปรภาษา
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @param mixed $status สถานะของสมาชิกที่ต้องการ
		 * @return string คืนค่าข้อความ
		 */
		public static function id2status($status) {
			global $lng, $config;
			$status = is_array($status) ? $status : explode(',', $status);
			$ds = array();
			foreach ($status AS $item) {
				if ($item == -1) {
					$ds[] = '<span class="status">'.$lng['LNG_GUEST'].'</span>';
				} else {
					$ds[] = '<span class="status'.$item.'">'.$config['member_status'][$item].'</span>';
				}
			}
			return implode(',', $ds);
		}
		/**
		 * ฟังก์ชั่น บันทึกไฟล์ config
		 *
		 * @param string $file path ของไฟล์ตั้งแต่ root
		 * @param array $config ตัวแปรเก็บการตั้งค่าที่ต้องการบันทึก
		 * @return boolean คืนค่า true หากสำเร็จ
		 */
		public static function saveConfig($file, $config) {
			if (!is_array($config) || sizeof($config) == 0) {
				return false;
			} else {
				$datas = array();
				$datas[] = '<'.'?php';
				$datas[] = '// '.str_replace(ROOT_PATH, '', $file);
				foreach ($config AS $key => $value) {
					if (is_array($value)) {
						foreach ($value AS $k => $v) {
							if (is_array($v)) {
								foreach ($v AS $k2 => $v2) {
									$datas[] = '$config[\''.$key.'\'][\''.$k.'\'][\''.$k2.'\'] = \''.$v2.'\';';
								}
							} else {
								$datas[] = '$config[\''.$key.'\'][\''.$k.'\'] = \''.$v.'\';';
							}
						}
					} elseif (is_int($value)) {
						$datas[] = '$config[\''.$key.'\'] = '.$value.';';
					} else {
						$datas[] = '$config[\''.$key.'\'] = \''.$value.'\';';
					}
				}
				$f = @fopen($file, 'wb');
				if (!$f) {
					return false;
				} else {
					fwrite($f, implode("\n\t", $datas));
					fclose($f);
					return true;
				}
			}
		}
		/**
		 * ฟังก์ชั่น แปลงขนาดของไฟล์จาก byte เป็น kb mb
		 *
		 * @param int $bytes ขนาดของไฟล์ เป็น byte
		 * @param int $precision (optional) จำนวนหลักหลังจุดทศนิยม (default 2)
		 * @return string คืนค่าขนาดของไฟล์เป็น KB MB
		 */
		public static function formatFileSize($bytes, $precision = 2) {
			$units = array('Bytes', 'KB', 'MB', 'GB', 'TB');
			if ($bytes <= 0) {
				return '0 Byte';
			} else {
				$bytes = max($bytes, 0);
				$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
				$pow = min($pow, count($units) - 1);
				$bytes /= pow(1024, $pow);
				return round($bytes, $precision).' '.$units[$pow];
			}
		}
		/**
		 * ฟังก์ชั่น increment
		 *
		 * @param int $number ค่าเริ่มต้น
		 * @param int $value (optional) ตัวเลขที่บวกเพิ่ม (default 1)
		 * @return int คืนค่า $number+$value
		 */
		public static function inc(&$number, $value = 1) {
			$number += $value;
			return $number;
		}
		/**
		 * ฟังก์ชั่น decrement
		 *
		 * @param int $number
		 * @param int $value (optional) ตัวเลขที่ลดลง (default 1)
		 * @return int คืนค่า $number-$value
		 */
		public static function dec(&$number, $value = 1) {
			$number -= $value;
			return $number;
		}
		/**
		 * ฟังก์ชั่น install database
		 *
		 * @global resource $db database resource
		 * @global array $content ตัวแปรเก็บข้อมูลแสดงผล
		 * @global array $defines ตัวแปรสำหรับ define()
		 * @global array $config ตัวแปรเก็บการตั้งค่าของ GCMS
		 * @global string $q
		 * @param string $sql ไฟล์ sql ที่ต้องการติดตั้ง (full path)
		 * @param string $owner (optional) โฟลเดอร์ของโมดูลที่ติดตั้ง
		 */
		public static function install($sql, $owner = '') {
			global $db, $content, $defines, $config, $q;
			// โหลดฐานข้อมูลของโมดูล
			$fr = file($sql);
			foreach ($fr AS $value) {
				$sql = str_replace(array('{prefix}', '{owner}', '/{WEBMASTER}/', '\r', '\n'), array(PREFIX, $owner, $_SESSION['login']['email'], "\r", "\n"), trim($value));
				if ($sql != '') {
					if (preg_match('/^<\?.*\?>$/', $sql)) {
						// php code
					} elseif (preg_match('/^define\([\'"]([A-Z0-9_]+)[\'"](.*)\);$/', $sql, $match)) {
						if (!defined($match[1])) {
							$defines[$match[1]] = $match[0];
						}
					} elseif (preg_match('/DROP[\s]+TABLE[\s]+(IF[\s]+EXISTS[\s]+)?`?([a-z0-9_]+)`?/iu', $sql, $match)) {
						$ret = $db->query($sql);
						$content[] = '<li class='.($ret === false ? 'in' : '').'valid>DROP TABLE <strong>'.$match[2].'</strong> ...</li>';
					} elseif (preg_match('/CREATE[\s]+TABLE[\s]+(IF[\s]+NOT[\s]+EXISTS[\s]+)?`?([a-z0-9_]+)`?/iu', $sql, $match)) {
						$ret = $db->query($sql);
						$content[] = '<li class='.($ret === false ? 'in' : '').'valid>CREATE TABLE <strong>'.$match[2].'</strong> ...</li>';
					} elseif (preg_match('/ALTER[\s]+TABLE[\s]+`?([a-z0-9_]+)`?[\s]+ADD[\s]+`?([a-z0-9_]+)`?(.*)/iu', $sql, $match)) {
						// add column
						$search = $db->customQuery("SELECT * FROM `information_schema`.`columns` WHERE `table_schema`='$config[db_name]' AND `table_name`='$match[1]' AND `column_name`='$match[2]'");
						if (sizeof($search) == 1) {
							$db->query("ALTER TABLE `$match[1]` DROP COLUMN `$match[2]`");
						}
						$ret = $db->query($sql);
						if (sizeof($search) == 1) {
							$content[] = '<li class='.($ret === false ? 'in' : '').'valid>REPLACE COLUMN <strong>'.$match[2].'</strong> to TABLE <strong>'.$match[1].'</strong></li>';
						} else {
							$content[] = '<li class='.($ret === false ? 'in' : '').'valid>ADD COLUMN <strong>'.$match[2].'</strong> to TABLE <strong>'.$match[1].'</strong></li>';
						}
					} elseif (preg_match('/INSERT[\s]+INTO[\s]+`?([a-z0-9_]+)`?(.*)/iu', $sql, $match)) {
						$ret = $db->query($sql);
						if ($q != $match[1]) {
							$q = $match[1];
							$content[] = '<li class='.($ret === false ? 'in' : '').'valid>INSERT INTO <strong>'.$match[1].'</strong> ...</li>';
						}
					} else {
						$db->query($sql);
					}
				}
			}
		}
		/**
		 * ฟังก์ชั่น คิดตั้ง โมดูลและ เมนู
		 *
		 * @global resource $db database resource
		 * @param string $owner โฟลเดอร์ของโมดูล
		 * @param string $module ชื่อโมดูล
		 * @param string $title (optional) ข้อความไตเติลบาร์ของโมดูล
		 * @param string $menupos (optional) ตำแหน่งของเมนู (MAINMENU,SIDEMENU,BOTTOMMENU)
		 * @param string $menu (optional) ข้อความเมนู
		 * @return int คืนค่า  ID ของโมดูลที่ติดตั้ง
		 */
		public static function installModule($owner, $module, $title = '', $menupos = '', $menu = '') {
			global $db;
			$search = $db->basicSearch(DB_MODULES, 'module', $module);
			if (!$search) {
				$id = $db->add(DB_MODULES, array('owner' => $owner, 'module' => $module));
				if ($title != '') {
					$index = $db->add(DB_INDEX, array('module_id' => $id, 'index' => '1', 'published' => '1'));
					$db->add(DB_INDEX_DETAIL, array('module_id' => $id, 'id' => $index, 'topic' => $title));
				}
				if ($menupos != '' && $menu != '') {
					$db->add(DB_MENUS, array('index_id' => $index, 'parent' => $menupos, 'level' => 0, 'menu_text' => $menu, 'menu_tooltip' => $title));
				}
				return $id;
			} else {
				return $search['id'];
			}
		}
		/**
		 * ฟังก์ชั่น แปลงเป็นรายการเมนู
		 *
		 * @param array $item แอเรย์ข้อมูลเมนู
		 * @param boolean $arrow (optional) true=แสดงลูกศรสำหรับเมนูที่มีเมนูย่อย (default false)
		 * @return string คืนค่า HTML ของเมนู
		 */
		public static function getMenu($item, $arrow = false) {
			$c = array();
			if ($item['alias'] != '') {
				$c[] = $item['alias'];
			} elseif ($item['module'] != '') {
				$c[] = $item['module'];
			}
			if (isset($item['published'])) {
				if ($item['published'] != 1) {
					if (gcms::isMember()) {
						if ($item['published'] == '3') {
							$c[] = 'hidden';
						}
					} else {
						if ($item['published'] == '2') {
							$c[] = 'hidden';
						}
					}
				}
			}
			$c = sizeof($c) == 0 ? '' : ' class="'.implode(' ', $c).'"';
			if ($item['index_id'] > 0 || $item['menu_url'] != '') {
				$a = $item['menu_target'] == '' ? '' : ' target='.$item['menu_target'];
				$a .= $item['accesskey'] == '' ? '' : ' accesskey='.$item['accesskey'];
				if ($item['index_id'] > 0) {
					$a .= ' href="'.gcms::getURL($item['module']).'"';
				} elseif ($item['menu_url'] != '') {
					$a .= ' href="'.$item['menu_url'].'"';
				} else {
					$a .= ' tabindex=0';
				}
			} else {
				$a = ' tabindex=0';
			}
			$b = $item['menu_tooltip'] == '' ? $item['menu_text'] : $item['menu_tooltip'];
			if ($b != '') {
				$a .= ' title="'.$b.'"';
			}
			if ($arrow) {
				return '<li'.$c.'><a class=menu-arrow'.$a.'><span>'.($item['menu_text'] == '' ? '&nbsp;' : htmlspecialchars_decode($item['menu_text'])).'</span></a>';
			} else {
				return '<li'.$c.'><a'.$a.'><span>'.($item['menu_text'] == '' ? '&nbsp;' : htmlspecialchars_decode($item['menu_text'])).'</span></a>';
			}
		}
		/**
		 * ฟังก์ชั่น สร้าง URL สำหรับ admin
		 *
		 * @global array $url_query แอเรย์เก็บค่าที่ส่งมา
		 * @param array $f ตัวแปรที่ส่งมาจาก preg_replace
		 * @return string คืนค่า URL สำหรับหน้าแอดมิน
		 */
		public static function adminURL($f) {
			global $url_query;
			$qs = array();
			foreach ($url_query AS $key => $value) {
				$qs[$key] = "$key=$value";
			}
			if (!empty($f[2])) {
				foreach (explode('&', str_replace('&amp;', '&', $f[2])) AS $item) {
					if (preg_match('/^(.*)=(.*)$/', $item, $match)) {
						$qs[$match[1]] = empty($match[2]) ? $match[1] : "$match[1]=$match[2]";
					}
				}
			}
			return str_replace('&amp;id=0', '', 'index.php'.(sizeof($qs) > 0 ? '?'.implode('&amp;', $qs) : ''));
		}
		/**
		 * ฟังก์ชั่น แปลงตัวเลขเป็นจำนวนเงิน
		 *
		 * @param double $amount จำนวนเงิน
		 * @param string $thousands_sep (optional) เครื่องหมายหลักพัน (default ,)
		 * @return string คืนค่าข้อความจำนวนเงิน
		 */
		public static function int2Curr($amount, $thousands_sep = ',') {
			return number_format((double)$amount, 2, '.', $thousands_sep);
		}
		/**
		 * ฟังก์ชั่น คำนวนความแตกต่างของวัน (อายุ)
		 *
		 * @param int $start_date วันที่เริ่มต้นหรือวันเกิด (mktime)
		 * @param int $end_date วันที่สิ้นสุดหรือวันนี้ (mktime)
		 * @return array คืนค่า ปี เดือน วัน [year, month, day] ที่แตกต่าง
		 */
		public static function dateDiff($start_date, $end_date) {
			$Year1 = (int)date("Y", $start_date);
			$Month1 = (int)date("m", $start_date);
			$Day1 = (int)date("d", $start_date);
			$Year2 = (int)date("Y", $end_date);
			$Month2 = (int)date("m", $end_date);
			$Day2 = (int)date("d", $end_date);
			// วันแต่ละเดือน
			$months = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
			// ปีอธิกสุรทิน
			if (($Year2 % 4) == 0) {
				$months[2] = 29;
			}
			// ปีอธิกสุรทิน
			if ((($Year2 % 100) == 0) & (($Year2 % 400) != 0)) {
				$months[2] = 28;
			}
			// คำนวนจำนวนวันแตกต่าง
			$YearDiff = $Year2 - $Year1;
			if ($Month2 >= $Month1) {
				$MonthDiff = $Month2 - $Month1;
			} else {
				$YearDiff--;
				$MonthDiff = 12 + $Month2 - $Month1;
			}
			if ($Day1 > $months[$Month2]) {
				$Day1 = 0;
			} elseif ($Day1 > $Day2) {
				$Month2 = $Month2 == 1 ? 13 : $Month2;
				$Day2 += $months[$Month2 - 1];
				$MonthDiff--;
			}
			$ret['year'] = $YearDiff;
			$ret['month'] = $MonthDiff;
			$ret['day'] = $Day2 - $Day1;
			return $ret;
		}
		/**
		 * ฟังก์ชั่นตรวจสอบความถูกต้องของอีเมล์
		 *
		 * @param string $email ข้อความที่ต้องการตรวจสอบ
		 * @return boolean คืนค่า true ถ้า $email ถูกต้อง
		 */
		public static function validMail($email) {
			return preg_match('/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i', $email);
		}
		/**
		 * ฟังก์ชั่นตรวจสอบว่าเป็นค่าว่างหรือไม่ ถ้าไม่ว่างเอาใส่ array
		 *
		 * @param string $detail ข้อความที่ต้องการตรวจสอบ
		 * @param pointer $result แอเรย์ของข้อมูล หาก $detail ไม่เป็นค่าว่างจะเอาใส่ลงใน $result
		 */
		public static function checkempty($detail, &$result) {
			if (trim($detail) != '') {
				$result[] = $detail;
			}
		}
		/**
		 * ฟังก์ชั่นบันทึกไฟล์ภาษา
		 *
		 * @global resource $db database resource
		 * @param string $database ชื่อ database ค่า default คือ DB_LANGUAGE
		 * @return array คืนค่าแอเรย์ของรายการภาษาที่พบเช่น [th, en]
		 */
		public static function saveLanguage($database = DB_LANGUAGE) {
			global $db;
			// ภาษาที่ติดตั้งหมด
			$languages = array();
			$save = array();
			$save2 = array();
			$l = array('id', 'key', 'type', 'owner', 'js');
			foreach ($db->customQuery("SHOW FIELDS FROM $database") AS $item) {
				if (!in_array($item['Field'], $l)) {
					$languages[] = $item['Field'];
					$save[$item['Field']][] = '<'.'?php';
				}
			}
			// อ่านภาษาและบันทึกเป็นไฟล์
			$sql = "SELECT * FROM `$database` ORDER BY `key`";
			$p2 = array("'", "\\\\'");
			foreach ($db->customQuery($sql) AS $item) {
				foreach ($languages AS $language) {
					if (!(isset($lng[$language][$item['key']]['js']) && $lng[$language][$item['key']]['js'] == $item['js'])) {
						$value[$language] = preg_replace('/[\r\n]{1,}/isu', '\n', $item[$language]);
						if ($item['js'] == 1) {
							$save2[$language][] = "var $item[key] = '".str_replace($p2, "\'", $value[$language])."';";
						} elseif ($item['type'] == 'array' && $value[$language] != '') {
							$lng[$language][$item['key']] = @unserialize($value[$language]);
							if (is_array($lng[$language][$item['key']])) {
								$save3 = array();
								foreach ($lng[$language][$item['key']] AS $k => $v) {
									if (preg_match('/^[0-9]+$/', $k)) {
										$save3[] = "$k => '".str_replace($p2, "\'", $v).'\'';
									} else {
										$save3[] = "'$k' => '".str_replace($p2, "\'", $v).'\'';
									}
								}
								$save[$language][] = '$lng[\''.$item['key'].'\'] = Array('.implode(', ', $save3).');';
							}
						} elseif ($item['type'] == 'int') {
							$lng[$language][$item['key']] = $value[$language];
							$save[$language][] = '$lng[\''.$item['key'].'\'] = '.str_replace($p2, "\'", $value[$language]).';';
						} else {
							$lng[$language][$item['key']] = $value[$language];
							$save[$language][] = '$lng[\''.$item['key'].'\'] = \''.str_replace($p2, "\'", $value[$language]).'\';';
						}
					}
				}
			}
			// เขียนไฟล์ $language.php,$language.js
			foreach ($languages AS $language) {
				if (sizeof($save[$language]) > 1) {
					$f = fopen(DATA_PATH."language/$language.php", 'wb');
					fwrite($f, implode("\n\t", $save[$language]));
					fclose($f);
				}
				if (isset($lng[$language]['MONTH_SHORT'])) {
					$save2[$language][] = 'Date.monthNames = ["'.implode('","', $lng[$language]['MONTH_SHORT']).'"];';
				}
				if (isset($lng[$language]['DATE_SHORT'])) {
					$save2[$language][] = 'Date.dayNames = ["'.implode('","', $lng[$language]['DATE_SHORT']).'"];';
				}
				if (isset($lng[$language]['YEAR_OFFSET'])) {
					$save2[$language][] = 'Date.yearOffset = '.$lng[$language]['YEAR_OFFSET'].';';
				}
				if (isset($save2[$language]) && sizeof($save2[$language]) > 0) {
					$f = fopen(DATA_PATH."language/$language.js", 'wb');
					fwrite($f, implode("\n", $save2[$language]));
					fclose($f);
				}
			}
			return $languages;
		}
		/**
		 *  ฟังก์ชั่นตรวจสอบข้อความใช้เป็น tags หรือ keywords
		 *  ลบช่องว่างไม่เกิน 1 ช่อง,ไม่ขึ้นบรรทัดใหม่,คั่นแต่ละรายการด้วย ,
		 *
		 * @param string $text ข้อความที่ถูกส่งมาจาก textarea
		 * @return string คืนค่าข้อความ
		 */
		public static function getTags($text) {
			$text = trim(strip_tags($text));
			if ($text == '') {
				return '';
			} else {
				$ds = array();
				foreach (explode(',', $text) AS $item) {
					$item = trim($item);
					if ($item != '') {
						$ds[] = $item;
					}
				}
				return trim(preg_replace('/[_\(\)\-\+\r\n\s\"\'<>\.\/\\\?&\{\}]{1,}/isu', ' ', implode(',', $ds)));
			}
		}
		/**
		 * ฟังก์ชั่นตรวจสอบข้อความ ใช้เป็น alias name ตัวพิมพ์เล็ก แทนช่องว่างด้วย _
		 *
		 * @param string $text ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function aliasName($text) {
			return preg_replace(array('/[_\(\)\-\+\#\r\n\s\"\'<>\.\/\\\?&\{\}]{1,}/isu', '/^(_)?(.*?)(_)?$/'), array('_', '\\2'), strtolower(trim(strip_tags($text))));
		}
		/**
		 * ฟังก์ชั่นสำหรับตรวจสอบความถูกต้องของรหัสบัตรประชาชน
		 *
		 * @param string $id ตัวเลข 13 หลัก
		 * @return boolean คืนค่า true=ถูกต้อง และ false=ไม่ถูกต้อง
		 */
		public static function checkIDCard($id) {
			if (preg_match('/^[0-9]{13,13}$/', $id)) {
				for ($i = 0, $sum = 0; $i < 12; $i++) {
					$sum += (int)($id{$i}) * (13 - $i);
				}
				if ((11 - ($sum % 11)) % 10 == (int)($id{12})) {
					return true;
				}
			}
			return false;
		}
		/**
		 * ฟังก์ชั่น unserialize
		 *
		 * @param mixed $datas ข้อความ serialize
		 * @param string $key (optional) ถ้า $datas เป็น array ต้องระบุ $key ด้วย
		 * @return array คืนค่าแอเรย์ที่ได้จากการทำ serialize
		 */
		public static function ser2Array($datas, $key = '') {
			if (is_array($datas)) {
				if (isset($datas[$key])) {
					$result = trim($datas[$key]);
				} else {
					return array();
				}
			} else {
				$result = trim($datas);
			}
			if ($result != '') {
				$result = @unserialize($result);
			}
			return is_array($result) ? $result : array();
		}
		/**
		 * ฟังก์ชั่น ตรวจสอบและทำ serialize สำหรับภาษา โดยรายการที่มีเพียงภาษาเดียว จะกำหนดให้ไม่มีภาษา
		 *
		 * @param array $array ข้อมูลที่ต้องการจะทำ serialize
		 * @return string คืนค่าข้อความที่ทำ serialize แล้ว
		 */
		public static function array2Ser($array) {
			$new_array = array();
			$l = sizeof($array);
			if ($l > 0) {
				foreach ($array AS $i => $v) {
					if ($l == 1 && $i == 0) {
						$new_array[''] = $v;
					} else {
						$new_array[$i] = $v;
					}
				}
			}
			return serialize($new_array);
		}
		/**
		 * ฟังก์ชั่น อ่านหมวดหมู่ในรูป serialize ตามภาษาที่เลือก
		 *
		 * @param mixed $datas ข้อความ serialize
		 * @param string $key (optional) ถ้า $datas เป็น array ต้องระบุ $key ด้วย
		 * @return string คืนค่าข้อความ
		 */
		public static function ser2Str($datas, $key = '') {
			if (is_array($datas)) {
				if (isset($datas[$key])) {
					$result = $datas[$key];
				} else {
					return '';
				}
			} else {
				$result = $datas;
			}
			if ($result == '') {
				return '';
			} else {
				$result = @unserialize($result);
				return empty($result[LANGUAGE]) ? $result[''] : $result[LANGUAGE];
			}
		}
		/**
		 * ฟังก์ชั่น ลบช่องว่าง และ ตัวอักษรขึ้นบรรทัดใหม่ ที่ติดกันเกินกว่า ๅ ตัว
		 *
		 * @param string $text  ข้อความ
		 * @return string คืนค่าข้อความ
		 */
		public static function oneLine($text) {
			return trim(preg_replace('/[\r\n\t\s]+/', ' ', $text));
		}
		/**
		 * ฟังก์ชั่นสร้าง breadcumb
		 *
		 * @param string $c class สำหรับลิงค์นี้
		 * @param string $url ลิงค์
		 * @param string $tooltip ทูลทิป
		 * @param string $menu ข้อความแสดงใน breadcumb
		 * @param string $skin template ของ breadcumb
		 * @return string คืนค่า breadcumb
		 */
		public static function breadcrumb($c, $url, $tooltip, $menu, $skin) {
			$patt = array('/{CLASS}/', '/{URL}/', '/{TOOLTIP}/', '/{MENU}/');
			return preg_replace($patt, array($c, $url, $tooltip, htmlspecialchars_decode($menu)), $skin);
		}
		/**
		 * ฟังก์ชั่น สร้าง URL สำหรับส่งกลับที่มาจากการโพสต์
		 *
		 * @global int $mmktime เวลาปัจจุบัน (mktime)
		 * @param string $url ลิงค์
		 * @param array $q query string
		 * @return string คืนค่า URL ที่เข้ารหัสแล้ว
		 */
		public static function retURL($url, $q) {
			global $mmktime;
			$ret = array();
			foreach ($_POST AS $k => $v) {
				if ($k == '_spage') {
					$ret['page'] = "page=$v";
				} elseif ($k == '_src') {
					$ret['module'] = "module=$v";
				} elseif (preg_match('/^_([a-z\-]+)$/', $k, $match)) {
					$ret[$match[1]] = "$match[1]=$v";
				}
			}
			if (is_array($q)) {
				foreach ($q AS $k => $v) {
					$ret[$k] = "$k=$v";
				}
			}
			$ret[$mmktime] = $mmktime;
			return rawurlencode($url.(strpos($url, '?') === false ? '?' : '&').implode('&', $ret));
		}
		/**
		 * ฟังก์ชั่นอ่านค่าจากตัวแปร คืนค่า null หากไม่พบ
		 *
		 * @param string $type GET POST REQUEST SESSION COOKIE SERVER
		 * @param string $key key ของตัวแปร $type
		 * @return mixed คืนค่า $type[$key] หรือ null หากไม่พบ
		 */
		private static function filterVars($type, $key) {
			switch ($type) {
				case 'REQUEST':
					if (isset($_POST[$key])) {
						return $_POST[$key];
					} else if (isset($_GET[$key])) {
						return $_GET[$key];
					}
				case 'POST':
					if (isset($_POST[$key])) {
						return $_POST[$key];
					}
				case 'GET':
					if (isset($_GET[$key])) {
						return $_GET[$key];
					}
				case 'SESSION':
					if (isset($_SESSION[$key])) {
						return $_SESSION[$key];
					}
				case 'COOKIE':
					if (isset($_COOKIE[$key])) {
						return $_COOKIE[$key];
					}
				case 'SERVER':
					if (isset($_SERVER[$key])) {
						return $_SERVER[$key];
					}
				case 'config':
					if (isset($config[$key])) {
						return $config[$key];
					}
				default:
					return null;
			}
		}
		/**
		 * ฟังก์ชั่นสำหรับรับค่าจากการโพสต์ แทนการใช้ $_GET[key] หรือ $_POST[key]
		 *
		 * @param mixed $typies array หรือข้อความ GET POST REQUEST SESSION COOKIE SERVER คั่นรายการด้วย ,
		 * @param string $keys key  ของ $typies หรือชื่อ key ที่ต้องการอ่านค่าจากกำหนดใน $typies
		 * @param mixed $default ค่าเริ่มต้นที่ต้องการหากไม่พบตัวแปร $typies[$keys]
		 * @return mixed คืนค่าที่อ่านได้จาก $typies[$keys] โดยมีการแปลงชนิดของตัวแปรตามที่กำหนดโดย $default
		 */
		public static function getVars($typies, $keys, $default) {
			if (is_array($typies)) {
				$value = isset($typies[$keys]) ? $typies[$keys] : null;
			} else {
				$value = null;
				$keys = explode(',', $keys);
				foreach (explode(',', $typies) AS $i => $type) {
					$value = gcms::filterVars($type, $keys[$i]);
					if ($value !== null) {
						break;
					}
				}
			}
			if ($value === null) {
				return $default;
			} elseif (is_float($default)) {
				return (double)$value;
			} elseif (is_int($default)) {
				return (int)$value;
			} else {
				return $value;
			}
		}
		/**
		 * ฟังก์ชั่นสำหรับสร้าง input สำหรับการส่งกลับที่มาจากการโพสต์ เพื่อใส่ลงในฟอร์ม
		 *
		 * @param array $q มาจาก $_GET หรือ $_POST
		 * @param string $ex ตัวแปรที่ไม่ต้องการให้ส่งค่าไปด้วย คั่นแต่ละรายการด้วย,
		 * @return string คืนค่า input สำหรับใส่ลงในฟอร์มได้ทันที
		 */
		public static function get2Input($q, $ex = '') {
			$exs = explode(',', $ex);
			$ret = array();
			if (isset($q)) {
				foreach ($q AS $k => $v) {
					if (!in_array($k, $exs)) {
						$ret[$k] = '<input type=hidden name="_'.$k.'" value="'.$v.'">';
					}
				}
			}
			return implode('', $ret);
		}
	}