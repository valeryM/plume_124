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

auth::checkAuth(PX_AUTH_ADMIN);

$m = new Manager();
$_px_theme = $m->user->getTheme();

//To improve security, defining a token
if (empty($_POST['save'])) {
	$token = md5(microtime().config::f('secret_key').$_COOKIE['px_session']);
	$_SESSION['token'] = $token;
}

/* ================================================= *
 *       Generate sub-menu                           *
 * ================================================= */
if (empty($_REQUEST['op']) && empty($_REQUEST['user_id'])) {
    $display_add_user = true;
} else {
    $display_add_user = false;
}

$px_submenu->addItem(__('New author'), 'users.php?op=add',  
                     'themes/'.$_px_theme.'/images/ico_new.png', 
                     false, $display_add_user);
$px_submenu->addItem(__('Back to the list of authors'), 'users.php',  
                     'themes/'.$_px_theme.'/images/ico_back.png', 
                     false, !$display_add_user);

/* ====================================================== *
 *                 Process block                          *
 * ====================================================== */

if (empty($_REQUEST['op']) && empty($_REQUEST['user_id'])) { 
    // list the users
    $px_users = $m->getUsers();
} else {
    // edit a user
    // set default values
    $px_is_admin = false; // Has the user the admin level somewhere?
    $px_edit_ok  = false;
    $px_id       = '';
    $px_username = '';   	  
    $px_password = '';  	  
    $px_realname = '';  	  
    $px_email    = '';  
    $px_pubemail = '';  	  
    $px_levels   = array();   
    $arry_levels[__('Administrator')] = PX_AUTH_ADMIN;
    $arry_levels[__('Advanced author')] = PX_AUTH_ADVANCED;
    $arry_levels[__('Intermediate author')] = PX_AUTH_INTERMEDIATE;
    $arry_levels[__('Simple author')] = PX_AUTH_NORMAL;
    $arry_levels[__('No access')]= PX_AUTH_DISABLE;
	$px_res = new Recordset();

    if (!empty($_REQUEST['user_id'])) {
        $px_user = $m->getUserById($_REQUEST['user_id']);
        $px_id       = $px_user->f('user_id');
        $px_username = $px_user->f('user_username');   	  
        $px_realname = $px_user->f('user_realname');  	  
        $px_email    = $px_user->f('user_email');  
        $px_pubemail = $px_user->f('user_pubemail');  	  
        $px_levels   = $px_user->getWebsiteLevels($px_id);
        // to get all the resources
        // even in another website 
		$px_res      = $px_user->getListResources(); 
		                                             
        foreach ($px_user->webs as $site => $score) {
            if ($score >= PX_AUTH_ADMIN) {
                $px_is_admin = true;
                break;
            }
        } 
        reset($px_user->webs);     
        if (auth::asLevel(PX_AUTH_ROOT)
            || !$px_is_admin || $px_id == $m->user->f('user_id')) {
            $px_edit_ok = true;
        }                                                                  
    } else {
        // new user
        $px_edit_ok = true;
    }        
        
}

/* ================================================= *
 *              Save/Add the user                    *
 * ================================================= */
if (!empty($_POST['save'])) {
    //Verifying token for security reasons
    $token = $_POST['token'];
    if ($token == $_SESSION['token']):
    // Populate the list of websites
    $authwebs = array();
    // populate the $authwebs with the data from database
    // so no site can be removed
    if ($px_id) {
        foreach ($px_user->webs as $site => $score) {
            $authwebs[$site] = $score;
        }
    }
    foreach ($m->user->webs as $site => $score) {
		if ($score >= PX_AUTH_ADMIN) {
    		if (isset($_POST['u_website_'.$site]) 
                && $_POST['u_website_'.$site] != PX_AUTH_DISABLE) {
    			$authwebs[$site] = $_POST['u_website_'.$site];
				$px_levels[$site] = $_POST['u_website_'.$site];
    		} elseif (isset($_POST['u_website_'.$site]) 
                      && $_POST['u_website_'.$site] == PX_AUTH_DISABLE) {
    			unset($authwebs[$site]);
				unset($px_levels[$site]);
            }
		}    
	}

    // now need to be sure that when the user is admin the level is not changed
    // except the case of the user doing the operation to be root
    if ($px_id) {
        if (!auth::asLevel(PX_AUTH_ROOT)) {
            reset($px_user->webs);
            foreach ($px_user->webs as $site => $score) {
                if ($score >= PX_AUTH_ADMIN) {
                    $authwebs[$site] = $score;
                }
            }     
        }
    }
    
    if ($px_edit_ok) {
    	$px_username = trim($_POST['u_username']); 
    	$px_password = trim($_POST['u_password']);
    	$px_realname = trim($_POST['u_realname']);
    	$px_email    = trim($_POST['u_email']);
    	$px_pubemail = trim($_POST['u_pubemail']);
    }
    if (false !== ($id=$m->saveUser($px_id, $px_username, $px_password, 
                                    $px_realname, $px_email, $px_pubemail, 
                                    $authwebs))
        ) {
		if ($id == $m->user->f('user_id')) {
			header('Location: login.php?logout=1');
			exit; 
		}
		$m->setMessage(__('The author has been successfully saved.'));
		header('Location: users.php');
		exit; 
	}
endif;//token verification	
} 
/* ================================================= *
 *              Remove a user                        *
 * ================================================= */
