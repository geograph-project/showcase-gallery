<?php
        chdir(__DIR__);

if (!empty($argv[1]) && $argv[1] == 'send') {
   $_GET['send'] = 1;
}

include "includes/database.php";
include "includes/mysql-config.inc.php";
include "includes/functions.inc.php";

if (isset($_GET['email'])) {
?>
	<body style="font-family:Georgia;">
	<script type="text/javascript" src="/livevalidation_standalone.compressed.js"></script>
	<h2>Subscribe/Unsubscribe from Daily Image - from the Gallery</h2>
	<form method="post" action="./gallery-email.php" style="background-color:#dddddd;padding:10px">

		<p>Email: <input type="text" name="email" id="email" <? if (!empty($_GET['email'])) { echo " value=\"".htmlentities($_GET['email'])."\""; } ?> size="40" maxlength="128"/></p>

		<? if (empty($_GET['email'])) { ?>
			<p>Confirm Email: <input type="text" name="email2" id="email2" size="40" maxlength="128"/></p>
		<? } ?>

		<ul>
			<li>We will <b>NEVER share</b> your email with anyone</li>
			<li>You will receive <b>one</b> email a day, showcasing an image from the <a href="./gallery.php">Gallery</a></li>
			<li>Can <b>unsubscribe</b> at any time by clicking the link in the email</li>
			<li>When you subscribe you will receive todays message <b>immediately</b></li>
		</ul>

		<input type="submit" name="subscribe" value="Subscribe" style="background-color:lightgreen"/>
		<? if (!empty($_GET['email'])) { ?>
			<input type="submit" name="unsubscribe" value="Unsubscribe" style="background-color:pink"/>
		<? } ?>

	</form>

<script>
var f20 = new LiveValidation('email',{validMessage: 'ok'});
f20.add( Validate.Email );
<? if (empty($_GET['email'])) { ?>
var f21 = new LiveValidation('email2',{validMessage: ' ok'});
f21.add( Validate.Email );
f21.add( Validate.Confirmation, {match:'email'} );
<? } ?>
</script>

	<hr/>
	<a href="./gallery.php">back to gallery</a>
<?
	exit;
}
if (isset($_POST['email'])) {
	if (empty($_POST['email'])) {
		die("please enter a email address!");
	}
	if (isset($_POST['email2']) && $_POST['email'] != $_POST['email2']) {
		die("emails don't match!");
	}

	if (isset($_POST['subscribe'])) {
	        $sql = "INSERT INTO gallery_email SET
                	email = ".dbQuote($email = trim($_POST['email'])).",
        	        ipaddr = INET6_ATON(".dbQuote(getRemoteIP())."),
	                useragent = ".dbQuote($_SERVER['HTTP_USER_AGENT']).",
                	session = ".dbQuote(my_session_id()).",
			created = NOW()
			ON DUPLICATE KEY UPDATE status = 2";

		$_GET['send'] = 1;
	} else {
		$sql = "UPDATE gallery_email SET status = -1 WHERE email = ".dbQuote(trim($_POST['email']));
	}

        queryExecute($sql);

	print "Subscription updated";

	if (isset($_POST['unsubscribe'])) {
?>
        <hr/>
        <a href="./gallery.php">back to gallery</a>
<?
		exit;
	}

}





if (empty($_GET)) {
	die("huh?");
}

##################################

$row = getRow("SELECT * FROM gallery_image WHERE showday = DATE(NOW())");

if (empty($row)) {
	queryExecute("LOCK TABLES gallery_image WRITE");

	$ids = getCol("select distinct profile_link from gallery_image where showday > date_sub(now(),interval 30 day)");
        if (empty($ids)) {
                $ids = "''";
        } else {
                $ids = "'".implode("','",$ids)."'";
        }

	if (1) {
		$where_and_order = "where profile_link not in ($ids) and status in ('geograph','accepted') AND showday IS NULL AND num > 3 and baysian > 3.9 order by num desc,(baysian > 4.1) desc,(baysian > 4.0) desc,(baysian > 3.9) desc";

	} elseif (1) {
		$where_and_order = "where profile_link not in ($ids) and status in ('geograph','accepted') AND showday IS NULL AND num > 3 and baysian > 4.0 order by num desc";

	} else {
		$where_and_order = "WHERE profile_link not in ($ids) and status in ('geograph','accepted') AND showday IS NULL AND users > 3 AND baysian > 4 ORDER BY baysian DESC";
	}

	$id = getOne("SELECT id FROM gallery_image $where_and_order LIMIT 1");



	if ($id) {
		queryExecute("UPDATE gallery_image SET showday = DATE(NOW()) WHERE id = $id");
		$row = getRow("SELECT * FROM gallery_image WHERE id = $id");
	}
	queryExecute("UNLOCK TABLES");
}

