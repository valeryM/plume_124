<?php
/* -*- tab-width: 4; indent-tabs-mode: nil; c-basic-offset: 4 -*- */
/*
# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Original Code is Plume CMS.
#
# The Initial Developer of the Original Code is Loic d'Anterroches
# Portions created by the Initial Developer are Copyright (C) 2003
# the Initial Developer. All Rights Reserved.
#
# Credits:
#   Olivier Meunier
#
# Contributor(s):
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK ***** */

class form
{

    /**
     * Build a combobox.
     *
     * @param string Id and name of the form
     * @param array Data, the keys of the array are the displayed strings
     * @param string Selected value ('')
     * @param string Default selected value ('')
     * @param int Tabulation index ('')
     * @param string CSS class of the combobox field ('')
     * @param string Extra data for javascript scripting ('')
     * @return string HTML string of the form
     */
    function combobox($name, $arryData, $selected='', $default='', 
                      $tabindex='', $class='', $html='', $id='')
    {
        if ($id == '') $id = $name;
        $res = '<select name="'.$name.'" ';
	
        if($class != '')
            $res .= 'class="'.$class.'" ';
	
        $res .= 'id="'.$id.'" ';
                
        $res .= ($tabindex != '') ? 'tabindex="'.$tabindex.'" ' : '';
        if($html != '')
            $res .= $html.' ';
        
        $res .= '>'."\n";
	
        if ($selected == '') 
            $selected = $default;

        foreach($arryData as $k => $v) {
            $res .= '<option value="'.$v.'"';
            if($v == $selected)
                $res .= ' selected="selected"';
        
            $res .= '>'.$k.'</option>'."\n";
        }
	
        $res .= '</select>'."\n";
        return $res;
    }

    /**
     * Build a single line text field.
     *
     * @param string Name and id of the field
     * @param int Size Size of the field
     * @param int Maximum size of the field (0)
     * @param string Default value (is htmlspecialchars escaped) ('')
     * @param int Tabulation index ('')
     * @param string Extra information for javascript scripting ('')
     * @return string HTML string of the form
     */
    function textField($name, $size, $max=0, $default='', $tabindex='', $html='', $id='')
    {
        if ($id == '') $id = $name;
        $res = '<input type="text" size="'.$size
            .'" name="'.$name.'" id="'.$id.'" ';
        $res .= ($max != 0) ? 'maxlength="'.$max.'" ' : '';
        $res .= ($tabindex != '') ? 'tabindex="'.$tabindex.'" ' : '';
        $res .= ($default != '') ? 
            'value="'.htmlspecialchars($default).'" ' : '';
        $res .= $html;
		$res .= ' />';
		return $res;
    }

    /**
     * Build a single line password text field.
     *
     * @param string Name and id of the field
     * @param int Size Size of the field
     * @param int Maximum size of the field (0)
     * @param string Default value (is htmlspecialchars escaped) ('')
     * @param int Tabulation index ('')
     * @param string Extra information for javascript scripting ('')
     * @return string HTML string of the form
     */
    function passwordField($id, $size, $max=0, $default='', 
                           $tabindex='', $html='')
    {
        $res = '<input type="password" size="'.$size
            .'" name="'.$id.'" id="'.$id.'" ';
        $res .= ($max != 0) ? 'maxlength="'.$max.'" ' : '';
        $res .= ($tabindex != '') ? 'tabindex="'.$tabindex.'" ' : '';
        $res .= ($default != '') ? 
            'value="'.htmlspecialchars($default).'" ' : '';
        $res .= $html;
		$res .= ' />';
		return $res;
    }


    /**
     * Build a date/time set of fields.
     * The array of the date must be in the same order as the
     * arguments of the mktime() function. ie. (h,m,s,M,D,Y)
     * 
     * @param string Id and name of the form
     * @param array Date to set the form ('') default to now
     * @param int Tabulation index ('')
     * @param string Extra data for javascript scripting ('')
     * @return string HTML string of the form
     */
    function datetime($id, $date='', $tabindex='', $html='')
    {
        $id = trim($id);
        //Need first to get the correct date
        if (!is_array($date)) {
            $date = date::explode(date::stamp());
        }
        $months = array();
        for ($i=1; $i<=12; $i++) {
            $month = sprintf('%02d', $i);
            $months[if_utf8(strftime('%B', strtotime('2000-'.$month.'-01')))] = $month;
        }

        return form::textField($id.'_d', 2, 2, $date[4], $tabindex).' '.
            form::combobox($id.'_m', $months, $date[3], $tabindex).' '.
            form::textField($id.'_y', 4, 4, $date[5], $tabindex).' '.
            __('Time:').' '.
            form::textField($id.'_h', 2, 2, $date[0], $tabindex).':'.
            form::textField($id.'_i', 2, 2, $date[1], $tabindex).':'.
            form::textField($id.'_s', 2, 2, $date[2], $tabindex);
    }

    /**
     * Build a textarea field.
     *
     * @param string Name and id 
     * @param int Number of columns
     * @param int Number of rows 
     * @param string Default value (is htmlspecialchars escaped) ('')
     * @param int Tabulation index ('')
     * @param string Extra information for javascript scripting ('')
     * @return string HTML string of the form element
     */
    function textArea($name, $cols, $rows, $default='', $tabindex='', $html='',
                      $id='')
    {
        if ($id == '') $id = $name;
        $res = '<textarea cols="'.$cols.'" rows="'.$rows.'" ';
        $res .= 'name="'.$name.'" id="'.$id.'" ';
        $res .= ($tabindex != '') ? 'tabindex="'.$tabindex.'" ' : '';
        $res .= $html.'>';
        $res .= htmlspecialchars($default);
        $res .= '</textarea>';

        return $res;
    }

