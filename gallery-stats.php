<?

include "includes/database.php";
include "includes/mysql-config.inc.php";

print "<div style='float:left;width:260px'>";

if (!empty($_GET['i']))
	$where = 'and length(grid_reference)=5';
else
	$where = '';


	print "<h3>Total images = ".getOne("SELECT COUNT(*) FROM gallery_image WHERE width>0 $where")."</h3>";

	print "<h3>Pending images = ".getOne("SELECT COUNT(*) FROM gallery_image WHERE width=0 $where")."</h3>";

	print "<h3>Votes Cast = ".getOne("SELECT COUNT(*) FROM gallery_log WHERE final=1")."</h3>";

	print "<h3>Voters = ".getOne("SELECT COUNT(DISTINCT session) FROM gallery_log WHERE final=1")."</h3>";

	print "<h3>Suggestors = ".getOne("SELECT COUNT(DISTINCT session) FROM gallery_image WHERE width>0 $where")."</h3>";

	print "<h3>Subscribers = ".getOne("SELECT COUNT(*) FROM gallery_email WHERE status > 0")."</h3>";

	$intervals = array('24 hour','7 day','1 month');
	$columns = array('created','updated','fetched','last_vote','showday','submitted','taken');

	print "<table>";
	print "<tr><td></td>";
	foreach ($intervals as $interval) {
		print "<th>$interval</th>";
	}

	foreach ($columns as $column) {
		print "<tr><th>$column</th>";
		foreach ($intervals as $interval) {
			print "<td>".getOne("SELECT COUNT(*) FROM gallery_image WHERE $column > DATE_SUB(NOW(), INTERVAL $interval)")."</td>";
		}
	}
	print "</table>";
	print "(submitted to geograph, not the gallery)";

print "</div>";
print "<div style='float:left;width:290px'>";

	print "<h3>Votes</h3>";

	dump_sql_table("SELECT substring(ts,1,10) day,COUNT(*),count(distinct session) as users,avg(vote) FROM gallery_log WHERE final = 1 GROUP BY substring(ts,1,10) DESC LIMIT 60");

print "</div>";
/*
print "<div style='float:left;width:260px'>";

	print "<h3>Updated</h3>";
	
	dump_sql_table("SELECT substring(updated,1,10) day,COUNT(*),count(*) as images FROM gallery_image where 1 $where GROUP BY substring(updated,1,10) DESC LIMIT 10");

print "</div>";
*/
print "<div style='float:left;width:260px'>";

	print "<hr/><h3>Images by number of votes</h3>";

	dump_sql_table("SELECT num,COUNT(*),ROUND(AVG(baysian),4) AS `AVG(baysian)` FROM gallery_image WHERE width>0 $where GROUP BY floor(ln(num)*10)");

print "</div>";
print "<div style='float:left;width:260px'>";

	print "<hr/><h3>Images by score</h3>";

	dump_sql_table("SELECT  FLOOR(baysian*10)/10 AS baysian,COUNT(*),AVG(num) FROM gallery_image WHERE width>0 $where GROUP BY FLOOR(baysian*7) DESC");

print "</div>";
print "<div style='float:left;width:260px'>";

	print "<hr/><h3>Images num>3</h3>";

	dump_sql_table("SELECT  FLOOR(baysian*7)/7 AS baysian,COUNT(*),AVG(num) FROM gallery_image WHERE width>0 AND num>3 $where GROUP BY FLOOR(baysian*7) DESC");
print "</div>";



function dump_sql_table($sql_query) {
	global $db;

	$result = mysqli_query($db,$sql_query) or die ("Couldn't select : [[ $sql_query ]] " . mysqli_error($db) . "\n");

	$row = mysqli_fetch_assoc($result);
	$num = mysqli_num_rows($result);
	if (!$num)
		return FALSE;

	print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
	foreach ($row as $key => $value) {
		print "<TH>$key</TH>";
	}
	print "</TR>";
	do {
		print "<TR onmouseover=\"this.style.background='#EFEFEF'\" onmouseout=\"this.style.background='#FFFFFF'\">";
		foreach ($row as $key => $value) {
			print "<TD>$value</TD>";
		}
		print "</TR>";
	} while ($row = mysqli_fetch_assoc($result));
	print "</TR></TABLE>";
	return $num;
}
