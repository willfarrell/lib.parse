<?php

require_once("htmLawed.php");

########################################################################
#
# LIB_parse.php     Parse Routines
#
#-----------------------------------------------------------------------
# FUNCTIONS
#
#    split_string()   Returns the portion of a string either before
#                     or after a delineator. The returned string may
#                     or may not include the delineator.
#
#    return_between() Returns the portion of a string that falls
#                     between two delineators, exclusive or inclusive
#                     of the delineators.
#
#    parse_array()    Returns an array containing all occurrences of
#                     text that falls between a set of delineators.
#
#    get_attribute()  Returns the value of a HTML tag attribute
#
#    remove()         Removes all occurrences of a string from
#                     another string.
#
#    tidy_html()      Puts raw HTML into a known state with proper
#                     with parsable syntax
#
########################################################################

/***********************************************************************
Parse Constants (scope = global)
----------------------------------------------------------------------*/
# Specifies if parse includes the delineator
define("EXCL", true);
define("INCL", false);
# Specifies if parse returns the text before or after the delineator
define("BEFORE", true);
define("AFTER", false);

/***********************************************************************
split_string($string, $delineator, $desired, $type)
-------------------------------------------------------------
DESCRIPTION:
        Returns a potion of the string that is either before or after
        the delineator. The parse is not case sensitive, but the case of
        the parsed string is not effected.
INPUT:
        $string         Input string to parse
        $delineator     Delineation point (place where split occurs)
        $desired        BEFORE: return portion before delineator
                        AFTER:  return portion before delineator
        $type           INCL: include delineator in parsed string
                        EXCL: exclude delineator in parsed string
***********************************************************************/
function split_string($string, $delineator, $desired, $type) {
    # Case insensitive parse, convert string and delineator to lower case
    $lc_str = strtolower($string);
        $marker = strtolower($delineator);

    # Return text BEFORE the delineator
    if ($desired == BEFORE) {
        if ($type == EXCL) // Return text EXCL of the delineator
            $split_here = strpos($lc_str, $marker);
        else               // Return text INCL of the delineator
            $split_here = strpos($lc_str, $marker)+strlen($marker);

        $parsed_string = substr($string, 0, $split_here);
    }
    # Return text AFTER the delineator
    else {
        if ($type==EXCL) // Return text EXCL of the delineator
            $split_here = strpos($lc_str, $marker) + strlen($marker);
        else               // Return text INCL of the delineator
            $split_here = strpos($lc_str, $marker) ;

        $parsed_string =  substr($string, $split_here, strlen($string));
    }
    return $parsed_string;
}

/***********************************************************************
$value = return_between($string, $start, $end, $type)
-------------------------------------------------------------
DESCRIPTION:
        Returns a substring of $string delineated by $start and $end
        The parse is not case sensitive, but the case of the parsed
        string is not effected.
INPUT:
        $string         Input string to parse
        $start          Defines the beginning of the sub string
        $end            Defines the end of the sub string
        $type           INCL: include delineators in parsed string
                        EXCL: exclude delineators in parsed string
***********************************************************************/
function return_between($string, $start = null, $stop = null, $type = EXCL) {
    if (isset($start)) $string = split_string($string, $start, AFTER, $type);
    if (isset($stop)) $string = split_string($string, $stop, BEFORE, $type);
    return $string;
}

/***********************************************************************
$array = parse_array($string, $open_tag, $close_tag)
-------------------------------------------------------------
DESCRIPTION:
        Returns an array of strings that exists repeatedly in $string.
        This function is usful for returning an array that contains
        links, images, tables or any other data that appears more than
        once.
INPUT:
        $string     String that contains the tags
        $open_tag   Name of the open tag (i.e. "<a>")
        $close_tag  Name of the closing tag (i.e. "</title>")

***********************************************************************/
function parse_array($string, $beg_tag, $close_tag) {
    preg_match_all("($beg_tag(.*)$close_tag)siU", $string, $matching_data);
    return $matching_data[0];
}

function parse_multi_array($string, $tag_array = array(array("beg_tag" => "", "close_tag" => ""))) {
	for ($i=0; $i<count($tag_array) ; $i++) {
		if ($i) $preg .= "|";
		$preg .= "(".$tag_array[$i]['beg_tag']."(.*)".$tag_array[$i]['close_tag'].")";
	}

    preg_match_all("($preg)siU", $string, $matching_data);
    return $matching_data[0];
}

