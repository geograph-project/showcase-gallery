<?php

chdir(__DIR__);

include "includes/mysql-config.inc.php";

if (!empty($_SERVER['REMOTE_ADDR']) && $_GET['pw'] != $CONF['cron_password'])
	die();

include "includes/database.php";
include "includes/functions.inc.php";

switch (rand(1,20)) {
	case 1: if (rand(1,10) > 1)
			$rows = getAll("SELECT url,title FROM gallery_image WHERE title IS NULL ORDER BY RAND() DESC LIMIT 1");
		break;
	case 2: $rows = getAll("SELECT url,title FROM gallery_image WHERE title IS NULL ORDER BY LENGTH(url) DESC,url DESC LIMIT 1");
		break;
	case 3: if (rand(1,10) > 5) {
			$n = rand(0,9);
			$rows = getAll("SELECT url,title FROM gallery_image WHERE title = '$n' ORDER BY RAND() LIMIT 1");
		}
		break;

	case 4:	$rows = getAll("(SELECT url,title FROM `gallery_image` WHERE title IS NULL ORDER BY LENGTH(url) DESC,url DESC LIMIT 30) ORDER BY RAND() LIMIT 1");
		break;

	case 5: $rows = getAll("(SELECT url,title FROM `gallery_image` WHERE title IS NULL ORDER BY created DESC LIMIT 300) ORDER BY RAND() LIMIT 1");
		break;

	case 16:
	case 6: $rows = getAll("(SELECT url,title FROM `gallery_image` WHERE title IS NULL ORDER BY substring_index(url,'/',-1)+0 DESC LIMIT 100) ORDER BY RAND() LIMIT 1");
		break;

	case 13:
	case 17:
	case 7: $rows = getAll("(SELECT url,title FROM `gallery_image` WHERE title IS NULL ORDER BY created LIMIT 300) ORDER BY RAND() LIMIT 1");
		break;

	case 9:
	case 8:
	case 10: $rows = getAll("SELECT url,title FROM `gallery_image` WHERE fullsize like '%error.jpg' LIMIT 10");
		break;

	case 11: $rows = getAll("SELECT url,title FROM `gallery_image` WHERE LENGTH(profile_link)>10 AND width=0 ORDER BY RAND() LIMIT 1");
}

if (empty($rows) && rand(1,5) > 1)
	exit;

ini_set('user_agent', 'Geograph ShowCase Bot +https://www.geograph.org.uk/contact.php');

if (!empty($rows))
foreach ($rows as $row) {
	if (preg_match('/geograph\.org\.uk\/photo\/(\d+)$/',$row['url'],$m)) {

		$data = file_get_contents("https://www.geograph.org.uk/api/Photo/{$m[1]}/geograph.org?output=json");

		if (!empty($data)) {
			$ar = json_decode($data);

			if (!empty($ar->title)) {
				if (is_object($ar->sizeinfo)) {
					$ar->sizeinfo = (array)$ar->sizeinfo;
				}
				//dont understand why this needed, but without it $ar->sizeinfo['0'] returns NULL. despite having that key
				$ar->sizeinfo = explode(',',implode(',',$ar->sizeinfo));

				$u = array();
				$u['title'] = utf8_decode($ar->title);
				$u['grid_reference'] = $ar->grid_reference;
				$u['profile_link'] = utf8_decode($ar->profile_link);
				$u['realname'] = utf8_decode($ar->realname);
				$u['thumbnail'] = $ar->imgserver.$ar->thumbnail;
				$u['fullsize'] = "https://s0.geograph.org.uk".$ar->image;
				$u['taken'] = $ar->taken;
				$u['submitted'] = "FROM_UNIXTIME(".$ar->submitted.")";
				if (!empty($ar->category))
					$u['category'] = utf8_decode($ar->category);
				if (!empty($ar->comment))
					$u['comment'] = utf8_decode($ar->comment);
				$u['wgs84_lat'] = $ar->wgs84_lat;
				$u['wgs84_long'] = $ar->wgs84_long;
				$u['width'] = intval($ar->sizeinfo['0']);
				$u['height'] = (int)$ar->sizeinfo['1'];
				if (!empty($ar->sizeinfo) && !empty($ar->sizeinfo['4'])) {
					$u['width_original'] =  intval($ar->sizeinfo['4']);
					$u['height_original'] =  intval($ar->sizeinfo['5']);
				}
				$u['fetched'] = 'NOW()';

				$sql= updates_to_update('gallery_image',$u,'url',$row['url']);

				queryExecute($sql);
				sleep(count($rows));
			} else {
				queryExecute("UPDATE gallery_image SET title = '*' WHERE url = ".dbQuote($row['url']));
			}
		} else {
			queryExecute("UPDATE gallery_image SET title = '-' WHERE url = ".dbQuote($row['url']));
		}
	} else {
		queryExecute("UPDATE gallery_image SET title = '?' WHERE url = ".dbQuote($row['url']));
	}
}