if (empty($row)) {
	die("Unable to load image!");
}

$host = "https://www.geograph.org.uk"; //TODO - get from $row['url']


$subject = "[Geograph Daily] ".htmlentities($row['title'])." by ".htmlentities($row['realname']);

$message = "<div align=\"center\" style=\"color:silver;text-size:0.8em\">This daily newsletter brought to you by <a href=\"https://www.geograph.org/\" style=\"color:silver\">geograph.org</a>. 
Unsubscribe from future updates by <a href=\"https://www.geograph.org/gallery-email.php?email=[EMAIL]\" style=\"color:silver\">clicking here</a>.</div>\n";


$html = "<body bgcolor=\"black\"><div style=\"font-family:Georgia;background-color:black;padding:10px;color:white\">";
$html .= $message;

$html .= "<p align=\"center\"><a href=\"{$row['url']}\"><img src=\"{$row['fullsize']}\" width=\"{$row['width']}\" height=\"{$row['height']}\" border=\"0\"/></a></p>\n";

$html .= "<p align=\"center\"><b><a href=\"{$row['url']}\" style=\"color:white\">".htmlentities($row['title'])."</a></b>";
$html .= " by <a href=\"$host{$row['profile_link']}\" style=\"color:white\">".htmlentities($row['realname'])."</a><br/>";
$html .= "for square <a href=\"$host/gridref/{$row['grid_reference']}\" style=\"color:white\">{$row['grid_reference']}</a>";
if (!empty($row['taken']) && strpos($row['taken'],'-00') === FALSE) {
	$html .= ", taken ".date('jS F, Y',strtotime($row['taken']));
}
$html .= "</p>\n";

$html .= "<p align=\"center\">[<a href=\"https://maps.google.co.uk/maps?q={$row['url']}.kml&ll={$row['wgs84_lat']},{$row['wgs84_long']}&z=15\">Map</a>] ";
if (!empty($row['comment'])) {
	$html .= "<br/><small>".htmlentities($row['comment'])."</small><hr/>";
}
$html .= "</p>\n";

$html .= "<p align=\"center\" style=\"clear:both\"><i>Other photos you might like</i>:<small><br/><br/></small>\n";
$rows = getAll("SELECT url,title,realname,thumbnail FROM gallery_image WHERE width > 0 AND baysian > 2.5 AND showday IS NULL ORDER BY last_vote DESC LIMIT 8");
foreach ($rows as $row) {
	#https://www.geograph.org/gallery.php#!/www.geograph.org.uk/photo/2391121
	$url = "https://www.geograph.org/gallery.php#!".str_replace('https:/','',$row['url']);
	$html .= "<a href=\"$url\" title=\"".htmlentities($row['title'])." by ".htmlentities($row['realname'])."\"><img src=\"{$row['thumbnail']}\" border=\"0\"/></a>\n ";
}
$html .= "<br/>If you have a moment, please go and <a href=\"https://www.geograph.org/gallery.php?tab=new\" style=\"color:white\">rate some images</a>, Thank you!</p>\n";

$html .= '<p align="center"> View:
<a href="https://www.geograph.org/gallery.php?tab=highest" title="view images rated the highest">highest rated</a> |
<a href="https://www.geograph.org/gallery.php?tab=latest" title="view latest suggested images" class="selected">latest</a> |
<a href="https://www.geograph.org/gallery.php?tab=current" title="view most current images">current</a> |
<a href="https://www.geograph.org/gallery.php?tab=unrated" title="images most in need of votes">unrated</a> |
<a href="https://www.geograph.org/gallery.php?tab=new" title="view images not see before">new</a> |
<a href="https://www.geograph.org/gallery.php?tab=random" title="view random images">random</a> |</p>';

$html .= $message;

$html .= "</div></body>";


###########################

if (isset($_GET['send']) && $_GET['pw'] == $CONF['cron_password']) {

	$headers = "From: noreply@geograph.org.uk\r\n";
#	$headers .= "Reply-To: ". strip_tags($_POST['req-email']) . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";


	if (!empty($_GET['to'])) {
		$emails = array($_GET['to']);
	} elseif (!empty($email)) {
		$emails = array($email);
	} else {
		$emails = getCol("SELECT email FROM gallery_email WHERE status > 0 AND created < DATE(NOW())");
	}

	foreach ($emails as $email) {
		$hash = substr(md5('This is a unknown secret'.$email),4,12);

		mail($email, $subject, str_replace('[EMAIL]',urlencode($email)."&h=".$hash,$html), $headers);
		print "<br/>email sent to ".htmlentities($email);
		if (count($emails) > 2) {
			sleep(rand(30,60));
		}
	}


} else {

	print "<h2>Subject: $subject</h2>";

	print $html;
}

?>
        <hr/>
        <a href="./gallery.php">back to gallery</a>

