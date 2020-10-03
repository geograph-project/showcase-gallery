<?

$tabs = array('highest','random','latest');
shuffle($tabs);
$extra = '';

$tab = (isset($_GET['tab']) && preg_match('/^\w+$/',$_GET['tab']))?$_GET['tab']:array_pop($tabs);
$r = rand(0,5);


 if (!empty($_GET['geo'])) {
   //very hacky, but r is only used in building urls right now!
   $r .= "&geo=1";
   $extra .= "&geo=1";
 }
 if (!empty($_GET['crit']) && preg_match('/^(\w+):([\w\/ ]+)$/',$_GET['crit'],$m)) {
                //very hacky, but r is only used in building urls right now!
                $r .= "&crit=".$m[1].":".$m[2];

                //yet another bodge, setting avoids showing the daily image!
                $_GET['tab'] = $tab;

                $extra .= "&crit=".$m[1].":".$m[2];
  }



if (!empty($_GET['_escaped_fragment_'])) {

	include "includes/database.php";
	include "includes/mysql-config.inc.php";

	$rows = getAll("SELECT * FROM gallery_image WHERE url = ".dbQuote("http:/".$_GET['_escaped_fragment_']));
	$row = $rows[0];

} else if ($_SERVER['HTTP_USER_AGENT'] == 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0') { //Google plus posting bot!
	include "includes/database.php";
	include "includes/mysql-config.inc.php";
	$rows = getAll("SELECT * FROM gallery_image WHERE width > 0 AND num > 4 ORDER BY baysian DESC LIMIT 4");
	$row = $rows[0];
}

include "includes/functions.inc.php";
customGZipHandlerStart();
customExpiresHeader(30,true);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<? if (!empty($row)) { ?>
	<title><? echo htmlentities($row['title']).' by '.htmlentities($row['realname']); ?> :: Geograph Showcase</title>
<? } else { ?>
	<title>Geograph Images</title>
<? } ?>
<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css"/>
<style type="text/css">
html,body,table {
	margin:0;
	padding:0;
	height:100%;
}
body {
	background-color:black;
	color:silver;
	font-size:1em;
	font-family: 'Open Sans', sans-serif;
}
a {
	text-decoration:none;
	color:#eeeeee;
}

#thumbs div {
	overflow: hidden;
}

#thumbs img {
  position:absolute;
}

#thumbs a{
	width:75px;
	height:75px;
	position:relative;
	display:block;
	float:left;
	margin:2px;
}

#footer {
	font-size:0.7em;
	color: gray;
}
#footer a {

}
#footer div.right {
	float:right;
}
#footer a.selected {
	color:silver;
	background-color:#444444;
}
a:hover {
	color:cyan;
	background-color:gray;
}
div#tribute {
	position:relative
}
div#tribute div {
	font-size:1.3em;
	position:absolute;
	top:-220px;
	left:-500px;
	border:1px solid white;
	height:200px;
	width:450px;
	background-color:#333;
	color:silver;padding:10px;
	display:none;
}
#footer div.right:hover div#tribute div {
	display:block;
}
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript">
/* <![CDATA[ */

var timer = null;
var start = 40;
$(function() {
	$('#thumbs div').css('height',$('#thumbs').height()+'px');

	w = Math.floor($('#thumbs').width()/80);
	h = Math.floor($('#thumbs').height()/80);
	var maxNumber = w*h;
	if (maxNumber > 400) maxNumber = 400;

	load_feed("gallery.json.php?tab=<? echo "$tab&r=$r"; ?>");
	timer = setInterval(function() {
		load_feed("gallery.json.php?tab=<? echo "$tab&r=$r"; ?>&start="+start,true);

		if (start > maxNumber) {
			setTimeout(function() {
				$('#thumbs img').hover(function() {
					$(this).css('z-index',10000).attr('clipper',$(this).css('clip')).css('clip','auto');
				},function() {
					$(this).css('z-index','inherit').css('clip',$(this).attr('clipper'));
				});
			}, 1000);
			setTimeout("clearInterval(timer)",10); //avoids memory leaks
		}
		start = start + 40;
	}, 450);
});
$(window).resize(function() {
	$('#thumbs div').css('height','10px');
	$('#thumbs div').css('height',$('#thumbs').height()+'px');
});

var images = [];
var credits = new Object();

