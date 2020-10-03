<?

if (empty($_POST['images'])) { ?>

<form method=post>
<textarea name=images rows=20 cols=100></textarea><br>
<input type=submit>
</form>


<? } else {
	include "includes/database.php";
	include "includes/mysql-config.inc.php";
	include "includes/functions.inc.php";

	$str = $_POST['images'];


        //the digits in a image url
        $str = preg_replace('/s\d\.geograph/','',$str);
        $str = preg_replace('/photos\/(\d{2}\/)+/','',$str);
        $str = preg_replace('/_\d+[xX]+\d+\.jpg/','.jpg',$str);
        $str = preg_replace('/_\w{8}\.jpg/','.jpg',$str);


	$str = trim(preg_replace('/[^\d]+/',' ',$str));

	$u = array();
	$u['created'] = 'NOW()';
	$u['session'] = my_session_id();

	foreach (explode(" ",$str) as $id) {

		$u['url'] = "https://www.geograph.org.uk/photo/".intval($id);
		$sql=updates_to_insert('gallery_image',$u);
		queryExecute($sql);
		$count++;
		$affected+=mysql_affected_rows();
	}

	print "<hr/>COUNT= $count; AFFECTED=$affected\n";
}
