<?php

function diff($old, $new)
{
    //print_r($old);
    //print_r($new);
    //return;
    $old = is_array($old)?$old:str_split($old);
    $new = is_array($new)?$new:str_split($new);

    $maxlen = 0;
    foreach ($old as $oindex => $ovalue) {
        $ovalue = (is_array($ovalue) && count($ovalue))?$ovalue[0]:$ovalue;
        $nkeys = array_keys($new, $ovalue); // search new array for old value
        foreach ($nkeys as $nindex) {
            $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
            $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
            if ($matrix[$oindex][$nindex] > $maxlen) {
                $maxlen = $matrix[$oindex][$nindex];
                $omax = $oindex + 1 - $maxlen;
                $nmax = $nindex + 1 - $maxlen;
            }
        }
    }
    if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
    /*return array_merge(diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
        array_slice($new, $nmax, $maxlen),
        diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));*/
    return array_merge(diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
}

function removeDiff($old, $new)
{
    if($old == '') return $new;
    $diff = diff($old, $new);
    $ret = '';
    foreach ($diff as $k) {
        if (is_array($k)) {
            /*$ret .= (!empty($k['d'])?"<del>".implode('',$k['d'])."</del>":'').
                            (!empty($k['i'])?"<ins>".implode('',$k['i'])."</ins>":'');*/
        } else $ret .= $k . '';
    }
    return $ret;
}

/*function htmlDiff($old, $new)
{
    $diff = diff(explode(' ', $old), explode(' ', $new));
    foreach ($diff as $k) {
        if(is_array($k))
            $ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
                            (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
        else $ret .= $k . ' ';
    }
    return $ret;
}

function charDiff($old, $new)
{
    $diff = diff(str_split( $old), str_split($new));
    $ret = '';
    foreach ($diff as $k) {
        if(is_array($k))
            $ret .= (!empty($k['d'])?"<del>".implode('',$k['d'])."</del>":'').
                            (!empty($k['i'])?"<ins>".implode('',$k['i'])."</ins>":'');
        else $ret .= $k . '';
    }
    return $ret;
}*/

/*
The following should be escaped if you are trying to match that character

\ ^ . $ | ( ) [ ]
* + ? { } ,

Special Character Definitions
\ Quote the next metacharacter
^ Match the beginning of the line
. Match any character (except newline)
$ Match the end of the line (or before newline at the end)
| Alternation
() Grouping
[] Character class
* Match 0 or more times
+ Match 1 or more times
? Match 1 or 0 times
{n} Match exactly n times
{n,} Match at least n times
{n,m} Match at least n but not more than m times
More Special Character Stuff
\t tab (HT, TAB)
\n newline (LF, NL)
\r return (CR)
\f form feed (FF)
\a alarm (bell) (BEL)
\e escape (think troff) (ESC)
\033 octal char (think of a PDP-11)
\x1B hex char
\c control char
\l lowercase next char (think vi)
\u uppercase next char (think vi)
\L lowercase till \E (think vi)
\U uppercase till \E (think vi)
\E end case modification (think vi)
\Q quote (disable) pattern metacharacters till \E
Even More Special Characters
\w Match a "word" character (alphanumeric plus "_")
\W Match a non-word character
\s Match a whitespace character
\S Match a non-whitespace character
\d Match a digit character
\D Match a non-digit character
\b Match a word boundary
\B Match a non-(word boundary)
\A Match only at beginning of string
\Z Match only at end of string, or before newline at the end
\z Match only at end of string
\G Match only where previous m//g left off (works only with /g)
*/
function escape_string_for_regex($str)
{
        //All regex special chars (according to arkani at iol dot pt below):
        // \ ^ . $ | ( ) [ ]
        // * + ? { } ,

        $patterns = array('/\//', '/\^/', '/\./', '/\$/', '/\|/',
 '/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/',
'/\?/', '/\{/', '/\}/', '/\,/');
        $replace = array('\/', '\^', '\.', '\$', '\|', '\(', '\)',
'\[', '\]', '\*', '\+', '\?', '\{', '\}', '\,');

        return preg_replace($patterns,$replace, $str);
}

function date_format_to_regex($date_format) {
	$regex_date = array(
		// time
		"g" => "([1-9]|1[0-2])", // 1 - 12
		"G" => "([1-9]|1[0-9]|2[0-3])", // 1 - 23
		"h" => "(0[1-9]|1[0-2])", // 01 - 12
		"H" => "(0[1-9]|1[0-9]|2[0-3])", // 01 - 23
		"i" => "(0[1-9]|[1-5][0-9])", // 01 - 59
		"s" => "(0[1-9]|[1-5][0-9])", // 01 - 59
		"O" => "(+[\d]{4})", // +0200
		// day
		"d" => "(0[1-9]|[12][0-9]|3[0-1])", // 01 - 31
		"j" => "([1-9]|[12][0-9]|3[0-1])", // 1 - 31
		// month
		"m" => "(0?[1-9]|1[0-2])", // 01 - 12
		"n" => "([1-9]|1[0-2])", // 1 - 12
		"M" => "(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)",
		"F" => "(january|feburary|march|april|may|june|july|august|september|october|november|december)",
		// year
		"Y" => "([12][0-9]{3})", // 2012
		"y" => "([0-9]{2})", // 12
		"e" => ""	// time zone
	);
	
	// built regex
	$date_format = str_split($date_format);
	for ($i = 0; $i < count($date_format); $i++) {
		if (array_key_exists($date_format[$i], $regex_date)) {
			$date_format[$i] = $regex_date[$date_format[$i]];
		} else {
			$date_format[$i] = escape_string_for_regex($date_format[$i]);
		}
	}
	$date_format = implode($date_format);
	return $date_format;
}

function getRegexRange($str)
{
    $a = is_array($str)?$str:str_split($str);
    //$punct = ''; // list of special chars
    $regex['punct'] = '';
    $regex['extra'] = '';
    //print_r($a);
    $size = 0;
    $b = array();
    foreach ($a as $v) {
        $size = max($size, sizeof($v));
        $b = array_merge(str_split($v),$b);
    }
    $b = array_unique($b);
    //$size = max($size, sizeof($c));
    //echo $c."\n";
    foreach ($b as $c) {
        if(preg_match('/[\d]/', $c)) $regex['digit'] = true;
        else if(preg_match('/[\w]/', $c)) $regex['word'] = true;
        else if(preg_match('/[\s]/', $c)) $regex['space'] = true;
        else if(preg_match('/[\]\[!"#\$%&\'\(\)\*\+\,\.\/:;<=>\?@\\^_`\{\|\}~-]/', $c)) $regex['punct'] .= $c;
        else $regex['extra'] = implode(array_unique(str_split($c.$regex['extra'])));
    }
    //print_r($regex);
    $regex_range = '[';
        $regex_range .= array_key_exists('word', $regex)?'\w':(
                                        array_key_exists('digit', $regex)?'\d':'');
        $regex_range .= array_key_exists('space', $regex)?'\s':'';
        $regex_range .= array_key_exists('punct', $regex)?escape_string_for_regex($regex['punct']):'';
        $regex_range .= array_key_exists('extra', $regex)?escape_string_for_regex($regex['extra']):'';
    $regex_range .= ']';
    $regex_range = (array_key_exists('word', $regex) && array_key_exists('space', $regex) && sizeof($regex['punct']))?'.':$regex_range;
    //echo "$regex_range*";
    return "$regex_range*";
}

function simplifyDiff($a)
{
    $size = count($a);
    for ($j=0;$j<$size;$j++) {
        if (is_array($a[$j])) { // 'i', 'd'
            $str = implode('', $a[$j]['d']).implode('', $a[$j]['i']);
            $out = '';
            if(preg_match('/[\d]/', $str)) $out .= implode('', array_unique(str_split($str)));
            else if(preg_match('/[\w]/', $str)) $out .= implode('', array_unique(str_split($str)));
            else if(preg_match('/[\s]/', $str)) $out .= ' ';
            else if (preg_match('/[\]\[!"#\$%&\'\(\)\*\+\,\.\/:;<=>\?@\\^_`\{\|\}~-]/', $str, $matches)) {
                foreach($matches as $m) $out .= $m;
            }
            //print_r($a[$j]);
            //echo " => ".$out." | ";
            $a[$j] = $out;

        }
    }
    return $a;
}

function simplifyDiff2($a)
{
    $size = count($a);
    for ($j=0;$j<$size;$j++) {
        if (is_array($a[$j])) { // 'i', 'd'
            $tmp = $a[$j];
            $new = array();
            if(array_key_exists('i', $tmp)) $new = array_merge($new,$a[$j]['i']);
            if(array_key_exists('d', $tmp)) $new = array_merge($new,$a[$j]['d']);

            $a[$j] = $new;

        }
    }
    return $a;
}

function regexDiff($list)
{
    $list = array_values($list);
    $diff[0] = array();
    $size = count($list)-1;
    for ($i=0;$i<$size;$i++) {
        $diff[0] = simplifyDiff($diff[0]);
        // simplify -> setup for multiple obj
        /*$sjze = count($diff[0]);
        for ($j=0;$j<$sjze;$j++) {
            if(is_array($diff[0][$j]))
                $diff[0][$j] = "a$i$j.";
            //echo $diff[$i][$j]." | ";
        }*/

        $diff[$i] = diff(str_split($list[$i]), str_split($list[$i+1]));
        //print_r(simplifyDiff2($diff[$i]));
        if ($i) {
            // simplify -> setup for multiple obj
            /*$sjze = count($diff[$i]);
            for ($j=0;$j<$sjze;$j++) {
                if(is_array($diff[$i][$j]))
                    $diff[$i][$j] = "a$i$j.";
                //echo $diff[$i][$j]." | ";
            }*/
            $diff[$i] = simplifyDiff($diff[$i]);
            $tmp = diff($diff[0], $diff[$i]);
            array_shift($tmp); // remove first blank
            array_pop($tmp); // remove end blank
            $diff = array();
            $diff[] = $tmp;
            //print_r($diff);
        }
    }
    $diff = $diff[0];
    //print_r($diff);
    //$diff = diff(str_split($old), str_split($new));
    //array_shift($diff); // remove first blank
    //array_pop($diff); // remove end blank
    $ret = '';
    foreach ($diff as $k) {
        if (is_array($k)) {
            //$ret .= "(.*)"; //
            //print_r($k);
            //echo "\n";
            $ret .= "(".getRegexRange(array_merge($k['d'],$k['i'])).")";
        } else $ret .= escape_string_for_regex($k);
    }
    // Make more flexable
    $patterns = array("/['\"]{1}/");
  $replace = array('[\'"]?');
    $ret = preg_replace($patterns, $replace, $ret);

    return "/$ret/i";
}

function regexDiff2($old, $new)
{
    $diff = diff(str_split($old), str_split($new));
    array_shift($diff); // remove first blank
    array_pop($diff); // remove end blank
    $ret = '';
    foreach ($diff as $k) {
        if(is_array($k))
            $ret .= "(".getRegexRange($k['d'].$k['i']).")";
        else $ret .= escape_string_for_regex($k);
    }

    return "/$ret/i";
}



// add tage to value ('/(?P<name>\w*) => $matches = array('name' => '');
?>