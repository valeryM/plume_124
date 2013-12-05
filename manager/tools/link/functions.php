<?php
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
# The Initial Developer of the Original Code is
# Olivier Meunier.
# Portions created by the Initial Developer are Copyright (C) 2003
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
# - Sebastien Fievet
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# ***** END LICENSE BLOCK ***** */

class pxLink {

    /**
     * Cette fonction affiche la liste des liens
     * 
     * @param string  SQL query
     * @param string  Substitution string for the category ('<h3>%s</h3>')
     * @param string  Substitution string for the list ('<ul>%s</ul>')
     * @param string  Substitution string for each link ('<li>%s</li>')
     */
    function _linkList($sql, $category='<h3>%s</h3>', $block='<ul>%s</ul>',
                       $item='<li>%s</li>')
    {
        $con =& pxDBConnect();
        if (($rs_link = $con->select($sql)) !== false) {
            $res = '';
            while (!$rs_link->EOF()) {
                $label = $rs_link->f('label');
                $href = $rs_link->f('href');
                $title = $rs_link->f('title');
                $lang = $rs_link->f('lang');
                $rel = $rs_link->f('rel');
                
                if (! $label && ! $href) {
                    if ('' != $res) {
                        printf($block, $res);
                    }
                    printf($category, $title);
                    $res = ''; 
                } else {
                    $link =
                        '<a href="'.htmlspecialchars($href).'"'.
                        ((!$lang) ? '' : ' hreflang="'.htmlspecialchars($lang).'"').
                        ((!$title) ? '' : ' title="'.htmlspecialchars($title).'"').
                        ((!$rel) ? '' : ' rel="'.htmlspecialchars($rel).'"').
                        '>'.
                        htmlspecialchars($label).
                        '</a>';
                    
                    $res .= sprintf($item,$link);
                }
                $rs_link->moveNext();
            }
            if ('' != $res) {
                printf($block,$res);
            }
        }
    } 

    /**
     * Cette fonction affiche la liste de tous les liens
     * 
     * @proto function linkList
     * @param string  category Chaine de substitution pour une catégorie ('<h3>%s</h3>')
     * @param string  block Chaine de substitution pour la liste ('<ul>%s</ul>')
     * @param string  item  Chaine de substitution pour un élément ('<li>%s</li>')
     */
    function linkList($category='<h3>%s</h3>',$block='<ul>%s</ul>',$item='<li>%s</li>')
    {   
        $sql = 'SELECT label, href, title, lang, rel FROM '.$GLOBALS['_PX_config']['db']['table_prefix'].'links ';
        $sql.= 'WHERE website_id=\''.$GLOBALS['_PX_website_config']['website_id'].'\' ';
        $sql.= 'ORDER BY position';
        
        pxLink::_linkList($sql, $category, $block, $item);
    }
    
    /**
     * Cette fonction affiche la liste des liens d'une catégorie donnée
     * 
     * @proto function linkListByCategory
     * @param string  category_name Nom de la catégorie
     * @param string  category Chaine de substitution pour une catégorie ('<h3>%s</h3>')
     * @param string  block Chaine de substitution pour la liste ('<ul>%s</ul>')
     * @param string  item  Chaine de substitution pour un élément ('<li>%s</li>')
     */
    function linkListByCategory($category_name, $category='<h3>%s</h3>',$block='<ul>%s</ul>',$item='<li>%s</li>')
    {
        global $con;
        if (!isset($con)) $con =& pxDBConnect();
        
        //-- Recupération de la position de la catégorie
        $sql = 'SELECT position FROM '.$GLOBALS['_PX_config']['db']['table_prefix'].'links ';
        $sql.= 'WHERE website_id=\''.$GLOBALS['_PX_website_config']['website_id'].'\' ';
        $sql.= 'AND title = \''.$category_name.'\'';
        
        if (($rs_category = $con->select($sql)) === false) {
            return false;
        }
        
        $category_position = $rs_category->f('position');
        
        //-- Récupération de la position de la catégorie suivante
        $sql = 'SELECT position FROM '.$GLOBALS['_PX_config']['db']['table_prefix'].'links ';
        $sql.= 'WHERE website_id=\''.$GLOBALS['_PX_website_config']['website_id'].'\' ';
        $sql.= 'AND position > '.$category_position.' ';
        $sql.= 'AND href = \'\' ';
        $sql.= 'LIMIT 1';
        
        if (($rs_next_category = $con->select($sql)) === false) {
            return false;
        }
        
        $next_category_position = $rs_next_category->f('position');
        
        //-- Affichage des liens
        $sql = 'SELECT label, href, title, lang, rel FROM '.$GLOBALS['_PX_config']['db']['table_prefix'].'links ';
        $sql.= 'WHERE website_id=\''.$GLOBALS['_PX_website_config']['website_id'].'\' ';
        $sql.= 'AND position >= '.$category_position.' ';
        if (!empty($next_category_position)) {
            $sql.= 'AND position < '.$next_category_position.' ';
        }
        $sql.= 'ORDER BY position';
        
        pxLink::_linkList($sql, $category, $block, $item);
    }
}
?>