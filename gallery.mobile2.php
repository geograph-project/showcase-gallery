<?

$tabs = array('highest','random','weekly');
shuffle($tabs);

$tab = (isset($_GET['tab']) && preg_match('/^\w+$/',$_GET['tab']))?$_GET['tab']:array_pop($tabs);
$r = rand(0,5);
$extra = '&mobile=1';

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

	$row = getRow("SELECT * FROM gallery_image WHERE url = ".dbQuote("https:/".$_GET['_escaped_fragment_']));

} else if ($_SERVER['HTTP_USER_AGENT'] == 'Mozilla/5.0 (Windows NT 6.1; rv:6.0) Gecko/20110814 Firefox/6.0') { //Google plus posting bot!
	include "includes/database.php";
	include "includes/mysql-config.inc.php";
	$row = getRow("SELECT * FROM gallery_image WHERE width > 0 AND num > 4 ORDER BY baysian DESC LIMIT 1");
}

include "includes/functions.inc.php";
customGZipHandlerStart();
customExpiresHeader(30,true);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<? if (!empty($row)) { ?>
	<title><? echo htmlentities($row['title']).' by '.htmlentities($row['realname']); ?> :: Geograph Showcase</title>
<? } else { ?>
	<title>Geograph Showcase</title>
<? } ?>
<meta name="description" content="Stunning collection of photographs taken across the British Isles"/>
<link rel="shortcut icon" type="image/x-icon" href="https://s1.geograph.org.uk/favicon.ico"/>
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
#titlebar {
	font-size:1.3em;
        text-align:center;
	padding-left:5px;
	--background-color:#333333;
}
#titlebar small small {
	font-size:0.7em;
}
#titlebar2 {
        font-size:1.3em;
        text-align:center;
	width:200px;
	display:none;
}
#titlebar2 .date {
        font-size:0.7em;
}
#titlebar2 .date b {
	font-size:1.5em;
}

#main {
	font-size:0.6em;
	position:relative;
}
#main img {
        margin:15px;
	margin-top:5px;
	max-width:calc(100vw - 100px);
	max-height:calc(100vh - 140px);
	border-radius:4px;
}
#main div.date {
	font-size:1.5em;
	color:silver;
}
#main div.date span {
	color:gray;
	font-size:0.8em;
}
#main div.next {
	position:absolute;
	top:10px;
	right:0;
	z-index:1000;
	border:1px solid #555;
	border-radius:20px;
}
#main div.next a {
	font-size:3em;
        padding:20px;
}

#main div.prev {
	position:absolute;
	top:10px;
	left:0;
	z-index:1000;
	border:1px solid #555;
	border-radius:20px;
}
#main div.prev a {
	font-size:3em;
        padding:20px;
}

#votes a {
	color:white;
	font-size:2em;
	font-family:monospace;
	display:block;
	text-align:center;
	padding:8px;
	border:1px solid #555;
	border-radius:15px;
}
#votes div a {
	font-size:0.7em;
	font-family: 'Open Sans', sans-serif;
}
#thumbs div {
	overflow: auto;
	white-space:nowrap;
	height:82px;
}
#thumbs img {
	border:0;
	height:60px;
	margin-right:1px;
	border-radius:4px;
}
#footer {
	font-size:0.7em;
	background-color:#333;
	color: gray;
	text-align:center;
}
#footer a {

}
#footer div.right {
	--float:right;
}
#footer a.selected {
	color:silver;
	background-color:#444444;
	border-bottom:1px solid silver;
}
select {
	color:silver;
	background-color:transparent;
	border:0;
}
#mapdiv {
        position:fixed;
        top:10px;
        right:10px;
	left:10px;
	background-color:#222;
	z-index:3000;
	text-align:center;
}
#message {
	z-index:1000000;
	float:left;
	margin-left:-300px;
	position:absolute;
	background-color:pink;
	color:black;
	padding:20px;
	display:none;
}
div#tribute {
	position:relative
}
div#tribute div {
	font-size:1.3em;
	position:absolute;
	top:20px;
	left:20px;
	right:20px;
	border:1px solid white;
	background-color:#333;
	color:silver;padding:10px;
	display:none;
	z-index:2000;
}

