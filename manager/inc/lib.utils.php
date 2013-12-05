<?php
/* -*- tab-width: 4; indent-tabs-mode: nil; c-basic-offset: 4 -*- */
/*
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Plume CMS, a website management application.
# Copyright (C) 2001-2005 Loic d'Anterroches and contributors.
#
# Plume CMS is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Plume CMS is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# ***** END LICENSE BLOCK ***** */

/**
 * This file contains several utility classes. 
 * These classes have only static methods.
 */

/**
 * Date and time utilities.
 *
 * A timestamp has the format YYYYMMDDHHMMSS. This is a "MYSQL" timestamp.
 */
class date
{

    /** 
     * Create a timestamp with an offset with respect to time or today.
     *
     * @param  int Day offset (0)
     * @param  int Month offset (0)
     * @param  int Year offset (0)
     * @param  int Time (now)
     * @return int Timestamp
     */
    function stamp($day=0, $month=0, $year=0, $time='')
    {
        $time = (strlen($time)) ? $time : time();
        return date('YmdHis', mktime(date('H',$time), date('i',$time), 
                                     date('s',$time), date('m',$time) + $month,
                                     date('d',$time) + $day, 
                                     date('Y',$time) + $year));
    }

    /** 
     * Round a timestamp to the day, month or year.
     *
     * For example: 
     * 20031202123212 => 20030101000000, 20031201000000, 20031203000000
     *
     * @param  int Timestamp
     * @param  string Level of rouding 'd', ('m') or 'y'            
     * @return int Timestamp
     */
    function round($time, $f='m')
    {
        $n['y'] = 4;
        $n['m'] = 6;
        $n['d'] = 8;
        return str_pad(str_pad(substr($time, 0, $n[$f]),8,'01'),14,'0');
    }


    /**
     * Get a timestamp rounded to the current day.
     *
     * @param int Day offset (0)
     * @return int Timestamp
     */
    function day($offset=0)
    {
        return date::round(date::stamp($offset),'d');
    }

    /**
     * Get a timestamp rounded to the current month.
     *
     * @param int Month offset (0)
     * @return int Timestamp
     */
    function month($offset=0)
    {
        return date::round(date::stamp(0,$offset),'m'); 
    }

    /**
     * Get a timestamp rounded to the current year.
     *
     * @param int Year offset (0)
     * @return int Timestamp
     */
    function year($offset=0)
    {
        return date::round(date::stamp(0,0,$offset),'y');
    }

    /** 
     * Convert a timestamp to unix time. 
     * If the timestamp is invalid, return current time.
     * If no timestamp given, return current time. Always use this
     * method instead of time() to be able to set some time shift
     * if needed (Server US time, but "application" time at GMT)
     *
     * @param  int mysql timestamp or (false)
     * @return int unix time    
     */
    function unix($string=false)
    {
        if (false === $string)
            return time();

        $d = array();
        if (false === ($d = date::explode($string))) {
            return time();
        }
        return mktime($d[0], $d[1], $d[2], $d[3], $d[4], $d[5]);
    }

