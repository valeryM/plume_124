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
auth::checkAuth(PX_AUTH_ADVANCED);

$m = new Manager();
$_px_theme = $m->user->getTheme();


if (!empty($_REQUEST['category_id']) || !empty($_REQUEST['op'])) {
    $display_list_cat = true;
} else {
    $display_list_cat = false;
}

/* ================================================= *
 *       Generate sub-menu                           *
 * ================================================= */

$px_submenu->addItem(__('Categories'), 'categories.php',
                     'themes/'.$_px_theme.'/images/ico_cat.png', 
                     false, $display_list_cat);
$px_submenu->addItem(__('New category'), 'categories.php?op=add', 
                     'themes/'.$_px_theme.'/images/ico_new.png', false);

/* =============================================================== *
 *                  Process of the data                            *
 * =============================================================== */
/* ================================================= *
 *   Set default values / Load requested category    *
 * ================================================= */
$is_editable = true;
$cat = new Category();
if (!empty($_REQUEST['category_id']) || !empty($_REQUEST['op'])) {
    $cat->setDefaults($m->user);

    if (!empty($_REQUEST['category_id'])) {
        if (false === $m->loadCategory($cat, $_REQUEST['category_id'])) {
            $m->setError(__('Error: The requested category is not available.'),
                         400);
            $is_editable = false;
        }
    }
}

/* Additional submenu item */
$px_submenu->addItem(__('See the category'), $cat->getPath(),
                         'themes/'.$_px_theme.'/images/ico_cat_site.png', 
                         false, ($cat->f('category_id') > 0));

    /* ========================== *
     *  Get values from the form  *
     * ========================== */
if ((!empty($_POST['preview']) || !empty($_POST['publish']) 
     || !empty($_POST['transform']) || !empty($_POST['increase']) 
     || !empty($_POST['increase_x']) || !empty($_POST['decrease']) 
     || !empty($_POST['decrease_x']) || !empty($_POST['delete']))
	&& $is_editable) {
    $cat->set(form::getPostField('c_parentid'),
              form::getPostField('c_name'),
              form::getPostField('c_description'),
              form::getPostField('c_format'),
              form::getPostField('c_keywords'),
              form::getPostField('c_path'),
              form::getPostField('c_template'),
              3600);

    if (!empty($_POST['transform'])) {
        $cat->setField('category_description', 
                       '=html'."\n"
                       .$cat->getFormattedContent('category_description',
                                                  'html'));
    }

	if (!empty($_POST['increase']) || !empty($_POST['increase_x'])) {
		$m->user->increase('category_textarea');
	}
	if (!empty($_POST['decrease']) || !empty($_POST['decrease_x'])) {
		$m->user->decrease('category_textarea');
	}

	if (!empty($_POST['publish'])) {
		if (false !== $m->saveCategory($cat)) {
			$m->setMessage(__('Category successfully saved.'));
			header('Location: categories.php');
			exit;
		}
	} else if (!empty($_POST['delete'])) {
        if ($m->delCategory($cat) !== false) {
            $m->setMessage(__('The category was successfully deleted.'));
            header('Location: categories.php');
            exit;
        }
    } else if (!empty($_POST['preview'])) {
        $m->check($cat); // Provide the error feedback
    }


}

if ($is_editable) {
	$cats = $m->getCategories();
	$arry_cat = array();
	$arry_cat_js = array();
	while (!$cats->EOF()) {
		$name =  $cats->f('category_name');
		$name .= ' ('.$cats->f('category_path').')';
		if (isGhostCat($cats->f('category_path'))) $name .= ' ['. __('Hidden category').']';
        if ($cats->f('category_id') != $cat->f('category_id')) {
            $arry_cat[$name] = $cats->f('category_id');
        }
		$cats->moveNext();
	}
	$arry_templates = $m->getTemplates('category');
}	

/* =================================================
 *      List the categories
 * ================================================= */
if (empty($_REQUEST['category_id']) && empty($_REQUEST['op'])) {
    $cats = $m->getCategories();
}


/*===============================================================
 Display of the data
===============================================================*/
/*=================================================
 title of the page
=================================================*/

$px_title = __('Categories'); // used in _top.php
include dirname(__FILE__).'/mtemplates/_top.php';

echo '<h1 id="title_categories">'.__('Categories').'</h1>'."\n\n";

