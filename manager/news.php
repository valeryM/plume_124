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
require_once $_PX_config['manager_path'].'/inc/class.news.php';

auth::checkAuth(PX_AUTH_NORMAL);
$is_user_admin = auth::asLevel(PX_AUTH_ADMIN);

$m = new Manager();
$_px_theme = $m->user->getTheme();

/* ================================================= *
 *       Generate sub-menu                           *
 * ================================================= */

$px_submenu->addItem(__('Back to the list of resources'), 'index.php', 
                     'themes/'.$_px_theme.'/images/ico_back.png', false);
$px_submenu->addItem(__('News list'), 'news.php?op=list', 
                     'themes/'.$_px_theme.'/images/ico_news.png', false);
$px_submenu->addItem(__('New news'), 'news.php',
                     'themes/'.$_px_theme.'/images/ico_new.png', false, 
                     (!empty($_REQUEST['op'])||empty($_REQUEST['resource_id']))
                     );

/* ========================================================================= *
 *                          Process block                                    *
 * ========================================================================= */

if (empty($_REQUEST['op'])) { 
    /* ===================================================================== *
     *                        add/edit/view a news                           *
     * ===================================================================== */
    $news = new News();
    /*=================================================
     * Get current news and check if right to edit it
     *=================================================*/
    $is_editable = true;
    if (!empty($_REQUEST['resource_id'])) {
        if (false !== $m->loadResource($news, $_REQUEST['resource_id'])) {
            // check the rights
            if (!$m->asRightToEdit($news)) {
                $is_editable = false;
            }
        } else {
            $m->setError(__('Error: The requested news is not available.'), 
                         400);
            $is_editable = false;
        }
    } else {
        // Set default values for a new news.
        $news->setDefaults($m->user);
    }

    $px_submenu->addItem(__('See the news'), $news->getPath(),
                         'themes/'.$_px_theme.'/images/ico_news_site.png', 
                         false, 
                         ($news->f('status') == PX_RESOURCE_STATUS_VALIDE) &&
                         ($news->f('resource_id') > 0));


    /* ================================================= *
     *  No news, set default values or from preview      *
     * ================================================= */
    if ((!empty($_POST['preview']) || !empty($_POST['publish']) 
         || !empty($_POST['transform']) || !empty($_POST['addcategory'])
         || !empty($_POST['increase']) || !empty($_POST['increase_x']) 
         || !empty($_POST['decrease']) || !empty($_POST['decrease_x'])
         ) && $is_editable) {
        $news->set(form::getPostField('n_title'),
                   form::getPostField('n_subject'),
                   form::getPostField('n_content'),
                   form::getPostField('n_content_format'),
                   form::getPostField('n_status'),
                   form::getTimeField('n_dt') /*Publication date */,
                   form::getTimeField('n_dt_e') /*End date */,
                   form::getPostField('n_noenddate'),
                   form::getPostField('n_comment_support'),
                   form::getPostField('n_subtype'));

        $news->setDetails(form::getPostField('n_titlewebsite'),
                          form::getPostField('n_linkwebsite'));
        if ($news->f('resource_id') == '') {
            $news->setField('category_id', form::getPostField('n_category_id'));
        }

        if (!empty($_POST['increase']) || !empty($_POST['increase_x'])) {
            $m->user->increase('news_textarea_content');
        }
        if (!empty($_POST['decrease']) || !empty($_POST['decrease_x'])) {
            $m->user->decrease('news_textarea_content');
        }

        if (!empty($_POST['transform'])) {
            $news->setField('description', 
                            '=html'."\n"
                            .$news->getFormattedContent('description','html'));
        }
    }

    /* ================================================= *
     *               Add, edit a news                    *
     * ================================================= */
    if (!empty($_POST['publish']) && $is_editable) {
        if ($news->f('resource_id') > 0) {
            // edit a news
            if (false !== ($id = $m->saveNews($news))) { 
                if ($news->f('status') != PX_RESOURCE_STATUS_INEDITION) { 
                    $m->setMessage(__('The news was successfully saved.'));
                    header('Location: news.php?op=list');
                } else {
                    header('Location: news.php?resource_id='.
                           $news->f('resource_id'));
                }
                exit;
            }                             
        } else {
            // add a news
            $px_news_category_id = form::getPostField('n_category_id');
            $m->user->savePref('news_category_id', $px_news_category_id);
            $m->user->savePref('news_status', 
                               form::getPostField('n_status'));
            $m->user->savePref('content_format',
                               form::getPostField('n_content_format'));
            $m->user->savePref('news_subtype', 
                               form::getPostField('n_subtype'));
            $m->user->savePref('news_comment_support', 
                               form::getPostField('n_comment_support'));
            $news->setField('category_id', $px_news_category_id);
            if (false !== ($id = $m->saveNews($news))) {
                $m->setMessage(__('The news was successfully saved.'));
                if ($news->f('status') != PX_RESOURCE_STATUS_INEDITION) {
                    header('Location: news.php?op=list');
                } else {
                    header('Location: news.php?resource_id='.$id);
                }
                exit;
            }  
        }
    }

    /* ================================================= *
     *               Associate to a category             *
     * ================================================= */
    if (strlen(form::getField('addcategory')) 
        && strlen(form::getField('n_category_id')) 
        && strlen($news->f('resource_id')) 
        && $is_editable) {

        $px_news_category_id = form::getField('n_category_id');
        
        $m->user->savePref('news_category_id', $px_news_category_id);

        if ('main' == form::getField('addcategory')) {
            $type = PX_RESOURCE_CATEGORY_MAIN;
        } else {
            $type = PX_RESOURCE_CATEGORY_OTHER;
        } 
        
        if (false !== $m->addResourceInCategory($news, $px_news_category_id, 
                                                $type)) {
            header('Location: news.php?resource_id='.$news->f('resource_id'));
            exit;
        }
    }

    /* ================================================= *
     *              Remove from a category               *
     * ================================================= */
    if (strlen(form::getField('delcat')) 
        && strlen($news->f('resource_id')) 
        && strlen(form::getField('n_category_id')) 
        && $is_editable) {

        $px_news_category_id = form::getField('n_category_id');
        if (false !== $m->removeResourceFromCategory($news,
                                                     $px_news_category_id)) {
            header('Location: news.php?resource_id='.$news->f('resource_id'));
            exit;
        }
    }

    /* ================================================= *
     *               Delete the news                     *
     * ================================================= */
    if (strlen(form::getPostField('delete')) && $is_editable) {
        if ($m->delNews($news) !== false) {
            $m->setMessage(__('The news was successfully deleted.'));
            header('Location: news.php?op=list');
            exit;
        }
    }

    /* ================================================= *
     *                 Remove a comment                  *
     * ================================================= */
    if (strlen(form::getField('delete-comment')) 
        && strlen($news->f('resource_id')) 
        && strlen(form::getField('comment_id')) 
        && $is_editable) {

        $id = form::getField('comment_id');
        if (false !== ($ct = $m->getComment($id, $news->f('resource_id')))) {
            if (false !== $m->delComment($ct)) {
                $m->setMessage(__('The comment was successfully deleted.'));
                header('Location: news.php?resource_id='.$news->f('resource_id'));
                exit;
            }
        }
    }

    /* ================================================= *
     *                 Add/Edit a comment                *
     * ================================================= */
    if (strlen(form::getField('publish-comment')) 
        && strlen($news->f('resource_id')) 
        && $is_editable) {
        $id = form::getField('comment_id');
        $ct = new Comment();
        if ((int) $id > 0) {
            if (false === $ct->load($id)) {
                $m->setError(__('The requested comment does not exist.'));
            }
        }
        if (false === $m->error()) {
            $ct->set(form::getField('c_author'), 
                     form::getField('c_email'), 
                     form::getField('c_website'), 
                     form::getField('c_content'),
                     $news->f('resource_id'), 
                     ($ct->f('comment_ip')) ? $ct->f('comment_ip') : $_SERVER["REMOTE_ADDR"],
                     form::getField('c_status'),
                     PX_COMMENT_TYPE_NORMAL,
                     ($ct->f('comment_id')) ? $ct->f('comment_user_id') : $m->user->getId());
            if (false !== $m->saveComment($ct)) {
                $m->setMessage(__('The comment was successfully saved.'));
                header('Location: news.php?resource_id='.$news->f('resource_id'));
                exit;
            }
        }
    }
    

    /* ================================================= *
     * Get the categories for the news, array of status  *
     * array for the dates                               *
     * ================================================= */
    if ($is_editable) {
        $arry_cat = $m->getArrayCategories();
        $arry_subtypes = $m->getSubTypesArray('news');
        $arry_subtypes_extra = $m->getSubTypesArray('news', 1);        
    }


} // add/edit/view a news