.nowrap {
	white-space:nowrap;
}

@media only screen and (orientation: landscape) {
	#titlebarrow {
		display:none;
	}
	#main .date {
		display:none;
	}
	#titlebar2 {
		display:table-cell;
	}
}

</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="https://s1.geograph.org.uk/js/jquery.mousewheel.min.js"></script>
<script type="text/javascript">
/* <![CDATA[ */

var firsttime = (document.cookie.indexOf('__utma') == -1);
var timer = null;
$(function() {
	$('#thumbs div').css('width',$('#thumbs').width()+'px');

	if (window.location.hash.indexOf('#!/www.geograph.org.uk/photo/') == 0) {
		load_feed("gallery.json.php?tab=<? echo "$tab&r=$r"; ?>&hash="+encodeURIComponent(window.location.hash.substring(2)));
	} else if ("<? echo $tab; ?>" == 'nearby') {
		$.getScript( "jquery.geolocation.js" ).done(function( script, textStatus ) {
			$.geolocation.get({success: function(position) {
				load_feed("gallery.json.php?tab=<? echo $tab; ?>&ll="+position.coords.latitude + "," + position.coords.longitude);
			}, fail:function() {
				alert('Unable to load location. Redirecting to another selection');
				window.location.href='?tab=weekly';
			}});
		});
	} else if ("<? echo $tab; ?>" == 'new') {
		load_feed("gallery.json.php?tab=<? echo $tab; ?>");
	} else {
		//this would COULD be loaded via a cachign CDN...
		load_feed("gallery.json.php?tab=<? echo "$tab&r=$r"; if (empty($_GET['tab'])) { echo "&today=1"; } ?>&callback=?");
	}
	if (firsttime || Math.random() > 0.96)
		timer = setTimeout("$('#message').show()",1000);
});
$(window).resize(function() {
	$('#thumbs div').css('width','10px');
	$('#thumbs div').css('width',$('#thumbs').width()+'px');
	if (current > 2) {
		show_main(current); //just to make sure the thumbnail is in view
	}
});
function gonext() {
	if (current+1 < images.length)
		show_main(current+1);
}
function goprev() {
	if (current > 0)
		show_main(current-1);
}
function show_tribute() {
	$('div#tribute div').show();
	return false;
}

$(document).mousewheel(function(e, delta) {
	if(delta > 0)
		goprev();
	else
		gonext();
	return false;
});
$(document).keydown(function(e){
	switch(e.which){
		case 37: case 38: case 80: goprev(); break;
		case 39: case 40: case 78: gonext(); break;
		case 49: vote(1); break;
		case 50: vote(2); break;
		case 51: vote(3); break;
		case 52: vote(4); break;
		case 53: vote(5); break;
	}
});

var images = [];
var current = -1;
var loaded = {};
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
				$('#main').html('unable to load images');
				$('#thumbs').html('unable to load images');
			}
			var thumbs = $('#thumbs div');
			if (append) {
				c=images.length;
			} else {
				c=0;
				images = data;

				if (images[0] && images[0].fullsize) {
					//start it loading before the thumbs load.
					var img = new Image();
					img.src = images[0].fullsize.replace(/https?:\/\//,'https://');
				}
				thumbs.empty();
			}
			$.each(data, function(key, item) {
				thumbs.append('<a href="javascript:void(thumb_click('+c+'))" title="'+item.title+' by '+item.realname+'" id="thumb'+c+'"><img src="'+item.thumbnail.replace(/https?:\/\//,'https://')+'"/></a>');
				c++;
				if (append) {
					images.push(item);
				}
			});

			if (!append) {
				show_main(0);
			}
		}
	});
}
function thumb_click(idx) {
	show_main(idx,true);
}