if (!empty($_POST['delete']) && !empty($px_id)) {
	if ($px_id == 1) {
		$m->setError(__('Error: This user cannot be deleted.'), 400);
	} else {
		if ($px_res->nbRow() != 0) {
			$m->setError(__('Error: This user cannot be deleted.'), 400);
		} else {
			if (false !== $m->delUser($px_id)) {
				$m->setMessage(__('Author successfully deleted.'));
				header('Location: users.php');
				exit; 
			}
		}
	}
}

/* =========================================================== *
 *                      Display block                          *
 * =========================================================== */


/* ================================================= *
 *  Set title of the page, and load common top page  *
 * ================================================= */
$px_title =  __('Authors');
include dirname(__FILE__).'/mtemplates/_top.php';

echo '<h1 id="title_authors">'. __('Authors')."</h1>\n\n";

if (empty($_REQUEST['op']) && empty($_REQUEST['user_id'])) { 
    // list the users
    while(!$px_users->EOF()) {
        $res = $px_users->getListResources(config::f('website_id'));
        if ($px_users->getWebsiteLevel(config::f('website_id')) > 0) {
            $cancel = '';
        } else {
            $cancel = ' cancel';
        }
        echo '<div class="resourcebox'.$cancel.'" id="p'.$px_users->f('user_id').'">';
	echo "\n<p class='resource_title'>";
        if (($px_users->f('user_id') != 1) || auth::asLevel(PX_AUTH_ROOT)) {
            echo '<span class="author_style"><a href="users.php?user_id='.$px_users->f('user_id').'">'.$px_users->f('user_realname').'</a></span>';
        } else {
            echo '<span class="author_style">'.$px_users->f('user_realname').'</span>';
        }
        echo ' ['.$res->nbRow().' '. __('resource(s)').']';
        echo "</p>\n\n";
        echo "\n</div>\n\n";    
        		

		$px_users->moveNext();			
	} 
} else {
    if ($m->user->f('user_id') == $px_id) {
        echo '<p class="message">'. __('Attention, you are modifying your profile. You will be logged out if changes are successfully made.').'</p>'."\n\n";
    }

?>
<h2><?php  echo __('Identity'); ?></h2>

<form action="users.php" method="post" id="formPost">
  <p class="field"><label class="float" for="u_username" style="display:inline"><span class="login_style"><?php  echo __('Login:'); ?></span></label>
  <?php if ($px_edit_ok) { 
            echo form::textField('u_username', 30, 30, $px_username, '', ''); 
        } else {
            echo $px_username;
        }      
  ?>
  </p>
  
  <p class="field"><label class="float" for="u_realname" style="display:inline"><span class="real_name"><?php  echo __('Name:'); ?></span></label>
  <?php if ($px_edit_ok) { 
            echo form::textField('u_realname', 30, 50, $px_realname, '', ''); 
        } else {
            echo $px_realname;
        }      
  ?>
  </p>

  <p class="field"><label class="float" for="u_email" style="display:inline"><span class="private_mail_style"><?php  echo __('Email <span class="small">(not shown)</span>:'); ?></span></label>
  <?php if ($px_edit_ok) { 
            echo form::textField('u_email', 30, 50, $px_email, '', ''); 
        } else {
            echo $px_email;
        }      
  ?>
  </p>  

  <p class="field"><label class="float" for="u_pubemail" style="display:inline"><span class="public_mail_style"><?php  echo __('Public email:'); ?></span></label>
  <?php if ($px_edit_ok) { 
            echo form::textField('u_pubemail', 30, 50, $px_pubemail, '', ''); 
        } else {
            echo $px_pubemail;
        }      
  ?>
  </p>  

<?php if ($px_edit_ok): ?>
  <p class="field"><label class="float" for="u_password" style="display:inline"><span class="password_style"><?php  echo __('Password:'); ?></span></label>
  <?php echo form::textField('u_password', 30, 50, '', '', ''); ?>
  <br /><span class="notification"><?php  echo __('(keep empty not to change it)'); ?></span></p>    
<?php endif; ?>
  
<h2><?php  echo __('Levels'); ?></h2>
  
<?php 
  foreach ($m->user->webs as $site => $score) {
    if ($score >= PX_AUTH_ADMIN) {
	    echo '<p class="field"><label for="u_website_'.$site.'" style="display:inline"><span class="sitename_style">';
        echo sprintf( __('Site <strong>%s</strong>:'), $m->user->wdata[$site]['website_name']);
        echo '</span></label> ';
        if (!isset($px_levels[$site])) $px_levels[$site] = PX_AUTH_DISABLE;
        if ($px_levels[$site] >= PX_AUTH_ADMIN && !$px_edit_ok) {
            echo  __('Administrator');
        } else {
            echo form::combobox('u_website_'.$site, $arry_levels, $px_levels[$site]);
        }
    }    
  }
  if (!empty($px_id)) {
   	 echo form::hidden('user_id',$px_id);
  } else { 
	 echo form::hidden('op','add');
  }
  //To improve security
  echo form::hidden('token', $token);
?>
  </p>
  <p class="button"> <input name="save" type="submit" class="submit" value="<?php  echo __('Save [s]'); ?>"
  accesskey="<?php  echo __('s'); ?>" />&nbsp;  
  <?php
    if ($px_res->nbRow() == 0 && !empty($px_id)) {
	echo '&nbsp;<input name="delete" type="submit" class="submit" '.
	'value="'.  __('Delete [d]').'" accesskey="'.__('d').'" onclick="return '.
	'window.confirm(\''.addslashes( __('Are you sure you want to delete this author?')).'\')" />';
   
  }
  ?>
  </p>
</form>
<?php
}

/*=================================================
 Load common bottom page
=================================================*/
include dirname(__FILE__).'/mtemplates/_bottom.php';

?>
