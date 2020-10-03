<?php

include "includes/database.php";
include "includes/mysql-config.inc.php";

####################################################
if (!empty($_GET['suggest'])) {
	if (empty($_COOKIE['__utma'])) {
		session_cache_expire(3600*24*30);
		session_start();
	}

	header("HTTP/1.0 204 No Content");
	header("Status: 204 No Content");
	header("Content-Length: 0");

	$u = array();
	$u['url'] = "http://www.geograph.org.uk/photo/".intval($_GET['suggest']);
	$u['created'] = 'NOW()';
	$u['session'] = empty($_COOKIE['__utma'])?session_id():md5($_COOKIE['__utma']);
	$sql= updates_to_insert('gallery_image',$u);
	queryExecute($sql);
	exit;

####################################################
} elseif (!empty($_GET['v'])) {
	if (empty($_COOKIE['__utma'])) {
		session_cache_expire(3600*24*30);
		session_start();
	}

	header("HTTP/1.0 204 No Content");
	header("Status: 204 No Content");
	header("Content-Length: 0");

	$id = getOne("SELECT id FROM gallery_image WHERE url = ".dbQuote($_GET['url']));

	$sql = "INSERT INTO gallery_log SET
		id = ".intval($id).",
		vote = ".intval(@$_GET['v']).",
		ipaddr = INET_ATON('".$_SERVER['REMOTE_ADDR']."'),
		useragent = ".dbQuote($_SERVER['HTTP_USER_AGENT']).",
		`tab` = ".dbQuote($_GET['tab']).",
		session = ".dbQuote(empty($_COOKIE['__utma'])?session_id():md5($_COOKIE['__utma']));

	queryExecute($sql);
	exit;

####################################################
} else {

	$tab = (isset($_GET['tab']) && preg_match('/^\w+$/',$_GET['tab']))?$_GET['tab']:'highest';
	$r = isset($_GET['r'])?intval($_GET['r']):0;

	$cols = "url,title,submitted,category,taken,grid_reference,profile_link,realname,comment,wgs84_lat,wgs84_long,thumbnail,fullsize,width,height";
	$join = '';

	$where = array("width > 0");

	if (!empty($_GET['crit']) && preg_match('/^(\w+):([\w\/ -]+)$/',$_GET['crit'],$m)) {
		if (is_numeric($m[2]) && in_array($m[1],array('fetched','taken','submitted'))) {
			if ($m[2] < 0)
				$where[] = $m[1]." > DATE_SUB(NOW(),INTERVAL ".abs($m[2])." DAY)";
			else
				$where[] = $m[1]." < DATE_SUB(NOW(),INTERVAL {$m[2]} DAY)";
		} elseif ($m[1] == 'hectad' && preg_match('/^([A-Z]+)(\d)\d?(\d)\d?$/',$m[2],$m2))
                        $where[] = "grid_reference LIKE ".dbQuote($m2[1].$m2[2]."_".$m2[3]."_");
		else
			$where[] = $m[1]." = ".dbQuote($m[2]);
	}

	if (!empty($_GET['geo']))
		$where[] = "status != 'accepted' AND ";



	$RAND = "UNIX_TIMESTAMP(DATE(NOW()))"; //seed for RAND()
	$RAND = "RAND($RAND)";
	//or $RAND = "CRC32(url)"; or "CRC32(CONCAT(DATE(NOW()),url))"; etc...
	//NOTE: if use RAND() without seed; then should do $limit=$num; to remove the offset.

	$limit = 40;
	if (!empty($_GET['start']) && $_GET['start'] < 400 && $_GET['start'] > 0)
		$limit = intval($_GET['start']).",$limit";

	switch ($tab) {
		case 'nearby':
			list($lat,$lng) = explode(',',$_GET['ll']);
		        $lat = floatval(trim($lat));
		        $lng = floatval(trim($lng));
			$cols .= ",(pow(wgs84_lat-$lat,2)+pow(wgs84_long- ($lng),2))*pow(5-baysian,4) AS comp";
			$where[] = "num > 1";
			$where[] = "baysian > 3";
			$order = "comp asc,$RAND";
			break;

		case 'weekly':  $where[] = "baysian > 3"; $where[] = "num_recent > 1"; $where[] = "num < 40";  $order = "avg_recent DESC"; break;
		case 'daily':   $where[] = "showday is not null"; $order = "showday DESC"; break;
		case 'unrated': $order = ($r%2 == 0)?'num asc, year(taken) desc':'num asc'; //$order = 'num asc';
				$where[] = "num >= ".($r-1); //use  num >= $r, becauase so many unrated photos, num=1 never get a look in for example
					//this should slow down rating brand new images, and move some older images though the ranks as well. 
				$order .=",$RAND";
				break;
		case 'second':  $where[] = "baysian < 3"; $where[] = "num between 2 and 7"; $where[] = "v5+v4 > 0";
				$where[] = "last_vote < date_sub(now(),interval 7 day)";
				$order = "last_vote ASC"; break;
		case 'latest':  $order = "fetched DESC"; $reorder= true; break;
		case 'added':   $order = "created DESC"; break;
		case 'active':  $where[] = "baysian > 3"; $order = "updated DESC"; break;
		case 'current': $where[] = "baysian > 3"; $where[] = "taken > 1"; $order = "taken DESC,$RAND"; if ($r%2 == 0) {$reorder= true;}; break;
		case 'new':
			if (empty($_COOKIE['__utma'])) {
				session_cache_expire(3600*24*30);
				session_start();
			}

			$sessid = dbQuote(empty($_COOKIE['__utma'])?session_id():md5($_COOKIE['__utma']));

			$orders = array('last_vote','submitted','created');
			$order = $orders[$r%count($orders)];

			$where[] = "taken > 1";
			$where[] = "vote_id IS NULL";
			$globalwhere = implode(" AND ",$where);

			//todo, convert to just setting '$join' ??
			$join = "LEFT JOIN gallery_log v ON (i.id = v.id and v.session=$sessid)";

			break;
		case 'random':
			$order = $RAND;
			$where[] = "baysian > 3";
			if (empty($_GET['crit'])) {
				if ($r%2 == 0) {
					$where[] = "num = 1"; $where[] = "baysian >= 3.3";
				} else {
					$where[] = "num > 3";
				}
			}
			break;
		case 'ireland':	$where[] = "num > 3"; $where[] = "LENGTH(grid_reference) = 5"; $order = "baysian DESC"; break;

		case 'hectad':
			//already filtered to hectad
			$where[] = "1 group by ifnull(floor(baysian*35),id div 1000)"; //MUST be last!
			$order = "baysian desc";
			 break;

		case 'highest':
		default:
			if (empty($_GET['crit']))
				$where[] = "num > 3";
			$order = "baysian DESC";
			if ($r%2 == 0) {
				$order = "ROUND(baysian,1) DESC, last_vote DESC";
			} elseif ($r%3 == 0) {
				$where[] = "num < 20";
			}
			break;
	}

	$globalwhere = implode(" AND ",$where);

	if (!empty($reorder)) {
		$sql = "(SELECT $cols FROM gallery_image i $join WHERE $globalwhere ORDER BY $order LIMIT 300) ORDER BY $RAND LIMIT $limit";
	} else {
		$sql = "SELECT $cols FROM gallery_image i $join WHERE $globalwhere ORDER BY $order LIMIT $limit";
	}
	$data = getAll($sql);

	if (!empty($_GET['hash'])) {
		$row = getRow("SELECT $cols FROM gallery_image WHERE url = ".dbQuote("http:/".$_GET['hash']));
		if ($row) {
			if ($data) {
				$data = array($row)+$data;
				array_pop($data);//bring back to the right number of results, otherwise paging messes up. We loose an image, sorry about that!
			} else {
				$data = array($row);
			}
		}
	} elseif (!empty($_GET['today'])) {
		$row = getRow("SELECT $cols FROM gallery_image WHERE showday = DATE(NOW())");
		if ($row) {
			if ($data) {
				$data = array($row)+$data;
				array_pop($data);//bring back to the right number of results, otherwise paging messes up. We loose an image, sorry about that!
			} else {
				$data = array($row);
			}
		}
	}

	if (!empty($data)) {
		foreach ($data as $idx => $row) {
			$data[$idx]['title'] = utf8_encode($row['title']);
			$data[$idx]['realname'] = utf8_encode($row['realname']);
			$data[$idx]['comment'] = utf8_encode($row['comment']);
		}
	}

	include "includes/functions.inc.php";

	$contents = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);

	if (isset($_GET['callback'])) {
		$callback=preg_replace('/[^\w$]+/','',$_GET['callback']);
		if (empty($callback)) {
			$callback = "geograph_callback";
		}

		customExpiresHeader(600,true);
		$contents = "{$callback}($contents);";
	} else {
		customExpiresHeader(60,($tab != 'new'));
	}

	if (empty($_GET['j']) && $encoding = getEncoding()) {
		// Send compressed contents
		$contents = gzencode($contents, 9,  ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
		header ('Content-Encoding: '.$encoding);
		header ('Vary: Accept-Encoding');
	}

	header('Content-Length: '.strlen($contents));
	echo $contents;
}

