<?

if (empty($_POST['images'])) { ?>

<form method=post>
<textarea name=images rows=20 cols=100></textarea><br>
<input type=submit>
</form>


<? } else {
	include "includes/database.php";
	include "includes/mysql-config.inc.php";

	if (empty($_COOKIE['__utma'])) {
		session_cache_expire(3600*24*30);
		session_start();
	}

	$str = $_POST['images'];


        //the digits in a image url
        $str = preg_replace('/s\d\.geograph/','',$str);
        $str = preg_replace('/photos\/(\d{2}\/)+/','',$str);
        $str = preg_replace('/_\d+[xX]+\d+\.jpg/','.jpg',$str);
        $str = preg_replace('/_\w{8}\.jpg/','.jpg',$str);


	$str = trim(preg_replace('/[^\d]+/',' ',$str));

	$u = array();
	$u['created'] = 'NOW()';
	$u['session'] = empty($_COOKIE['__utma'])?session_id():md5($_COOKIE['__utma']);

	foreach (explode(" ",$str) as $id) {

		$u['url'] = "https://www.geograph.org.uk/photo/".intval($id);
		$sql=updates_to_insert('gallery_image',$u);
		queryExecute($sql);
		$count++;
		$affected+=mysql_affected_rows();
	}

	print "<hr/>COUNT= $count; AFFECTED=$affected\n";
}
