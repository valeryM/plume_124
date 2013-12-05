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

class link
{
	var $con;
	var $site_id;
	var $table;
	
	var $protocols = array('http', 'https', 'ftp');
	
	function link($site_id)
	{
		$this->getConnection();
		$this->site_id = $site_id;
		$this->table = $this->con->pfx.'links';
	}
	
	function getConnection()
	{
		if ($this->con === null) $this->con =& pxDBConnect();
	}
	
	function isRunning() {
		$sql = 'SHOW TABLES LIKE \''.$this->table.'\'';
		$rs = $this->con->select($sql);
		
		if($rs->isEmpty()) return false;
		return true;
	}
	
	/**
	 * Validate an URI (RFC2396)
	 *
	 * @param string    $url        URI to validate
	 * @param array     $options    Options used by the validation method.
	 *                              key => type
	 *                              'domain_check' => boolean
	 *                                  Whether to check the DNS entry or not
	 *                              'allowed_schemes' => array, list of protocols
	 *                                  List of allowed schemes ('http',
	 *                                  'ssh+svn', 'mms')
	 * @author Copyrights PEAR::Validate package
	 */
	function isURI($url, $options = null)
	{
		$domain_check = false;
		$allowed_schemes = null;
		if (is_array($options)) {
			extract($options);
		}
		if (preg_match(
			'!^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?!',
			$url,$matches)
		) {
			$scheme = $matches[2];
			$authority = $matches[4];
			if ( is_array($allowed_schemes) &&
				!in_array($scheme,$allowed_schemes)
			) {
				return false;
			}
			if ($domain_check && function_exists('checkdnsrr')) {
				if (!checkdnsrr($authority, 'A')) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
	
	function addLink($label,$href,$title='',$lang='',$cat='-1')
	{
		#Position maximum
		$strReq = 'SELECT MAX(position) '.
				'FROM '.$this->table.' '.
				'WHERE website_id=\''.$this->site_id.'\'';
			
		if (($rs = $this->con->select($strReq)) === false) {
			return false;
		}
		$max = $rs->f(0);
		$position = $rs->f(0)+1;

		# On calcule la position d'insertion
		$strReq = 'SELECT position '.
				'FROM '.$this->table.' '.
				'WHERE href=\'\' AND position>='.($cat+1).' AND website_id=\''.$this->site_id.'\' '.
				'ORDER BY position ASC';
		$rs = $this->con->select($strReq);
		if (!$rs->isEmpty()) {
			$position = $rs->f('position');
		}

		#On met � jour la position des �l�ments de $position � $max
		$this->__updPosition($position, $max, +1);

		$insReq = 'INSERT INTO '.$this->table.' '.
				'(website_id, label, href, title, lang, position) VALUES '.
				'(\''.$this->con->escapeStr($this->site_id).'\', '.
				'\''.$this->con->escapeStr($label).'\', '.
				'\''.$this->con->escapeStr($href).'\', '.
				'\''.$this->con->escapeStr($title).'\', '.
				'\''.$this->con->escapeStr($lang).'\', '.
				'\''.(integer) $position.'\')';
		
		if ($this->con->execute($insReq) === false) {
			return false;
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}
	
	function updLink($link_id,$label,$href,$title='',$lang='', $rel='',$cat='-1', $oldCat='-1')
	{
		$updReq = '';
		if ($cat!=$oldCat) {
			#Position actuelle
			$strReq = 'SELECT position '.
					'FROM '.$this->table.' '.
					'WHERE link_id = '.$link_id.' AND website_id=\''.$this->site_id.'\'';
			$rs = $this->con->select($strReq);
			if ($rs->isEmpty()) {
				return false;
			}
			$currentPosition = $rs->f('position');

			#Maximum
			$strReq = 'SELECT MAX(position) '.
					'FROM '.$this->table.' '.
					'WHERE website_id=\''.$this->site_id.'\'';
				
			if (($rs = $this->con->select($strReq)) === false) {
				return false;
			}
			$position = $rs->f(0);

			# On calcule la position d'insertion
			$strReq = 'SELECT position '.
					'FROM '.$this->table.' '.
					'WHERE href=\'\' AND position>='.($cat+1).' AND website_id=\''.$this->site_id.'\' '.
					'ORDER BY position ASC';
			$rs = $this->con->select($strReq);
			if (!$rs->isEmpty()) {
				$position = $rs->f('position');
			}

			#On met � jour la position des �l�ments de $position � $max
			if ($currentPosition < $position) {
				$this->__updPosition($currentPosition+1, $position, -1);
			} else {
				$this->__updPosition($position, $currentPosition-1, +1);
			}
			$updReq = 'UPDATE '.$this->table.' SET '.
					'label = \''.$this->con->escapeStr($label).'\','.
					'href = \''.$this->con->escapeStr($href).'\','.
					'title = \''.$this->con->escapeStr($title).'\','.
					'lang = \''. $this->con->escapeStr($lang).'\','.
					'rel = \''. $this->con->escapeStr($rel).'\','.
					'position = '.(integer)$position.' '.
					'WHERE link_id = '.$link_id;
		} else {
			$updReq = 'UPDATE '.$this->table.' SET '.
					'label = \''.$this->con->escapeStr($label).'\','.
					'href = \''.$this->con->escapeStr($href).'\','.
					'title = \''.$this->con->escapeStr($title).'\','.
					'lang = \'' . $this->con->escapeStr($lang).'\','.
					'rel = \'' . $this->con->escapeStr($rel).'\''.
					'WHERE link_id = '.$link_id;
		}
		
		if ($this->con->execute($updReq) === false) {
			return false;
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}

	# Ordonner un lien
	function ordLink($link_id,$ord)
	{
		$this->__reordEntries();
		
		$ord = ($ord == '+') ? '+' : '-';
		
		$strReq = 'SELECT position '.
				'FROM '.$this->table.' '.
				'WHERE link_id = '.$link_id.' AND website_id=\''.$this->site_id.'\'';
		$rs = $this->con->select($strReq);
		
		if ($rs->isEmpty()) {
			return false;
		}
		
		$position = $rs->f('position');
		
		$strReq = 'SELECT MAX(position) FROM '.$this->table.' WHERE website_id=\''.$this->site_id.'\'';
		$rs = $this->con->select($strReq);
		$max_ord = $rs->f(0);
		
		# Si on veut monter le plus haut, on arr�te
		if ($position == 0 && $ord == '+') {
			return false;
		}
		
		# Idem pour le plus bas
		if ($position == $max_ord && $ord == '-') {
			return false;
		}
		
		$new_ord = ($ord == '+') ? $position-1 : $position+1;
		
		# On met � jour les deux entr�es
		$updReq = 'UPDATE '.$this->table.' SET '.
				'position = '.$position.' '.
				'WHERE position = '.$new_ord.' AND website_id=\''.$this->site_id.'\'';
		
		if (!$this->con->execute($updReq)) {
			return false;
		}
		
		$updReq = 'UPDATE '.$this->table.' SET '.
				'position = '.$new_ord.' '.
				'WHERE link_id = '.$link_id;
		
		if (!$this->con->execute($updReq)) {
			return false;
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}
	
	# Cr�ation de cat�gorie
	function addCat($title)
	{
		$strReq = 'SELECT MAX(position) '.
				'FROM '.$this->table.' '.
				'WHERE website_id=\''.$this->site_id.'\'';
			
		if (($rs = $this->con->select($strReq)) === false) {
			return false;
		}
		
		$position = $rs->f(0)+1;

		$insReq = 'INSERT INTO '.$this->table.' '.
				'(website_id, title, position) VALUES '.
				'(\''.$this->con->escapeStr($this->site_id).'\', '.
				'\''.$this->con->escapeStr($title).'\', '.
				'\''.(integer) $position.'\')';
		
		if ($this->con->execute($insReq) === false) {
			return false;
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}
	
	# Modification de cat�gories
	function updCat($id,$title)
	{
		return $this->updLink($id,'','',$title,'');
	}
	
	# Ordonner une cat�gorie
	function ordCat($link_id,$ord)
	{
		$this->__reordEntries();
		
		$ord = ($ord == '+') ? '+' : '-';
		
		$strReq = 'SELECT position '.
				'FROM '.$this->table.' '.
				'WHERE link_id = '.$link_id;
		$rs = $this->con->select($strReq);
		
		if ($rs->isEmpty()) {
			return false;
		}
		
		$position = $rs->f('position');

		$strReq = 'SELECT MAX(position) FROM '.$this->table.' WHERE website_id=\''.$this->site_id.'\'';
		$rs = $this->con->select($strReq);
		$max = $rs->f(0);
		
		# Position de la cat�gorie avec laquelle �changer
		if ($ord == '+') {
			# On calcule la position de la cat�gorie avec laquelle �changer
			$strReq = 'SELECT position '.
					'FROM '.$this->table.' '.
					'WHERE href=\'\' AND position<'.$position.' AND website_id=\''.$this->site_id.'\' '.
					'ORDER BY position DESC';
			$rs = $this->con->select($strReq);
			$swapPosition = -1;
			$swapPositionEnd = $position-1;
			if (!$rs->isEmpty()) {
				$swapPosition = $rs->f('position');
			}

			# On calcule la position du dernier �l�ment de la cat�gorie
			$strReq = 'SELECT position '.
					'FROM '.$this->table.' '.
					'WHERE href=\'\' AND position>='.($position+1).' AND website_id=\''.$this->site_id.'\' '.
					'ORDER BY position ASC';
			$rs = $this->con->select($strReq);
			$positionEnd = $max;
			if (!$rs->isEmpty()) {
				$positionEnd = $rs->f('position')-1;
			}
		} else {
			# On calcule la position de la cat�gorie avec laquelle �changer
			$strReq = 'SELECT position '.
					'FROM '.$this->table.' '.
					'WHERE href=\'\' AND position>'.$position.' AND website_id=\''.$this->site_id.'\' '.
					'ORDER BY position ASC';
			$rs = $this->con->select($strReq);
			$swapPosition = $max+1;
			$positionEnd = $max;
			if (!$rs->isEmpty()) {
				$swapPosition = $rs->f('position');
				$positionEnd = $swapPosition-1;
			}

			# On calcule la position du dernier �l�ment de la cat�gorie avec laquelle �changer
			$strReq = 'SELECT position '.
					'FROM '.$this->table.' '.
					'WHERE href=\'\' AND position>='.($swapPosition+1).' AND website_id=\''.$this->site_id.'\' '.
					'ORDER BY position ASC';
			$rs = $this->con->select($strReq);
			$swapPositionEnd = $max;
			if (!$rs->isEmpty()) {
				$swapPositionEnd = $rs->f('position')-1;
			}
		}
		$strReq = 'SELECT link_id, position '.
				'FROM '.$this->table.' '.
				'WHERE position>='.$position.' AND position<='.$positionEnd.' AND website_id=\''.$this->site_id.'\' '.
				'ORDER BY position';
		$rs = $this->con->select($strReq);
		
		$strReq = 'SELECT link_id, position '.
				'FROM '.$this->table.' '.
				'WHERE position>='.$swapPosition.' AND position<='.$swapPositionEnd.' AND website_id=\''.$this->site_id.'\' '.
				'ORDER BY position';
		$rsSwap = $this->con->select($strReq);

		if (!$rs->isEmpty()) {
			if ($ord == '-') {
				$delta = $swapPositionEnd-$swapPosition+1;
			} else {
				$delta = -($swapPositionEnd-$swapPosition+1);
			}
			while(!$rs->EOF()) {
				$pos = $rs->f('position')+$delta;
				$updReq = 'UPDATE '.$this->table.' SET '.
						'position = '.$pos.' '.
						'WHERE link_id = '.$rs->f('link_id');

				if (!$this->con->execute($updReq)) {
					return false;
				}
				$rs->moveNext();
			}
		}

		if (!$rsSwap->isEmpty()) {
			if ($ord == '-') {
				$delta = -($positionEnd-$position+1);
			} else {
				$delta = $positionEnd-$position+1;
			}
			while(!$rsSwap->EOF()) {
				$pos = $rsSwap->f('position')+$delta;
				$updReq = 'UPDATE '.$this->table.' SET '.
						'position = '.$pos.' '.
						'WHERE link_id = '.$rsSwap->f('link_id');

				if (!$this->con->execute($updReq)) {
					return false;
				}
				$rsSwap->moveNext();
			}
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}
	
	# Suppression (lien ou cat�gorie)
	function delEntry($link_id)
	{
		$delReq = 'DELETE FROM '.$this->table.' '.
				'WHERE link_id = '.$link_id;
		
		if ($this->con->execute($delReq) === false) {
			return false;
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}
	
	# Ordonner les entr�es
	function ordEntries($ord)
	{
		if (!is_array($ord)) {
			return false;
		}
		
		foreach ($ord as $k => $v)
		{
			$updReq = 'UPDATE '.$this->table.' SET '.
					'position = '.(integer) $v.' '.
					'WHERE link_id = '.(integer) $k;
			
			if (!$this->con->execute($updReq)) {
				return false;
			}
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}
	
	# Recuperer les entr�es
	function &getEntries() {
		$sql = 'SELECT link_id, label, href, title, lang, position ';
		$sql.= 'FROM '.$this->table.' ';
		$sql.= 'WHERE website_id=\''.$this->site_id.'\' ';
		$sql.= 'ORDER BY position ';
	
		$rs = $this->con->select($sql);
		
		return $rs;
	}
	
	function &getEntry($link_id) {
		$strReq = 'SELECT link_id, label, href, title, lang, rel, position '.
			'FROM '.$this->table.' '.
			'WHERE link_id = '.$link_id;
	
		$rs = $this->con->select($strReq);
		
		return $rs;
	}
	
	# Incr�mente/d�cr�mente la position des �l�ments entre $min et $max
	function __updPosition($min, $max, $delta)
	{
		if ($min<=$max) {
			$strReq = 'SELECT link_id, position '.
					'FROM '.$this->table.' '.
					'WHERE position>='.$min.' AND position<='.$max.' AND website_id=\''.$this->site_id.'\' '.
					'ORDER BY position ';
			$rs = $this->con->select($strReq);
		
			while (!$rs->EOF()) {
				$position = $rs->f('position')+$delta;
				$updReq = 'UPDATE '.$this->table.' SET '.
						'position = '.$position.' '.
						'WHERE link_id = '.$rs->f('link_id');

				if (!$this->con->execute($updReq)) {
					return false;
				}
				$rs->moveNext();
			}
		}
		return true;
	}

	# R�ordonner les entr�es
	function __reordEntries()
	{
		$i = 0;
		$strReq = 'SELECT link_id, position '.
				'FROM '.$this->table.' '.
				'WHERE website_id=\''.$this->site_id.'\' '.
				'ORDER BY position ';
		$rs = $this->con->select($strReq);
		
		while (!$rs->EOF())
		{
			$updReq = 'UPDATE '.$this->table.' SET '.
					'position = '.$i.' '.
					'WHERE link_id = '.$rs->f('link_id');
			
			if (!$this->con->execute($updReq)) {
				return false;
			}
			
			$i++;
			$rs->moveNext();
		}
		
		@touch($_PX_config['manager_path'].'/cache/'.$this->site_id.'/MASS_UPDATE', time());
		return true;
	}
	
	# Ajout de header
	function insertHeader() {
		global $_px_theme;
		
		$PLUGIN_HEAD =
		'<style type="text/css">'."\n".
		'.sort {border : 1px solid #ccc; padding : 0.3em; margin : 5px 0 0 0; background : #f7f5f0; clear : both;}'."\n".
		'.sort p {margin : 0.5em 0 0 0;}'."\n".
		'.sortJS {padding : 0 0.3em 0.5em 35px; cursor : move; background : #f7f5f0 url(tools/link/themes/'.$_px_theme.'/images/updown.png) no-repeat 5px 5px;}'."\n".
		'.sort img.status {float: right; margin: 2px 0 0 4px; position: relative;}'."\n".
		'</style>'."\n".
		'<script type="text/javascript" src="js/drag.js"></script>'.
		'<script type="text/javascript" src="js/dragsort.js"></script>'.
		'<script type="text/javascript">'."\n".
		'  if (document.getElementById) { '."\n".
		'    window.onload = function() { '."\n".
		'    dragSort.dest = document.getElementById(\'dndSort\');'."\n".
		'    dragSort.makeElementSortable(document.getElementById(\'sortlinks\'));'."\n".
		'    };'."\n".
		'  }'."\n".
		'</script>';
		
		echo($PLUGIN_HEAD);
	}
}
?>