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
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# ***** END LICENSE BLOCK ***** */

if (basename($_SERVER['SCRIPT_NAME']) == '_top.php') exit;

/*
 * Can be include only after the creation of the manager ($m)
 * object as used here. 
 */
header('Content-Type: text/html; charset='.strtolower(config::f('encoding')));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo $px_title; ?> - PLUME CMS</title>
  <link rel="stylesheet" type="text/css" href="themes/<?php echo $_px_theme; ?>/style.css" />
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo strtolower(config::f('encoding')); ?>" />
  <script type="text/javascript">
  <!--  
  var pxThemeid = '<?php echo $_px_theme; ?>';  
  //-->
  </script>
  <script type="text/javascript" src="tools.js"> </script>
  <?php Hook::run('onPrintHeaderManagerPage', array('m' => &$m)); ?>
</head>

<body>
<?php  include dirname(__FILE__).'/menu.php'; ?>

<div id="main">

<?php  echo $px_submenu->draw(); ?>

<div id="content">


<p class="user-info"><?php echo $m->user->f('user_realname'); ?>
<br /><a href="login.php?logout=1" accesskey="<?php  echo __('o'); ?>"><?php  echo __('Logout'); ?></a></p>

<?php

if(!empty($_GET['msg'])) {
	$msg = $_GET['msg'];
} else {
    $msg = $m->getMessage();
}
if (!empty($msg)) {
    echo '<p class="message">'.htmlspecialchars($msg).'</p>';
}
if (false !== ($px_error = $m->error(true, false)) )
    echo "\n\n" . $px_error . "\n\n";
?>


