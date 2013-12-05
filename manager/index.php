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

require_once 'path.php';
require_once $_PX_config['manager_path'].'/prepend.php';
auth::checkAuth(PX_AUTH_NORMAL);

$m = new Manager();
$_px_theme = $m->user->getTheme();

/* ========================================================== *
 *        Send the user to the requested public website       *
 * ========================================================== */
if (!empty($_REQUEST['goto'])) {
    $site = $_REQUEST['switchid'];
    if (isset($m->user->wdata[$site]['website_url'])) {
        $location = $m->user->wdata[$site]['website_url'] .'/';     
        header('Location: '.$location);
        exit;
    }        
}

/* ========================================================== *
 *  Switch the user to the requested website in the manager   *
 * ========================================================== */
if (!empty($_REQUEST['switchid'])) {
    $_SESSION['website_id'] = $_REQUEST['switchid'];
    $location = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    if (false !== ($pos = strpos($location, '?'))) {
        $location = substr($location, 0, $pos);
    }
    header('Location: '.$location);
    exit;
}


/* ========================================================== *
 *                      Add sub menu items                    *
 * ========================================================== */
$px_submenu->addItem(__('News'), 'news.php', 
		     'themes/'.$_px_theme.'/images/ico_news.png', false);
$px_submenu->addItem(__('Articles'), 'articles.php', 
		     'themes/'.$_px_theme.'/images/ico_article.png', false);
$px_submenu->addItem(__('Files or images'), 'xmedia.php', 
		     'themes/'.$_px_theme.'/images/ico_image.png', false);
$_site_url =
$m->user->wdata[$GLOBALS['_PX_website_config']['website_id']]['website_url'].'/';
$px_submenu->addItem(__('See the site'), $_site_url, 
             'themes/'.$_px_theme.'/images/ico_site.png', false);

/* ================================================= *
 *            list resources retrieval               *
 * ================================================= */

//Get the category id and save it
$cat_id = (!empty($_GET['cat_id'])) ? $_GET['cat_id'] : $m->user->getPref('list_index_cat_id');
$m->user->savePref('list_index_cat_id', $cat_id, $_SESSION['website_id'], true);
if ($cat_id == 'allcat') $cat_id = '';

//Get the search query
$px_q = (!empty($_GET['q'])) ? $_GET['q'] : '';

//Get available months and selected
list($first, $last, $arry_months) = $m->getArrayMonths(''/* All the types */, $cat_id);
$px_m_s = $m->user->getPref('list_index_month');
$px_m = (!empty($_GET['m'])) ? $_GET['m'] : ((!empty($px_m_s)) ? $px_m_s : $last);
$m->user->savePref('list_index_month', $px_m, $_SESSION['website_id'], true);

if ($px_m == 'alldate') {
    $px_m = $first;
    $px_end = date::stamp(0, 1 /*1 month after now */, 0);
} else {
    $px_end = date::stamp(0, 1 /*1 month after $px_m */, 0, date::unix($px_m));
}

if (empty($px_q)) {
	$res = $m->getResources(''/* All users */, '' /* All status */, $cat_id, ''/*All types*/, $px_m /*Date start */, $px_end /*Date end */);
	//get again as possibly modified because of the 'alldate' case
	$px_m = $m->user->getPref('list_index_month');
} else {
	$res = $m->searchResources($px_q, false /*Not only the online resources */, '' /*All the types */);
	//Search is made on all the date and all the categories
	$px_m = 'alldate';
	$cat_id = 'allcat';
}

/* ================================================= *
 *                title of the page                  *
 * ================================================= */

$px_title =  __('Home'); // used in _top.php
include dirname(__FILE__).'/mtemplates/_top.php';

echo '<h1 id="title_index">'.__('Resource list').'</h1>'."\n\n";

/* ================================================= *
 *           Form to select some resources           *
 * ================================================= */
echo '<form action="index.php" method="get"><p id="resource-select">';
echo '<label for="m" style="display:inline;"><strong>'. __('Month:').' </strong></label>';
echo form::comboBox('m', $arry_months, $px_m);
echo ' <label for="cat_id" style="display:inline;"><strong>'. __('Category:').' </strong></label>';
echo form::comboBox('cat_id', $m->getArrayCategories(true), $cat_id);
echo ' <input type="hidden" name="op" id="op" value="list" /><input class="submit" type="submit" value="'. __('ok').'" />';
echo '</p></form>';


/* ================================================= *
 *                  list resources                   *
 * ================================================= */