    /** 
     * Convert a string with the format
     * yyyymmddhhmmss or yyyy-mm-dd hh:mm:ss
     * into a list that can be passed to mktime
     *
     * @param string Timestamp or date
     * @return mixed Array, false if not a good timestamp format.
     */
    function explode($string)
    {
        $res = array();
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/',
                       $string, $match)) {
            $res[] = $match[4];
            $res[] = $match[5];
            $res[] = $match[6];
            $res[] = $match[2];
            $res[] = $match[3];
            $res[] = $match[1];
        } elseif (preg_match('/(\d+)-(\d+)-(\d+)\s+(\d+):(\d+):(\d+)/',
                             $string, $match)) {
            $res[] = $match[4];
            $res[] = $match[5];
            $res[] = $match[6];
            $res[] = $match[2];
            $res[] = $match[3];
            $res[] = $match[1];
        } else {
            $res = false;
        }        
        return $res;
    }

    /** 
     * Check the validity of a date, if not valid try to force it
     * to be valid. The date can be given as an array
     * from year to seconds, or as a single string. If a array
     * is given, an array is sent back else a MySQL timestamp.
     * The single string format must be able to be parsed by
     * the date::explode() method.
     *
     * @param  string Date time or array(h,m,s,M,D,Y)
     * @return string MySQL timestamp or array
     */
    function clean($inputdate)
    {
        $as_array = true;
        $bad_date = false;
        $dt_h = $dt_i = $dt_s = $dt_m = $dt_d = $dt_y = 0;

        if (!is_array($inputdate)) {
            $as_array = false;
            if (false !== ($list = date::explode($inputdate))) {
                list($dt_h, $dt_i, $dt_s, $dt_m, $dt_d, $dt_y) = $list;
            } else {
                $bad_date = true;
            }
        } else {
            list($dt_h, $dt_i, $dt_s, $dt_m, $dt_d, $dt_y) = $inputdate;
        }

        if (!$bad_date) {
            $dt_y = (string) sprintf('%04d',$dt_y);
            $dt_m = (string) sprintf('%02d',$dt_m);
            $dt_d = (string) sprintf('%02d',$dt_d);
            $dt_h = (string) sprintf('%02d',$dt_h);
            $dt_i = (string) sprintf('%02d',$dt_i);
            $dt_s = (string) sprintf('%02d',$dt_s);
            
            if ($dt_d > 31 || $dt_d < 1) { $dt_d = '01'; }
            if ($dt_h > 23 || $dt_h < 0) { $dt_h = '00'; }
            if ($dt_i > 59 || $dt_i < 0) { $dt_i = '00'; }
            if ($dt_s > 59 || $dt_s < 0) { $dt_s = '00'; }
           
            if (!checkdate($dt_m, $dt_d, $dt_y)) {
                // try to 'clean' the date
                $date = @date('YmdHis', @mktime($dt_h, $dt_i, $dt_s, $dt_m, $dt_d, $dt_y));
                if (14 != strlen($date)) {
                    $bad_date = true;
                }
            } else {
                $date = $dt_y.$dt_m.$dt_d.$dt_h.$dt_i.$dt_s;
            }
        }

        if ($bad_date) {
            $date = date::stamp();
        }

        if (!$as_array) {
            return $date;
        } else {
            return date::explode($date);
        }
    }


    /**
     * Return a date far in the future considered as
     * the End Of Time for the application.
     *
     * @return string '99991231235959'
     */
    function EOT()
    {
        return '99991231235959';
    }

    /**
     * Returns true if the string is the end of time
     *
     * @param string String date
     * @return bool True if at end of time
     */
    function isEOT($date)
    {
        return (date::EOT() == $date);
    }
    
} //End of date class.


/**
 * Utilities for domains, urls.
 */
