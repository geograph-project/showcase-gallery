<?php
/***************************************************************************

FeedCreator class v1.7.71(BH)
originally (c) Kai Blankenhorn
www.bitfolge.de
kaib@bitfolge.de
v1.3 work by Scott Reynen (scott@randomchaos.com) and Kai Blankenhorn
v1.5 OPML support by Dirk Clemens

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

****************************************************************************


Changelog:

v1.7.12(BH)	14-06-18
	Added Enclosures (Barry Hunter)

v1.7.10(BH)	20-08-11
	added JSON  (Barry Hunter)

v1.7.9(BH)	16-11-08
	added ATOM10  (Barry Hunter)

v1.7.8(BH)	31-12-07
	added MediaRSS output (and georss to RSS2) (Barry Hunter)

v1.7.71(BH)	13-02-07
	correct georss namespace (Barry Hunter)

v1.7.7(BH)	28-03-06
	added GPX Feed (Barry Hunter)

v1.7.6(BH)	20-02-06
	added GeoRSS Feed (Barry Hunter)

v1.7.5(BH)	16-11-05
	added BASE Feed (Barry Hunter)

v1.7.4(BH)	05-07-05
	added KML Feed (Barry Hunter)

v1.7.3(BH)	05-07-05
	added PHP Feed (Barry Hunter)

v1.7.2	10-11-04
	license changed to LGPL

v1.7.1
	fixed a syntax bug
	fixed left over debug code

v1.7	07-18-04
	added HTML and JavaScript feeds (configurable via CSS) (thanks to Pascal Van Hecke)
	added HTML descriptions for all feed formats (thanks to Pascal Van Hecke)
	added a switch to select an external stylesheet (thanks to Pascal Van Hecke)
	changed default content-type to application/xml
	added character encoding setting
	fixed numerous smaller bugs (thanks to S�ren Fuhrmann of golem.de)
	improved changing ATOM versions handling (thanks to August Trometer)
	improved the UniversalFeedCreator's useCached method (thanks to S�ren Fuhrmann of golem.de)
	added charset output in HTTP headers (thanks to S�ren Fuhrmann of golem.de)
	added Slashdot namespace to RSS 1.0 (thanks to S�ren Fuhrmann of golem.de)

v1.6	05-10-04
	added stylesheet to RSS 1.0 feeds
	fixed generator comment (thanks Kevin L. Papendick and Tanguy Pruvot)
	fixed RFC822 date bug (thanks Tanguy Pruvot)
	added TimeZone customization for RFC8601 (thanks Tanguy Pruvot)
	fixed Content-type could be empty (thanks Tanguy Pruvot)
	fixed author/creator in RSS1.0 (thanks Tanguy Pruvot)

v1.6 beta	02-28-04
	added Atom 0.3 support (not all features, though)
	improved OPML 1.0 support (hopefully - added more elements)
	added support for arbitrary additional elements (use with caution)
	code beautification :-)
	considered beta due to some internal changes

v1.5.1	01-27-04
	fixed some RSS 1.0 glitches (thanks to St�phane Vanpoperynghe)
	fixed some inconsistencies between documentation and code (thanks to Timothy Martin)

v1.5	01-06-04
	added support for OPML 1.0
	added more documentation

v1.4	11-11-03
	optional feed saving and caching
	improved documentation
	minor improvements

v1.3    10-02-03
	renamed to FeedCreator, as it not only creates RSS anymore
	added support for mbox
	tentative support for echo/necho/atom/pie/???
        
v1.2    07-20-03
	intelligent auto-truncating of RSS 0.91 attributes
	don't create some attributes when they're not set
	documentation improved
	fixed a real and a possible bug with date conversions
	code cleanup

v1.1    06-29-03
	added images to feeds
	now includes most RSS 0.91 attributes
	added RSS 2.0 feeds

v1.0    06-24-03
	initial release



***************************************************************************
*          A little setup                                                 *
**************************************************************************/

// your local timezone, set to "" to disable or for GMT
define("TIME_ZONE","");




/**
 * Version string.
 **/
define("FEEDCREATOR_VERSION", "FeedCreator 1.7.12(BH)");

class EmptyClass {} 


/**
 * A FeedItem is a part of a FeedCreator feed.
 *
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 * @since 1.3
 */
class FeedItem extends HtmlDescribable {
	/**
	 * Mandatory attributes of an item.
	 */
	var $title, $description, $link;
	
	/**
	 * Optional attributes of an item.
	 */
	var $author, $authorEmail, $image, $category, $comments, $guid, $source, $creator;
	
	/**
	 * Publishing date of an item. May be in one of the following formats:
	 *
	 *	RFC 822:
	 *	"Mon, 20 Jan 03 18:05:41 +0400"
	 *	"20 Jan 03 18:05:41 +0000"
	 *
	 *	ISO 8601:
	 *	"2003-01-20T18:05:41+04:00"
	 *
	 *	Unix:
	 *	1043082341
	 */
	var $date;
	
	/**
	 * Any additional elements to include as an assiciated array. All $key => $value pairs
	 * will be included unencoded in the feed item in the form
	 *     <$key>$value</$key>
	 * Again: No encoding will be used! This means you can invalidate or enhance the feed
	 * if $value contains markup. This may be abused to embed tags not implemented by
	 * the FeedCreator class used.
	 */
	var $additionalElements = Array();

	// on hold
	// var $source;
}



/**
 * An FeedImage may be added to a FeedCreator feed.
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 * @since 1.3
 */
class FeedImage extends HtmlDescribable {
	/**
	 * Mandatory attributes of an image.
	 */
	var $title, $url, $link;
	
	/**
	 * Optional attributes of an image.
	 */
	var $width, $height, $description;
}



/**
 * An HtmlDescribable is an item within a feed that can have a description that may
 * include HTML markup.
 */
class HtmlDescribable {
	/**
	 * Indicates whether the description field should be rendered in HTML.
	 */
	var $descriptionHtmlSyndicated;
	
	/**
	 * Indicates whether and to how many characters a description should be truncated.
	 */
	var $descriptionTruncSize;
	
	/**
	 * Returns a formatted description field, depending on descriptionHtmlSyndicated and
	 * $descriptionTruncSize properties
	 * @return    string    the formatted description  
	 */
	function getDescription($overrideSyndicateHtml = false) {
		$descriptionField = new FeedHtmlField($this->description);
		$descriptionField->syndicateHtml = $overrideSyndicateHtml || $this->descriptionHtmlSyndicated;
		$descriptionField->truncSize = $this->descriptionTruncSize;
		return $descriptionField->output();
	}

}


/**
 * An FeedHtmlField describes and generates
 * a feed, item or image html field (probably a description). Output is 
 * generated based on $truncSize, $syndicateHtml properties.
 * @author Pascal Van Hecke <feedcreator.class.php@vanhecke.info>
 * @version 1.6
 */
class FeedHtmlField {
	/**
	 * Mandatory attributes of a FeedHtmlField.
	 */
	var $rawFieldContent;
	
	/**
	 * Optional attributes of a FeedHtmlField.
	 * 
	 */
	var $truncSize, $syndicateHtml;
	
	/**
	 * Creates a new instance of FeedHtmlField.
	 * @param  $string: if given, sets the rawFieldContent property
	 */
	function FeedHtmlField($parFieldContent) {
		if ($parFieldContent) {
			$this->rawFieldContent = $parFieldContent;
		}
	}
		
		
	/**
	 * Creates the right output, depending on $truncSize, $syndicateHtml properties.
	 * @return string    the formatted field
	 */
	function output() {
		// when field available and syndicated in html we assume 
		// - valid html in $rawFieldContent and we enclose in CDATA tags
		// - no truncation (truncating risks producing invalid html)
		if (!$this->rawFieldContent) {
			$result = "";
		}	elseif ($this->syndicateHtml) {
			$result = "<![CDATA[".$this->rawFieldContent."]]>";
		} else {
			if ($this->truncSize and is_int($this->truncSize)) {
				$result = FeedCreator::iTrunc(htmlnumericentities($this->rawFieldContent),$this->truncSize);
			} else {
				$result = htmlnumericentities($this->rawFieldContent);
			}
		}
		return $result;
	}

}