    /**
     * Build a button.
     *
     * @param string Type ('submit')
     * @param string Id ('')
     * @param string Value ('ok')
     * @param int Tabulation index ('')
     * @param string Access key ('')
     * @param string Class ('')
     * @return string HTML of the button
     */
    function button($type='submit', $name='', $value='ok', 
                    $tabindex='', $accesskey='', $class='', $id='')
    {
        if ($id == '') $id = $name;
        $res = '<input type="'.$type.'" value="'.$value.'" ';
        $res .= ($name != '') ? 'name="'.$name.'" id="'.$id.'" ' : '';
        $res .= ($tabindex != '') ? 'tabindex="'.$tabindex.'" ' : '';
        $res .= ($accesskey != '') ? 'accesskey="'.$accesskey.'" ' : '';
        $res .= ($class != '') ? 'class="'.$class.'" ' : '';
        $res .= '/>';
        
        return $res;
    }

    /** 
     * Build a hidden field.
     *
     * @param string Id
     * @param string Value
     * @param boolean With_Id
     * @return string HTML of the field
     */
    function hidden($id, $value, $with_id=true)
    {
    	if ($with_id) {
			$res = '<input type="hidden" name="'.$id.'" id="'
            .$id.'" value="'.$value.'" />';
		} else {
			$res = '<input type="hidden" name="'.$id.'" value="'.$value.'" />';
		}
	
		return $res;
    }

    /**
     * Build a checkbox field.
     *
     * @param string Id of the field
     * @param string Value 
     * @param bool Is the checkbox checked (false)
     * @param int Tabulation index ('')
     * @param string Extra information for javascript scripting ('')
     * @return string HTML of the field
     */
    function checkbox($id, $value, $checked=false, $tabindex='', $html='')
    {
        $res = '<input type="checkbox" value="'.$value.'" ';
        $res .= 'name="'.$id.'" id="'.$id.'" ';
        $res .= ($tabindex != '') ? 'tabindex="'.$tabindex.'" ' : '';
        $res .= ($checked) ? 'checked="checked" ' : '';
        $res .= $html.' />';
	
        return $res;
    }
    
    /**
     * Build a radio button field.
     *
     * @param string Name of the field
     * @param string Value 
     * @param bool Is the checkbox checked (false)
     * @param string Class name
     * @param string Id of the field
     * @return string HTML of the field
     */
    function radio($name, $value, $checked='', $class='', $id='')
	{
		$res = '<input type="radio" name="'.$name.'" value="'.$value.'" ';
		
		if($class != '') {
			$res .= 'class="'.$class.'" ';
		}
		
		if($id != '') {
			$res .= 'id="'.$id.'" ';
		}
		
		if (($checked === 0) or $checked >= 1) {
			$res .= 'checked="checked" ';
		}
		
		$res .= '/>'."\n";
		
		return $res;	
	}

    /* ================================================================== *
     *           Methods to get data from forms                           *
     * ================================================================== */

    /**
     * Get REQUEST field.
     * A request field is field that can be set through POST, GET or COOKIE
     * method.
     *
     * @param string REQUEST field to get
     * @return string Empty string if field not set.
     */
    function getField($field)
    {
        if (!empty($_REQUEST[$field])) {
            return $_REQUEST[$field];
        }
        return '';
    }

    
    /**
     * Get POST field.
     * Returns an empty string if the value is not set.
     *
     * @param string POST field to get
     * @return string Empty string if field not sent
     */
    function getPostField($field)
    {
        if (!empty($_POST[$field])) {
            return $_POST[$field];
        }
        return '';
    }

    /**
     * Get a "time" field from a POST method.
     * Returns a timestamp.
     *
     * @param string Time field to get
     * @return timestamp Time
     */
    function getTimeField($field)
    {
        $field = trim($field);
        $dt_y = (string) sprintf('%04d', form::getPostField($field.'_y'));
        $dt_m = (string) sprintf('%02d', form::getPostField($field.'_m'));
        $dt_d = (string) sprintf('%02d', form::getPostField($field.'_d'));
        $dt_h = (string) sprintf('%02d', form::getPostField($field.'_h'));
        $dt_i = (string) sprintf('%02d', form::getPostField($field.'_i'));
        $dt_s = (string) sprintf('%02d', form::getPostField($field.'_s'));
            
        //Small "fixes"
        if ($dt_d > 31 || $dt_d < 1) { $dt_d = '01'; }
        if ($dt_h > 23 || $dt_h < 0) { $dt_h = '00'; }
        if ($dt_i > 59 || $dt_i < 0) { $dt_i = '00'; }
        if ($dt_s > 59 || $dt_s < 0) { $dt_s = '00'; }

        return $dt_y.$dt_m.$dt_d.$dt_h.$dt_i.$dt_s;
    }
}


class Validate
{
    /**
     * Check if an email has the right format.
     *
     * @param string Email
     * @return bool Success
     */
    function checkEmail($email)
    {
        if (!eregi('^[_a-z0-9\-]+(\.[_a-z0-9\-\+]+)*@[a-z0-9\-]+(\.[a-z0-9\-]+)*(\.[a-z]{2,4})$', $email)) {
            return false;
        }
        return true;
    }

}
?>