function parse_form($string, $form_ID = null) {
	// returns post array
	if (isset($form_ID)) {
		$forms = parse_array($string, '<form', '</form>');
		//print_r($forms);
		for ($f=0; $f<count($forms) ; $f++) {
			$form = return_between($forms[$f], '<form', '>', INCL);
			//echo $forms[$f]."<br />";
			if (get_attribute($form, 'id') == $form_ID || get_attribute($form, 'name') == $form_ID) {
				$string = $forms[$f];
				break;
			}
		}
	}

	$elements = parse_multi_array(
		$string,
		$tag_array = array(
			array("beg_tag" => "<input", "close_tag" => ">"),
			array("beg_tag" => "<select", "close_tag" => "</select>"),
            array("beg_tag" => "<textarea", "close_tag" => "</textarea>")
        )
	);
	//print_r($elements);
	$post = array();
	for ($i=0; $i<count($elements) ; $i++) {
		$element = array();
		$element['tag'] = return_between($elements[$i], '<', ' ');
		//$element['id'] = get_attribute($elements[$i], 'id');
		$element['name'] = get_attribute($elements[$i], 'name');

		if ($element['tag'] == 'input') {
			$element['type'] = get_attribute($elements[$i], 'type');
			if ($element['type'] == 'image') $element['value'] = '';
			else {
				if (stristr($elements[$i], 'VALUE')) $element['value'] = get_attribute($elements[$i], 'value');
				else $element['value'] = '';
			}
		} else if ($element['tag'] == 'select') {
			$element['options'] = parse_array($elements[$i], '<option', '</option>');
			for ($o=0; $o<count($element['options']) ; $o++) {
				if (stristr($element['options'][$o],'selected="selected"')) {
					$element['value'] = get_attribute($element['options'][$o], 'value');
					break;
				}
			}
			if (!$element['value']) $element['value'] = get_attribute($element['options'][0], 'value');
		} else if ($element['tag'] == 'select') {
			$element['value'] = return_between($elements[$i], '>', '<');
		}
		if ($element['name']) $post[$element['name']] = $element['value'];
		//print_r($element);
	}
	return $post;
}



/***********************************************************************
$value = get_attribute($tag, $attribute)
-------------------------------------------------------------
DESCRIPTION:
        Returns the value of an attribute in a given tag.
INPUT:
        $tag         The tag that contains the attribute
        $attribute   The name of the attribute, whose value you seek

***********************************************************************/
function get_attribute($tag, $attribute, $clean = false) {
    if ($clean) { // only needed if a page clean was no run
		# Use Tidy library to 'clean' input
		$cleaned_html = tidy_html($tag);

		# Remove all line feeds from the string
		$cleaned_html = str_replace("\r", "", $cleaned_html);
		$cleaned_html = str_replace("\n", "", $cleaned_html);
	} else {
		$cleaned_html = $tag;
	}

    # Use return_between() to find the properly quoted value for the attribute
	if (stristr($cleaned_html, strtoupper($attribute) .'=')) {
		if (stristr($cleaned_html, strtoupper($attribute) ."='"))
			return return_between($cleaned_html, strtoupper($attribute).'=\'', '\'', EXCL);
		else if (stristr($cleaned_html, strtoupper($attribute) .'="'))
			return return_between($cleaned_html, strtoupper($attribute)."=\"", "\"", EXCL);
		else
			return return_between($cleaned_html, strtoupper($attribute)."=", " ", EXCL);
	} else
	return '';
}

function parseURL($src) {
    $regex = "/['\"]?(?P<url>[^'\"]*)['\"]?/i";
    // HREF
    $url = get_attribute($src, 'href');
    if (stristr($url, "javascript:")) {
        preg_match($regex, $url, $matches);
        if ($matches['url']) $url = $matches['url'];
    }
    // ONCLICK
    if (!$url) {
        $url = get_attribute($src, 'onclick');
        preg_match($regex, $url, $matches);
        if ($matches['url']) $url = $matches['url'];
    }
    $url = html_entity_decode($url);
    $url = str_replace(" ", "%20", $url);

    switch (preg_last_error()) {
        //case PREG_NO_ERROR : $this->debug('No error'); break;
        case PREG_INTERNAL_ERROR : echo 'An internal PCRE error'; break;
        case PREG_BACKTRACK_LIMIT_ERROR : echo 'Backtrack limit exceeded'; break;
        case PREG_RECURSION_LIMIT_ERROR : echo 'Recursion limit exceeded'; break;
        case PREG_BAD_UTF8_ERROR : echo 'Bad UTF-8 error'; break;
    }
    if (preg_last_error()) echo "Regex Matches (".$regex."): ".print_r($matches, true);

    return $url;
}

