<?

		$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP','KML','BASE','GeoRSS','GeoPhotoRSS','GPX','TOOLBAR','MEDIA');

		if (isset($_GET['extension']) && !isset($_GET['format']))
		{
			$_GET['format'] = strtoupper($_GET['extension']);
			$_GET['format'] = str_replace('GEO','Geo',$_GET['format']);
			$_GET['format'] = str_replace('PHOTO','Photo',$_GET['format']);
		}

		$format="GeoRSS";
		if (isset($_GET['format']) && in_array($_GET['format'], $valid_formats))
		{
			$format=$_GET['format'];
		}

		if ($format == 'KML') {
			if (!isset($_GET['simple']))
				$_GET['simple'] = 1; //default to on
			$extension = (empty($_GET['simple']))?'kml':'simple.kml';
		} elseif ($format == 'GPX') {
			$extension = 'gpx';
		} else {
			$extension = 'xml';
		}

if (!empty($_GET['daily'])) {
	$cachepath = "cache-service/daily.$format.$extension";
} else {
	$cachepath = "cache-service/spread.$format.$extension";
}

include('includes/functions.inc.php');

$encoding = '';//getEncoding();
if ($encoding) {
	$cachepath .= ".$encoding";
	header ('Content-Encoding: '.$encoding);
}
header ('Vary: Accept-Encoding');

if (file_exists($cachepath) &&
                @filemtime($cachepath) < time() - 3600 ) {
        unlink($cachepath);
}

$rss_timeout = 3600*12;

if (file_exists($cachepath) && ( @filemtime($cachepath) > time() - $rss_timeout ) && empty($_GET['refresh'])) {
	$mtime = @filemtime($cachepath);

	customExpiresHeader(3600*24*24,true);
	customCacheControl($mtime,$cachepath);

	header('Content-length: '.filesize($cachepath));

	readfile($cachepath);
	exit;
}



	$cols = "url,grid_reference,realname,title,comment,taken,submitted,wgs84_lat,wgs84_long,thumbnail,fullsize";

	if (!empty($_GET['daily'])) {
		$sql = "SELECT $cols FROM gallery_image WHERE showday IS NOT NULL ORDER BY showday DESC LIMIT 30";

	} elseif (true) {
		$sql = "SELECT $cols FROM gallery_image WHERE LENGTH(title) > 2 ORDER BY `baysian` DESC"; //cheap hack to get the highest rated in each myriad... 

		$sql = "SELECT * FROM ($sql) t1 GROUP BY left(grid_reference,length(grid_reference)-4)";
	}

	if ($sql) {
		include "includes/mysql-config.inc.php";

		$result = mysql_query($sql) or die ("Couldn't select query : $sql " . mysql_error() . "\n");
		$r = '';
		if (mysql_num_rows($result) > 0) {
			require ("includes/feedcreator.class.php");

			$rss = new UniversalFeedCreator();
			$rss->useCached($format,$cachepath,$rss_timeout);
			$rss->title = 'Geograph Showcase';
			$rss->link = "http://www.geograph.org/gallery.php";

			$rss->syndicationURL = "http://www.geograph.org/gallery-syndicator.php?format=$format";

			$geoformat = ($format == 'KML' || $format == 'GeoRSS' || $format == 'GeoPhotoRSS' || $format == 'GPX');
			$photoformat = ($format == 'KML' || $format == 'GeoPhotoRSS' || $format == 'BASE' || $format == 'MEDIA');

			while ($row = mysql_fetch_assoc($result)) {

				$item = new FeedItem();
				$item->guid = $row['url'];
				$item->title = $row['grid_reference']." : ".$row['title']." by ".$row['realname'];
				$item->link = $row['url'];

				$item->description = $row['comment'];

				if (!empty($row['taken']) && strpos($row['taken'],'-00') === FALSE) {
					$item->imageTaken = $row['taken'];
				}

				$item->date = strtotime($row['submitted']);
				$item->source = "http://www.geograph.org.uk".$row['profile_link'];
				$item->author = $row['realname'];

				if ($geoformat) {
					$item->lat = $row['wgs84_lat'];
					$item->long = $row['wgs84_long'];
				}
				if ($photoformat || $format == 'PHP') {

					$item->thumb = str_replace('_120x120','_213x160',$row['thumbnail']);
					$item->thumbTag = "<img src=\"{$row['thumbnail']}\" border=\"0\"/>";

					if ($format == 'MEDIA') {
						$item->content = $row['fullsize'];
					}
				}

                                $item->description = '<a href="'.$item->link.'" title="'.$item->title.'"><img src="'.str_replace('_120x120','_213x160',$row['thumbnail']).'" border="0"/></a><br/>'. $item->description;
                                $item->descriptionHtmlSyndicated = true;


				$item->licence = "&copy; Copyright <i class=\"attribution\">".htmlspecialchars($row['realname'])."</i> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a>";

				$rss->addItem($item);
			}
			customExpiresHeader($rss_timeout,true); //we cache it for a while anyway!
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

			$rss->saveFeed($format, $cachepath);
			exit;
		} else {
			$r = "\t--none--";
		}
	}

	if ($r) {
		if ($encoding) {
			$r = gzencode($r, 9,  ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
		}

		customExpiresHeader(3600*24*24,true);

		if (!$nocache) {
			file_put_contents($cachepath,$r);

			$mtime = @filemtime($cachepath);

			customCacheControl($mtime,$cachepath);
		}
		header('Content-length: '.strlen($r));

		print $r;
	}