/**
 * UniversalFeedCreator lets you choose during runtime which
 * format to build.
 * For general usage of a feed class, see the FeedCreator class
 * below or the example above.
 *
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class UniversalFeedCreator extends FeedCreator {
	var $_feed;
	
	function _setFormat($format) {
		switch (strtoupper($format)) {
			
			case "MEDIA":
			case "BASE":
				$this->format = $format;
			case "2.0":
				// fall through
			case "RSS2.0":
				$this->_feed = new RSSCreator20();
				break;
			
			case "GEOPHOTORSS":
			case "PHOTORSS":
			case "GEORSS":
				$this->format = $format;
			case "1.0":
				// fall through
			case "RSS1.0":
				$this->_feed = new RSSCreator10();
				break;
			
			case "JSON":
				// fall through
			case "JSONP":
				$this->_feed = new JSONCreator();
				break;
			
			case "0.91":
				// fall through
			case "RSS0.91":
				$this->_feed = new RSSCreator091();
				break;
			
			case "PIE0.1":
				$this->_feed = new PIECreator01();
				break;
			
			case "MBOX":
				$this->_feed = new MBOXCreator();
				break;
			
			case "OPML":
				$this->_feed = new OPMLCreator();
				break;
				
			case "TOOLBAR":
				$this->format = $format;
			case "ATOM":
				// fall through: always the latest ATOM version
				
			case "ATOM1.0":
				$this->_feed = new AtomCreator10();
				break;
				
			case "ATOM0.3":
				$this->_feed = new AtomCreator03();
				break;
				
			case "HTML":
				$this->_feed = new HTMLCreator();
				break;
			
			case "PHP":
				$this->_feed = new PHPCreator();
				break;
			case "GPX":
				$this->_feed = new GPXCreator();
				break;
			case "KML":
				$this->_feed = new KMLCreator();
				break;
			case "JS":
				// fall through
			case "JAVASCRIPT":
				$this->_feed = new JSCreator();
				break;
			
			default:
				$this->_feed = new RSSCreator091();
				break;
		}
        
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			// prevent overwriting of properties "contentType", "encoding"; do not copy "_feed" itself
			if (!in_array($key, array("_feed", "contentType", "encoding"))) {
				$this->_feed->{$key} = $this->{$key};
			}
		}
	}
	
	/**
	 * Creates a syndication feed based on the items previously added.
	 *
	 * @see        FeedCreator::addItem()
	 * @param    string    format    format the feed should comply to. Valid values are:
	 *			"PIE0.1", "mbox", "RSS0.91", "RSS1.0", "RSS2.0", "OPML", "ATOM0.3", "HTML", "JS"
	 * @return    string    the contents of the feed.
	 */
	function createFeed($format = "RSS0.91") {
		$this->_setFormat($format);
		return $this->_feed->createFeed();
	}
	
	
	
	/**
	 * Saves this feed as a file on the local disk. After the file is saved, an HTTP redirect
	 * header may be sent to redirect the use to the newly created file.
	 * @since 1.4
	 * 
	 * @param	string	format	format the feed should comply to. Valid values are:
	 *			"PIE0.1" (deprecated), "mbox", "RSS0.91", "RSS1.0", "RSS2.0", "OPML", "ATOM", "ATOM0.3", "HTML", "JS"
	 * @param	string	filename	optional	the filename where a recent version of the feed is saved. If not specified, the filename is $_SERVER["PHP_SELF"] with the extension changed to .xml (see _generateFilename()).
	 * @param	boolean	displayContents	optional	send the content of the file or not. If true, the file will be sent in the body of the response.
	 */
	function saveFeed($format="RSS0.91", $filename="", $displayContents=true) {
		$this->_setFormat($format);
		$this->_feed->saveFeed($filename, $displayContents);
	}


   /**
    * Turns on caching and checks if there is a recent version of this feed in the cache.
    * If there is, an HTTP redirect header is sent.
    * To effectively use caching, you should create the FeedCreator object and call this method
    * before anything else, especially before you do the time consuming task to build the feed
    * (web fetching, for example).
    *
    * @param   string   format   format the feed should comply to. Valid values are:
    *       "PIE0.1" (deprecated), "mbox", "RSS0.91", "RSS1.0", "RSS2.0", "OPML", "ATOM0.3".
    * @param filename   string   optional the filename where a recent version of the feed is saved. If not specified, the filename is $_SERVER["PHP_SELF"] with the extension changed to .xml (see _generateFilename()).
    * @param timeout int      optional the timeout in seconds before a cached version is refreshed (defaults to 3600 = 1 hour)
    */
   function useCached($format="RSS0.91", $filename="", $timeout=3600) {
      $this->_setFormat($format);
      $this->_feed->useCached($filename, $timeout);
   }

}


/**
 * FeedCreator is the abstract base implementation for concrete
 * implementations that implement a specific format of syndication.
 *
 * @abstract
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 * @since 1.4
 */
class FeedCreator extends HtmlDescribable {

	/**
	 * Mandatory attributes of a feed.
	 */
	var $title, $description, $link;
	
	
	/**
	 * Optional attributes of a feed.
	 */
	var $syndicationURL, $image, $language, $copyright, $pubDate, $lastBuildDate, $editor, $editorEmail, $webmaster, $category, $docs, $ttl, $rating, $skipHours, $skipDays;

	/**
	* The url of the external xsl stylesheet used to format the naked rss feed.
	* Ignored in the output when empty.
	*/
	var $xslStyleSheet = "";
	
	
	/**
	 * @access private
	 */
	var $items = Array();
 	
	
	/**
	 * This feed's MIME content type.
	 * @since 1.4
	 * @access private
	 */
	var $contentType = "application/xml";
	
	
	/**
	 * This feed's character encoding.
	 * @since 1.6.1
	 **/
	var $encoding = "ISO-8859-1";
	
	
	/**
	 * Any additional elements to include as an assiciated array. All $key => $value pairs
	 * will be included unencoded in the feed in the form
	 *     <$key>$value</$key>
	 * Again: No encoding will be used! This means you can invalidate or enhance the feed
	 * if $value contains markup. This may be abused to embed tags not implemented by
	 * the FeedCreator class used.
	 */
	var $additionalElements = Array();
   
    
	/**
	 * Adds an FeedItem to the feed.
	 *
	 * @param object FeedItem $item The FeedItem to add to the feed.
	 * @access public
	 */
	function addItem($item) {
		$this->items[] = $item;
	}
	
	
	/**
	 * Truncates a string to a certain length at the most sensible point.
	 * First, if there's a '.' character near the end of the string, the string is truncated after this character.
	 * If there is no '.', the string is truncated after the last ' ' character.
	 * If the string is truncated, " ..." is appended.
	 * If the string is already shorter than $length, it is returned unchanged.
	 * 
	 * @static
	 * @param string    string A string to be truncated.
	 * @param int        length the maximum length the string should be truncated to
	 * @return string    the truncated string
	 */
	function iTrunc($string, $length) {
		if (strlen($string)<=$length) {
			return $string;
		}
		
		$pos = strrpos($string,".");
		if ($pos>=$length-4) {
			$string = substr($string,0,$length-4);
			$pos = strrpos($string,".");
		}
		if ($pos>=$length*0.4) {
			return substr($string,0,$pos+1)." ...";
		}
		
		$pos = strrpos($string," ");
		if ($pos>=$length-4) {
			$string = substr($string,0,$length-4);
			$pos = strrpos($string," ");
		}
		if ($pos>=$length*0.4) {
			return substr($string,0,$pos)." ...";
		}
		
		return substr($string,0,$length-4)." ...";
			
	}
	
	
	/**
	 * Creates a comment indicating the generator of this feed.
	 * The format of this comment seems to be recognized by
	 * Syndic8.com.
	 */
	function _createGeneratorComment() {
		return "<!-- generator=\"".FEEDCREATOR_VERSION."\" -->\n";
	}
	
	
	/**
	 * Creates a string containing all additional elements specified in
	 * $additionalElements.
	 * @param	elements	array	an associative array containing key => value pairs
	 * @param indentString	string	a string that will be inserted before every generated line
	 * @return    string    the XML tags corresponding to $additionalElements
	 */
	function _createAdditionalElements($elements, $indentString="") {
		$ae = "";
		if (is_array($elements)) {
			foreach($elements AS $key => $value) {
				$ae.= $indentString."<$key>$value</$key>\n";
			}
		}
		return $ae;
	}
	
	function _createStylesheetReferences() {
		$xml = "";
		if (!empty($this->cssStyleSheet)) $xml .= "<?xml-stylesheet href=\"".$this->cssStyleSheet."\" type=\"text/css\"?>\n";
		if (!empty($this->xslStyleSheet)) $xml .= "<?xml-stylesheet href=\"".$this->xslStyleSheet."\" type=\"text/xsl\"?>\n";
		return $xml;
	}
	
	
	/**
	 * Builds the feed's text.
	 * @abstract
	 * @return    string    the feed's complete text 
	 */
	function createFeed() {
	}
	
