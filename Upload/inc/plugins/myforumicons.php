<?php

/*
 *	MyForumIcons
 *	Created by Ethan DeLong, modified by Svepu
 *
 *	- File: "{$mybb->settings['bburl']}/inc/plugins/myforumicons.php"
 *
 *  This plugin and its contents are free for use.
 *
 *	Like pokemon? Check out http://www.pokemonforum.org/
 */
 
// Admin settings injection
$plugins->add_hook("admin_formcontainer_output_row", "myforumicons_admin_settings");
$plugins->add_hook("admin_forum_management_add_commit", "myforumicons_admin_settings_save");
$plugins->add_hook("admin_forum_management_edit", "myforumicons_admin_settings_save");

// Inject creation of forum row.
$plugins->add_hook("build_forumbits_forum", "myforumicons_display_icons");
$plugins->add_hook("global_start", "myforumicons_css");

function myforumicons_info()
{
	$modifiedby = 'Modified by <a href="https://github.com/SvePu/MyForumIcons" target="_blank">SvePu</a>';
	return array(
		'name'			=> 'MyForumIcons',
		'description'	=> "Lets you implement custom icons for your forums.<br />".$modifiedby,
		'website'		=> 'http://www.pokemonforum.org',
		'author'		=> 'Ethan',
		'authorsite'	=> 'http://www.pokemonforum.org',
		'version'		=> '1.1',
		'codename'		=> 'myforumicons',
		'compatibility' => '18*'
	);
}

function myforumicons_install()
{
	global $db, $lang;
	$lang->load('config_myforumicons');
	$query_add = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query_add, "rows");
	$settingsgroup = array(
		"name" 			=>	"myforumicons_settings",
		"title" 		=>	$db->escape_string($lang->myforumicons_settings_title),
		"description" 	=>	$db->escape_string($lang->myforumicons_settings_title_desc),
		"disporder"		=> 	$rows+1,
		"isdefault" 	=>  0
	);
	$db->insert_query("settinggroups", $settingsgroup);
	$gid = $db->insert_id();
	
	$settings_1 = array(
		'name'			=> 'myforumicons_max_size',
		'title'			=> $db->escape_string($lang->myforumicons_max_size_title),
		'description'  	=> $db->escape_string($lang->myforumicons_max_size_title_desc),
		'optionscode'  	=> 'numeric',
		'value'        	=> 30,
		'disporder'		=> 1,
		"gid" 			=> (int)$gid
	);
	$db->insert_query('settings', $settings_1);
	
	$settings_2 = array(
		'name'			=> 'deldataonuninstall',
		'title'			=> $db->escape_string($lang->deldataonuninstall_title),
		'description'  	=> $db->escape_string($lang->deldataonuninstall_title_desc),
		'optionscode'  	=> 'yesno',
		'value'        	=> 0,
		'disporder'		=> 2,
		"gid" 			=> (int)$gid
	);
	$db->insert_query('settings', $settings_2);
	rebuild_settings();
	
	if(!$db->field_exists('myforumicons_icon', 'forums'))
	{
		$db->add_column('forums', 'myforumicons_icon', 'TEXT NOT NULL');
	}
}

function myforumicons_is_installed()
{
	global $mybb;
    if($mybb->settings['myforumicons_max_size'])
    {
        return true;
    }
    return false;
}

function myforumicons_uninstall()
{
	global $db, $mybb;
	if ($mybb->settings['deldataonuninstall'] == 1)
	{
		if($db->field_exists('myforumicons_icon', 'forums'))
		{
			$db->drop_column('forums', 'myforumicons_icon');
		}
	}	
	
	$result = $db->simple_select('settinggroups', 'gid', "name = 'myforumicons_settings'", array('limit' => 1));
	$group = $db->fetch_array($result);
	
	if(!empty($group['gid']))
	{
		$db->delete_query('settinggroups', "gid='{$group['gid']}'");
		$db->delete_query('settings', "gid='{$group['gid']}'");
		rebuild_settings();
	}
}