/* ========================================================================= *
 *                            List the news                                  *
 * ========================================================================= */
if (!empty($_REQUEST['op']) && 'list' == $_REQUEST['op']) {

    //Get the category id and save it
    $cat_id = (!empty($_GET['cat_id'])) ? $_GET['cat_id'] : $m->user->getPref('list_news_cat_id');

    $m->user->savePref('list_news_cat_id', $cat_id, $_SESSION['website_id'], true);
    if ($cat_id == 'allcat') $cat_id = '';
    
    //Get the search query
    $px_q = (!empty($_GET['q'])) ? $_GET['q'] : '';
    
    //Get available months and selected
    list($first, $last, $arry_months) = $m->getArrayMonths('news', $cat_id);
    $px_m_s = $m->user->getPref('list_news_month');
    $px_m = (!empty($_GET['m'])) ? $_GET['m'] : ((!empty($px_m_s)) ? $px_m_s : $last);
    $m->user->savePref('list_news_month', $px_m, $_SESSION['website_id'], true);
    
    if ($px_m == 'alldate') {
        $px_m = $first;
        $px_end   =  date::stamp(0, 1 /*1 month after now*/, 0);
    } else {
        $px_end   =  date::stamp(0, 1 /*1 month after $px_m */, 0, date::unix($px_m));
    }
    
    if (empty($px_q)) {
        $res = $m->getResources(''/* All users */, '' /* All status */, $cat_id, 'news', $px_m /*Date start */, $px_end /*Date end */);
        //get again as possibly modified because of the 'alldate' case
        $px_m = $m->user->getPref('list_news_month');
    } else {
        $res = $m->searchResources($px_q, false /*Not only the online resources */, 'news', 'ResourceSet' /*Class used for the results */);
        //Search is made on all the date and all the categories
        $px_m = 'alldate';
        $cat_id = 'allcat';
    }
    
    // Categories
    $arry_cat = $m->getArrayCategories(true);
    
}

/* ========================================================================= *
 *                              Display block                                *
 * ========================================================================= */

$px_title = __('News');
include dirname(__FILE__).'/mtemplates/_top.php';

echo '<h1 id="title_news">'.__('News').'</h1>'."\n\n";

if (empty($_REQUEST['op'])) {
    include dirname(__FILE__).'/mtemplates/news-edit.php';
    $px_resource_id = $news->f('resource_id');
    // Include list of comments
    // Include add/edit comment
} elseif (!empty($_REQUEST['op']) && 'list' == $_REQUEST['op']) { 
    include dirname(__FILE__).'/mtemplates/news-list.php';
}

include dirname(__FILE__).'/mtemplates/_bottom.php';

?>