	/**
	 * Generate a filename for the feed cache file. The result will be $_SERVER["PHP_SELF"] with the extension changed to .xml.
	 * For example:
	 * 
	 * echo $_SERVER["PHP_SELF"]."\n";
	 * echo FeedCreator::_generateFilename();
	 * 
	 * would produce:
	 * 
	 * /rss/latestnews.php
	 * latestnews.xml
	 *
	 * @return string the feed cache filename
	 * @since 1.4
	 * @access private
	 */
	function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".xml";
	}

	/**
	 * @since 1.4
	 * @access private
	 */
	function _redirect($filename) {

		//uses Geograph specific functions, get them seperatly or just comment out.
		header('Access-Control-Allow-Origin: *');
		if (function_exists('customCacheControl')) {
			$mtime = filemtime($filename);
			customCacheControl($mtime,$mtime);
			$timeout = 3600;
			if (!empty($GLOBALS['rss_timeout']))
				$timeout = $GLOBALS['rss_timeout'];
			customExpiresHeader($timeout-(time()-$mtime),true);
		} //end;

		if ($filesize = filesize($filename)) {
			header('Content-Length: '.$filesize);
		}

		Header("Content-Type: ".$this->contentType."; charset=".$this->encoding);
		if (preg_match("/\.(kml|gpx)$/",$filename)) {
			Header("Content-Disposition: attachment; filename=".basename($filename));
		} else {
			Header("Content-Disposition: inline; filename=".basename($filename));
		}
		readfile($filename, "r");
		die();
	}

	/**
	 * Turns on caching and checks if there is a recent version of this feed in the cache.
	 * If there is, an HTTP redirect header is sent.
	 * To effectively use caching, you should create the FeedCreator object and call this method
	 * before anything else, especially before you do the time consuming task to build the feed
	 * (web fetching, for example).
	 * @since 1.4
	 * @param filename	string	optional	the filename where a recent version of the feed is saved. If not specified, the filename is $_SERVER["PHP_SELF"] with the extension changed to .xml (see _generateFilename()).
	 * @param timeout	int		optional	the timeout in seconds before a cached version is refreshed (defaults to 3600 = 1 hour)
	 */
	function useCached($filename="", $timeout=3600) {
		$this->_timeout = $timeout;
		if ($filename=="") {
			$filename = $this->_generateFilename();
		}

		if (file_exists($filename) AND (time()-filemtime($filename) < $timeout)) {
			$this->_redirect($filename);
		}
	}
	
	
	/**
	 * Saves this feed as a file on the local disk. After the file is saved, a redirect
	 * header may be sent to redirect the user to the newly created file.
	 * @since 1.4
	 * 
	 * @param filename	string	optional	the filename where a recent version of the feed is saved. If not specified, the filename is $_SERVER["PHP_SELF"] with the extension changed to .xml (see _generateFilename()).
	 * @param redirect	boolean	optional	send an HTTP redirect header or not. If true, the user will be automatically redirected to the created file.
	 */
	function saveFeed($filename="", $displayContents=true) {
		if ($filename=="") {
			$filename = $this->_generateFilename();
		}

		$feedFile = fopen($filename, "w+");
		if ($feedFile) {
			fputs($feedFile,$this->createFeed());
			fclose($feedFile);
			if ($displayContents) {
				$this->_redirect($filename);
			}
		} else {
			echo "<br /><b>Error creating feed file, please check write permissions.</b><br />";
		}
	}
	
}


/**
 * FeedDate is an internal class that stores a date for a feed or feed item.
 * Usually, you won't need to use this.
 */
class FeedDate {
	var $unix;
	
	/**
	 * Creates a new instance of FeedDate representing a given date.
	 * Accepts RFC 822, ISO 8601 date formats as well as unix time stamps.
	 * @param mixed $dateString optional the date this FeedDate will represent. If not specified, the current date and time is used.
	 */
	function FeedDate($dateString="") {
		if ($dateString=="") $dateString = date("r");
		
		if (is_integer($dateString)) {
			$this->unix = $dateString;
			return;
		}
		if (preg_match("~(?:(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun),\\s+)?(\\d{1,2})\\s+([a-zA-Z]{3})\\s+(\\d{4})\\s+(\\d{2}):(\\d{2}):(\\d{2})\\s+(.*)~",$dateString,$matches)) {
			$months = Array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);
			$this->unix = mktime($matches[4],$matches[5],$matches[6],$months[$matches[2]],$matches[1],$matches[3]);
			if (substr($matches[7],0,1)=='+' OR substr($matches[7],0,1)=='-') {
				$tzOffset = (substr($matches[7],0,3) * 60 + substr($matches[7],-2)) * 60;
			} else {
				if (strlen($matches[7])==1) {
					$oneHour = 3600;
					$ord = ord($matches[7]);
					if ($ord < ord("M")) {
						$tzOffset = (ord("A") - $ord - 1) * $oneHour;
					} elseif ($ord >= ord("M") AND $matches[7]!="Z") {
						$tzOffset = ($ord - ord("M")) * $oneHour;
					} elseif ($matches[7]=="Z") {
						$tzOffset = 0;
					}
				}
				switch ($matches[7]) {
					case "UT":
					case "GMT":	$tzOffset = 0;
				}
			}
			$this->unix += $tzOffset;
			return;
		}
		if (preg_match("~(\\d{4})-(\\d{2})-(\\d{2})T(\\d{2}):(\\d{2}):(\\d{2})(.*)~",$dateString,$matches)) {
			$this->unix = mktime($matches[4],$matches[5],$matches[6],$matches[2],$matches[3],$matches[1]);
			if (substr($matches[7],0,1)=='+' OR substr($matches[7],0,1)=='-') {
				$tzOffset = (substr($matches[7],0,3) * 60 + substr($matches[7],-2)) * 60;
			} else {
				if ($matches[7]=="Z") {
					$tzOffset = 0;
				}
			}
			$this->unix += $tzOffset;
			return;
		}
		$this->unix = 0;
	}

	/**
	 * Gets the date stored in this FeedDate as an RFC 3339 date.
	 *
	 * @return a date in RFC 3339 format
	 */
	function rfc3339() {
		$date = gmdate("Y-m-d\TH:i:sO",$this->unix);
		$date = substr($date,0,22) . ':' . substr($date,-2);
		return $date;
	}
	
	/**
	 * Gets the date stored in this FeedDate as an RFC 822 date.
	 *
	 * @return a date in RFC 822 format
	 */
	function rfc822() {
		//return gmdate("r",$this->unix);
		$date = gmdate("D, d M Y H:i:s", $this->unix);
		if (TIME_ZONE!="") $date .= " ".str_replace(":","",TIME_ZONE);
		return $date;
	}
	
	/**
	 * Gets the date stored in this FeedDate as an ISO 8601 date.
	 *
	 * @return a date in ISO 8601 format
	 */
	function iso8601() {
		$date = gmdate("Y-m-d\TH:i:sO",$this->unix);
		$date = substr($date,0,22) . ':' . substr($date,-2);
		if (TIME_ZONE!="") $date = str_replace("+00:00",TIME_ZONE,$date);
		return $date;
	}
	
	/**
	 * Gets the date stored in this FeedDate as unix time stamp.
	 *
	 * @return a date as a unix time stamp
	 */
	function unix() {
		return $this->unix;
	}
}



/**
 * JSONCreator is a FeedCreator that implements JSON.
 *
 * @see http://www.purl.org/rss/1.0/
 * @since XX
 * @author barry hunter <geo@barryhunter.co.uk>
 */
class JSONCreator extends FeedCreator {

        function JSONCreator() {
                $this->contentType = "application/json";
        }

	/**
	 * Builds the feed's text. The feed will be compliant to JSON
	 * The feed will contain all items previously added in the same order.
	 * @return    string    the feed's complete text 
	 */
	function createFeed() {    
	
		$data = new EmptyClass;
		
		$data->generator = FEEDCREATOR_VERSION;
		foreach (explode(' ','title description link syndicationURL prevURL nextURL icon additionalElements') as $key) {
			if (!empty($this->$key)) 
				$data->$key = $this->$key;
		}
		if ($this->image!=null) {
			$data->image_url = $this->image->url;
			$data->image_title = $this->image->title;
			$data->image_link = $this->image->link;
		}
		$now = new FeedDate();
		$data->date = $now->iso8601();

		$data->items = $this->items;
		for ($i=0;$i<count($this->items);$i++) {
			unset($data->items[$i]->descriptionHtmlSyndicated);
			unset($data->items[$i]->descriptionTruncSize);
			//TODO geograph specific!
			$data->items[$i]->guid = basename($data->items[$i]->guid);
			if (function_exists('latin1_to_utf8')) {
	                        $data->items[$i]->title = latin1_to_utf8($data->items[$i]->title);
				$data->items[$i]->description = latin1_to_utf8($data->items[$i]->description);
			}
			foreach ($data->items[$i] as $key => $value) {
				if (empty($value))
					unset($data->items[$i]->$key);
			}
		}

		if (!function_exists('json_encode'))
			require_once __DIR__.'/JSON.php';

		if (isset($_GET['callback'])) {
			$this->callback=preg_replace('/[^\w\.$]+/','',$_GET['callback']);
			if (empty($this->callback)) {
				$this->callback = "geograph_callback";
			}
		} elseif (isset($_GET['_callback'])) {
			$this->callback=preg_replace('/[^\w\.$]+/','',$_GET['_callback']);
		}

		if (!empty($this->callback)) {
			return "/**/{$this->callback}(".json_encode($data,JSON_PARTIAL_OUTPUT_ON_ERROR  ).")";
		} else
			return json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR  );
	}
}