function load_feed(url,append) {
	dataType = (url.indexOf('callback') > 1)?'jsonp':'json';
	if (append === undefined)
		append = false;

	$.ajax({
		url: url,
		dataType: dataType,
		jsonpCallback: (dataType == 'jsonp')?'serveCallback':undefined,
		cache: true,
		success: function(data) {
			if (!data || !data.length) {
				$('#thumbs').append('unable to load more images');
			}
			var thumbs = $('#thumbs div');
			if (append) {
				c=images.length;
			} else {
				c=0;
				images = data;

				thumbs.empty();
			}
			$.each(data, function(key, item) {
				var bits = ['',item.width,item.height];

				if (bits[1] > bits[2]) {
					bits[2] = Math.round(bits[2] * 120/640);
					bits[1] = 120;

					size = bits[2]-6;
				} else {
					bits[1] = Math.round(bits[1] * 120/640);
					bits[2] = 120;

					size = bits[1]-6;
				}
				//thumbs.append('<br style="clear:both"/>width="'+bits[1]+'" height="'+bits[2]+'"');
				wi2 = bits[1] / 2;
				hi2 = bits[2] / 2;
				si2 = size / 2;
				ratio = 75 / size;

				clip = new Array();
				clip[0] = hi2-si2;//top
				clip[1] = wi2+si2;//right
				clip[2] = hi2+si2;//bottom
				clip[3] = wi2-si2;//left

				for(q=0;q<4;q++)
					clip[q] = Math.round(clip[q]*ratio);
				for(q=1;q<3;q++)
					bits[q] = Math.round(bits[q]*ratio);

				//thumbs.append('width="'+bits+'" height="'+clip+'"'+size);
				$('<a href="'+item.url+'" title="'+item.title+' by '+item.realname+'" id="thumb'+c+'" target="_top"><img src="'+item.thumbnail+'" width="'+bits[1]+'" height="'+bits[2]+'" style="clip:rect('+clip.join('px,')+'px);top:-'+clip[0]+'px;left:-'+clip[3]+'px;"/></a>').appendTo(thumbs).find('img').hide().bind('load', function () { $(this).fadeIn(); });
				c++;
				if (append) {
					images.push(item);
				}
				credits[item.realname] = 1;
			});

			if (!append) {

			}
		}
	});
}

var tabs = ['latest','current','new','unrated','highest','random'];

function shakeup(that) {
	 that.href = "?tab="+tabs[Math.floor(Math.random()*tabs.length)]+"<? echo $extra; ?>";
}

function listCredits() {
	var list = [];
	for(var key in credits) {
		list.push(key);
	}
	prompt("List of Contributors", "Images Copyright: "+list.join(', '));
}

/* ]]> */
</script>
</head>
<body>

<? if (!empty($row)) { foreach ($rows as $row) { ?>

	<img src="<? echo str_replace('_120x120','_213x160',$row['thumbnail']); ?>" align="left" alt="<? echo htmlentities($row['title'].' by '.$row['realname']); ?>"/>

<? } } ?>

<table height="100%" width="100%" border="0" cellspacing="0"cellpadding="0">
	<tr>
		<td width="95%" height="90%" id="thumbs" align="center" valign="center"><div>loading... (if you are still seeing this and have JavaScript turned off - turn it on!)</div></td>
	</tr>
	<tr>
		<td height="20" colspan="2" id="footer">
			<div class="right">
				<div id="tribute">
					<div>
						This mini-site shows a hand selected and curated selection of images from the millions submitted to the <b>Geograph Britian and Ireland</b> project :- 
						Showcasing not only the diversity of subjects the British Isles has to offer, but also the dedication and talent of the photographers who venture out - often off the beaten path - and capture the unique photos you see here. <br/><br/>
						... finally we hope this stands as a tribute to everybody to has contributed in building the Geograph Archive so far. 
					</div>
				</div>
				(i) a mini project by <a href="https://www.geograph.org/" target="_top">geograph.org</a>
			</div> |
			<a href="?tab=highest<? echo $extra; ?>" title="view images rated the highest"<? if ($tab == 'highest') { echo ' class="selected"'; } ?>>highest rated</a> |
			<a href="?tab=latest<? echo $extra; ?>" title="view latest suggested images"<? if ($tab == 'latest') { echo ' class="selected"'; } ?>>latest</a> | 
			<a href="?tab=current<? echo $extra; ?>" title="view most current images"<? if ($tab == 'current') { echo ' class="selected"'; } ?>>current</a> | 
			<!--a href="?tab=unrated<? echo $extra; ?>" title="images most in need of votes"<? if ($tab == 'unrated') { echo ' class="selected"'; } ?>>unrated</a> | --> 
			<a href="?tab=new<? echo $extra; ?>" title="view images not see before"<? if ($tab == 'new') { echo ' class="selected"'; } ?>>new</a> | 
			<a href="?tab=random<? echo $extra; ?>" title="view random images"<? if ($tab == 'random') { echo ' class="selected"'; } ?>>random</a> |
			<!--a href="?tab=random<? echo $extra; ?>" onmousemove="shakeup(this)">I don't know!</a> | -->
			<small><a href="./gallery.php?tab=<? echo $tab.$extra; ?>" target="_top">back to slideshow</a></small>
			&middot; <small><a href="#" onclick="listCredits()">List Contributors</a></small>
		</td>
	</tr>
</table>

</body>
</html>