function myforumicons_activate()
{
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("forumbit_depth2_forum", "#".preg_quote("forum_{\$lightbulb['folder']} ajax_mark_read\" title=\"{\$lightbulb['altonoff']}\" id=\"mark_read_{\$forum['fid']}\" ></span>")."#i", "forum_{\$lightbulb['folder']} {\$forum['myforumicon_class']}ajax_mark_read\" title=\"{\$lightbulb['altonoff']}\" id=\"mark_read_{\$forum['fid']}\" {\$forum['myforumicon_override']}></span>");
	find_replace_templatesets("headerinclude", '#{\$stylesheets}(\r?)\n#', "{\$stylesheets}\n{\$forumicon_css}\n");
}

function myforumicons_deactivate()
{
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("forumbit_depth2_forum", "#".preg_quote("{\$forum['myforumicon_class']}ajax_mark_read\" title=\"{\$lightbulb['altonoff']}\" id=\"mark_read_{\$forum['fid']}\" {\$forum['myforumicon_override']}></span>")."#i", "ajax_mark_read\" title=\"{\$lightbulb['altonoff']}\" id=\"mark_read_{\$forum['fid']}\" ></span>");
	find_replace_templatesets("headerinclude", '#{\$forumicon_css}(\r?)\n#', "", 0);
}

function myforumicons_display_icons($forum)
{
	global $mybb, $theme;
	$forum['myforumicon_class'] = "";
	if(!empty($forum['myforumicons_icon']))
	{
		$icon_path = str_replace("{theme}", $theme['imgdir'], $forum['myforumicons_icon']);
		if (@getimagesize($icon_path))
		{
			list($width, $height, $type, $width_height) = getimagesize($icon_path);
			$imgwidth = 'width:'.$width.'px;';
			$imgheight = 'height:'.$height.'px;';
			$maximum = $mybb->settings['myforumicons_max_size'];
			if(!empty($maximum) && $maximum > 0)
			{
				if ($width > $maximum || $height > $maximum)
				{
					if ($width > $maximum)
					{
						$imgwidth = 'width:'.$maximum.'px;';
						$imgheight = 'height:'.intval((int)$height/(int)$width*$maximum).'px;';
					}
					if ($height > $maximum)
					{
						$imgwidth = 'width:'.intval((int)$width/(int)$height*$maximum).'px;';
						$imgheight = 'height:'.$maximum.'px;';
					}						
				}
			}
			
			$forum['myforumicon_override'] = ' style="background:url('.$icon_path.') no-repeat 0;'.$imgwidth.$imgheight.'background-size: contain;"';
			$forum['myforumicon_class'] = "myforumicon ";
		}
	}
	return $forum;
}

function myforumicons_css()
{	
	global $forumicon_css;
	
	$forumicon_css = "";
	if(my_strpos($_SERVER['PHP_SELF'], 'index.php') || my_strpos($_SERVER['PHP_SELF'], 'forumdisplay.php'))
	{
		$forumicon_css = "<style type=\"text/css\">.forum_on.myforumicon {opacity: 1}.forum_off.myforumicon {opacity: .5}</style>";
	}
}
	
function myforumicons_admin_settings(&$pluginargs)
{
	global $form, $form_container, $forum_data, $lang, $mybb;
	
	if($mybb->input['module'] == 'forum-management')
	{
		if($pluginargs['title'] == $lang->display_order)
		{
			$lang->load('forum_management_myforumicons');
			$form_container->output_row($lang->forum_icons, $lang->forum_icons_desc, $form->generate_text_box('myforumicons_icon', $forum_data['myforumicons_icon'], array('id' => 'myforumicons_icon')));
		}
	}
}

function myforumicons_admin_settings_save()
{
	global $db, $fid, $mybb;
	
	if($mybb->request_method == "post")
	{
		$db->update_query("forums", array("myforumicons_icon" => $db->escape_string($mybb->input['myforumicons_icon'])), "fid='{$fid}'");
	}
}