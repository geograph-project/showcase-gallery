<?php

function my_session_id() {
	if (!empty($_COOKIE['PHPSESSID']))
		return $_COOKIE['PHPSESSID'];
	if (!empty($_COOKIE['__utma']))
		return $_COOKIE['__utma'];
	if (!empty($_COOKIE['GALSESSID']))
		return $_COOKIE['GALSESSID'];
	$id = md5(uniqid('sess',true));
	setcookie('GALSESSID', $id, 0, false, false, true, true);
	return $id;
}

function he($in) {
	return  htmlentities($in);
}
function dis($in) {
	return "<b>".number_format($in,0)."</b>";
}
function hec($in) {
        global $c;
        return  htmlentities($c[$in]);
}
function ehec($in) {
        echo hec($in);
}

function print_rp($q) {
	print "<pre style='border:1px solid red; padding:10px; text-align:left; background-color:silver'>";
	print_r($q);
	print "</pre>";
}

###############################################

// the functions below, generally stoled from the main Geograph project.
//  .. see main project for copyright.

function getRemoteIP()
{
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
                $ips=explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip=trim($ips[0]);
        }
        else
        {
                $ip=$_SERVER['REMOTE_ADDR'];
        }
//    [HTTP_X_FORWARDED_FOR] => 2a05:d018:fcc:be01:35e0:4306:928a:97ca

        if (!preg_match('/^[a-f\d]+([\.:][a-f\d]*)+$/i',$ip))
         //we often use getRemoteIP to insert directly into database. because from HTTP_X_FORWARDED_FOR there is a chance is spoofed, and vulnerable to SQL injection (although should be ok if REALLY behind cache, as OUR proxy will set it safely.)
                return 0;
        return $ip;
}


function getGeographUrl($gridimage_id,$hash,$size = 'small') {
	$ab=sprintf("%02d", floor(($gridimage_id%1000000)/10000));
	$cd=sprintf("%02d", floor(($gridimage_id%10000)/100));
	$abcdef=sprintf("%06d", $gridimage_id);
	if ($gridimage_id<1000000) {
		$fullpath="/photos/$ab/$cd/{$abcdef}_{$hash}";
	} else {
		$yz=sprintf("%02d", floor($gridimage_id/1000000));
		$fullpath="/geophotos/$yz/$ab/$cd/{$abcdef}_{$hash}";
	}
	$server =  "https://s".($gridimage_id%4).".geograph.org.uk";

	switch($size) {
		case 'full': return "https://s0.geograph.org.uk$fullpath.jpg"; break;
		case 'med': return "$server{$fullpath}_213x160.jpg"; break;
		case 'small':
		default: return "$server{$fullpath}_120x120.jpg";

	}
}

if (!function_exists('linktoself')) {
        function linktoself($params,$selflink= '') {
                $a = array();
                $b = explode('?',$_SERVER['REQUEST_URI']);
                if (isset($b[1]))
                        parse_str($b[1],$a);

                if (isset($params['value']) && isset($a[$params['name']])) {
                        if ($params['value'] == 'null') {
                                unset($a[$params['name']]);
                        } else {
                                $a[$params['name']] = $params['value'];
                        }

                } else {
                        foreach ($params as $key => $value)
                                $a[$key] = $value;
                }

                if (!empty($params['delete'])) {
                        if (is_array($params['delete'])) {
                                foreach ($params['delete'] as $del) {
                                        unset($a[$del]);
                                }
                        } else {
                                unset($a[$params['delete']]);
                        }
                        unset($a['delete']);
                }
                if (empty($selflink)) {
                        $selflink = $_SERVER['SCRIPT_NAME'];
                }
                if ($selflink == '/index.php') {
                        $selflink = '/';
                }

                return htmlentities($selflink.(count($a)?("?".http_build_query($a,'','&')):''));
        }
}

function externalLink($params)
{
	global $CONF;
  	//get params and use intelligent defaults...
  	$href=str_replace(' ','+',$params['href']);
	if (!preg_match('/^https?:\/\//',$href) && strpos($href,'/') !== 0)
  		$href ="http://$href";

  	if (isset($params['text']))
  		$text=$params['text'];
  	else
  		$text=$href;

  	if (isset($params['title']))
		$title=$params['title'];
	else
		$title=$text;

	if (isset($params['nofollow']))
		$title .= "\" rel=\"nofollow";

  	if ($params['target'] == '_blank') {
  		return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\" target=\"_blank\">$text</a>".
  			"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - opens in a new window\" src=\"https://s1.geograph.org.uk/img/newwin.png\" width=\"10\" height=\"10\"/></span>";
  	} else {
  		return "<span class=\"nowrap\"><a title=\"$title\" href=\"$href\">$text</a>".
  			"<img style=\"padding-left:2px;\" alt=\"External link\" title=\"External link - shift click to open in new window\" src=\"https://s1.geograph.org.uk/img/external.png\" width=\"10\" height=\"10\"/></span>";
  	}
}


