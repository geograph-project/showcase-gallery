<?

include "includes/database.php";
include "includes/mysql-config.inc.php";


function counter($crit) {
	return getOne("SELECT COUNT(*) FROM gallery_image WHERE width>0 AND num=0 AND $crit");
}

function linker($crit) {
	print " <a href=\"gallery.php?tab=unrated&crit=".urlencode($crit)."\">Rate now</a>";
}

function countandlink($crit1,$crit2) {
	$count = counter($crit1);
	print "Unrated = <b>$count</b>.";
	if ($count)
		linker($crit2);
	return $count;
}

#####################################################################################

?>
<base target="_blank">
<title>Unrated Images Stats</title>
<style>
body {
	font-family:Georgia;
	background-color:cream;
	color:gray;
}
h2 {
	color:blue;
}
b {
	color:black;
}
</style>

<h2>Showcase Gallery :: Unrated Images</h2>

<p>This page counts images suggested to the <a href=gallery.php>Showcase Gallery</a> collection, but as yet hasn't been rated at all.</p>

<p>(Figures on this page, updated once an hour or so)</p>

<?




print "<b>All Images</b>: ";
countandlink("1","");

print "<hr>";

#####################################################################################

print "<b>Taken in Last 7 Days</b>: ";
countandlink("taken > date_sub(now(),interval 8 day)","taken:-7");
print "<br>";

print "<b>Submitted in Last 7 Days</b>: ";
countandlink("submitted > date_sub(now(),interval 8 day)","submitted:-7");
print "<br>";

print "<b>Added to Gallery in Last 7 Days</b>: ";
countandlink("fetched > date_sub(now(),interval 8 day)","fetched:-7");
print "<br>";

print "<hr>";

#####################################################################################

print "Added to Gallery <b>Over 2 years</b> ago: ";
countandlink("fetched < date_sub(now(),interval 2 year)","fetched:+730");


print "<hr>";

#####################################################################################

print "<h3>Featured Hectads</h3>";

$rows = getAll("select * from gallery_hectad order by day_hectad desc limit 10");
foreach ($rows as $row) {
	if (preg_match('/^([A-Z]+)(\d)\d?(\d)\d?$/',$row['hectad'],$m)) {
		print "&middot; <b>{$row['hectad']}</b> <small>[{$row['day_hectad']}]</small> &middot; ";

		countandlink("grid_reference LIKE ".dbQuote($m[1].$m[2]."_".$m[3]."_"), "hectad:{$row['hectad']}");
		print "<br>";
	}
}
print "<hr>";

#####################################################################################


print "<h3>Countries</h3>";

$rows = getAll("select Country,count(*) as `count` from gallery_image where width>1 and num=0 group by Country");
foreach ($rows as $row) {
        if (!empty($row['Country'])) {
                print "&middot; <b>{$row['Country']}</b>  &middot; ";

		$count = $row['count'];
		print "Unrated = <b>$count</b>.";
		if ($count)
			linker("Country:{$row['Country']}");
                print "<br>";
        }
}
print "<hr>";

#####################################################################################

print "<h3>Top Counties</h3>";

$rows = getAll("select County,count(*) as `count` from gallery_image where width>1 and num=0 group by County order by count desc limit 30");
foreach ($rows as $row) {
        if (!empty($row['County'])) {
                print "&middot; <b>{$row['County']}</b>  &middot; ";

		$count = $row['count'];
		print "Unrated = <b>$count</b>.";
		if ($count)
			linker("County:{$row['County']}");
                print "<br>";
        }
}
print "<hr>";

#####################################################################################

print "<h3>Bottom Counties</h3>";

$rows = getAll("select County,count(*) as `count` from gallery_image where width>1 and num=0 group by County order by count asc limit 30");
foreach ($rows as $row) {
        if (!empty($row['County'])) {
                print "&middot; <b>{$row['County']}</b>  &middot; ";

		$count = $row['count'];
		print "Unrated = <b>$count</b>.";
		if ($count)
			linker("County:{$row['County']}");
                print "<br>";
        }
}
print "<hr>";

#####################################################################################

	$count =  getOne("SELECT COUNT(*) FROM gallery_image WHERE width>0 AND num=1 AND last_vote > DATE_SUB(NOW(),INTERVAL 1 DAY)");

print "<p>$count images getting their first vote in the last 24 hours</p>";