/***********************************************************************
remove($string, $open_tag, $close_tag)
-------------------------------------------------------------
DESCRIPTION:
        Removes all text between $open_tag and $close_tag
INPUT:
        $string     The target of your parse
        $open_tag   The starting delimitor
        $close_tag  The ending delimitor

***********************************************************************/
function remove($string, $open_tag, $close_tag)
{
    # Get array of things that should be removed from the input string
    $remove_array = parse_array($string, $open_tag, $close_tag);

    # Remove each occurrence of each array element from string;
    for ($xx=0; $xx<count($remove_array) ; $xx++)
        $string = str_replace($remove_array, "", $string);

    return $string;
    }

/***********************************************************************
tidy_html($input_string)
-------------------------------------------------------------
DESCRIPTION:
        Returns a "Cleans-up" (parsable) version raw HTML
INPUT:
        $string     raw HTML

OUTPUT:
        Returns a string of cleaned-up HTML
***********************************************************************/
function tidy_html($input_string)
{
	// Detect if Tidy is in configured
    if (function_exists('tidy_get_release')) {
		// Tidy for PHP version 4
		/*if (substr(phpversion() , 0, 1) == 4) {
			tidy_setopt('uppercase-attributes', true);
			tidy_setopt('wrap', 800);
			tidy_parse_string($input_string);
			$cleaned_html = tidy_get_output();
		}*/
		// Tidy for PHP version 5
		if (substr(phpversion() , 0, 1) == 5) {
			$config = array(
				'uppercase-attributes' => true,
				'wrap'                 => 800
			);
			$tidy = new tidy;
			$tidy->parseString($input_string, $config, 'utf8');
			$tidy->cleanRepair();
			$cleaned_html  = tidy_get_output($tidy);
		}
	} else {
		// Tidy not configured for this computer
		//echo 'Tidy not configured for this computer';
		$config = array(
			'tidy' => -1
		);
		$cleaned_html = htmLawed($input_string, $config);
	}
    return $cleaned_html;
}


function page_clean($content, $config = null)
{
    $config_default = array(
		"tidy" => true, // run through tidy?
		"body" => false,
		"head" => false, // remove head?
		"script" => true, // remove script tags?
		"style" => true, // remove style tags?
		"comments" => true, // remove comments?
		"img" => false, // remove img tags?
		"del" => false, // remove del tags?
		"space" => true, // clean up spaces?
		"accent" => false
	);

    foreach ($config_default as $key => $value) if (!isset($config[$key])) $config[$key] = $value;

    if ($config['tidy']) $content = tidy_html($content); // clean the HTML to make it easier for parsing
	
	$content = preg_replace("/<body[^>]*?>/i", "<body>", $content); // clear <body> attributes
    if ($config['body']) $content = return_between($content, '<body>', '</body>'); // return <body> tag contents

    if ($config['head']) $content = remove($content, '<head', 'head>'); // remove <head> tag
    if ($config['script']) {// remove <script> tags
        $content = remove($content, '<script', '</script>');
        $content = remove($content, '<script', '/>');
    }
    if ($config['style']) {// remove <style> tags
        $content = remove($content, '<style', '</style>');
        $content = remove($content, '<style', '/>');
    }
	
	// remove all tbody tags - are known to be auto generated by browsers
	$content = preg_replace("/<\/?tbody[^>]*?>/i", "", $content);
	
    //strip_tags() // removes all links, js, img, css

    if ($config['comments']) $content = remove($content, '<!--', '-->'); // remove <!-- --> tags
    if ($config['img']) $content = remove($content, '<img', '/>'); // remove <img> tags
    if ($config['del']) $content = remove($content, '<del', '</del>'); // remove <del> tags

    // general clean up
    // trim() removing excessive spaces, line feeds, and other white space from start or end
    $content = preg_replace("/\n|\r|\t/", "", $content); // returns -> word wrap
    if ($config['space']) {
        $content = str_replace("&nbsp;",' ',$content);
        $content = preg_replace("/\\s\\s+/", " ", $content); // double spaces -> single space
    }

    $content = str_replace("&amp;",'&',$content);
    //$content = replace_accents($content); // replace with &code;
    //if (!$config['accent']) $content = remove_accents($content);
    return $content;
    }


// a.m. -> AM
function dateCleanUp($date)
{
    // remove popular break chars
    $date = str_replace("a.m.", 'am', $date);
    $date = str_replace("A.M.", 'AM', $date);
    $date = str_replace("p.m.", 'pm', $date);
    $date = str_replace("P.M.", 'PM', $date);

    // remover double chars
    $date = str_replace(',,', ',', $date);
    $date = str_replace('..', '.', $date);

    // remove number post scripts
    $date = preg_replace("/(\d{1,2})(st|nd|rd|th)/", "$1", $date);

    // remove possible filler text
    $date = str_replace(' at ', ' ', $date);
    $date = str_replace(' on ', ' ', $date);
    return $date;
}