/*=================================================
 add/edit a category
=================================================*/
if ($is_editable 
    && (!empty($_REQUEST['op']) || !empty($_REQUEST['category_id']))
    ) {
    /*=================================================
     * Preview of the content if some content is available
     *=================================================*/
    if (strlen($cat->getUnformattedContent('category_description'))) {
        echo '<div class="preview">';			
        echo '<h3>'.$cat->getTextContent('category_name').'</h3>';
        echo $cat->getFormattedContent('category_description', 'html');

        echo "<hr class='invisible' id=\"zoubida\"/></div>\n\n";
    }

    echo '<script type="text/javascript">'."\n".
        "<!--\n".
        "var js_pathlist = new Array();\n";
    $cats->moveStart();
    while (!$cats->EOF()) {	
        echo "js_pathlist[".$cats->f('category_id')."] = '".$cats->f('category_path')."';\n";
        $cats->moveNext();
    }	
    echo "//-->\n". "</script>\n";

    echo '<form action="categories.php" method="post" id="formPost" '
        .'name="formPost">'."\n";
    echo "<p class='selections'>\n";
    if (($cat->f('category_path') != '/' && strlen($cat->f('category_id')))
        || '' == $cat->f('category_id')
        ) {
        echo '<span class="nowrap"><label for="c_parentid" '
            .'style="display:inline">'.__('Parent category:').'</label> ';
        echo form::combobox('c_parentid', $arry_cat, 
                            $cat->f('category_parentid'), '', '', '',
                            'onchange="catChangePath(js_pathlist);"'); 
        echo '</span>'."\n";
    } else {
        echo form::hidden('c_parentid', $cat->f('category_parentid'));
    }
  
    echo '<span class="nowrap"><label for="c_format" '
        .'style="display:inline">'.__('Format:').'</label>'."\n";
    echo form::combobox('c_format', array('HTML'=>'html','Wiki'=>'wiki'),
                        $cat->getContentFormat('category_description'),
                        $m->user->getPref('content_format'));
    echo '</span>'."\n";
    echo '<span class="nowrap"> ';
    echo '<label for="c_template" style="display:inline">'
        .__('Template:').'</label> ';
    echo form::combobox('c_template', $arry_templates, 
                        $cat->f('category_template'));
    echo '</span>'."\n";
    echo '</p>'."\n";

    echo '<p><label for="c_name"><strong>'.__('Title:').'</strong></label> ';
    echo form::textField('c_name', 30, 255, $cat->f('category_name'), '', 
                         'style="width:100%" onkeyup="setUrl(\'c_name\', \'c_path\', \'cat\', js_pathlist)"'); 
    echo "</p>\n<p>\n";
	echo '<span  id="insert-img" class="right-block"><img src="themes/'.$_px_theme
        .'/images/ico_image.png" alt="" /> ';
    echo '<strong><a href="xmedia.php" '
        .'onclick="popup(this.href+\'?mode=popup\'); return false;">'
        .__('Insert an image or a file').'</a></strong></span>'."\n";
    echo '<label for="c_description"><strong>'.__('Description:')
        .'</strong></label>'."\n";
    echo form::textArea('c_description', 60, 
                        $m->user->getPref('category_textarea'),
                        $cat->getUnformattedContent('category_description'), 
                        '', 'style="width:100%"');
    echo "\n".'<span id="size-control" class="size-control"> '
        .'<input type="image" title="'.__('shrink textarea')
        .'" name="decrease" value="-" src="themes/'.$_px_theme
        .'/images/ico_shrink.png" accesskey="-" class="size-control" /> '
        .'<input type="image" title="'.__('grow textarea')
        .'" name="increase" value="+" src="themes/'.$_px_theme
        .'/images/ico_grow.png" accesskey="+" class="size-control" /> ';
    echo '</span>'."\n";
    echo "</p>\n";
    echo '<p><label for="c_keywords">'.__('Keywords:').'</label> ';
    echo form::textArea('c_keywords', 60, 2, $cat->f('category_keywords'),
                        '', 'style="width:100%"');
    echo "</p>\n";
    echo '<p><label for="c_path"><strong>'
        .__('Path <span class="small">(/category/ or /cat/subcat/)</span>:')
        .'</strong></label> ';
	if ($cat->f('category_path') == '/' 
        && strlen($cat->f('category_id'))) {
        echo '<strong>/</strong>';
    } else {
        echo form::textField('c_path', 30, 255, $cat->f('category_path'),
                             '', 'style="width:100%"');
    }
    echo "</p>\n";
    
  
    echo '<p class="button"><input name="preview" type="submit" class="submit" '
        .'value="'.__('Visualize [v]').'" accesskey="'.__('v').'" />&nbsp; '
        .'<input name="publish" type="submit" class="submit" '
        .'value="'.__('Save [s]').'" accesskey="'.__('s').'" /> ';

    if (strlen($cat->f('category_id')) 
        && $cat->getContentFormat('category_description') == 'wiki') {
        echo '&nbsp;<input name="transform" type="submit" class="submit" '
            .'value="'. __('Transform in XHTML [x]').'" accesskey="'.__('x').'" />';
    }

    if (strlen($cat->f('category_id')) && $cat->f('category_path') != '/')  {
        echo '&nbsp;<input name="delete" type="submit" class="submit" '
            .'value="'.  __('Delete [d]').'" accesskey="'.__('d').'" onclick="return '
            .'window.confirm(\''
            .addslashes( __('Are you sure you want to delete this category?'))
            .'\')" />';
    }
    if (strlen($cat->f('category_id'))) {
        echo form::hidden('category_id', $cat->f('category_id'));
    } else {
        echo form::hidden('op','add');
    }
    echo form::hidden('quirk_category_path', strlen($cat->f('category_path')));
    echo "</p>\n";
    echo '</form>'."\n\n";
    echo '<h2>'. __('Online help').'</h2>'."\n";
    echo '<h3><a onclick="openClose(\'wikihelp\',0); return false" '
        .'href="#"><img alt="'.__('show/hide').'" id="img_wikihelp" '
        .'src="themes/'.$_px_theme.'/images/plus.png" style="vertical-align: middle;" /></a> '
        .__('Wiki syntax').'</h3>'."\n";
    echo '<div id="wikihelp" style="display: none;"> ';
    echo $m->getHelp('wiki-inline'); 
    echo '</div>'."\n";
    echo '<script type="text/javascript"><!--'."\n"
        .'openClose(\'wikihelp\',-1);'."\n"
        .'//--></script>';
    
    echo '<h3><a onclick="openClose(\'htmlhelp\',0); return false" '
        .'href="#"><img alt="'.__('show/hide').'" id="img_htmlhelp" '
        .'src="themes/'.$_px_theme.'/images/plus.png" style="vertical-align: middle;" /></a> '
        .__('XHTML coding').'</h3>'."\n";
    echo '<div id="htmlhelp" style="display: none;">'
        .$m->getHelp('html-inline').'</div>'."\n";
    echo '<script type="text/javascript"><!-- '."\n"
        .'openClose(\'htmlhelp\',-1);'."\n"
        .'//--></script>';
} //end of $is_editable