/**
 * RSSCreator10 is a FeedCreator that implements RDF Site Summary (RSS) 1.0.
 *
 * @see http://www.purl.org/rss/1.0/
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class RSSCreator10 extends FeedCreator {

	/**
	 * Builds the RSS feed's text. The feed will be compliant to RDF Site Summary (RSS) 1.0.
	 * The feed will contain all items previously added in the same order.
	 * @return    string    the feed's complete text 
	 */
	function createFeed() {     
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		if (empty($this->cssStyleSheet)) {
			$this->cssStyleSheet = "http://www.w3.org/2000/08/w3c-synd/style.css";
		}
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<rdf:RDF\n";
		$feed.= "    xmlns=\"http://purl.org/rss/1.0/\"\n";
		$feed.= "    xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n"; 
		$feed.= "    xmlns:slash=\"http://purl.org/rss/1.0/modules/slash/\"\n";
		if (!empty($this->items[0]->thumb))
			$feed.= "    xmlns:photo=\"http://www.pheed.com/pheed/\"\n";
		if (!empty($this->items[0]->lat) || !empty($this->geo))
			$feed.= "    xmlns:georss=\"http://www.georss.org/georss\"\n";
		$feed.= "    xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
		$feed.= "    <channel rdf:about=\"".$this->syndicationURL."\">\n";
		$feed.= "        <title>".htmlspecialchars($this->title)."</title>\n";
		$feed.= "        <description>".htmlspecialchars($this->description)."</description>\n";
		$feed.= "        <link>".$this->link."</link>\n";
		if ($this->image!=null) {
			$feed.= "        <image rdf:resource=\"".$this->image->url."\" />\n";
		}
		$now = new FeedDate();
		$feed.= "       <dc:date>".htmlspecialchars($now->iso8601())."</dc:date>\n";
		$feed.= "        <items>\n";
		$feed.= "            <rdf:Seq>\n";
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "                <rdf:li rdf:resource=\"".htmlspecialchars($this->items[$i]->link)."\"/>\n";
		}
		$feed.= "            </rdf:Seq>\n";
		$feed.= "        </items>\n";
		$feed.= "    </channel>\n";
		if ($this->image!=null) {
			$feed.= "    <image rdf:about=\"".$this->image->url."\">\n";
			$feed.= "        <title>".$this->image->title."</title>\n";
			$feed.= "        <link>".$this->image->link."</link>\n";
			$feed.= "        <url>".$this->image->url."</url>\n";
			$feed.= "    </image>\n";
		}
		$feed.= $this->_createAdditionalElements($this->additionalElements, "    ");
		
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "    <item rdf:about=\"".htmlspecialchars($this->items[$i]->link)."\">\n";
			//$feed.= "        <dc:type>Posting</dc:type>\n";
			$feed.= "        <dc:format>text/html</dc:format>\n";
			if ($this->items[$i]->date!=null) {
				$itemDate = new FeedDate($this->items[$i]->date);
				$feed.= "        <dc:date>".htmlspecialchars($itemDate->iso8601())."</dc:date>\n";
			}
			if (!empty($this->items[$i]->source)) {
				$feed.= "        <dc:source>".htmlspecialchars($this->items[$i]->source)."</dc:source>\n";
			}
			if (!empty($this->items[$i]->author)) {
				$feed.= "        <dc:creator>".htmlspecialchars($this->items[$i]->author)."</dc:creator>\n";
			}
			if (!empty($this->items[$i]->lat)) {
				$feed.= "        <georss:point>".$this->items[$i]->lat." ".$this->items[$i]->long."</georss:point>\n";
			}
			if (!empty($this->items[$i]->thumb)) {
				$feed.= "        <photo:thumbnail>".htmlspecialchars($this->items[$i]->thumb)."</photo:thumbnail>\n";
			}
			$feed.= "        <title>".htmlspecialchars(strip_tags(strtr($this->items[$i]->title,"\n\r","  ")))."</title>\n";
			$feed.= "        <link>".htmlspecialchars($this->items[$i]->link)."</link>\n";
			$feed.= "        <description>".htmlspecialchars($this->items[$i]->description)."</description>\n";
			$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			$feed.= "    </item>\n";
		}
		$feed.= "</rdf:RDF>\n";
		return $feed;
	}
}



