<?

include('includes/functions.inc.php');



	$cols = "url,grid_reference,realname,title,thumbnail,id";

	if (!empty($_GET['daily'])) {
		$sql = "SELECT $cols,showday FROM gallery_image WHERE width_original >= 1024 AND showday IS NOT NULL ORDER BY showday DESC LIMIT 30";

	} elseif (true) {

		$sql = "SELECT $cols FROM gallery_image WHERE width_original >= 1024 AND users > 3 ORDER BY baysian DESC LIMIT 30";
	}

	if ($sql) {
		include "includes/mysql-config.inc.php";

		$result = mysql_query($sql) or die ("Couldn't select query : $sql " . mysql_error() . "\n");
		$r = '';
		if (mysql_num_rows($result) > 0) {
?>
<html>
<head>
<style>
html {
  background: url(https://www.geograph.org.uk/reuse.php?id=2736862&download=0c69f011&size=1024) no-repeat center center fixed;
  -webkit-background-size: cover;
  -moz-background-size: cover;
  -o-background-size: cover;
  background-size: cover;
}
h2, h2 a {
	color:white;
    text-shadow:
    -1px -1px 0 #000,
    1px -1px 0 #000,
    -1px 1px 0 #000,
    1px 1px 0 #000;
}
tr a {
	color:black;
	text-shadow: 0px 0px 4px #fff;

    color: white;
    text-shadow:
    -1px -1px 0 #000,
    1px -1px 0 #000,
    -1px 1px 0 #000,
    1px 1px 0 #000;
}
tr:hover a {
	font-weight:bold;
	color:yellow;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script>
function updateBackground(id,hash) {
	var image = new Image();
	image.onload = function(){
		$('html').css({'background':'url(https://www.geograph.org.uk/reuse.php?id='+id+'&download='+hash+'&size=1024) no-repeat center center fixed','background-size':'cover'});
	};
	image.src = 'https://www.geograph.org.uk/reuse.php?id='+id+'&download='+hash+'&size=1024';

}
</script>
</head>
<body>
<h2>Wallpapers :: From the <a href="https://www.geograph.org.uk/gallery.php?tab=daily">Geograph gallery</a></h2>
<table>

<?
			while ($row = mysql_fetch_assoc($result)) {
				$bits = preg_split('/[_\/\.]/',$row['thumbnail']);
				array_pop($bits); //jpg
				array_pop($bits); //size
				$hash = array_pop($bits); //hash
				$id = array_pop($bits); //id
?>
<tr onmouseover="updateBackground(<? echo "$id,'$hash'"; ?>);">
	<td><img src="<? echo $row['thumbnail']; ?>" width=120></td>
	<td><a href="<? echo $row['url']; ?>"><? echo htmlentities($row['grid_reference']." : ".$row['title']." by ".$row['realname']); ?></a>
	<? if (!empty($row['showday'])) { echo "<br/>{$row['showday']}"; } ?>
	</td>
	<td><a href="https://www.geograph.org.uk/more.php?id=<? echo basename($row['url']); ?>">wallpaper</a></td>
</tr>
<?

			}
		}
	}
?>
</table>
</body>
</html>

