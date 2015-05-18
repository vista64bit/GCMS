<?php
	// admin/report.php
	if (MAIN_INIT == 'admin' && $canAdmin) {
		// ค่าที่ส่งมา
		$date = gcms::getVars($_GET, 'date', date('Y-m-d', $mmktime));
		$type = gcms::getVars($_GET, 'type', '');
		$ip = gcms::getVars($_GET, 'ip', '');
		if (preg_match('/^([0-9]+)\-([0-9]+)\-([0-9]+)$/', $date, $match)) {
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		} else {
			$y = $myear;
			$m = $mmonth;
			$d = $mtoday;
			$date = "$y-$m-$d";
		}
		$counter_dat = DATA_PATH.'counter/'.(int)$y.'/'.(int)$m.'/'.(int)$d.'.dat';
		if (is_file($counter_dat)) {
			$datas = array();
			$agents = array();
			$ssid = array();
			$ips = array();
			foreach (file($counter_dat) AS $a => $item) {
				list($sid, $sip, $sref, $sagent, $time) = explode(chr(1), $item);
				if ($ip == '' || $ip == $sip) {
					$ssid[$sid] = empty($ssid[$sid]) ? 1 : $ssid[$sid] + 1;
					$ips[$sip] = empty($ips[$sip]) ? 1 : $ips[$sip] + 1;
					$k = $ip == '' ? ($type == 'ip' ? $sip : $sref) : $a;
					$datas[$k]['ip'] = $sip;
					$datas[$k]['agent'] = $sagent;
					$datas[$k]['referer'] = $sref;
					$datas[$k]['time'] = $time;
					if ($ip == '' && preg_match('/.*(Googlebot|Baiduspider|bingbot|MJ12bot|yahoo).*/isu', $sagent, $match)) {
						$agents[$match[1]] = empty($agents[$match[1]]) ? 1 : $agents[$match[1]] + 1;
					} else {
						$datas[$k]['total'] = empty($datas[$k]['total']) ? 1 : $datas[$k]['total'] + 1;
					}
				}
			}
			if ($ip == '') {
				if ($type == 'ip') {
					// เรียงลำดับตาม ip
					gcms::sortby($datas, 'ip');
				} else {
					// เรียงลำดับตาม referer
					gcms::sortby($datas, 'referer');
				}
			} else {
				// เรียงลำดับตาม time
				gcms::sortby($datas, 'time');
			}
			$graphs['Google Search'] = 0;
			$graphs['Google Cached'] = 0;
			$graphs['Inbound'] = 0;
			$graphs['Direct'] = 0;
			$graphs['Direct'] = 0;
			$graphs['other'] = 0;
			$list = array();
			$total = 0;
			$i = 0;
			$bg = 'bg2';
			foreach ($datas AS $item) {
				$i++;
				if (isset($item['total'])) {
					$total = $total + $item['total'];
				} else {
					$item['total'] = 0;
				}
				if (preg_match('/^(https?.*(www\.)?google(usercontent)?.*)\/.*[\&\?]q=(.*)($|\&.*)/iU', $item['referer'], $match) && $match[4] != '') {
					// จาก google search
					$a = $match[1].'/search?q='.htmlspecialchars($match[4]);
					$text = gcms::cutstring($match[1].'/search?q='.htmlspecialchars(rawurldecode(rawurldecode($match[4]))), 170);
					$name = '<a href="'.$a.'" target=_blank>'.$text.'</a>';
					$graphs['Google Search'] += $item['total'];
				} elseif (preg_match('/^(https?:\/\/(www.)?google[\.a-z]+\/url\?).*&url=(.*)($|\&.*)/iU', $item['referer'], $match) && $match[3] != '') {
					// จาก google cached
					$a = rawurldecode(rawurldecode($match[3]));
					$text = gcms::cutstring($match[1].'url='.htmlspecialchars($a), 170);
					$name = '<a href="'.$a.'" target=_blank>'.$text.'</a>';
					$graphs['Google Cached'] += $item['total'];
				} elseif ($item['referer'] == '') {
					$name = '&nbsp;';
					$graphs['Direct'] += $item['total'];
				} elseif (preg_match('/'.preg_quote(WEB_URL, '/').'/', $item['referer'], $match)) {
					$graphs['Inbound'] += $item['total'];
					$text = gcms::cutstring(htmlspecialchars(rawurldecode(rawurldecode($item['referer']))), 170);
					$name = '<a href="'.htmlspecialchars($item['referer']).'" target=_blank>'.$text.'</a>';
				} else {
					$graphs['other'] += $item['total'];
					$text = gcms::cutstring(htmlspecialchars(rawurldecode(rawurldecode($item['referer']))), 170);
					$name = '<a href="'.htmlspecialchars($item['referer']).'" target=_blank>'.$text.'</a>';
				}
				$bg = $bg == 'bg1' ? 'bg2' : 'bg1';
				$row = '<tr class='.$bg.'><td class="center mobile">'.$i.'</td>';
				if ($ip == '') {
					$row .= '<td><a href="index.php?module=report&amp;date='.$date.'&amp;ip='.$item['ip'].'">'.$item['ip'].'</a></td><td class="center tablet">'.$item['total'].'</td>';
				} else {
					$row .= '<td>'.$item['time'].'</td>';
				}
				$row .= '<td>'.$name.'</td></tr>';
				$list[] = $row;
			}
			// รวม bot
			foreach ($agents AS $a => $b) {
				$total = $total + $b;
			}
			$my_date = $db->sql_date2date($date);
			// title
			$title = sprintf($lng['USERONLINE_REPORT_TITLE'], $my_date);
			$a = array();
			if ($ip == '') {
				$a[] = '<span class=icon-summary>'.$title.'</span>';
			} else {
				$a[] = '<a class=icon-summary href="index.php?module=report&amp;date='.$date.'">'.$title.'</a>';
				$a[] = '{LNG_IP} <a href="http://whatismyipaddress.com/ip/'.$ip.'" target=_blank>'.$ip.'</a>';
			}
			// แสดงผล
			$content[] = '<div class=breadcrumbs><ul><li>'.implode('</li><li>', $a).'</li></ul></div>';
			$content[] = '<section>';
			if ($ip == '') {
				$content[] = '<header><h1 class=icon-stats>{LNG_TOTAL} '.number_format($total).' {LNG_COUNT}  '.number_format(sizeof($ips)).' Uniqe IP  '.number_format(sizeof($ssid)).' Uniqe Session</h1></header>';
			} else {
				$content[] = '<header><h1 class=icon-stats>{LNG_DATE} '.$my_date.'  {LNG_IP} '.$ip.'  {LNG_TOTAL} '.number_format($total).' {LNG_COUNT}</h1></header>';
			}
			// ตารางข้อมูล
			$content[] = '<table id=report class="tbl_list fullwidth">';
			$content[] = '<thead>';
			$content[] = '<tr>';
			$content[] = '<th id=c0 scope=col class=mobile>&nbsp;</th>';
			if ($ip == '') {
				$content[] = '<th id=c1 scope=col><a href="index.php?module=report&amp;date='.$date.'&amp;type=ip">{LNG_IP}</a></th>';
				$content[] = '<th id=c2 scope=col class="center tablet">{LNG_COUNT}</th>';
			} else {
				$content[] = '<th id=c1 scope=col>{LNG_TIME}</th>';
			}
			$content[] = '<th id=c3 scope=col><a href="index.php?module=report&amp;date='.$date.'">{LNG_REFERER}</a></th>';
			$content[] = '</tr>';
			$content[] = '</thead>';
			$content[] = '<tbody>'.implode("\n", $list).'</tbody>';
			$content[] = "</table>";
			if ($ip == '') {
				// graphs
				$content[] = '<div class="ggrid collapse">';
				$content[] = '<div class="block6 float-left">';
				$graphs = array_merge($graphs, $agents);
				$content[] = '<div id=online_graph class=ggraphs>';
				$content[] = '<canvas></canvas>';
				$content[] = '<table class=hidden>';
				$content[] = '<thead><tr><th></th>';
				foreach ($graphs AS $k => $v) {
					$content[] = '<td>'.$k.'</td>';
				}
				$content[] = '</tr></thead>';
				$content[] = '<tbody><tr><th>{LNG_USERONLINE_GRAPH_REPORT}</th>';
				foreach ($graphs AS $k => $v) {
					$content[] = '<td>'.$v.'</td>';
				}
				$content[] = '</tr></tbody>';
				$content[] = '</table>';
				$content[] = '</div>';
				$content[] = '</div>';
				$content[] = '<div class="block6 float-right">';
				$content[] = '<div id=uniqe_graph class=ggraphs>';
				$content[] = '<canvas></canvas>';
				$content[] = '<table class=hidden>';
				$content[] = '<thead><tr><th></th><td>Uniqe Session</td><td>Uniqe IP</td></tr></thead>';
				$content[] = '<tbody><tr><th>{LNG_USERONLINE_UNIQE_REPORT}</th><td>'.sizeof($ssid).'</td><td>'.sizeof($ips).'</td></tr></tbody>';
				$content[] = '</table>';
				$content[] = '</div>';
				$content[] = '</div>';
				$content[] = '</div>';
				$content[] = '<script>';
				$content[] = '$G(window).Ready(function(){';
				$content[] = 'new gGraphs("online_graph", {type:"hchart"});';
				$content[] = 'new gGraphs("uniqe_graph", {type:"pie",startColor:9,centerX:Math.round($G("uniqe_graph").getHeight() / 2)});';
				$content[] = '});';
				$content[] = '</script>';
			}
			$content[] = '</section>';
			// หน้าปัจจุบัน
			$url_query['module'] = 'report';
		} else {
			$title = $lng['LNG_DATA_NOT_FOUND'];
			$content[] = '<aside class=error>'.$title.'</aside>';
		}
	} else {
		$title = $lng['LNG_DATA_NOT_FOUND'];
		$content[] = '<aside class=error>'.$title.'</aside>';
	}