class www
{
    /**
     * Get the current resource url.
     */
    function getRequestUri()
    {
        $s = '';
        if (isset($_SERVER['HTTPS'])) {
            $s = 's';
        }
        return 'http'.$s.'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }

    /** 
     * Get the relative url of the website as
     * automatically as possible.
     * This is used in the installer.
     *
     * @return string Relative URL without trailing slash
     */
    function getRelativeUrl() 
    {    
        if (preg_match('#(.*)/manager(.*)#i',$_SERVER["SCRIPT_NAME"],$match)) 
            return $match[1];
        if (preg_match('#(.*)/\w+\.php#i', $_SERVER["SCRIPT_NAME"], $match))
            return $match[1];
        return '';
    }

    /**
     * get the current full url for redirection (with / at the end)
     *
     * @return string Full URL with trailing /
     */
    function getCurrentFullUrl()
    {
        return www::getCurrentHostUrl().dirname($_SERVER['PHP_SELF']).'/';
    }

    /** 
     * get the current host url no / at the end
     *
     * @return string Host URL without trailing /
     */
    function getCurrentHostUrl()
    {
        $s = '';
        if (isset($_SERVER['HTTPS']))
            $s = 's';
        return 'http'.$s.'://'.$_SERVER['HTTP_HOST'];
    }


    /**
     * Get current managed website base url.
     *
     * The url will be a full url or an empty string depending of the
     * context.
     */
    function getManagedWebsiteUrl()
    {
        $c = config::f('context');
        $url = '';
        if ($c == 'manager' or $c == 'external') {
            $s = '';
            if (config::fbool('secure')) {
                $s = 's';
            }
            $url = 'http'.$s.'://'.config::f('domain');
        }
        return $url;

    }

    /**
     * Get the document root of a website.
     * If the configuration array of the website is not given, 
     * it will find the information in the global configuration.
     *
     * @return string Document root.
     */
    function getDocumentRoot()
    {
        // this does not work in case of complex aliases only for
        // the document folder.
        return substr(config::f('xmedia_root'), 0, 
                      -strlen(config::f('rel_url_files')));
    }

} //End of www class.


class Misc
{
    /**
     * Produces a random string.
     *
     * @param int Length of the random string to be generated.
     * @return string Random string
     */
    function getRandomString($len=35)
    {
        $string = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz'
            .'ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%&*()+=-_}{[]:><?/'; 
        $lchars = strlen($chars);
        $i = 0; 
        while ($i<$len) { 
            $string .= substr($chars, mt_rand(0, $lchars-1), 1);
            $i++;
        }
        return $string;
    }