/* ================================================= *
 *  list categories                                  *
 * ================================================= */
if (empty($_REQUEST['category_id']) && empty($_REQUEST['op'])) {

    if ($cats->isEmpty()) {
        echo '<p id="message">'.__('No categories.').'</p>'."\n\n";
    } else {
        echo '<script type="text/javascript">'."\n"
            ."<!--\n"
            ."var js_post_ids = new Array('"
            .implode("','",$cats->getIDs('category_id', 'content'))."');\n"
            ."//-->\n"
            ."</script>\n";
	
        echo '<p id="showhide"><a href="#" onclick="mOpenClose(js_post_ids,1); return false;">'.__('Show all').'</a>'
            .' - <a href="#" onclick="mOpenClose(js_post_ids,-1); return false;">'.__('Hide all').'</a></p>';

        
        while (!$cats->EOF()) {
              //Defining indentation related to the level of each category
	      $level = substr_count($cats->f('category_path'), '/');
	      if ($level>1){ $indent = $level*20;}
	      else {$indent = 10;}
            echo '<div class="resourcebox" id="p'.$cats->f('category_id').' " style="margin-left:'.$indent.'px;">'.
                '<a href="#" onclick="openClose(\'content'.$cats->f('category_id').'\',0); return false;" title="'.__('Show/hide').'">'.
                '<img src="themes/'.$_px_theme.'/images/plus.png" class="show_button" id="img_content'.$cats->f('category_id').'" '.
                'alt="'. __('show/hide').'" /></a>';
	    echo '<p class="resource_title"><span class="category_style">';
            echo '<a href="index.php?cat_id='.$cats->f('category_id').'" title="'.__('Resource list').'">'.$cats->f('category_name').'</a></span> ';
            echo '<span class="small">'.$cats->f('category_path').'</span> ';
            echo '[<span class="editlink"><a href="categories.php?category_id='.$cats->f('category_id').'">'. __('edit').'</a></span>]';
              //Test to define the link to the category on the site when using simple or nice URL
              if ($_PX_config['url_format'] == 'simple')
                 {
                   echo ' [<span class="link_style"><a href="'.$_PX_website_config['rel_url'].'/?'.$cats->f('category_path').'">'.__('See the category').'</a></span>]';
                 }
              else
                 {
                   echo ' [<span class="link_style"><a href="'.$_PX_website_config['rel_url'].$cats->f('category_path').'">'.__('See the category').'</a></span>]';
                 }
            echo "</p>\n\n";
            echo '<div id="content'.$cats->f('category_id').'" class="hided" style="display:none;">';
            echo "<div class=\"description_style\">\n".text::parseContent($cats->f('category_description'))."</div>\n";
            echo "<p class='idmakelink'>". __('Id to use in the templates:').' '.$cats->f('category_id')."</p>";
            echo "\n<hr class='invisible' /></div></div>\n\n";	
		
            $cats->moveNext();
        }
    }
}

include config::f('manager_path').'/mtemplates/_bottom.php';

?>