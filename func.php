<?php

#################################################################################
# FUNCTIONS

// create new filename for renaming
function filerename($basename) {
  global $OPTPOD;
  global $PODCASTS;
  global $podcast;
  global $rssinfo;
  // take file ending off the filename so we can append it at new name
  $file_parts = pathinfo($basename);
  $searchreplace = array(
    "%podyear%" => date('Y', strtotime($rssinfo['pubDate'])),
    "%podmonth%" => date('m', strtotime($rssinfo['pubDate'])),
    "%podday%" => date('d', strtotime($rssinfo['pubDate'])),
    "%podhour24%" => date('H', strtotime($rssinfo['pubDate'])),
    "%podmin%" => date('i', strtotime($rssinfo['pubDate'])),
    "%podsec%" => date('s', strtotime($rssinfo['pubDate'])),
    "%nowyear%" => date('Y'),
    "%nowmonth%" => date('m'),
    "%nowday%" => date('d'),
    "%nowhour24%" => date('H'),
    "%nowmin%" => date('i'),
    "%nowsec%" => date('s'),
    "%filename%" => $file_parts['filename'], // the filename is the basename without the extension
    "%podcastname%" => $podcast, // podcast name as set in the serverlist.ini file
    "%podcastfolder%" => $PODCASTS[$podcast]['folder'], // podcast folder name as set in the serverlist.ini file
    "%itemtitle%" => $rssinfo['title'] // title of the individual item taken from RSS
  );
  // create the new name with a funky array flip flop
  $search = array_keys($searchreplace);
  $replace = array_values($searchreplace);
  $return = str_replace ( $search , $replace , $OPTPOD['rename'] );
  // now make sure we have a valid file name
  $return = sanitize_filename($return);
  // set extension to default from options.ini - and overwrite in the following "if" if needed
  $ext = ".".$OPTPOD['extension'];
  if($file_parts['extension']) {
    // set file extension as found on file
    $ext = ".".$file_parts['extension'];
  }
  // add file extension
  $return .= $ext;
  return $return;
}
// write flat ini config file from array
function array2ini($file, $array) {
  $ini = "";
  foreach ($array as $key => $value) {
    $ini .= $key." = \"".$value."\"\n";
  }
  if(!file_put_contents($file, $ini)) {
    $logwrite = logwrite("ERROR: FUNCTION ".__FUNCTION__." : ERROR writing status to file '".$file."'", 1);
    return "false";
  } else {
    $logwrite = logwrite("INFO: FUNCTION ".__FUNCTION__." : SUCCESS writing status to file '".$file."'", 4);
    return "true";
  }
}