    /**
     * Convert a string from latin1 to utf8
     *
     * @credit Olivier Meunier
     * @param string Latin 1 string
     * @return string utf-8 string
     */
	function latin1_utf8($str)
	{
		$conv = array(
                      chr(194).chr(128) => chr(226).chr(130).chr(172),
                      chr(194).chr(130) => chr(226).chr(128).chr(154),
                      chr(194).chr(131) => chr(198).chr(146),
                      chr(194).chr(132) => chr(226).chr(128).chr(158),
                      chr(194).chr(133) => chr(226).chr(128).chr(166),
                      chr(194).chr(134) => chr(226).chr(128).chr(160),
                      chr(194).chr(135) => chr(226).chr(128).chr(161),
                      chr(194).chr(136) => chr(203).chr(134),
                      chr(194).chr(137) => chr(226).chr(128).chr(176),
                      chr(194).chr(138) => chr(197).chr(160),
                      chr(194).chr(139) => chr(226).chr(128).chr(185),
                      chr(194).chr(140) => chr(197).chr(146),
                      chr(194).chr(145) => chr(226).chr(128).chr(152),
                      chr(194).chr(146) => chr(226).chr(128).chr(153),
                      chr(194).chr(147) => chr(226).chr(128).chr(156),
                      chr(194).chr(148) => chr(226).chr(128).chr(157),
                      chr(194).chr(149) => chr(226).chr(128).chr(162),
                      chr(194).chr(150) => chr(226).chr(128).chr(147),
                      chr(194).chr(151) => chr(226).chr(128).chr(148),
                      chr(194).chr(152) => chr(203).chr(156),
                      chr(194).chr(153) => chr(226).chr(132).chr(162),
                      chr(194).chr(154) => chr(197).chr(161),
                      chr(194).chr(155) => chr(226).chr(128).chr(186),
                      chr(194).chr(156) => chr(197).chr(147),
                      chr(194).chr(159) => chr(197).chr(184)
                      );
		$str = utf8_encode($str);
		return str_replace(array_keys($conv), array_values($conv), $str);
	}

}

/** 
 * Inform if the category is an hidden category or not.
 *
 * @param string Category path
 * @return bool Success
 */
function isGhostCat($path)
{
    return preg_match('#/_#', $path);
}


function strlenCompare($a, $b) 
{
    $a = textRemoveEntities($a);
    $b = textRemoveEntities($b);
    if (strlen($a) == strlen($b)) {
        return 0;
    }
    return (strlen($a) > strlen($b)) ? -1 : 1;
}

/** 
 * Give is a file is safe or not
 *
 * @param string file name
 * @param string Path to the data ('')
 * @return bool Success
 */
function isFileSafe($file_name, $file_data ='')
{
    if (preg_match('/[^A-Za-z0-9\-\_\.]/', $file_name)) return false;
    if (!preg_match('/\.(png|jpg|jpeg|gif|bmp|psd|tif|aiff|asf|avi|bz2|css|doc|eps|gz|htm|mid|mov|mp3|mpg|ogg|pdf|ppt|ps|qt|ra|ram|rm|rtf|sdd|sdw|sit|sxi|sxw|swf|tgz|txt|wav|xls|xml|wmv|zip)$/i', $file_name)) return false;
    return true;
}

function prettySize($size)
{
    $mb = 1024*1024;
    if ( $size > $mb ) {
        $mysize = sprintf('%01.2f', $size/$mb) . ' ' .  __('MB');
    } elseif ($size >= 1024) {
        $mysize = sprintf('%01.2f', $size/1024) . ' ' . __('KB');
    } else {
        $mysize = sprintf('%01.2f', $size/1024) . ' ' . __('bytes');
    }
    return $mysize;
}

function isImage($file_name)
{
    if (preg_match('/\.(png|jpg|gif|jpeg)$/i',$file_name)) return true;
    return false;
}

function getFileExtension($file_name)
{
    if (preg_match('/\.([A-Za-z0-9]{2,4})$/i',$file_name, $match)) return $match[1];
    return 'default';
}

function removeFileExtension($file_name)
{
    return preg_replace('/(\.[A-Za-z0-9]{2,4})$/i', '', $file_name);
}

function getParentDir($current_dir)
{
    if (preg_match('#(.*/)*([^/])+/$#i',$current_dir, $match)) return $match[1];
    return '';
}


function cleanDirname($dir)
{
    return str_replace('\\','/',$dir);
}

/**
 utf 8 encode a string if the global encoding is utf-8
*/
function if_utf8($string)
{
    if (strtolower($GLOBALS['_PX_config']['encoding']) == 'utf-8') return utf8_encode($string);
    return $string;
}

function showDebugInfo()
{
    if (config::f('debug') == false) {
        return;
    }
    echo '<!-- '."\n";
    echo 'Loaded locale files';
    if (!empty($GLOBALS['_PX_locale_files'])) {
        echo ' [original encoding]:'."\n";
        foreach($GLOBALS['_PX_locale_files'] as $file => $encoding) {
            echo $file .' ['.$encoding."]\n";
        }
    } else {
        echo ': None loaded.'."\n";
    }
    echo "\n";
    echo 'Untranslated strings:';
    if (!empty($GLOBALS['_PX_debug_data']['untranslated'])) {
        echo "\n";
        foreach($GLOBALS['_PX_debug_data']['untranslated'] as $string) {
            echo '    \''.$string."'\n";
        }
    } else {
        echo ' None.'."\n";
    }
    echo "\n";
    echo sprintf('%d SQL queries:', count($GLOBALS['_PX_debug_data']['sql_queries']));
    if (!empty($GLOBALS['_PX_debug_data']['sql_queries'])) {
        echo "\n";
        foreach($GLOBALS['_PX_debug_data']['sql_queries'] as $string) {
            echo '    '.str_replace("\n", ' ', $string)."\n";
        }
        echo 'Queries starting with * are direct mysql function calls.'."\n";
    } else {
        echo ' None.'."\n";
    }
    echo '-->';
}


/* break magic_quotes */
function magicStrip(&$k, $key)
{
    if(get_magic_quotes_gpc()) {
        $k = handleMagicQuotes($k);
    }
}

function handleMagicQuotes(&$value)
{
    if (get_magic_quotes_gpc()) {
        if (is_array($value)) {
            $result = array();
            foreach ($value as $k => $v) {
                if (is_array($v)) {
                    $result[$k] = handleMagicQuotes($v);
                } else {
                    $result[$k] = stripslashes($v);
                }
            }
            return $result;
        } else {
            return stripslashes($value);
        }
    }
    return $value;
}
?>