/**
 * RSSCreator091 is a FeedCreator that implements RSS 0.91 Spec, revision 3.
 *
 * @see http://my.netscape.com/publish/formats/rss-spec-0.91.html
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class RSSCreator091 extends FeedCreator {

	/**
	 * Stores this RSS feed's version number.
	 * @access private
	 */
	var $RSSVersion;

	function RSSCreator091() {
		$this->_setRSSVersion("0.91");
		$this->contentType = "application/rss+xml";
	}
	
	/**
	 * Sets this RSS feed's version number.
	 * @access private
	 */
	function _setRSSVersion($version) {
		$this->RSSVersion = $version;
	}

	/**
	 * Builds the RSS feed's text. The feed will be compliant to RDF Site Summary (RSS) 1.0.
	 * The feed will contain all items previously added in the same order.
	 * @return    string    the feed's complete text 
	 */
	function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<rss version=\"".$this->RSSVersion."\"";
		if (!empty($this->format) && $this->format == 'MEDIA')
			$feed.= " xmlns:media=\"http://search.yahoo.com/mrss/\"";
		if (!empty($this->items[0]->licence) || !empty($this->creativeCommons))
			$feed.= " xmlns:creativeCommons=\"http://backend.userland.com/creativeCommonsRssModule\"";
		if (!empty($this->items[0]->lat) || !empty($this->geo))
			$feed.= " xmlns:georss=\"http://www.georss.org/georss\"";
		if (!empty($this->syndicationURL))
			$feed.= " xmlns:atom=\"http://www.w3.org/2005/Atom\"";
		$feed.= " xmlns:dc=\"http://purl.org/dc/elements/1.1/\"";
		$feed.= ">\n";
		if (!empty($this->format) && $this->format == 'BASE') {
			$feed.= "    <channel xmlns:g=\"http://base.google.com/ns/1.0\">\n";
		} else {
			$feed.= "    <channel>\n";
		}
		$feed.= "        <title>".FeedCreator::iTrunc(htmlspecialchars($this->title),100)."</title>\n";
		$this->descriptionTruncSize = 500;
		$feed.= "        <description>".$this->getDescription()."</description>\n";
		$feed.= "        <link>".$this->link."</link>\n";
		if (!empty($this->syndicationURL)) {
			$feed.= "        <atom:link href=\"".$this->syndicationURL."\" rel=\"self\" type=\"".$this->contentType."\" />\n";
		}
		if (!empty($this->icon)) {
			$feed.= "        <atom:icon>".$this->icon."</atom:icon>\n";
		}
		if (!empty($this->prevURL)) {
			$feed.= "        <atom:link href=\"".$this->prevURL."\" rel=\"previous\" type=\"".$this->contentType."\" />\n";
		}
		if (!empty($this->nextURL)) {
			$feed.= "        <atom:link href=\"".$this->nextURL."\" rel=\"next\" type=\"".$this->contentType."\" />\n";
		}
		$now = new FeedDate();
		$feed.= "        <lastBuildDate>".htmlspecialchars($now->rfc822())."</lastBuildDate>\n";
		$feed.= "        <generator>".FEEDCREATOR_VERSION."</generator>\n";

		if (!empty($this->image)) {
			$feed.= "        <image>\n";
			$feed.= "            <url>".$this->image->url."</url>\n"; 
			$feed.= "            <title>".FeedCreator::iTrunc(htmlspecialchars($this->image->title),100)."</title>\n"; 
			$feed.= "            <link>".$this->image->link."</link>\n";
			if (!empty($this->image->width)) {
				$feed.= "            <width>".$this->image->width."</width>\n";
			}
			if (!empty($this->image->height)) {
				$feed.= "            <height>".$this->image->height."</height>\n";
			}
			if (!empty($this->image->description)) {
				$feed.= "            <description>".$this->image->getDescription()."</description>\n";
			}
			$feed.= "        </image>\n";
		}
		if (!empty($this->language)) {
			$feed.= "        <language>".$this->language."</language>\n";
		}
		if (!empty($this->copyright)) {
			$feed.= "        <copyright>".FeedCreator::iTrunc(htmlspecialchars($this->copyright),100)."</copyright>\n";
		}
		if (!empty($this->editor)) {
			$feed.= "        <managingEditor>".FeedCreator::iTrunc(htmlspecialchars($this->editor),100)."</managingEditor>\n";
		}
		if (!empty($this->webmaster)) {
			$feed.= "        <webMaster>".FeedCreator::iTrunc(htmlspecialchars($this->webmaster),100)."</webMaster>\n";
		}
		if (!empty($this->pubDate)) {
			$pubDate = new FeedDate($this->pubDate);
			$feed.= "        <pubDate>".htmlspecialchars($pubDate->rfc822())."</pubDate>\n";
		}
		if (!empty($this->category)) {
			$feed.= "        <category>".htmlspecialchars($this->category)."</category>\n";
		}
		if (!empty($this->docs)) {
			$feed.= "        <docs>".FeedCreator::iTrunc(htmlspecialchars($this->docs),500)."</docs>\n";
		}
		if (!empty($this->ttl)) {
			$feed.= "        <ttl>".htmlspecialchars($this->ttl)."</ttl>\n";
		}
		if (!empty($this->rating)) {
			$feed.= "        <rating>".FeedCreator::iTrunc(htmlspecialchars($this->rating),500)."</rating>\n";
		}
		if (!empty($this->skipHours)) {
			$feed.= "        <skipHours>".htmlspecialchars($this->skipHours)."</skipHours>\n";
		}
		if (!empty($this->skipDays)) {
			$feed.= "        <skipDays>".htmlspecialchars($this->skipDays)."</skipDays>\n";
		}
		$feed.= $this->_createAdditionalElements($this->additionalElements, "    ");

		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "        <item>\n";
			$feed.= "            <title>".FeedCreator::iTrunc(htmlspecialchars(strip_tags($this->items[$i]->title)),100)."</title>\n";
			$feed.= "            <link>".htmlspecialchars($this->items[$i]->link)."</link>\n";
			$feed.= "            <description>".$this->items[$i]->getDescription()."</description>\n";
			
			if (!empty($this->items[$i]->author) && strpos($this->items[$i]->author,'@') === FALSE) {
				$feed.= "            <dc:creator>".htmlspecialchars($this->items[$i]->author)."</dc:creator>\n";
			} elseif (!empty($this->items[$i]->author)) {
				$feed.= "            <author>".htmlspecialchars($this->items[$i]->author)."</author>\n";
			}
			/*
			// on hold
			if (!empty($this->items[$i]->source)) {
					$feed.= "            <source>".htmlspecialchars($this->items[$i]->source)."</source>\n";
			}
			*/
			if (!empty($this->items[$i]->category)) {
				$feed.= "            <category>".htmlspecialchars($this->items[$i]->category)."</category>\n";
			}
			if (!empty($this->items[$i]->comments)) {
				$feed.= "            <comments>".htmlspecialchars($this->items[$i]->comments)."</comments>\n";
			}
			if (!empty($this->items[$i]->date)) {
			$itemDate = new FeedDate($this->items[$i]->date);
				$feed.= "            <pubDate>".htmlspecialchars($itemDate->rfc822())."</pubDate>\n";
			}
			if (!empty($this->items[$i]->guid)) {
				$feed.= "            <guid>".htmlspecialchars($this->items[$i]->guid)."</guid>\n";
			}
			if (!empty($this->items[$i]->content)) {
				if (!empty($this->items[$i]->thumb)) {
					$feed.= "            <media:thumbnail url=\"".htmlspecialchars($this->items[$i]->thumb)."\"/>\n";
				}
				$feed.= "            <media:content url=\"".htmlspecialchars($this->items[$i]->content)."\"/>\n";
			} elseif (!empty($this->items[$i]->thumb)) {
				$feed.= "            <g:image_link>".htmlspecialchars($this->items[$i]->thumb)."</g:image_link>\n";
			}
			if (!empty($this->items[$i]->enclosure)) {
				 $feed.= "            <enclosure url=\"".htmlspecialchars($this->items[$i]->enclosure)."\" type=\"image/jpeg\"/>\n";
			}
			if (!empty($this->items[$i]->lat)) {
				$feed.= "            <georss:point>".$this->items[$i]->lat." ".$this->items[$i]->long."</georss:point>\n";
			}
			if (!empty($this->items[$i]->licence)) {
				$feed.= "            <creativeCommons:license>".htmlspecialchars($this->items[$i]->licence)."</creativeCommons:license>\n";
			}
			$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			$feed.= "        </item>\n";
		}
		$feed.= "    </channel>\n";
		$feed.= "</rss>\n";
		return $feed;
	}
}



/**
 * RSSCreator20 is a FeedCreator that implements RDF Site Summary (RSS) 2.0.
 *
 * @see http://backend.userland.com/rss
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class RSSCreator20 extends RSSCreator091 {

    function RSSCreator20() {
        parent::_setRSSVersion("2.0");
    }
}


/**
 * KMLCreator is a FeedCreator that implements a KML output, suitable for Keyhole/Google Earth
 *
 * @since 1.7.3
 * @author Barry Hunter <geo@barryhunter.co.uk>
 */
class KMLCreator extends FeedCreator {

	function KMLCreator() {
		$this->contentType = "application/vnd.google-earth.kml+xml";
		$this->encoding = "utf-8";
	}

	function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<kml xmlns=\"http://earth.google.com/kml/2.0\">\n";
		$feed.= "<Document>\n";
		$feed.= "<name>".FeedCreator::iTrunc(htmlspecialchars($this->title),100)."</name>";
		if (!empty($_GET['LinkControl']))
			$feed.= "<NetworkLinkControl>\n<minRefreshPeriod>3600</minRefreshPeriod>\n</NetworkLinkControl>\n";
		if (!empty($_GET['simple']) && count($this->items) > 0) {
			$normalscale = 1;
			$highscale = 2.1;
			if (!empty($_GET['large'])) {
	                        $normalscale = 2.1;
	                        $highscale = 3.4;
			}
		$feed.= "<Style id=\"defaultIcon\">
	<LabelStyle>
		<scale>0</scale>
	</LabelStyle>
	<IconStyle>
		<scale>$normalscale</scale>
	</IconStyle>
</Style>
<Style id=\"hoverIcon\">
	<IconStyle>
		<scale>$highscale</scale>
	</IconStyle>
</Style>
<StyleMap id=\"defaultStyle\">
	<Pair>
		<key>normal</key>
		<styleUrl>#defaultIcon</styleUrl>
	</Pair>
	<Pair>
		<key>highlight</key>
		<styleUrl>#hoverIcon</styleUrl>
	</Pair>
</StyleMap>
";
		  $style = "#defaultStyle";
		} else {
		$feed.= "<Style id=\"defaultIcon\">
	<IconStyle>
		<Icon>
			<href>https://maps.google.com/mapfiles/kml/icon46.png</href>
		</Icon>
	</IconStyle>
</Style>";
			$style = "#defaultIcon";
		}
		if (!isset($_GET['BBOX'])) {
			$feed.= "<Folder>\n  <name>".FeedCreator::iTrunc(htmlspecialchars($this->title),100)."</name>
  <description>".$this->getDescription()."</description>
  <visibility>1</visibility>\n";
		}
		$this->truncSize = 500;

		for ($i=0;$i<count($this->items);$i++) {
			$snippet = strip_tags($this->items[$i]->description);

			//added here beucase description gets auto surrounded by cdata
			if (!empty($this->items[$i]->thumbTag)) {
				$this->items[$i]->description = "<a href=\"".htmlspecialchars($this->items[$i]->link)."\">".$this->items[$i]->thumbTag."</a><br/>".$this->items[$i]->description;
			}

			$this->items[$i]->description = "<p align=\"center\"><b>".$this->items[$i]->description."</b><br/>
			".$this->items[$i]->licence."
				<br/><br/><a href=\"".htmlspecialchars($this->items[$i]->link)."\">View Online</a></b>";

			if ($this->items[$i]->guid != '') {
				$feed.= "
		<Placemark id=\"".htmlspecialchars($this->items[$i]->guid)."\">";
			} else {
			$feed.= "
		<Placemark>";
			}
			$feed.= "
			<description>".utf8_encode($this->items[$i]->getDescription(true))."</description>
			<Snippet maxLines=\"2\">".utf8_encode(htmlnumericentities($snippet))."</Snippet>
			<name>".FeedCreator::iTrunc(utf8_encode(htmlnumericentities(strip_tags($this->items[$i]->title))),100)."</name>
			<visibility>1</visibility>
			<Point>
				<extrude>1</extrude><altitudeMode>relativeToGround</altitudeMode>
				<coordinates>".$this->items[$i]->long.",".$this->items[$i]->lat.",125</coordinates>
			</Point>";
			if (!empty($this->items[$i]->thumb)) {
				$feed.= "
			<styleUrl>$style</styleUrl>
			<Style>
				<IconStyle>
					<Icon>
						<href>".htmlspecialchars($this->items[$i]->thumb)."</href>
					</Icon>
				</IconStyle>
			</Style>";
			}
			if (!empty($this->items[$i]->imageTaken)) {
				$feed.= "
			<TimeStamp>
				<when>".str_replace('-00','',$this->items[$i]->imageTaken)."</when>
			</TimeStamp>";
			}
			$feed.= "
		</Placemark>\n";
		}
		if (!isset($_GET['BBOX']))
			$feed .= "</Folder>\n";
		$feed .= "</Document>\n</kml>\n";
		return $feed;
	}

	/**
	 * Generate a filename for the feed cache file. Overridden from FeedCreator to prevent XML data types.
	 * @return string the feed cache filename
	 * @since 1.4
	 * @access private
	 */
	function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".kml";
	}
}