// reading all item information from podcast, returning array
function getitemsfromxml($localpodxml) {
  $return = array(); // this will return the array with items
  // TODO: what if there are no items in the xml? This is not an error, but an empty podcast.
  
  $xmlraw = file_get_contents($localpodxml);
  $xml = cleanxml($xmlraw);
  $doc = new DOMDocument();
  $doc->preserveWhiteSpace = false;
  $doc->loadXML($xml);
  // Initialize XPath    
  $xpath = new DOMXpath($doc);
  // Register namespaces
  $xpath->registerNamespace( 'dc', 'http://purl.org/dc/elements/1.1/');
  $xpath->registerNamespace( 'media', 'http://search.yahoo.com/mrss/');
  $xpath->registerNamespace( 'itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
  
  ### READ PODCAST INFORMATION TO ARRAY
  // channel
  $return['channel'] = xmlchannel2array($xpath->query( 'channel')->item(0), $xpath);
  // items
  $items = $doc->getElementsByTagName('item'); 
  foreach($items as $item) {
    ### READ PODCAST INFORMATION TO ARRAY
    $return['items'][] = xmlitem2array($item, $xpath);
  }
  return $return;
}

// extract channel information from podcast XML 
function xmlchannel2array($channel, $xpath) {
  $return = array(); // the information we want to know in an array
  $keys = array( // the keys we are looking for
    "title",
    "link",
    "description",
    "language",
    "copyright",
    "itunes:author",
    "itunes:subtitle",
    "itunes:owner",
    "itunes:category",
    "itunes:image",
    "lastBuildDate"
  );
  foreach($keys as $key) {
    if($return[$key] = $xpath->query( $key, $channel)->item(0)->nodeValue) {
    } else {
      $logwrite = logwrite("INFO: FUNCTION ".__FUNCTION__." : PHP Notice: Trying to get property '".$key."' of non-object", 4);
    }
    // and crop this of extra baggage
    $return[$key] = trim(strip_tags($return[$key]));
  }
  // check for image URL
  $return['image_url'] = "";
  // first check inside itunes tag
  $image = $xpath->query( 'itunes:image', $channel)->item(0);
  $return['image_url'] = trim(strip_tags($image->attributes->getNamedItem('href')->value));
  // check if we were successful
  if($return['image_url'] == "") {
    // no success, try image tag
    $image = $xpath->query( 'image', $channel)->item(0);
    $return['image_url'] = $xpath->query( 'url', $image)->item(0)->nodeValue;
  }
  // unset fields in the array, if the value is empty - so we can later check for missing values and improvise
  foreach($keys as $key) {
    if($return[$key] == "") {
      unset($return[$key]);
      $logwrite = logwrite("INFO: FUNCTION ".__FUNCTION__." : Item field '".$key."' is empty, non existent or can not be read", 4);
    }
  }
  return $return;
}
// extract item information from podcast XML and adds more information (e.g. filedate and mp3 basename)
function xmlitem2array($item, $xpath) {
  $return = array(); // the information we want to know in an array
  $keys = array( // the keys we are looking for
    "title",
    "guid",
    "pubDate",
    "link",
    "category",
    "copyright",
    "description",
    "generator",
    "itunes:author",
    "itunes:duration",
    "itunes:subtitle",
    "itunes:summary",
  );
  foreach($keys as $key) {
    if($return[$key] = $xpath->query( $key, $item)->item(0)->nodeValue) {
    } else {
      $logwrite = logwrite("INFO: FUNCTION ".__FUNCTION__." : PHP Notice: Trying to get property '".$key."' of non-object", 4);
    }
    // and crop this of extra baggage
    $return[$key] = trim(strip_tags($return[$key]));
  }
  // now let's look at the enclosure tag to get all relevant file information
  $enclosure = $xpath->query( 'enclosure', $item)->item(0);
  $return['enclosure_url'] = trim(strip_tags($enclosure->attributes->getNamedItem('url')->value));
  $return['enclosure_length'] = trim(strip_tags($enclosure->attributes->getNamedItem('length')->value));
  $return['enclosure_type'] = trim(strip_tags($enclosure->attributes->getNamedItem('type')->value));
  
  // little error checking and guessing for essential fields
  // check if the pubDate field exists. If not, we have to set the value to "now" - assuming the file is new since the last crontab check
  if(!isset($return['pubDate'])) {
    date('r');
  }  
  
  // done with the XML, now some additional stuff we need
  
  // audio file name is needed to see if we already have this file locally
  $return['enclosure_filename'] = cleanfilename($return['enclosure_url']);
  
  // changing the file date with exec(touch) we need a special date format: touch -t yyyyMMddHHmm.ss $filename
  $return['filedate_touch'] = date('YmdHi.s', strtotime($return['pubDate'])); 
  // special format used by id3v2
  $return['id3v2_timestamp'] = date('Y-m-d', strtotime($return['pubDate']))."T".date('H:i:s', strtotime($return['pubDate'])); //yyyy-MM-ddTHH:mm:ss
  
  // year of publication
  $return['dateY'] = date('Y', strtotime($return['pubDate'])); 
  
  // unset fields in the array, if the value is empty - so we can later check for missing values and improvise
  foreach($keys as $key) {
    if($return[$key] == "") {
      unset($return[$key]);
      $logwrite = logwrite("INFO: FUNCTION ".__FUNCTION__." : Item field '".$key."' is empty, non existent or can not be read", 4);
    }
  }
  return $return;
}

// chop up the original XML file and return the XML as an array for channel, items and header
function rssxml2array($localpodxml) {
  $return = array(); // this will return the array with items
  $xmlraw = file_get_contents($localpodxml);
  // change item and channel tags to lowercase
  $match = array("/<item/i", "/<\/item/i", "/<channel/i", "/<\/channel/i");
  $replace = array("<item", "</item", "<channel", "</channel");
  $xmlraw = preg_replace($match, $replace, $xmlraw);
  // apologies for the rough chopping with "explode". It works. I am open for more elegant solutions.
  // I tried DOMXpath with Namespaces, but the rough'n'ready solution below worked better (for me).
  $tempitems = explode("<item>", $xmlraw); 
  $xmlstart = array_shift($tempitems); // first chunk will containt channel, the others are all items
  $xmlstart = explode("<channel>", $xmlstart); // now we have header and channel start in an array
  $return['header'] = $xmlstart[0];
  $return['header'] = trim($return['header']);
  $return['channel'] = trim($xmlstart[1]);
  foreach($tempitems as $itemraw) {
    $itemraw = explode("</item>", $itemraw); // chop off the end of the item
    $itemxml = "<item>".$itemraw[0]."\n</item>";
    $return['items'][] = trim($itemxml);
    $xmlend =  $itemraw[1]; // don't forget to take a look at the end of the item list, might contain channel xml
  }
  // take a look at the end of the xml file
  $xmlend = explode("</channel>", $xmlend); // now we have header and channel start in an array
  $return['channel'] .= $xmlend[0];
  $return['channel'] = trim($return['channel']);
  return $return;
}

// make machine readable name
function machinename($name) {
  $name = trim($name);
  $name = strtolower($name);
  $name = preg_replace('/\s+/', '_',$name);
  $name = preg_replace('/&/', '-',$name);
  $name = preg_replace('/:/', '-',$name);
  $name = preg_replace('/\./', '-',$name);
  $name = preg_replace('/\,/', '',$name);
  //$name = urlencode($name); 
  return $name;
}

function sanitize_filename($string, $separator = '-') {
  // Remove special accented characters - ie. sí.
  $clean_name = strtr($string, array('Š' => 'S','Ž' => 'Z','š' => 's','ž' => 'z','Ÿ' => 'Y','À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A','Ç' => 'C','È' => 'E','É' => 'E','Ê' => 'E','Ë' => 'E','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I','Ñ' => 'N','Ò' => 'O','Ó' => 'O','Ô' => 'O','Õ' => 'O','Ö' => 'O','Ø' => 'O','Ù' => 'U','Ú' => 'U','Û' => 'U','Ü' => 'U','Ý' => 'Y','à' => 'a','á' => 'a','â' => 'a','ã' => 'a','ä' => 'a','å' => 'a','ç' => 'c','è' => 'e','é' => 'e','ê' => 'e','ë' => 'e','ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ñ' => 'n','ò' => 'o','ó' => 'o','ô' => 'o','õ' => 'o','ö' => 'o','ø' => 'o','ù' => 'u','ú' => 'u','û' => 'u','ü' => 'u','ý' => 'y','ÿ' => 'y'));
  $clean_name = strtr($clean_name, array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', 'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
  // replace whitespaces with seperator
  $clean_name = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array($separator, '.', ''), $clean_name);
  return $clean_name;
}

// returns the filename only, no prefix folders or suffix url parameters ?key=value&andsoforth
function cleanfilename($name) {
  $temp = parse_url($name);
  $temp = pathinfo($temp['path']);
  $name = $temp['basename'];
  return $name;
}

// this might need some extra work, helps to bypass namespace problems
function cleanxml($xml) {
  $xml = preg_replace('/\&amp\;/', '+',$xml);
  return $xml;
}

// write log file
function logwrite($message, $loglevel = 5) {
  global $VAR;
  if($VAR['loglevel'] > 0) { // check if we should log anything
    if($loglevel <= $VAR['loglevel']) { // check if we should log this message
      $message = date('Y-m-dTH:i:s', time())." : ".trim($message);
      if($VAR['logscreen'] == "true") { // check if we should also display on screen (running from the command line)
        echo $message."\n";
      }
      // todo: writing log file
    }
  } 
  return "true";
}

// create a folder if non existent
function foldercreate($folder) {
  $return = "false"; // the return value of this function
  if (!file_exists($folder)) {
    if(!mkdir($folder, 0777, true)) {
      $logwrite = logwrite("ERROR in FUNCTION ".__FUNCTION__." : ERROR creating folder : ".$folder, 1);
    } else {
      $logwrite = logwrite("INFO: FUNCTION ".__FUNCTION__." : SUCCESS creating folder : ".$folder, 4);
      $return = "true";
    }
  } else {
    $logwrite = logwrite("INFO: FUNCTION ".__FUNCTION__." : folder already exists : ".$folder, 4);
    $return = "true";
  }
  return $return;
}

// get rid off multiple backslashes like: This isn\\\\\'t working
function removeslashes($string) {
  $string=implode("",explode("\\",$string));
  return stripslashes(trim($string));
}

?>