function customCacheControl($mtime,$uniqstr,$useifmod = true,$gmdate_mod = 0) {
	global $encoding;
	if (isset($encoding) && $encoding != 'none') {
		$uniqstr .= $encoding;
	}

	$hash = "\"".md5($mtime.'-'.$uniqstr)."\"";

	if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) { // check ETag
		if($_SERVER['HTTP_IF_NONE_MATCH'] == $hash ) {
			header("HTTP/1.0 304 Not Modified");
			header ("Etag: $hash");
			header('Content-Length: 0');
			exit;
		}

		//also check legacy Etag
		$hash2 = "\"".$mtime.'-'.md5($uniqstr)."\"";

		if($_SERVER['HTTP_IF_NONE_MATCH'] == $hash2 ) {
			header("HTTP/1.0 304 Not Modified");
			header ("Etag: $hash2");
			header('Content-Length: 0');
			exit;
		}
	}

	header ("Etag: $hash");

	if (!$gmdate_mod)
		$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

	if ($useifmod && !empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		$if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);

		if ($if_modified_since == $gmdate_mod) {
			header("HTTP/1.0 304 Not Modified");
			header('Content-Length: 0');
			exit;
		}
	}

	header("Last-Modified: $gmdate_mod");
}

function customNoCacheHeader($type = 'nocache',$disable_auto = false) {
	//none/nocache/private/private_no_expire/public
	if ($type == 'nocache') {
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		customExpiresHeader(-1);
	}
	if ($disable_auto) {
		//call to disable the auto session one, could then call another here if needbe
		session_cache_limiter('none');
	}
}

function customExpiresHeader($diff,$public = false) {
	if ($diff > 0) {
		$expires=gmstrftime("%a, %d %b %Y %H:%M:%S GMT", time()+$diff);
		header("Expires: $expires");
		header("Cache-Control: max-age=$diff",false);
	} else {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Cache-Control: max-age=0",false);
	}
	if ($public)
		header("Cache-Control: Public",false);
}

function getEncoding() {
	global $encoding;
	if (!empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
		$gzip = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
		$deflate = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate');

		$encoding = $gzip ? 'gzip' : ($deflate ? 'deflate' : '');

		if (!strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') &&
				preg_match('/^Mozilla\/4\.0 \(compatible; MSIE ([0-9]\.[0-9])/i', $_SERVER['HTTP_USER_AGENT'], $matches)) {
			$version = floatval($matches[1]);

			if ($version < 6)
				$encoding = '';

			if ($version == 6 && !strstr($_SERVER['HTTP_USER_AGENT'], 'EV1'))
				$encoding = '';
		}
	} else {
		$encoding = '';
	}
	return $encoding;
}

function customGZipHandlerStart() {
	global $encoding;
	if ($encoding = getEncoding()) {
		ob_start();
		register_shutdown_function('customGZipHandlerEnd');
		return true;
	}
	return false;
}

function customGZipHandlerEnd() {
	global $encoding,$cachepath;

	$contents = ob_get_clean();

	if (isset($encoding) && $encoding) {
		// Send compressed contents
		$contents = gzencode($contents, 9,  ($encoding == 'gzip') ? FORCE_GZIP : FORCE_DEFLATE);
		header ('Content-Encoding: '.$encoding);
		header ('Vary: Accept-Encoding');
	}
	//else ... we could still send Vary: but because a browser that doesnt will accept non gzip in all cases, doesnt matter if the cache caches the non compressed version (the otherway doesnt hold true, hence the Vary: above)
	header('Content-Length: '.strlen($contents));

	if (!empty($cachepath) && empty($nocache)) {
		file_put_contents($cachepath,$contents);

		$mtime = @filemtime($cachepath);

		customExpiresHeader(3600*24*24,true);
		customCacheControl($mtime,$cachepath);
	}

	echo $contents;
}


function htmlspecialchars2( $myHTML,$quotes = ENT_COMPAT,$char_set = 'ISO-8859-1')
{
	return preg_replace( "/&amp;([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};|#x[0-9a-fA-F]{2,4};)/", '&$1' ,htmlspecialchars($myHTML,$quotes,$char_set));
}