/**
 * GPXCreator is a FeedCreator that implements a GPX output, suitable for a GIS packages
 *
 * @since 1.7.6
 * @author Barry Hunter <geo@barryhunter.co.uk>
 */
class GPXCreator extends FeedCreator {

	function GPXCreator() {
		$this->contentType = "text/xml";
		$this->encoding = "utf-8";
	}

function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<gpx xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" version=\"1.0\"
creator=\"".FEEDCREATOR_VERSION."\"
xsi:schemaLocation=\"http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd\" xmlns=\"http://www.topografix.com/GPX/1/0\">\n";

		$now = new FeedDate();
		$feed.= "<desc>".FeedCreator::iTrunc(htmlspecialchars($this->title),100)."</desc>
<author>{$_SERVER['HTTP_HOST']}</author>
<url>".htmlspecialchars($this->link)."</url>
<time>".htmlspecialchars($now->iso8601())."</time>
\n";

		for ($i=0;$i<count($this->items);$i++) {
			$title = utf8_encode(htmlnumericentities(strip_tags($this->items[$i]->title)));
			$feed.= "<wpt lat=\"".$this->items[$i]->lat."\" lon=\"".$this->items[$i]->long."\">
				<name>".substr($title,0,6)."</name>
				<desc>".$title."</desc>
				<src>".htmlspecialchars($this->items[$i]->author)."</src>
				<url>".htmlspecialchars($this->items[$i]->link)."</url>
			</wpt>\n";
		}
		$feed .= "</gpx>\n";
		return $feed;
	}
}



/**
 * PHPCreator is a FeedCreator that implements a PHP output, suitable for a include
 *
 * @since 1.7.3
 * @author Barry Hunter <geo@barryhunter.co.uk>
 */
class PHPCreator extends FeedCreator {

	function PHPCreator() {
		$this->contentType = "text/plain";
		$this->encoding = "utf-8";
	}

	function createFeed() {
		$feed = "<?php\n";
		$feed.= "if (!class_exists(\"FeedItem\")) { class FeedItem {} }\n";
		$feed.= "  \$feedTitle='".addslashes(FeedCreator::iTrunc(htmlspecialchars($this->title),100))."';\n";
		$this->truncSize = 500;
		$feed.= "  \$feedDescription='".addslashes($this->getDescription())."';\n";
		$feed.= "  \$feedLink='".$this->link."';\n";
		$feed.= "  \$feedItem = array();\n";
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "   \$feedItem[$i] = new FeedItem();\n";
			if (!empty($this->items[$i]->guid)) {
				$feed.= "    \$feedItem[$i]->id='".htmlspecialchars($this->items[$i]->guid)."';\n";
			}
			$feed.= "    \$feedItem[$i]->title='".addslashes(FeedCreator::iTrunc(utf8_encode(htmlnumericentities(strip_tags($this->items[$i]->title))),100))."';\n";
			$feed.= "    \$feedItem[$i]->link='".htmlspecialchars($this->items[$i]->link)."';\n";
			$feed.= "    \$feedItem[$i]->date=".htmlspecialchars($this->items[$i]->date).";\n";
			if (!empty($this->items[$i]->author)) {
				$feed.= "    \$feedItem[$i]->author='".htmlspecialchars($this->items[$i]->author)."';\n";
				if (!empty($this->items[$i]->authorEmail)) {
					$feed.= "    \$feedItem[$i]->authorEmail='".$this->items[$i]->authorEmail."';\n";
				}
			}
			$feed.= "    \$feedItem[$i]->description='".addslashes(utf8_encode($this->items[$i]->getDescription()))."';\n";
			if (!empty($this->items[$i]->thumb)) {
				$feed.= "    \$feedItem[$i]->thumbURL='".htmlspecialchars($this->items[$i]->thumb)."';\n";
			}
		}
		$feed .= "?>\n";
		return $feed;
	}
}

/**
 * PIECreator01 is a FeedCreator that implements the emerging PIE specification,
 * as in http://intertwingly.net/wiki/pie/Syntax.
 *
 * @deprecated
 * @since 1.3
 * @author Scott Reynen <scott@randomchaos.com> and Kai Blankenhorn <kaib@bitfolge.de>
 */
class PIECreator01 extends FeedCreator {

	function PIECreator01() {
		$this->encoding = "utf-8";
	}

	function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<feed version=\"0.1\" xmlns=\"http://example.com/newformat#\">\n";
		$feed.= "    <title>".FeedCreator::iTrunc(htmlspecialchars($this->title),100)."</title>\n";
		$this->truncSize = 500;
		$feed.= "    <subtitle>".$this->getDescription()."</subtitle>\n";
		$feed.= "    <link>".$this->link."</link>\n";
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "    <entry>\n";
			$feed.= "        <title>".FeedCreator::iTrunc(utf8_encode(htmlnumericentities(strip_tags($this->items[$i]->title))),100)."</title>\n";
			$feed.= "        <link>".htmlspecialchars($this->items[$i]->link)."</link>\n";
			$itemDate = new FeedDate($this->items[$i]->date);
			$feed.= "        <created>".htmlspecialchars($itemDate->iso8601())."</created>\n";
			$feed.= "        <issued>".htmlspecialchars($itemDate->iso8601())."</issued>\n";
			if (!empty($this->items[$i]->dateUpdated)) {
				$itemDateUpdated = new FeedDate($this->items[$i]->dateUpdated);
				$feed.= "        <modified>".htmlspecialchars($itemDateUpdated->rfc3339())."</modified>\n";
			} else
				$feed.= "        <modified>".htmlspecialchars($itemDate->iso8601())."</modified>\n";
			$feed.= "        <id>".htmlspecialchars($this->items[$i]->guid)."</id>\n";
			if (!empty($this->items[$i]->author)) {
				$feed.= "        <author>\n";
				$feed.= "            <name>".htmlspecialchars($this->items[$i]->author)."</name>\n";
				if (!empty($this->items[$i]->authorEmail)) {
					$feed.= "            <email>".$this->items[$i]->authorEmail."</email>\n";
				}
				$feed.="        </author>\n";
			}
			$feed.= "        <content type=\"text/html\" xml:lang=\"en-us\">\n";
			$feed.= "            <div xmlns=\"http://www.w3.org/1999/xhtml\">".utf8_encode($this->items[$i]->getDescription())."</div>\n";
			$feed.= "        </content>\n";
			$feed.= "    </entry>\n";
		}
		$feed.= "</feed>\n";
		return $feed;
	}
}


/**
 * AtomCreator10 is a FeedCreator that implements the atom specification,
 * as in http://www.atomenabled.org/developers/syndication/atom-format-spec.php
 *
 * @since 1.7.9(BH)
 * @author Barry Hunter
 */
class AtomCreator10 extends FeedCreator {

	function AtomCreator10() {
		$this->contentType = "application/atom+xml";
		$this->encoding = "utf-8";
	}

	function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<feed xmlns=\"http://www.w3.org/2005/Atom\"";
		if (!empty($this->format) && $this->format=='TOOLBAR') {
			$feed.= " xmlns:gtb=\"http://toolbar.google.com/custombuttons/\"";
		}
		if (!empty($this->language)) {
			$feed.= " xml:lang=\"".$this->language."\"";
		}
		$feed.= ">\n"; 
		$feed.= "    <title>".htmlspecialchars($this->title)."</title>\n";
		$feed.= "    <subtitle>".htmlspecialchars($this->description)."</subtitle>\n";
		$feed.= "    <link rel=\"alternate\" type=\"text/html\" href=\"".htmlspecialchars($this->link)."\"/>\n";
		if ($this->syndicationURL != '') {
			$feed.= "    <link href=\"".$this->syndicationURL."\" rel=\"self\" type=\"".$this->contentType."\"/>\n";
		}