var months = [ "December", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];

function show_main(idx,skipscroll) {
	var item = images[idx];
	item.comment = $('<div/>').text(item.comment).html(); //aka htmlentities()
	desc = item.comment; if (desc.length> 140) desc = desc.substring(0,140)+"...";
	var date = item.taken;
	if (date.indexOf('0000') ==0) {
		date = '';
	} else if (date.indexOf('-00') ==4) {
		date = "<span>Taken:</span> "+date.substring(0,4);
	} else if (date.indexOf('-00') ==7) {
		var bits = date.split(/-/);
		var d = new Date(bits[0],bits[1],1);
		date = "<span>Taken:</span> "+months[d.getMonth()]+' <b>'+date.substring(0,4)+'</b>';
	} else {
		var bits = date.split(/-/);
		var d = new Date(bits[0],bits[1],bits[2]);
//console.log(d.getMonth(),date);
		date = "<span>Taken:</span> "+months[d.getMonth()]+' '+d.getDate()+', <b>'+date.substring(0,4)+'</b>';
	}

	$('#titlebar').html('<div><a href="'+item.url+'" target="_blank">'+item.title+'</a> by <a href="https://www.geograph.org.uk'+item.profile_link+'" target="_blank">'+item.realname+'</a> <small>for <a href="https://www.geograph.org.uk/gridref/'+item.grid_reference+'">'+item.grid_reference+'</a> - <a href="#" onclick="showMap(\''+item.wgs84_lat+' '+item.wgs84_long+'\');return false;">Map</a></div>');

	$('#titlebar2').html('<div><a href="'+item.url+'" target="_blank">'+item.title+'</a><br/><div class="date">'+date+'</div> by <a href="https://www.geograph.org.uk'+item.profile_link+'" target="_blank">'+item.realname+'</a><br/> <small>for <a href="https://www.geograph.org.uk/gridref/'+item.grid_reference+'">'+item.grid_reference+'</a> <a href="#" onclick="showMap(\''+item.wgs84_lat+' '+item.wgs84_long+'\'); return false">Map</a></div>');

	$('#main').html('<div class="date">'+date+'</div><a href="'+item.url+'" title="'+item.title+' by '+item.realname+'\n\n'+item.comment+'" target="_blank"><img src="'+item.fullsize.replace(/https?:\/\//,'https://')+'"/></a><br/>&copy; Copyright <a href="https://www.geograph.org.uk'+item.profile_link+'" target="_blank">'+item.realname+'</a> and licensed <span class=nowrap>for reuse under this <a href="https://creativecommons.org/licenses/by-sa/2.0/" target="_blank">Creative Commons Licence</a></span>.');

	$('#main').prepend('<div class="next"><a href="javascript:void(gonext())" accesskey="+">&gt;</a></div>');
	if (current > 0)
		$('#main').prepend('<div class="prev"><a href="javascript:void(goprev())" accesskey="-">&lt;</a></div>');

	document.title = item.title;
	window.location.hash = "!"+item.url.replace(/https?:\//,'');

	if (idx+1 < images.length) {
		var img = new Image();
		img.src = images[idx+1].fullsize.replace(/https?:\/\//,'https://');
	}
	current = idx;

	if (!skipscroll) {
		var positionLeft = $('#thumb'+idx).first().position().left;
		var scrollLeft = $('#thumbs div').scrollLeft();
		$('#thumbs div').scrollLeft(positionLeft + scrollLeft - 150);
	}

	$('#thumbs a').css('background-color','inherit');
	$('a#thumb'+idx).css('background-color','blue');

	if (current > (images.length-3) && images.length < 400 && loaded["done"+images.length] === undefined) {
		load_feed("gallery.json.php?tab=<? echo "$tab&r=$r"; ?>&start="+images.length,true);
		loaded["done"+images.length] = true;
	}
}

function suggest() {
	var list = prompt("Enter the id (or link) of the IMAGE you wish to add. - CURRENTLY ONLY WORKS WITH geograph.org.uk IMAGES",'');
	if (list == null) {
		return;
	}

        //the digits in a image url
        list = list.replace(/s\d\.geograph/g,'')
        	   .replace(/photos\/(\d{2}\/)+/g,'')
                   .replace(/_\w{8}_\d+[xX]+\d+\.jpg/g,'')
                   .replace(/_\w{8}\.jpg/g,'');

	var splited = list.split(/[^\d]+/);

	count=0;
	for(i=0; i < splited.length; i++) {
		image = splited[i];
		if (image != '') {
			$.ajax({
  				url: "gallery.json.php?suggest="+image
  			});
  			count++;
		}
	}
	alert("Thank you for submitting "+count+" image(s)");
	return false;
}
function vote(v) {
	$('#message').hide(); if (timer) { clearTimeout(timer); timer = null;}
	var item = images[current];
	$.ajax({
		url: "gallery.json.php?tab=<? echo $tab; ?>&v="+v+"&url="+encodeURIComponent(item.url)
	});
	if (current+1 < images.length) {
		show_main(current+1);
	} else if (loaded["done"+images.length]) { //if true suggests its still loading.
		setTimeout(function() {
			if (current+1 < images.length) {
		                show_main(current+1);
		        }
		},1500);
	} else {
		alert("no more images");
	}
}
function showMap(geo) {
	bits = geo.split(/ /);
	url = "https://maps.googleapis.com/maps/api/staticmap?markers=size:med|"+bits[0]+","+bits[1]+"&zoom=7&size=200x200&maptype=terrain&key=".$CONF['maps_api_key'];

	document.images['map'].src= url;
	$("#mapdiv").toggle();
}
function hideMap() {
	$("#mapdiv").hide('fast');
}

var tabs = ['latest','current','new','unrated','highest','random','weekly'];

function shakeup(that) {
	 that.href = "?tab="+tabs[Math.floor(Math.random()*tabs.length)];
}

/* ]]> */
</script>
</head>
<body>
<div id="mapdiv" style="display:none"><a href="javascript:void(hideMap());">Close</a><br> <img name="map"/></div>

<? if (!empty($row)) { ?>

	<img src="<? echo str_replace('_120x120','_213x160',$row['thumbnail']); ?>" align="left" alt="<? echo htmlentities($row['title'].' by '.$row['realname']); ?>"/>

<? } ?>

<table height="100%" width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td height="40" colspan="3" id="footer" valign="top">
			<div class="right">
				<div id="tribute">
					<div>
						This mini-site shows a hand selected and curated selection of images from the millions submitted to the <b>Geograph Britian and Ireland</b> project :- 
						Showcasing not only the diversity of subjects the British Isles has to offer, but also the dedication and talent of the photographers who venture out - often off the beaten path - and capture the unique photos you see here. <br/><br/>
						Please have a browse of the images and use the buttons on the right to vote on the current shown image.<br/><br/>
						TIP: Use the cursor keys, or mousewheel to scroll though images.<br/><br/>
						... finally we hope this stands as a tribute to everybody to has contributed in building the Geograph Archive so far. 
						<br><br>
						<a href=# onclick="$('div#tribute div').hide('slow');return false">Close</a>
					</div>
				</div>

				a mini project by <a href="https://www.geograph.org/" target="_top">geograph.org</a> (<a href="#" onclick="return show_tribute()">about</a>)     &nbsp; &nbsp;
				<a href="javascript:void(suggest())" title="suggest new images for this gallery">suggest images</a>

			</div>&nbsp; <b>Selection</b>: |
			<a href="?tab=highest<? echo $extra; ?>" title="view images rated the highest"<? if ($tab == 'highest') { echo ' class="selected"'; } ?>>highest rated</a> |
			<a href="?tab=weekly<? echo $extra; ?>" title="view images rated in the last 7 days"<? if ($tab == 'weekly') { echo ' class="selected"'; } ?>>this week</a> |
			<a href="?tab=random<? echo $extra; ?>" title="view random images"<? if ($tab == 'random') { echo ' class="selected"'; } ?>>random</a> |
			<a href="?tab=current<? echo $extra; ?>" title="view most current images"<? if ($tab == 'current') { echo ' class="selected"'; } ?>>current</a> | 
			<a href="?tab=latest<? echo $extra; ?>" title="view latest suggested images"<? if ($tab == 'latest') { echo ' class="selected"'; } ?>>latest</a> | 
			<a href="?tab=unrated<? echo $extra; ?>" title="images most in need of votes"<? if ($tab == 'unrated') { echo ' class="selected"'; } ?>>unrated</a> | 
			<!--a href="?tab=new<? echo $extra; ?>" title="view images not see before"<? if ($tab == 'new') { echo ' class="selected"'; } ?>>new</a> | 
			<a href="?tab=random<? echo $extra; ?>" title="pick a random mode" onmousemove="shakeup(this)">I don't know!</a> | -->
			<? if (empty($_GET['crit'])) { ?>
			<a href="?tab=nearby<? echo $extra; ?>" title="high rated images near you"<? if ($tab == 'nearby') { echo ' class="selected"'; } ?>>nearby</a> |
			<? } ?>
			<!--small><a href="https://www.geograph.org.uk/explore/searches.php" target="_top">more...</a></small-->
			<span class=nowrap>
			<? if ($tab != 'nearby') { ?>
			<form style="display:inline">
				<input type=hidden name=tab value="<? echo $tab; ?>"/>
				<input type=hidden name=mobile value="1"/>
				<b>Filter</b>:
				<select name="crit" onchange="this.form.submit()">
					<option value="">All Countries</option>
					<?
					foreach(explode(',','England,Isle of Man,Northern Ireland,Republic of Ireland,Scotland,Wales') as $country) {
						printf('<option value="Country:%s"%s>%s</option>',$country,(isset($_GET['crit']) && $_GET['crit'] == "Country:$country")?' selected':'',$country);
					}
					?>
				</select> |
			</form>
			<? } ?>
			or <a href="https://www.geograph.org.uk/browser/#!/content_title=Showcase+Gallery/content_id=1/sort=score" target="_top" title="view gallery images in a interactive viewer">View in Browser</a>
			</span>
		</td>
	</tr>
	<tr id="titlebarrow">
		<td colspan="3" height="40" id="titlebar"></td>
	</tr>
	<tr>
		<td id="titlebar2"></td>
		<td id="main" align="center" valign="center"><big>TIP: Use the cursor keys, or mousewheel to scroll though images.</big><br/><br/> 
		loading... (if you are still seeing this and have JavaScript turned off - you need to turn it on to use this page)</td>
		<td width="70" id="votes" align="right" valign="top" style="padding-top:10px">vote:&nbsp;&nbsp;&nbsp;<br/>

		<div id="message" onmouseover="$(this).hide()">Cast your vote for this image here -><br>(1 poor, 5 excellent) </div><br/>

			<a href="javascript:void(vote(5))" title="Excellent!" accesskey="5">5</a>
			<a href="javascript:void(vote(4))" title="Good!" accesskey="4">4</a>
			<a href="javascript:void(vote(3))" title="Average" accesskey="3">3</a>
			<a href="javascript:void(vote(2))" title="So So" accesskey="2">2</a>
			<a href="javascript:void(vote(1))" title="Poor" accesskey="1">1</a>
		</td>
	</tr>
	<tr>
		<td height="80" colspan="3" id="thumbs"><div>loading...</div></td>
	</tr>
</table>

</body>
</html>
