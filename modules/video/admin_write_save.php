<?php
	// modules/video/admin_write_save.php
	header("content-type: text/html; charset=UTF-8");
	// inint
	include '../../bin/inint.php';
	$ret = array();
	// referer, member
	if (gcms::isReferer() && gcms::canConfig($config, 'video_can_write')) {
		if (isset($_SESSION['login']['account']) && $_SESSION['login']['account'] == 'demo') {
			$ret['error'] = 'EX_MODE_ERROR';
		} else {
			// ค่าที่ส่งมา
			$id = gcms::getVars($_POST, 'write_id', 0);
			$youtube = trim(gcms::getVars($_POST, 'write_youtube', ''));
			$topic = $db->sql_trim_str($_POST, 'write_topic');
			$description = $db->sql_trim($_POST, 'write_description');
			// ตรวจสอบรายการและโมดูลที่เลือก
			if ($id > 0) {
				$sql = "SELECT C.`id`,C.`module_id`,M.`module`";
				$sql .= " FROM `".DB_MODULES."` AS M";
				$sql .= " INNER JOIN `".DB_VIDEO."` AS C ON C.`module_id`=M.`id` AND C.`id`=$id";
			} else {
				$sql = "SELECT M.`id` AS `module_id`,M.`module` FROM `".DB_MODULES."` AS M";
			}
			$sql .= " WHERE M.`owner`='video' LIMIT 1";
			$index = $db->customQuery($sql);
			if (sizeof($index) == 0) {
				$ret['error'] = 'ACTION_ERROR';
			} elseif (!preg_match('/[a-zA-Z0-9\-_]{11,11}/', $youtube)) {
				$ret['ret_write_youtube'] = 'YOUTUBE_INVALID';
				$ret['error'] = 'YOUTUBE_INVALID';
				$ret['input'] = 'write_youtube';
			} else {
				$index = $index[0];
				// ตรวจสอบ video ซ้ำ
				$sql = "SELECT `id` FROM `".DB_VIDEO."`";
				$sql .= " WHERE `youtube`='$youtube' AND `id`!='$id' AND `module_id`='$index[module_id]'";
				$sql .= " LIMIT 1";
				$search = $db->customQuery($sql);
				if (sizeof($search) > 0) {
					$ret['ret_write_youtube'] = 'VIDEO_EXISTS';
					$ret['error'] = 'VIDEO_EXISTS';
					$ret['input'] = 'write_youtube';
				} else {
					// get video info
					$url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id='.$youtube.'&key='.gcms::getVars($config, 'google_api_key', '');
					if (function_exists('curl_init') && $ch = @curl_init()) {
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$feed = curl_exec($ch);
						curl_close($ch);
					} else {
						$feed = file_get_contents($url);
					}
					if ($feed == '') {
						$ret['error'] = 'VIDEO_SERVER_ERROR';
					} else {
						$datas = json_decode($feed);
						if (isset($datas->{'error'})) {
							$ret['alert'] = $datas->{'error'}->{'message'};
						} else {
							$items = $datas->{'items'};
							if (sizeof($items) == 0) {
								$ret['ret_write_youtube'] = 'VIDEO_NOT_FOUND';
								$ret['error'] = 'VIDEO_NOT_FOUND';
								$ret['input'] = 'write_youtube';
							} else {
								$item = $items[0]->{'snippet'};
								$save['topic'] = addslashes(trim($item->{'title'}));
								$save['description'] = addslashes(trim($item->{'description'}));
								$save['views'] = (int)$items[0]->{'statistics'}->{'viewCount'};
								// video thumbnail
								if (isset($item->{'thumbnails'}->{'standard'})) {
									$url = $item->{'thumbnails'}->{'standard'}->{'url'};
								} else {
									$url = $item->{'thumbnails'}->{'high'}->{'url'};
								}
								if (function_exists('curl_init') && $ch = @curl_init()) {
									curl_setopt($ch, CURLOPT_URL, $url);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
									$thumbnail = curl_exec($ch);
									curl_close($ch);
								} else {
									$thumbnail = file_get_contents($url);
								}
								// ตรวจสอบโฟลเดอร์
								gcms::testDir(DATA_PATH.'video/');
								$f = @fopen(DATA_PATH."video/$youtube.jpg", 'w');
								if (!$f) {
									$ret['error'] = 'DO_NOT_UPLOAD';
								} else {
									fwrite($f, $thumbnail);
									fclose($f);
									$ret['imgIcon'] = rawurlencode(DATA_URL."video/$youtube.jpg?$mmktime");
									$save['youtube'] = $youtube;
									$save['last_update'] = $mmktime;
									if ($id == 0) {
										$save['module_id'] = $index['module_id'];
										$id = $db->add(DB_VIDEO, $save);
									} else {
										$db->edit(DB_VIDEO, $index['id'], $save);
									}
									// คืนค่า
									$ret['write_topic'] = rawurlencode(stripslashes($save['topic']));
									$ret['write_description'] = rawurlencode(stripslashes(str_replace(array('\r', '\n'), array("\r", "\n"), $save['description'])));
									$ret['write_id'] = $id;
									$ret['error'] = 'SAVE_COMPLETE';
								}
							}
						}
					}
				}
			}
		}
	} else {
		$ret['error'] = 'ACTION_ERROR';
	}
	// คืนค่าเป็น JSON
	echo gcms::array2json($ret);
