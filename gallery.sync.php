<?
	chdir(__DIR__);

	include "includes/database.php";
	include "includes/mysql-config.inc.php";

	$str = file_get_contents("https://www.geograph.org.uk/stuff/gallery-list.php");

	if (strpos($str,'mod:') === FALSE)
		exit; //just an extra check to it got a list. Should change to use JSON really! (so can validate, rather than just extacting all numbers from the page!

        //the digits in a image url
        $str = preg_replace('/s\d\.geograph/','',$str);
        $str = preg_replace('/photos\/(\d{2}\/)+/','',$str);
        $str = preg_replace('/_\d+[xX]+\d+\.jpg/','.jpg',$str);
        $str = preg_replace('/_\w{8}\.jpg/','.jpg',$str);


	$str = trim(preg_replace('/[^\d]+/',' ',$str));

	$u = array();
	$u['created'] = 'NOW()';
	$u['session'] = "gallery.sync.php";

	$count=$affected=0;
	foreach (explode(" ",$str) as $id) {

		$u['url'] = "https://www.geograph.org.uk/photo/".intval($id);
		$sql= str_replace('INSERT ','INSERT IGNORE ',updates_to_insert('gallery_image',$u));

		queryExecute($sql);
		$count++;
		$affected+=mysql_affected_rows();
	}

	if (posix_isatty(STDOUT))
		print "COUNT=$count; AFFECTED=$affected\n";

	if ($count > 100)
		file_get_contents("http://www.geograph.org.uk/project/systemtask.php?id[]=76&spotcheck=1&api=1&method=POST");