function htmlentities2( $myHTML,$quotes = ENT_COMPAT,$char_set = 'ISO-8859-1')
{
	return preg_replace( "/&amp;([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};|#x[0-9a-fA-F]{2,4};)/", '&$1' ,htmlentities($myHTML,$quotes,$char_set));
}

function htmlnumericentities($myXML){
        return str_replace('&#38;amp;','&#38;', preg_replace_callback('/[^!-%\x27-;=?-~ ]/',
               function($m) { return '&#'.ord($m[0]).';'; },
               htmlspecialchars($myXML)));
}


function MakeLinks($posterText) {
        $posterText = preg_replace_callback('/(?<!["\'>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', function($m) {
                return externalLink(array('href'=>$m[1],'text'=>'Link','nofollow'=>1,'title'=>$m[1]));
        }, $posterText);

        $posterText = preg_replace_callback('/(?<![>\/F\."\'])(www\.[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:\@\!]*)(?<!\.)(?!["\'])/', function($m) {
                return externalLink(array('href'=>"http://".$m[1],'text'=>'Link','nofollow'=>1,'title'=>$m[1]));
        }, $posterText);

	$posterText = str_replace("/\n\n\n/",'<br/><br/>',$posterText);

	return $posterText;
}


function mail_wrapper($email, $subject, $body, $headers = '', $param = '', $debug = false) {

	if (!empty($_SERVER['CONF_SMTP_HOST'])) {
		require_once __DIR__."/class.phpmailer.php";
		require_once __DIR__."/class.smtp.php";

		$mail = new PHPMailer;

		#########################
		if ($debug)
			$mail->SMTPDebug = 3;                               // Enable verbose debug output

		$mail->XMailer = 'x'; //used to SKIP the header

		$mail->isSMTP();
		$mail->Host = $_SERVER['CONF_SMTP_HOST'];
		if (!empty($_SERVER['CONF_SMTP_USER'])) {
			$mail->SMTPAuth = true;
			$mail->Username = $_SERVER['CONF_SMTP_USER'];
			$mail->Password = $_SERVER['CONF_SMTP_PASS'];
		}
		if ($_SERVER['CONF_SMTP_PORT']> 25)
			$mail->SMTPSecure = 'tls';                    // Enable TLS encryption, `ssl` also accepted
		$mail->Port = $_SERVER['CONF_SMTP_PORT'];                     // TCP port to connect to

		#########################

		$mail->setFrom($_SERVER['CONF_SMTP_FROM'],'',true);//set sender too

		#########################
		// parse the general headers from request

		if ($headers) {
			if (is_array($headers))
				$headers = implode("\n", $headers);

			//basic header parser

			if (preg_match('/Received:(.*)/',$headers, $m)) {
                                $mail->addCustomHeader('Received',trim($m[1]));
                        }

			if (preg_match('/From:(.*)<(.*)>/',$headers, $m)) { //no DOTALL, so shouldnt match mutliline!
				//$mail->setFrom('from@example.com', 'Mailer');
				$mail->setFrom(trim($m[2]), trim($m[1]));
			} elseif (preg_match('/From:(.*)/',$headers, $m)) {
                                $mail->setFrom(trim($m[1]));
                        }

			if (preg_match('/Reply-To:(.*)<(.*)>/',$headers, $m)) {
				$mail->addReplyTo(trim($m[2]), trim($m[1]));
			} elseif (preg_match('/Reply-To:(.*)/',$headers, $m)) {
                                $mail->addReplyTo(trim($m[1]));
                        }

			if (preg_match('/Sender:(.*)/',$headers, $m)) {
                                $mail->Sender = trim($m[1]);
                        }

			//$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			if (preg_match('/Content-Type:([^;]+)/',$headers, $m)) {
				//could use isHtml, but might as well just set content type directly. 
				$mail->ContentType = trim($m[1]);
				//$mail->CharSet = ... charset goes in seperate variable, ignore for now
			}

                }

		if (preg_match('/(.*)<(.*)>/',$email, $m)) {
			$mail->addAddress(trim($m[2]), trim($m[1]));
		} else
			$mail->addAddress($email);

		$mail->Subject = $subject;
		$mail->Body = $body; //if using isHTML will be the HTML verson, AltBody, will be plain text!

		return $mail->send();
	} else {
		return mail($email, $subject, $body, $headers, $param);
	}
}