		$feed.= "    <id>".htmlspecialchars($this->link)."</id>\n";
		$now = new FeedDate();
		$feed.= "    <updated>".htmlspecialchars($now->rfc3339())."</updated>\n";
		if (!empty($this->editor)) {
			$feed.= "    <author>\n";
			$feed.= "        <name>".$this->editor."</name>\n";
			if (!empty($this->editorEmail)) {
				$feed.= "        <email>".$this->editorEmail."</email>\n";
			}
			$feed.= "    </author>\n";
		}
		$feed.= "    <generator>".FEEDCREATOR_VERSION."</generator>\n";
		$feed.= $this->_createAdditionalElements($this->additionalElements, "    ");
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "    <entry>\n";
			$feed.= "        <title>".utf8_encode(htmlnumericentities(strip_tags($this->items[$i]->title)))."</title>\n";
			$feed.= "        <link rel=\"alternate\" type=\"text/html\" href=\"".htmlspecialchars($this->items[$i]->link)."\"/>\n";
			if ($this->items[$i]->date=="") {
				$this->items[$i]->date = time();
			}
			$itemDate = new FeedDate($this->items[$i]->date);
			if (!empty($this->items[$i]->dateUpdated)) {
				$itemDateUpdated = new FeedDate($this->items[$i]->dateUpdated);
				$feed.= "        <updated>".htmlspecialchars($itemDateUpdated->rfc3339())."</updated>\n";
			} else
				$feed.= "        <updated>".htmlspecialchars($itemDate->rfc3339())."</updated>\n";
			$feed.= "        <published>".htmlspecialchars($itemDate->rfc3339())."</published>\n";
			$feed.= "        <id>".htmlspecialchars($this->items[$i]->link)."</id>\n";
			$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			if (!empty($this->items[$i]->author)) {
				$feed.= "        <author>\n";
				$feed.= "            <name>".htmlnumericentities($this->items[$i]->author)."</name>\n";
				$feed.= "        </author>\n";
			}
			if (!empty($this->items[$i]->description)) {
				$feed.= "        <summary>".$this->items[$i]->getDescription()."</summary>\n";
			}
			if (!empty($this->items[$i]->thumbdata)) {
				$feed.= "        <gtb:icon mode=\"base64\" type=\"image/jpeg\">\n";
				$feed.= chunk_split(base64_encode($this->items[$i]->thumbdata))."\n";
				$feed.= "        </gtb:icon>\n";
			}
			$feed.= "    </entry>\n";
		}
		$feed.= "</feed>\n";
		return $feed;
	}
}


/**
 * AtomCreator03 is a FeedCreator that implements the atom specification,
 * as in http://www.intertwingly.net/wiki/pie/FrontPage.
 * Please note that just by using AtomCreator03 you won't automatically
 * produce valid atom files. For example, you have to specify either an editor
 * for the feed or an author for every single feed item.
 *
 * Some elements have not been implemented yet. These are (incomplete list):
 * author URL, item author's email and URL, item contents, alternate links,
 * other link content types than text/html. Some of them may be created with
 * AtomCreator03::additionalElements.
 *
 * @see FeedCreator#additionalElements
 * @since 1.6
 * @author Kai Blankenhorn <kaib@bitfolge.de>, Scott Reynen <scott@randomchaos.com>
 */
class AtomCreator03 extends FeedCreator {

	function AtomCreator03() {
		$this->contentType = "application/atom+xml";
		$this->encoding = "utf-8";
	}

	function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<feed version=\"0.3\" xmlns=\"http://purl.org/atom/ns#\"";
		if (!empty($this->format) && $this->format=='TOOLBAR') {
			$feed.= " xmlns:gtb=\"http://toolbar.google.com/custombuttons/\"";
		}
		if (!empty($this->language)) {
			$feed.= " xml:lang=\"".$this->language."\"";
		}
		$feed.= ">\n";
		$feed.= "    <title>".htmlspecialchars($this->title)."</title>\n";
		$feed.= "    <tagline>".htmlspecialchars($this->description)."</tagline>\n";
		$feed.= "    <link rel=\"alternate\" type=\"text/html\" href=\"".htmlspecialchars($this->link)."\"/>\n";
		$feed.= "    <id>".htmlspecialchars($this->link)."</id>\n";
		$now = new FeedDate();
		$feed.= "    <modified>".htmlspecialchars($now->iso8601())."</modified>\n";
		if (!empty($this->editor)) {
			$feed.= "    <author>\n";
			$feed.= "        <name>".$this->editor."</name>\n";
			if (!empty($this->editorEmail)) {
				$feed.= "        <email>".$this->editorEmail."</email>\n";
			}
			$feed.= "    </author>\n";
		}
		$feed.= "    <generator>".FEEDCREATOR_VERSION."</generator>\n";
		$feed.= $this->_createAdditionalElements($this->additionalElements, "    ");
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "    <entry>\n";
			$feed.= "        <title>".utf8_encode(htmlnumericentities(strip_tags($this->items[$i]->title)))."</title>\n";
			$feed.= "        <link rel=\"alternate\" type=\"text/html\" href=\"".htmlspecialchars($this->items[$i]->link)."\"/>\n";
			if ($this->items[$i]->date=="") {
				$this->items[$i]->date = time();
			}
			$itemDate = new FeedDate($this->items[$i]->date);
			$feed.= "        <created>".htmlspecialchars($itemDate->iso8601())."</created>\n";
			$feed.= "        <issued>".htmlspecialchars($itemDate->iso8601())."</issued>\n";
			if (!empty($this->items[$i]->dateUpdated)) {
				$itemDateUpdated = new FeedDate($this->items[$i]->dateUpdated);
				$feed.= "        <modified>".htmlspecialchars($itemDateUpdated->rfc3339())."</modified>\n";
			} else
				$feed.= "        <modified>".htmlspecialchars($itemDate->iso8601())."</modified>\n";
			$feed.= "        <id>".htmlspecialchars($this->items[$i]->link)."</id>\n";
			$feed.= $this->_createAdditionalElements($this->items[$i]->additionalElements, "        ");
			if (!empty($this->items[$i]->author)) {
				$feed.= "        <author>\n";
				$feed.= "            <name>".htmlnumericentities($this->items[$i]->author)."</name>\n";
				$feed.= "        </author>\n";
			}
			if (!empty($this->items[$i]->description)) {
				$feed.= "        <summary>".$this->items[$i]->getDescription()."</summary>\n";
			}
			if (!empty($this->items[$i]->thumbdata)) {
				$feed.= "        <gtb:icon mode=\"base64\" type=\"image/jpeg\">\n";
				$feed.= chunk_split(base64_encode($this->items[$i]->thumbdata))."\n";
				$feed.= "        </gtb:icon>\n";
			}
			$feed.= "    </entry>\n";
		}
		$feed.= "</feed>\n";
		return $feed;
	}
}


/**
 * MBOXCreator is a FeedCreator that implements the mbox format
 * as described in http://www.qmail.org/man/man5/mbox.html
 *
 * @since 1.3
 * @author Kai Blankenhorn <kaib@bitfolge.de>
 */
class MBOXCreator extends FeedCreator {

	function MBOXCreator() {
		$this->contentType = "text/plain";
		$this->encoding = "ISO-8859-15";
	}

	function qp_enc($input = "", $line_max = 76) {
		$hex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');
		$lines = preg_split("/(?:\r\n|\r|\n)/", $input);
		$eol = "\r\n";
		$escape = "=";
		$output = "";
		while( list(, $line) = each($lines) ) {
			//$line = rtrim($line); // remove trailing white space -> no =20\r\n necessary
			$linlen = strlen($line);
			$newline = "";
			for($i = 0; $i < $linlen; $i++) {
				$c = substr($line, $i, 1);
				$dec = ord($c);
				if ( ($dec == 32) && ($i == ($linlen - 1)) ) { // convert space at eol only
					$c = "=20";
				} elseif ( ($dec == 61) || ($dec < 32 ) || ($dec > 126) ) { // always encode "\t", which is *not* required
					$h2 = floor($dec/16); $h1 = floor($dec%16);
					$c = $escape.$hex["$h2"].$hex["$h1"];
				}
				if ( (strlen($newline) + strlen($c)) >= $line_max ) { // CRLF is not counted
					$output .= $newline.$escape.$eol; // soft line break; " =\r\n" is okay
					$newline = "";
				}
				$newline .= $c;
			} // end of for
			$output .= $newline.$eol;
		}
		return trim($output);
	}

	/**
	 * Builds the MBOX contents.
	 * @return    string    the feed's complete text
	 */
	function createFeed() {
		for ($i=0;$i<count($this->items);$i++) {
			if (!empty($this->items[$i]->author)) {
				$from = $this->items[$i]->author;
			} else {
				$from = $this->title;
			}
			$itemDate = new FeedDate($this->items[$i]->date);
			$feed.= "From ".strtr(MBOXCreator::qp_enc($from)," ","_")." ".date("D M d H:i:s Y",$itemDate->unix())."\n";
			$feed.= "Content-Type: text/plain;\n";
			$feed.= "	charset=\"".$this->encoding."\"\n";
			$feed.= "Content-Transfer-Encoding: quoted-printable\n";
			$feed.= "Content-Type: text/plain\n";
			$feed.= "From: \"".MBOXCreator::qp_enc($from)."\"\n";
			$feed.= "Date: ".$itemDate->rfc822()."\n";
			$feed.= "Subject: ".MBOXCreator::qp_enc(FeedCreator::iTrunc($this->items[$i]->title,100))."\n";
			$feed.= "\n";
			$body = chunk_split(MBOXCreator::qp_enc($this->items[$i]->description));
			$feed.= preg_replace("~\nFrom ([^\n]*)(\n?)~","\n>From $1$2\n",$body);
			$feed.= "\n";
			$feed.= "\n";
		}
		return $feed;
	}

