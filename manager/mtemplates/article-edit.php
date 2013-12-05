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

if (basename($_SERVER['SCRIPT_NAME']) == 'article-edit.php') exit;

/* ===================================================== *
 *  Preview of the content if some content is available  *
 * ===================================================== */
if (strlen($ar->getUnformattedContent('description'))) {
    echo '<div class="preview">';
    
    echo '<h2>'.$ar->getTextContent('title').'</h2>';
    echo $ar->getFormattedContent('description','html');

    echo "<hr class='invisible' id=\"xxx-prevent\" /></div>\n\n";

    if (!$is_editable) {
        while (!$ar->pages->EOF()) {
            echo '<div class="preview">';

            echo '<h3>'.$ar->getTextContent('page_title', 'pages').'</h3>';
            echo $ar->getFormattedContent('page_content', 'html', 'pages');

            echo "<hr class='invisible' id=\"zzz\"/></div>\n\n";
            $ar->pages->moveNext();
        }

    }
}

/* =========================================== *
 *  If is editable form to modify the content  *
 * =========================================== */
if ($is_editable) { 
    echo '<form action="articles.php" method="post" id="formPost"'
        .'onsubmit="return isReady(\'a_title\',\''
        .__('You need to give a title.').'\')">'."\n\n";

    if ($ar->cats->nbRow() >= 1) {
        /* ==================================================== *
         *  The article is already in a category, propose more  * 
         * ==================================================== */
        echo '<fieldset><legend><span class="category_style">'. __('Categories')."</span></legend>\n\n";
        echo "<ol>\n";
        while (!$ar->cats->EOF()) {
            echo '<li>'.$ar->cats->f('category_name');
            if ($ar->cats->f('categoryasso_type') != PX_RESOURCE_CATEGORY_MAIN) {
                echo ' - <a href=\'articles.php?delcat=1&amp;resource_id='
                    .$ar->f('resource_id').'&amp;a_category_id='
                    .$ar->cats->f('category_id').'\' title=\''
                    . __('Remove from this category').'\'>';
                echo '<img src="themes/'.$_px_theme
                    .'/images/delete.png" alt="Delete icon" /></a> ';
                echo ' <a href=\'articles.php?addcategory=main&amp;resource_id='
                    .$ar->f('resource_id').'&amp;a_category_id='
                    .$ar->cats->f('category_id').'\' title=\''
                    .__('Set as main category').'\'>';
                echo '<img src="themes/'.$_px_theme
                    .'/images/ico_set_as_home.png" alt="Home icon" /></a> ';
            } else {
                echo ' - <img src="themes/'.$_px_theme
                    .'/images/ico_home.png" alt="'.__('Main category')
                    .'" />';
            }
            echo "</li>\n";
            $ar->cats->moveNext();
        }
        echo "</ol>\n\n";
        echo '<p><span class="nowrap"><label for="a_category_id" '
            .'style="display:inline">'.__('Add in another category').' '
            .$m->HelpLink('article', 'h-category').'</label><br />'."\n";
        echo form::combobox('a_category_id',$arry_cat, '', 
                            $m->user->getPref('article_category_id'), 1)."\n";
        echo '<input name="addcategory" tabindex="2" type="submit" '
            .'class="submit" value="'.__('Add').'" />'."\n";
        echo '</span></p></fieldset>'."\n".'<p class="selections">';
    } else {  
        /* ================================================= *
         *  A new news, propose a list of categories         * 
         * ================================================= */
        
        echo '<p class="selections">'."\n";
        echo '<span class="nowrap"><label for="a_category_id" '
            .'style="display:inline">'.__('Category').' '
            .$m->HelpLink('article', 'h-category').'</label>'."\n";
        echo form::combobox('a_category_id', $arry_cat, 
                            $ar->f('category_id'), 
                            $m->user->getPref('article_category_id'),
                            2).'</span>'."\n";
    } 
    /* ================================================= *
     *  Rest of the edition form                         * 
     * ================================================= */

    // Content format
    echo '<span class="nowrap"><label for="a_description_format" ';
    echo 'style="display:inline">';
    echo __('Format').' '; 
    echo $m->HelpLink('article', 'h-format');
    echo '</label> ';
    echo form::combobox('a_description_format',
                        array('HTML'=>'html','Wiki'=>'wiki'),
                        $ar->getContentFormat('description'),
                        $m->user->getPref('content_format'), 3);
    echo "</span>\n";

    // Status
    echo '<span class="nowrap"><label for="a_status" ';
    echo 'style="display:inline">';
    echo __('Status').' '; 
    echo $m->HelpLink('article', 'h-status');
    echo '</label> ';
    echo form::combobox('a_status', $m->getArrayResStatus(),
                        $ar->f('status'), 
                        $m->user->getPref('article_status'), 4);
    echo "</span>\n";

    // Comment open or not
    // Show the choice only if possible to choose as defined by
    // the website configuration settings.
    if (config::f('comment_support') == 2) {
        echo '<span class="nowrap"><label for="a_comment_support" ';
        echo 'style="display:inline">';
        echo __('Comments').' ';  
        echo $m->HelpLink('article', 'h-comments'); 
        echo '</label> ';
        echo form::combobox('a_comment_support', 
                            $m->getArrayCommentSupport(),
                            $ar->f('comment_support'), 
                            $m->user->getPref('article_comment_support'), 4);
        echo "</span>\n\n";
    } else {
        echo form::hidden('a_comment_support', config::f('comment_support'));
    }


    // Subtype
    if (count($arry_subtypes) > 1) {
        echo '<span class="nowrap"><label for="a_subtype" ';
        echo 'style="display:inline">';
        echo __('Type'); 
        echo '</label> ';
        echo form::combobox('a_subtype', $arry_subtypes, 
                            $ar->f('subtype_id'), 
                            '', 5); 
        echo "</span>\n";
    } else {
        echo form::hidden('a_subtype', array_shift($arry_subtypes));
    }
    echo "</p>\n";

    // Title
    echo '<p><label for="a_title"><strong>';
    echo __('Title'); 
    echo '</strong> ';
    echo $m->HelpLink('article', 'h-title'); 
    echo '</label> ';
    echo form::textField('a_title', 30, 255, $ar->f('title'), 6, 
                         'style="width:100%" onkeyup="setUrl(\'a_title\', '
                         .'\'a_path\', \'\', '.strlen($ar->f('path')).')"');
    echo "</p>\n";

    echo "<p>\n";

    // Insert an image or a file
    echo '<span id="insert-img" class="right-block"><img src="themes/'.$_px_theme
        .'/images/ico_image.png" alt="" /> ';
    echo '<strong><a href="xmedia.php" accesskey="i" ';
    echo 'onclick="popup(this.href+\'?mode=popup\'); return false;">';
    echo __('Insert an image or a file'); 
    echo '</a></strong></span>'."\n";

    // Description
    echo '<label for="a_description"><strong>'. __('Description').'</strong> ';
    echo $m->HelpLink('article', 'h-description').' ';
    echo '</label>';
    echo form::textArea('a_description', 60,
                        $m->user->getPref('article_textarea_description'),
                        $ar->getUnformattedContent('description'), 7,
                        'style="width:100%"');

    // Size controls
    echo '<span id="size-control" class="size-control">';
    echo '<input type="image" title="'.__('shrink textarea');
    echo '" name="decrease" value="-" src="themes/'.$_px_theme; 
    echo '/images/ico_shrink.png" accesskey="-" class="size-control" /> ';
    echo '<input type="image" title="'.__('grow textarea');
    echo '" name="increase" value="+" src="themes/'.$_px_theme; 
    echo '/images/ico_grow.png" accesskey="+" class="size-control" />';
    echo "</span>\n</p>\n\n";

    // Keywords
    echo '<p><label for="a_subject">'.__('Keywords').' ';
    echo $m->HelpLink('article', 'h-keywords').' </label>';
    echo form::textArea('a_subject',60,4,
                        $ar->f('subject'), 8,'style="width:100%"');
    echo "</p>\n";

    // Path
    echo '<p><label for="a_path"><strong>'.__('Path').'</strong> ';
    echo $m->HelpLink('article', 'h-path').'</label> ';
    echo form::textField('a_path', 30, 255, $ar->f('path'), 9, 
                         'style="width:100%"'); 
    echo "</p>\n";

    // Publication date
    if ($ar->f('resource_id') > 0) {
        echo '<p>'. __('Publication date').' ';
        echo $m->HelpLink('article', 'h-publication-date');
        echo ' '.form::datetime('a_dt', 
                                $ar->getArrayDate('publicationdate'), 9);
        echo "<br />\n";
        $noenddate_style = ($ar->isDateEOT('enddate')) ? 
            'style="display: none"' : '';

        echo '<span class="nowrap">';
        echo form::checkbox('a_noenddate', 1, $ar->isDateEOT('enddate'), 10, 
                            "onclick=\"openCloseSpan('noenddate',0)\"").' ';
        echo '<label for="a_noenddate" style="display:inline">';
        echo __('Do not use an expiration date.');
        echo '</label></span>';
        echo ' <span class="nowrap" id="noenddate" '.$noenddate_style.'>';
        echo '<br />';
        echo __('Expiration date').' ';
        echo $m->HelpLink('article', 'h-expiration-date').' ';
        echo form::datetime('a_dt_e', $ar->getArrayDate('enddate'), 11);
        echo "</span></p>\n\n";
    }

    if ($ar->f('resource_id') > 0) {
        //  list the pages, link to add a new page
        echo '<fieldset><legend><span class="art_style">'. __('Article pages').'</span></legend>'."\n\n";
        if ($ar->pages->nbRow() >= 1) {
            echo "<ol>\n";
            while (!$ar->pages->EOF()) {
                echo '<li><a tabindex="4'.$ar->pages->f('page_number').'" ';
                echo 'href=\'articles.php?op=page&amp;resource_id='
                    .$ar->f('resource_id').'&amp;a_page_id='
                    .$ar->pages->f('page_id').'\' title=\''
                    . __('Edit this page').'\'>'
                .$ar->pages->f('page_title').'</a> ';
                echo "</li>\n";
                $ar->pages->moveNext();
            }
            echo "</ol>\n\n";
        }
        echo '<p><a tabindex="900" href="articles.php?op=page&amp;resource_id='
            .$ar->f('resource_id').'">'.__('Add a page to the article')
            .'</a></p>';
        echo '</fieldset>'."\n";
    } else {
        echo '<p class="message">';
        echo __('You need first to save the article, before adding a page to it.');
        echo '</p>';
    }

    // Submit buttons
    echo '<p class="button"><input name="preview" type="submit" class="submit" ';
    echo 'tabindex="912" value="'.__('Visualize [v]').'" ';
    echo 'accesskey="'.__('v').'" />&nbsp; ';
    echo '<input name="publish" type="submit" class="submit" tabindex="913" ';
    echo 'value="'.__('Save [s]').'" accesskey="'.__('s').'" />';

    if ($ar->f('resource_id') > 0 && 
        $ar->getContentFormat('description') == 'wiki') {
        echo '&nbsp;<input tabindex="914" name="transform" type="submit" ';
        echo 'class="submit" value="'.__('Transform in XHTML [x]').'" accesskey="'.__('x').'" />';
    }

    if ($ar->f('resource_id') > 0) {
        echo '&nbsp;<input tabindex="915" name="delete" type="submit" ';
        echo 'class="submit" accesskey="'.__('d').'" value="'. __('Delete [d]').'" onclick="return ';
        echo 'window.confirm(\'';
        echo addslashes(__('Are you sure you want to delete this article?'));
        echo '\')" />';
        echo form::hidden('resource_id',$ar->f('resource_id'));
    }
    echo "</p>\n\n";
    echo "</form>\n\n";
    ?>
<h2><?php  echo __('Online help') ?></h2>
<h3><a onclick="openClose('wikihelp',0); return false" href="#"><img alt="<?php  echo __('show/hide'); ?>" id="img_wikihelp" src="themes/<?php echo $_px_theme; ?>/images/plus.png" /></a>&nbsp;
<?php  echo __('Wiki syntax'); ?></h3>
<div id="wikihelp" style="display: none;">
<?php echo $m->getHelp('wiki-inline'); ?>
</div>
<script type="text/javascript"><!--
openClose('wikihelp',-1);
//--></script>

<h3><a onclick="openClose('htmlhelp',0); return false" href="#"><img alt="<?php  echo __('show/hide'); ?>" id="img_htmlhelp" src="themes/<?php echo $_px_theme; ?>/images/plus.png" /></a>&nbsp;
<?php  echo __('XHTML coding'); ?></h3>
<div id="htmlhelp" style="display: none;">
<?php echo $m->getHelp('html-inline'); ?>
</div>
<script type="text/javascript"><!--
openClose('htmlhelp',-1);
//-->
</script>

 <?php
    $px_resource_id = $ar->f('resource_id');
    if ($px_resource_id > 0) {
        $ct = $ar->comments;
        include dirname(__FILE__).'/comments-rlist.php';
    }


}
?>