queryExecute("CREATE TEMPORARY TABLE vote_final AS SELECT MAX(vote_id) AS vote_id FROM gallery_log WHERE final > -1 GROUP BY id,ipaddr,session");
queryExecute("UPDATE `gallery_log` SET `final` = 0 WHERE final > 0");
queryExecute("UPDATE `gallery_log`,vote_final SET gallery_log.final = 1 WHERE `gallery_log`.vote_id = vote_final.vote_id");


$avg = getOne("SELECT AVG(vote) FROM gallery_log WHERE vote > 0 AND final = 1");


$wm = 1; #minimum votes required to be listed (//todo if change need to add a having to clause below!)


queryExecute("CREATE TEMPORARY TABLE vote_stat (id INT UNSIGNED PRIMARY KEY,
 `num` mediumint(8) unsigned NOT NULL,
 `num_recent` mediumint(8) unsigned NOT NULL,
 `users` mediumint(8) unsigned NOT NULL,
 `avg` float NOT NULL,
 `avg_recent` float NOT NULL,
 `std` float NOT NULL,
 `baysian` float NOT NULL,
 `v1` mediumint(8) unsigned NOT NULL,
 `v2` mediumint(8) unsigned NOT NULL,
 `v3` mediumint(8) unsigned NOT NULL,
 `v4` mediumint(8) unsigned NOT NULL,
 `v5` mediumint(8) unsigned NOT NULL,
 `first_vote` datetime,
 `last_vote` datetime)");


queryExecute("INSERT INTO vote_stat
	SELECT
		id,
		COUNT(*) AS `num`,
		SUM(ts>DATE_SUB(NOW(),INTERVAL 7 DAY)) AS `num_recent`,
		COUNT(DISTINCT ipaddr) AS `users`,
		AVG(vote) AS `avg`,
		AVG(IF(ts>DATE_SUB(NOW(),INTERVAL 7 DAY),vote,NULL)) AS `avg_recent`,
		STD(vote) AS `std`,
		(COUNT(*) / (COUNT(*)+$wm)) * AVG(vote) + ($wm / (COUNT(*)+$wm)) * $avg AS `baysian`,
		SUM(vote=1) AS v1,
		SUM(vote=2) AS v2,
		SUM(vote=3) AS v3,
		SUM(vote=4) AS v4,
		SUM(vote=5) AS v5,
		MIN(ts) AS first_vote,
		MAX(ts) AS last_vote
	FROM gallery_log
	WHERE vote > 0 AND final = 1
	GROUP BY id
	ORDER BY NULL");

queryExecute("UPDATE gallery_image i INNER JOIN vote_stat v USING (id) SET i.users = v.users, i.num = v.num, i.num_recent = v.num_recent, i.avg = v.avg, i.avg_recent = v.avg_recent, i.std = v.std, i.baysian = v.baysian, i.v1 = v.v1, i.v2 = v.v2, i.v3 = v.v3, i.v4 = v.v4, i.v5 = v.v5, i.first_vote = v.first_vote, i.last_vote = v.last_vote,updated=updated");

queryExecute("update gallery_image g inner join square2place s using (grid_reference) set g.Place = s.Place, g.County = s.County, g.Country = s.Country,updated=updated where g.Place = ''");