	/**
	 * Generate a filename for the feed cache file. Overridden from FeedCreator to prevent XML data types.
	 * @return string the feed cache filename
	 * @since 1.4
	 * @access private
	 */
	function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".mbox";
	}
}


/**
 * OPMLCreator is a FeedCreator that implements OPML 1.0.
 * 
 * @see http://opml.scripting.com/spec
 * @author Dirk Clemens, Kai Blankenhorn
 * @since 1.5
 */
class OPMLCreator extends FeedCreator {

	function OPMLCreator() {
		$this->encoding = "utf-8";
	}

	function createFeed() {
		$feed = "<?xml version=\"1.0\" encoding=\"".$this->encoding."\"?>\n";
		$feed.= $this->_createGeneratorComment();
		$feed.= $this->_createStylesheetReferences();
		$feed.= "<opml xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\n";
		$feed.= "    <head>\n";
		$feed.= "        <title>".htmlspecialchars($this->title)."</title>\n";
		if (!empty($this->pubDate)) {
			$date = new FeedDate($this->pubDate);
			$feed.= "         <dateCreated>".$date->rfc822()."</dateCreated>\n";
		}
		if (!empty($this->lastBuildDate)) {
			$date = new FeedDate($this->lastBuildDate);
			$feed.= "         <dateModified>".$date->rfc822()."</dateModified>\n";
		}
		if (!empty($this->editor)) {
			$feed.= "         <ownerName>".$this->editor."</ownerName>\n";
		}
		if (!empty($this->editorEmail)) {
			$feed.= "         <ownerEmail>".$this->editorEmail."</ownerEmail>\n";
		}
		$feed.= "    </head>\n";
		$feed.= "    <body>\n";
		for ($i=0;$i<count($this->items);$i++) {
			$feed.= "    <outline type=\"rss\" ";
			$title = utf8_encode(htmlnumericentities(strip_tags(strtr($this->items[$i]->title,"\n\r","  "))));
			$feed.= " title=\"".$title."\"";
			$feed.= " text=\"".$title."\"";
			//$feed.= " description=\"".htmlspecialchars($this->items[$i]->description)."\"";
			$feed.= " url=\"".htmlspecialchars($this->items[$i]->link)."\"";
			$feed.= "/>\n";
		}
		$feed.= "    </body>\n";
		$feed.= "</opml>\n";
		return $feed;
	}
}



/**
 * HTMLCreator is a FeedCreator that writes an HTML feed file to a specific
 * location, overriding the createFeed method of the parent FeedCreator.
 * The HTML produced can be included over http by scripting languages, or serve
 * as the source for an IFrame.
 * All output by this class is embedded in <div></div> tags to enable formatting
 * using CSS.
 *
 * @author Pascal Van Hecke
 * @since 1.7
 */
class HTMLCreator extends FeedCreator {

	var $contentType = "text/html";

	/**
	 * Contains HTML to be output at the start of the feed's html representation.
	 */
	var $header;

	/**
	 * Contains HTML to be output at the end of the feed's html representation.
	 */
	var $footer ;

	/**
	 * Contains HTML to be output between entries. A separator is only used in
	 * case of multiple entries.
	 */
	var $separator;

	/**
	 * Used to prefix the stylenames to make sure they are unique
	 * and do not clash with stylenames on the users' page.
	 */
	var $stylePrefix;

	/**
	 * Determines whether the links open in a new window or not.
	 */
	var $openInNewWindow = true;

	var $imageAlign ="right";

	/**
	 * In case of very simple output you may want to get rid of the style tags,
	 * hence this variable.  There's no equivalent on item level, but of course you can
	 * add strings to it while iterating over the items ($this->stylelessOutput .= ...)
	 * and when it is non-empty, ONLY the styleless output is printed, the rest is ignored
	 * in the function createFeed().
	 */
	var $stylelessOutput ="";

	/**
	 * Writes the HTML.
	 * @return    string    the scripts's complete text
	 */
	function createFeed() {
		// if there is styleless output, use the content of this variable and ignore the rest
		if (!empty($this->stylelessOutput)) {
			return $this->stylelessOutput;
		}

		//if no stylePrefix is set, generate it yourself depending on the script name
		if ($this->stylePrefix=="") {
			$this->stylePrefix = str_replace(".", "_", $this->_generateFilename())."_";
		}

		//set an openInNewWindow_token_to be inserted or not
		if ($this->openInNewWindow) {
			$targetInsert = " target='_blank'";
		}
		
		// use this array to put the lines in and implode later with "document.write" javascript
		$feedArray = array();
		if ($this->image!=null) {
			$imageStr = "<a href='".$this->image->link."'".$targetInsert.">".
							"<img src='".$this->image->url."' border='0' alt='".
							FeedCreator::iTrunc(htmlspecialchars($this->image->title),100).
							"' align='".$this->imageAlign."' ";
			if ($this->image->width) {
				$imageStr .=" width='".$this->image->width. "' ";
			}
			if ($this->image->height) {
				$imageStr .=" height='".$this->image->height."' ";
			}
			$imageStr .="/></a>";
			$feedArray[] = $imageStr;
		}
		
		if ($this->title) {
			$feedArray[] = "<div class='".$this->stylePrefix."title'><a href='".$this->link."' ".$targetInsert." class='".$this->stylePrefix."title'>".
				FeedCreator::iTrunc(htmlspecialchars($this->title),100)."</a></div>";
		}
		if ($this->getDescription()) {
			$feedArray[] = "<div class='".$this->stylePrefix."description'>".
				str_replace("]]>", "", str_replace("<![CDATA[", "", $this->getDescription())).
				"</div>";
		}
		
		if ($this->header) {
			$feedArray[] = "<div class='".$this->stylePrefix."header'>".$this->header."</div>";
		}
		
		for ($i=0;$i<count($this->items);$i++) {
			if ($this->separator and $i > 0) {
				$feedArray[] = "<div class='".$this->stylePrefix."separator'>".$this->separator."</div>";
			}
			
			if ($this->items[$i]->title) {
				if ($this->items[$i]->link) {
					$feedArray[] = 
						"<div class='".$this->stylePrefix."item_title'><a href='".$this->items[$i]->link."' class='".$this->stylePrefix.
						"item_title'".$targetInsert.">".FeedCreator::iTrunc(htmlspecialchars(strip_tags($this->items[$i]->title)),100).
						"</a></div>";
				} else {
					$feedArray[] = 
						"<div class='".$this->stylePrefix."item_title'>".
						FeedCreator::iTrunc(htmlspecialchars(strip_tags($this->items[$i]->title)),100).
						"</div>";
				}
			}
			if ($this->items[$i]->getDescription()) {
				$feedArray[] = 
				"<div class='".$this->stylePrefix."item_description'>".
					str_replace("]]>", "", str_replace("<![CDATA[", "", $this->items[$i]->getDescription())).
					"</div>";
			}
		}
		if ($this->footer) {
			$feedArray[] = "<div class='".$this->stylePrefix."footer'>".$this->footer."</div>";
		}
		
		$feed= "".join($feedArray, "\r\n");
		return $feed;
	}
    
	/**
	 * Overrrides parent to produce .html extensions
	 *
	 * @return string the feed cache filename
	 * @since 1.4
	 * @access private
	 */
	function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".html";
	}
}	


/**
 * JSCreator is a class that writes a js file to a specific 
 * location, overriding the createFeed method of the parent HTMLCreator.
 *
 * @author Pascal Van Hecke
 */
class JSCreator extends HTMLCreator {
	var $contentType = "text/javascript";
	
	/**
	 * writes the javascript
	 * @return    string    the scripts's complete text 
	 */
	function createFeed() 
	{
		$feed = parent::createFeed();
		$feedArray = explode("\n",$feed);
		
		$jsFeed = "";
		foreach ($feedArray as $value) {
			$jsFeed .= "document.write('".trim(addslashes($value))."');\n";
		}
		return $jsFeed;
	}
    
	/**
	 * Overrrides parent to produce .js extensions
	 *
	 * @return string the feed cache filename
	 * @since 1.4
	 * @access private
	 */
	function _generateFilename() {
		$fileInfo = pathinfo($_SERVER["PHP_SELF"]);
		return substr($fileInfo["basename"],0,-(strlen($fileInfo["extension"])+1)).".js";
	}
}