if ($res->isEmpty()) {
    echo '<p id="noresource">'.__('No resource.').'</p>'."\n\n";
} else {
    echo '<script type="text/javascript">'."\n<!--\n".
        "var js_post_ids = new Array('".implode("','",$res->getIDs('resource_id', 'content'))."');\n".
        "//-->\n</script>\n";
    
    echo '<p id="showhide"><a href="#" onclick="mOpenClose(js_post_ids,1); return false;">'. __('Show all').'</a>'.
        ' - <a href="#" onclick="mOpenClose(js_post_ids,-1); return false;">'. __('Hide all').'</a></p>';
    
        
    while (!$res->EOF()) {
        //edition links
        if ($m->asRightToEdit($res)) {
            $editlinks = ' [<span class="editlink"><a href="'.$res->f('type_id').'.php?resource_id='.$res->f('resource_id').'">'.__('edit').'</a></span>]'; 
        } else {
            $editlinks = ' [<span class="visualize"><a href="'.$res->f('type_id').'.php?resource_id='.$res->f('resource_id').'">'.__('visualize').'</a></span>]';        
        }
        switch ($res->f('status')) {
        case PX_RESOURCE_STATUS_VALIDE:
            $res_class = 'published';
            $res_img = '<img src="themes/'.$_px_theme.'/images/check_on.png" alt="'. __('Resource on-line').'" class="status" />';
            break;
        case PX_RESOURCE_STATUS_INEDITION:
            $res_class = 'published';
            $res_img = '<img src="themes/'.$_px_theme.'/images/check_edit.png" alt="'. __('Resource in edition').'" class="status" />';
            break;
        case PX_RESOURCE_STATUS_OFFLINE:
            $res_class = 'cancel';
            $res_img = '<img src="themes/'.$_px_theme.'/images/check_off.png" alt="'. __('Resource off-line').'" class="status" />';
            break;
        case PX_RESOURCE_STATUS_TOBEVALIDATED:
        default:
            $res_class = 'published';
            $res_img = '<img src="themes/'.$_px_theme.'/images/check_wait.png" alt="'. __('Resource waiting for validation').'" class="status" />';
            break;
        }
        echo '<div class="resourcebox '.$res_class.'" id="p'.$res->f('resource_id').'">'.
            '<a href="#" onclick="openClose(\'content'.$res->f('resource_id').'\',0); return false;" title="'.__('Show/hide').'">'.
            '<img src="themes/'.$_px_theme.'/images/plus.png" class="show_button" id="img_content'.$res->f('resource_id').'" '.
            'alt="'. __('show/hide').'" /></a>';
        echo $res_img;
        //adding style for articles or news
        if (PX_RESOURCE_MANAGER_ARTICLE == $res->f('type_id')) {
            $span_class = 'art_style';
            $seeonweblink = ' [<span class="link_style"><a href="'.$res->getPath().'">'.__('See the article').'</a></span>]';
        } else {
            $span_class = 'news_style';
            $seeonweblink = ' [<span class="link_style"><a href="'.$res->getPath().'">'.__('See the news').'</a></span>]';
        }
        echo '<p class="resource_title"><span class="'.$span_class.'">'.$res->f('title').'</span> - '. __('by');
	
        $temp = '';
        while (!$res->extEOF('authors')) {
            //Testing the level of the user to define if the edition
            //link could be added
            if ((auth::asLevel(PX_AUTH_ROOT) 
                 && $res->extf('authors','user_id') == 1) 
                || (auth::asLevel(PX_AUTH_ADMIN) 
                    && $res->extf('authors','user_id') != 1)) {
                $edituser = ' <span class="author_style"><a href="users.php?user_id='.$res->extf('authors','user_id').'" title="'.__('Edit author profile').'">'.$res->extf('authors','user_realname').'</a></span>';
            } else {
                $edituser = ' <span class="author_style">'.$res->extf('authors','user_realname').'</span>';
            }
            //end testing level user
            $temp .= $edituser;
            $res->extMoveNext('authors');
        }
        echo $temp;
        echo ' - '. __('in');
        $temp = array();
        $base = '<span class="category_style">%s%s%s</span>';
        while (!$res->extEOF('cats')) {
            $_def = array('', $res->extf('cats','category_name'), '');
            if (auth::asLevel(PX_AUTH_ADVANCED)) {
                $_def[0] = '<a href="index.php?cat_id='.$res->extf('cats','category_id').'" title="'.__('Resource list').'">';
                $_def[2] = '</a>';
            }
            $temp[] = vsprintf($base, $_def);
            $res->extMoveNext('cats');
        }
        echo ' '.implode(', ', $temp)."<br />\n";
        echo '<span class="date-time">'.date(__('Y/m/d at H:i:s'), date::unix($res->f('modifdate')));
        echo "</span>".$editlinks.$seeonweblink."</p>\n\n";
        echo '<div id="content'.$res->f('resource_id').'" class="hided" style="display:none;">';
        echo "<div class=\"description_style\">\n".text::parseContent($res->f('description'))."</div>\n";
        echo "\n<p class='idmakelink'>".__('Id to make a link:').' '.$res->f('identifier')."</p>\n<hr class='invisible' /></div></div>\n\n";
        
        $res->moveNext();
    }
}

/* ================================================= *
 *          Form to search in the resources          *
 * ================================================= */
echo '<form action="index.php" method="get"><p id="search">';
echo '<label for="q" style="display:inline;"><strong>'. __('Search:').' </strong></label>';
echo form::textField('q', 30, 255, $px_q, '', 'accesskey="4"'); 
echo ' <input class="submit" type="submit" value="'. __('ok').'" />';
echo '</p></form>'."\n\n";

include dirname(__FILE__).'/mtemplates/_bottom.php';

?>