function dateParseFromFormat($aPieces, $stData)
{
        if (!$stData) return;
        $stData = dateCleanUp($stData);

    $aDataRet = array();
        $pattern = '[\(\)\:/.\ \,\-]';
    $aDatePart = split($pattern, $stData);

        // strip out blanks
        foreach ($aDatePart as $key => $value) {
            if ($value == "") {
                unset($aDatePart[$key]);
            }
        }
        $aDatePart = array_values($aDatePart);

        $size = sizeof($aPieces);
        for ($i=0; $i < $size; $i++) {
    //foreach ($aPieces as $key=>$chPiece) {
            $chPiece = $aPieces[$i];

            if ($aDatePart[$i]) {
        switch ($chPiece) {
                    // Timezone
                    case ($chPiece == 'e' || $chPiece == 'T' || $aDatePart[$i] == "America"):
                        date_default_timezone_set($aDatePart[$i]."/".$aDatePart[$i+1]); // [content]/[city]
                        $i++;
                        break;
                    // Day
                    case ($chPiece == 'd' || $chPiece == 'j'):
                        // Day of the month, 2 digits with leading zeros      01 to 31
                        $aDataRet['day'] = $aDatePart[$i];
                        break;
                    case ($chPiece == 'D' || $chPiece == 'l'):
                        //$day = array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
                        // D      A textual representation of a day, three letters      Mon through Sun
                        break;
                // Month
                    case ($chPiece == 'F' || $chPiece == 'M'):
                        $mon = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

                        $msize = sizeof($mon);
                        for ($j=0; $j<$msize; $j++) {
                            if (strpos(strtolower($mon[$j]) , strtolower($aDatePart[$i])) === false) {
                            } else {
                                $aDataRet['month'] = $j+1;
                                break;
                            }
                        }

                        break;
                    case ($chPiece == 'm' || $chPiece == 'n'):
                        $aDataRet['month'] = $aDatePart[$i];
                        break;
                // Year
                    case ($chPiece == 'o' || $chPiece == 'y' || $chPiece == 'Y'):
                        $aDataRet['year'] = $aDatePart[$i];
                        break;
                // Time
                    case ($chPiece == 'a' || $chPiece == 'A'):
                        if (strtolower($aDatePart[$i]) == "pm" && $aDataRet['hour'] < 12) $aDataRet['hour'] += 12;
                        else if (strtolower($aDatePart[$i]) == "am" && $aDataRet['hour'] == 12) $aDataRet['hour'] -= 12;
                        break;
                    case ($chPiece == 'g' || $chPiece == 'G' || $chPiece == 'h' || $chPiece == 'H'):
                        $aDataRet['hour'] += $aDatePart[$i];
                        break;
                    case 'i':
                        $aDataRet['minute'] = $aDatePart[$i];
                        break;
                    case 's':
                        $aDataRet['second'] = $aDatePart[$i];
                        break;

        } // end case
            } // end if isset

    } // end loop through parts

        $mktime = mktime((int) $aDataRet['hour'],
                                    (int) $aDataRet['minute'],
                                    (int) $aDataRet['second'],
                                    (int) $aDataRet['month'],
                                    (int) $aDataRet['day'],
                                    (int) $aDataRet['year']);

        if (!$mktime) {
            echo "mktime Fail<br />";
            print_r($aPieces);
            print_r($aDatePart);
            print_r($aDataRet);
        }
        if (!$mktime) echo "mktime(".$stData.") -> date('r', $mktime) = ".date("r", $mktime)."<br />";

    return $mktime;
}



function remove_accents($string)
{
    return (strtr($string, "¿¡¬√ƒ≈∆‡·‚„‰ÂÊ“”‘’’÷ÿÚÛÙıˆ¯»… ÀËÈÍÎ«Á–ÃÕŒœÏÌÓÔŸ⁄€‹˘˙˚¸—Òﬁﬂˇ˝",
                                                 "aaaaaaaaaaaaaaoooooooooooooeeeeeeeeecceiiiiiiiiuuuuuuuunntsyy"));
}

function replace_accents($res)
{
    $res = html_entity_decode($res);
    //echo "?RES?".$res;
    $res = htmlentities($res);
    //$res = htmlentities($res, ENT_NOQUOTES, "UTF-8", false);
    //echo "?RES?".$res;
    $res = str_replace("&quot;",'"',$res);
    $res = str_replace("&lt;","<",$res);
    $res = str_replace("&gt;",">",$res);
    $res = str_replace("&amp;",'&',$res);
    $res = str_replace("&nbsp;",' ',$res);
    $res = str_replace("&apos",' ',$res);
    return $res;
}

/*function preg_replace_all($pattern,$replace,$text)
{
    while (preg_match($pattern,$text)) {
        $text = preg_replace($pattern,$replace,$text);
    }
    return $text;
}*/

?>