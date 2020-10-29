<?php

 /*     This file is part of StatusFeed

    Status Feed is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Status Feed is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Status Feed.  If not, see <http://www.gnu.org/licenses/>.
*/
// Disallow direct access to this file for security reasons


/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

/* Credits: 
	Statusfeed is a huge project spanning many years of development, testing, and use. 
	With that said, I will take a moment to thank a few users in particular who helped make
	this plugin possible. 

	MyAlerts integration: MyBB Thank You/Like plugin: https://github.com/mybbgroup/Thank-you-like-system/blob/master/inc/plugins/thankyoulike.php
		By Eldenroot, Whiteneo, G33k, and others. 

	Statusfeed Myalerts integration was based upon the implementation used in the aforementioned plugin. 
	This was done with permission, a special thanks to WhiteNeo for allowing us to use their implementation. 

	A small snippit of code from MyProfile was used to handle status reports. https://github.com/mohamedbenjelloun/MyProfile

	A special thanks to Eldenroot, WhiteNeo, and Omar G for random help during development, 
	and for feedback during the development process. 
*/ 

if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed. Please make sure IN_MYBB is defined.");
}
global $mybb, $templatelist, $statusfeed_parser_options;

require_once("statusfeed/ajax_multipage.php");
require_once("statusfeed/install_functions.php");
require_once("statusfeed/myalerts_functions.php");

// Global parser options (reused across multiple functions)
$statusfeed_parser_options = array(
	'allow_html' => 0,
	'allow_mycode' => 1,
	'allow_smilies' => 1,
	'allow_imgcode' => 0,
	'filter_badwords' => 1,
	'nl2br' => 0
); 

// Add hooks if Statusfeed is enabled using plugin settings
if (isset($mybb->settings['statusfeed_enabled']) && $mybb->settings['statusfeed_enabled'] == 1) {
	global $lang;
	$lang->load('statusfeed');
	if ($mybb->settings['statusfeed_enabled_profile'] == 1) {
		$plugins->add_hook('member_profile_start', 'statusfeed_profile');
	}
	if ($mybb->settings['statusfeed_enabled_portal'] == 1) {
		$plugins->add_hook('portal_start', 'statusfeed_portal');
	}

	$plugins->add_hook("misc_start", "statusfeed_requestController");
    $plugins->add_hook('index_start', 'statusfeed_index');
	$plugins->add_hook('usercp_start', 'statusfeed_usercp');
	$plugins->add_hook('global_start', 'statusfeed_alert');	
	$plugins->add_hook('global_start', 'statusfeed_no_online');

	// Reports (inspired by MyProfile)
	$plugins->add_hook("report_type", "statusfeed_report_type");
	$plugins->add_hook("modcp_reports_report", "statusfeed_modcp_reports_report");

	$plugins->add_hook("postbit", "statusfeed_postbit");
	$plugins->add_hook("postbit_pm", "statusfeed_postbit");
	$plugins->add_hook("postbit_announcement", "statusfeed_postbit");

	// Register alert formatter.
	// Based on Thank You/Like implementation. See myalerts_function.php 
	// Original TYL plugin: https://github.com/mybbgroup/Thank-you-like-system/blob/master/inc/plugins/thankyoulike.php

	statusfeed_myalerts_formatter_load();

	$templatelist .=",statusfeed_likeButton,statusfeed_post_full,statusfeed_portal,statusfeed_profile,statusfeed_post_mini,statusfeed_comment_mini,statusfeed_comment_full,statusfeed_comments_container,statusfeed_notifications_container,statusfeed_all,statusfeed_notification";
}

function statusfeed_info() {
	global $lang;
	$lang->load('statusfeed');

	return array(
		"name"			=> $lang->statusfeed_name,
		"description"	=> $lang->statusfeed_desc,
		"website"		=> "http://makestation.net",
		"author"		=> "Darth-Apple",
		"authorsite"	=> "http://makestation.net",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "18*"
	);
}

/* **************************
	ALL ACTIVATE/DEACTIVATE AND INSTALL/UNINSTALL FUNCTIONS ARE WITHIN
	statusfeed/install_functions.php
*  *************************/

	// This function checks what kind of request we are trying to serve. 
	// It directs the request to the correct function afterwards. 

	// Handles WOL functionality and disables it for certain ajax functions. 
	function statusfeed_no_online() {
		global $mybb; 
		if ($mybb->input['action'] == "getLikesPopup") {
			define("NO_ONLINE", 1);
		}
	}

	function statusfeed_requestController () {
		global $mybb, $db, $lang;

		if (($mybb->input['action'] == "update_status") && ($mybb->request_method=="post")) {
			statusfeed_push_status(); 
		}

		else if (isset($mybb->input["action"]) && $mybb->input['action'] == "edit_status") {
			statusfeed_edit_push();
		}

		else if (isset($mybb->input["action"]) && $mybb->input['action'] == "report") {
			statusfeed_report_push();
		}

		else if (isset($mybb->input['action']) && $mybb->input['action'] == "like") {
			if ($mybb->settings['statusfeed_likes_enable'] == 1) {
				statusfeed_pushlike();
			} else {
				echo statusfeed_jgrowl($lang->statusfeed_likes_not_enabled);
				exit; 
				// error($lang->statusfeed_likes_not_enabled);
			}
		}

		else if (isset($mybb->input['action']) && $mybb->input['action'] == "report") {
			statusfeed_generate_report($mybb->input['statusid']);
		}
		
		else if (isset($mybb->input["action"]) && $mybb->input['action'] == "statusfeed_delete_status") {
			statusfeed_delete_status();
		}

		else if ($mybb->input['action'] == "statusfeed_popup" && (isset($mybb->input['uid']) && !empty((int) $mybb->input['uid']))) {
			statusfeed_popup();
		}

		else if (isset($mybb->input['action']) && $mybb->input['action'] == "getLikesPopup") {
			statusfeed_getLikesPopup ();
		}
	}


	function statusfeed_profile ($altTemplate="statusfeed_profile", $ajax=false) {
		global $mybb, $templates, $statusfeed_profile, $db, $lang;
		require_once MYBB_ROOT."/inc/class_parser.php";

		$parser = new postParser(); 
		$parser_options = array(
    			'allow_html' => 0,
    			'allow_mycode' => 1,
    			'allow_smilies' => 1,
    			'allow_imgcode' => 0,
    			'filter_badwords' => 1,
    			'nl2br' => 1
		);

		if (!isset($altTemplate) || empty($altTemplate)) {
			$altTemplate = "statusfeed_profile"; // Bug fix. 
		}
	
		if (isset($mybb->input['uid'])) {
			$profile_UID = (int)$mybb->input['uid'];			
		}	
		else {
			$profile_UID = (int)$mybb->user['uid']; // if no UID is defined, user is viewing profile of self. 
		}	

		$avatar_size = $mybb->settings['statusfeed_avatarsize_full'];
		if (isset ($mybb->input['status_mode']) && $mybb->input['status_mode'] == "edit") {
			statusfeed_edit ();
			return;
		}
		
		// define the number of rows per page. If no value is defined, default to 10. 
		if ($mybb->settings['statusfeed_rowsperpage'] != 0 && (int)$mybb->settings['statusfeed_rowsperpage'] != null) {
			$rowsperpage = (int)$mybb->settings['statusfeed_rowsperpage'];
		}
		else {
			$rowsperpage = 10;
		}
		
		if (isset($mybb->input['page'])) {
			$mybb->input['page'] = (int) $mybb->input['page'];
		}
		
		$query = $db->simple_select("statusfeed", "COUNT(PID) AS nodes", "wall_id = '$profile_UID' AND shown=1 AND parent < 1");
		$numrows = $db->fetch_field($query, "nodes");
		$totalpages = ceil($numrows / $rowsperpage);
		
		if (isset($mybb->input['page']) && is_numeric($mybb->input['page'])) {
			$currentpage = (int) $mybb->input['page'];
		} else {
			$currentpage = 1;
		} 

		if ($currentpage > $totalpages) {
			$currentpage = $totalpages;
		} 
		if ($currentpage < 1) {
			$currentpage = 1;
		}
		 
		$offset = ($currentpage - 1) * $rowsperpage;			
		$query = $db->query("
			SELECT 
				s.*, 
				u.username AS fromusername,
				u.avatar,
				w.username AS tousername
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
			LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
			WHERE shown=1 AND wall_id = $profile_UID AND parent < 1
			ORDER BY PID DESC
			LIMIT $offset, $rowsperpage
		");		
		$data = array();
		$count = 0;
		
		while($querydata = $db->fetch_array($query)) {
			if($querydata['parent'] > -1) {
				continue; // these are comments to statuses, and don't need to be loaded now. 
			}

			$options['style'] = "full";
			$options['expanded'] = false;
			if ((isset($mybb->input['expanded'])) && ($mybb->input['expanded'] == $querydata['PID'])) {
				$options['expanded'] = true; 
			}			
			
			$feed .= statusfeed_render_status($querydata, $options);
			$count++;	
		}
		
		if ($count == 0) {
			$feed = "<tr><td><div class='pm_alert sf_nonefound'>".$lang->statusfeed_none_found."</div></td></tr>";
		}
		if ($totalpages > 0) {
			$pagination = multipage_ajax($numrows, $rowsperpage, $currentpage, "member.php?action=profile&uid=$profile_UID", $profile_UID);
		}		
		
		$status_updates = $feed;
		eval("\$statusfeed_profile = \"".$templates->get($altTemplate)."\";");

		if ($ajax == true) {
			echo $statusfeed_profile; // Handle pagination with ajax. 
		}

		return $statusfeed_profile;
	}
	
	// Wrapper that pulls from the profile box and generates a list of statuses. 
	function statusfeed_index () {
		global $statusfeed, $templates, $db, $mybb, $lang;

		if ($mybb->settings['statusfeed_enabled_index']) {
			$statusfeed = statusfeed_portal("full");
		} else {
			$statusfeed = "";
		}
	}

	function statusfeed_portal ($altTemplate=false) {
		global $templates, $statusfeed, $db, $mybb, $lang;
		require_once MYBB_ROOT."/inc/class_parser.php";

		$parser = new postParser(); 
		$parser_options = array(
    			'allow_html' => 0,
    			'allow_mycode' => 1,
    			'allow_smilies' => 1,
    			'allow_imgcode' => 0,
    			'filter_badwords' => 1,
    			'nl2br' => 1
		);

		$feed = "";
		if ($altTemplate == "full") {
			$templateSuffix = "_full";
			$statusStyle = "full";
			$options['style'] = "full";
			$profile_UID = -1; // Bug fix. 
			$avatar_size = $mybb->settings['statusfeed_avatarsize_mini']; // We still use the mini avatar, even if using an alternative template. 
		} 
		else {
			$options['style'] = "mini";
			$templateSuffix = "_mini";
			$statusStyle = "mini";
			$avatar_size = $mybb->settings['statusfeed_avatarsize_mini'];
		}

		// define the number of rows per page. If no value is defined, default to 10. 
		$rowsperpage = 10;
		if ($mybb->settings['statusfeed_rowsperpage'] != 0 && (int)$mybb->settings['statusfeed_rowsperpage'] != null) {
			$rowsperpage = (int)$mybb->settings['statusfeed_rowsperpage'];
		} 

		$query = $db->simple_select("statusfeed", "COUNT(PID) AS nodes", "shown=1 AND (UID = wall_id) AND (parent = -1)");
		$numrows = $db->fetch_field($query, "nodes");
		$totalpages = ceil($numrows / $rowsperpage);
		if (isset($mybb->input['comment_page']) && is_numeric($mybb->input['comment_page'])) {
			$currentpage = (int) $mybb->input['comment_page'];
		} else {
			$currentpage = 1;
		} 

		if ($currentpage > $totalpages) {
			$currentpage = $totalpages;
		} 
		if ($currentpage < 1) {
			$currentpage = 1;
		} 	
		$offset = ($currentpage - 1) * $rowsperpage;			
		
		$query = $db->query("
			SELECT 
				s.*, 
				u.username AS fromusername,
				u.avatar,
				w.username AS tousername
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
			LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
			WHERE shown=1 AND (s.UID = s.wall_id) AND (s.parent = -1)
			ORDER BY PID DESC
			LIMIT $offset, $rowsperpage
		");		
		
		$data = array();
		$count = 0;
		
		while($querydata = $db->fetch_array($query)) {	
			if ($querydata['parent'] > 0) {
				continue; // no need to fetch replies to statuses. These are fetched by ajax on demand. 
			}

			$options['expanded'] = false;
			if ((isset($mybb->input['expanded'])) && ($mybb->input['expanded'] == $querydata['PID'])) {
				$options['expanded'] = true; 
			}
			
			$feed .= statusfeed_render_status($querydata, $options);
			$count++;		
		}
		
		if ($count == 0) {
			$feed = "<tr><td><div class='pm_alert sf_nonefound'>".$lang->statusfeed_none_found."</div></td></tr>";

		}
		$status_updates = $feed;
			
		// $pagination = multipage($numrows, $rowsperpage, $currentpage, "member.php?action=profile&uid=$profile_UID");
		$statusfeed_viewall = '<center><a href="statusfeed.php">'.$lang->statusfeed_view_all_updates.'</a></center>';
		eval("\$statusfeed = \"".$templates->get("statusfeed_portal")."\";");
		return $statusfeed;
	}


	// Inserts a status or comment into the database. 
	function statusfeed_push_status() {
		global $mybb, $db, $lang;
		
		if (($mybb->input['action'] == "update_status") && ($mybb->request_method=="post")) {
			verify_post_check($mybb->input['post_key']);
			
			if (($mybb->user['uid'] == 0) || !isset ($mybb->user['uid'])) {
				// error($lang->statusfeed_guest);
				echo statusfeed_jgrowl($lang->statusfeed_guest);
				exit;
			}	
		
			if ((strlen($mybb->input['status']) > $mybb->settings['statusfeed_maxlength']) || strlen($mybb->input['status']) > 1024) {
				//error($lang->statusfeed_comment_too_long);
				echo statusfeed_jgrowl($lang->statusfeed_comment_too_long);
				exit; 
			}
			
			$user = (int) $mybb->user['uid'];
			$status = htmlspecialchars($db->escape_string($mybb->input['status']), ENT_QUOTES); // Yep. Sanitize this thing. 
			
			$wall_id = null; // initialize to avoid PHP notices. 

			// Check to make sure we have the required parameters. 
			if(isset($mybb->input['wall_id']) && ($mybb->request_method=="post")) {
				$wall_id = (int)$mybb->input['wall_id']; 
				if ($wall_id == (int) $mybb->user['uid']) {
					$self = 1; // We are posting to our own wall. 
				}
				else {
					$self = 0; // This message is going to someone else. 
				}
				if ($wall_id == null) {
					// error($lang->statusfeed_generic_error); // wall ID was defined, but was not an integer.
					echo statusfeed_jgrowl($lang->statusfeed_generic_error);
					exit; 
				}
			}
			
			// We aren't making a reply. Make this post on our own wall. 
			if ($mybb->input['reply_id'] < 0) {
				$wall_id = (int) $mybb->user['uid']; // no specific wall ID defined, post to the poster's wall. 
				$self = 1;
			}
			/*else {
				// We aren't posting this on a wall. Set to null until set later. 
					$wall_id = null; 
			}*/
		
			
			// If this is a comment to a user's status, need to create a notification for the author. 
			if(($mybb->input['reply_id'] > 0) && ($mybb->request_method=="post")) {
				$self = 0;
				$reply_id = (int)$mybb->input['reply_id'];
				$query = $db->query("
					SELECT u.uid
					FROM ".TABLE_PREFIX."statusfeed s
					LEFT JOIN " . TABLE_PREFIX . "users u ON u.uid = s.UID
					WHERE PID=".(int) $reply_id);
				
				$data = array();
				$querydata = $db->fetch_array($query);

				// Add the author of the status to notification receivers. 
				$notification_receiver = (int) $querydata['uid'];
				$wall_id = $notification_receiver;		
			}
			else {
				$reply_id = -1; // This status is not a reply to another status.  
				$notification_receiver = $wall_id; // Add a notification to the user's wall where the status was posted.
			}

			// Insert the actual status into the statusfeed. 

			$inserts = array(
				'status' => $status, // Sanitized already
				'UID' => (int) $mybb->user['uid'],
				'shown' => 1,
				'wall_id' => (int) $wall_id,
				'self' => (int) $self,
				'parent' => (int) $reply_id,
				'date' => time()
				);

			$db->insert_query('statusfeed', $inserts); // insert status
			$insert_ID = (int) $db->insert_id('statusfeed', 'PID');

			// Generate some alerts if this is a post on someone's profile. 
			
			// if ($mybb->settings['statusfeed_alerts_enable'] == 1) {
				if (($reply_id < 0) && ($notification_receiver != $mybb->user['uid'])) {
					if (($mybb->user['uid'] != $wall_id)) { // user is commenting on someone else's profile. 
						
						// Insert a native alert for this status. 
						if ($mybb->settings['statusfeed_alerts_enable'] == 1) {
							$inserts = array(
								'sid' => $insert_ID, 
								'uid' => (int) $mybb->user['uid'],
								'to_uid' => (int) $wall_id,
								'type' => 0,
								'date' => time()
							);
							$db->insert_query('statusfeed_alerts', $inserts); // insert alert for status. OLD: wall ID
							$db->query("UPDATE ".TABLE_PREFIX."users SET sf_unreadcomments=sf_unreadcomments+1 WHERE uid=".(int) $wall_id); 
						}

						// Process MyAlerts if MyAlerts is enabled. 
						// See statusfeed/myalerts_functions.php for more information and for credits. 

						else if ($mybb->settings['statusfeed_alerts_enable'] == 2) {
							$a_status['uid'] = (int) $notification_receiver; // To_uid? 
							$a_status['tid'] = 2; // Placeholder
							$a_status['pid'] = (int)$insert_ID; // Status ID
							$a_status['subject'] = $lang->statusfeed_myalert_title_wall;  
							$a_status['comment'] = 0; // Placeholder
							statusfeed_recordAlertStatus($a_status); 
						}
					}
				}
				
				else if (($reply_id > 0) && ($notification_receiver != $mybb->user['uid'])){ // user is commenting on a status
					$statusID = (int) $db->insert_id('statusfeed', 'PID');

					// Native alerts
					if ($mybb->settings['statusfeed_alerts_enable'] == 1 ) { 

						$inserts = array(
							'sid' => $statusID, 
							'parent' => (int) $reply_id,
							'uid' => (int) $mybb->user['uid'],
							'to_uid' => (int) $notification_receiver,
							'type' => 1,
							'date' => time()
						);

						// Insert alert and update count. 
						$db->insert_query('statusfeed_alerts', $inserts); 
						$db->query("UPDATE ".TABLE_PREFIX."users SET sf_unreadcomments=sf_unreadcomments+1 WHERE uid=".(int) $notification_receiver);
					}

					// Process MyAlerts
					else if ($mybb->settings['statusfeed_alerts_enable'] == 2) {
						$a_status['uid'] = (int) $notification_receiver; // To_uid? 
						$a_status['tid'] = 2; // Placeholder
						$a_status['pid'] = (int)$insert_ID; // Status ID
						$a_status['subject'] = $lang->statusfeed_myalert_title_comment;  
						$a_status['comment'] = 1; // Placeholder
						statusfeed_recordAlertStatus($a_status); 						
					}
					$querydata = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed_alerts WHERE parent = ".(int) $reply_id." AND marked_read = 0");
					
					$notifications_cache_existing = array();
					while ($querydata = $db->fetch_array($query)) {
						if ($querydata['type'] == 2) {
							$notifications_cache_existing[] = (int) $querydata['to_uid']; // avoid creating multiple notifications. 
						}
					}
					
					// Get maximum comments and make sure not to select more. 
					$select_limit = intval($mybb->settings['statusfeed_max_comments']) ? "50" : intval($mybb->settings['statusfeed_max_comments']); 
					$query = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed WHERE parent=".(int) $reply_id." LIMIT $select_limit");
					$notifications_cache = array($notification_receiver); // create an array, insert the author of the parent status. 

					while($querydata = $db->fetch_array($query)) {
						if ((!in_array((int)$querydata['UID'], $notifications_cache)) && (!in_array((int) $querydata['UID'], $notifications_cache_existing))) {

							// Version 1.1 todo: Improve the performance of this. 
							$notification_receiver = (int) $querydata['UID'];
							if ($notification_receiver != $mybb->user['uid']) {	
								$db->query("UPDATE ".TABLE_PREFIX."users SET sf_unreadcomments=sf_unreadcomments+1 WHERE uid=".(int) $notification_receiver);
							}

							$inserts = array(
								'sid' => (int) $db->insert_id('statusfeed', 'PID'), 
								'parent' => (int) $reply_id,
								'uid' => (int) $mybb->user['uid'],
								'to_uid' => (int) $notification_receiver,
								'type' => 2,
								'date' => time()
							);

							if ($notification_receiver != $mybb->user['uid']) {
								if ($mybb->settings['statusfeed_alerts_enable'] == 1) {
									$db->insert_query('statusfeed_alerts', $inserts); // insert alert for status
								} 
								else if ($mybb->settings['statusfeed_alerts_enable'] == 2) {
									$a_status['uid'] = (int) $notification_receiver; // To_uid? 
									$a_status['tid'] = 2; // Placeholder
									$a_status['pid'] = (int)$insert_ID; // Status ID
									$a_status['subject'] = $lang->statusfeed_myalert_title_also_comment;  
									$a_status['comment'] = 1; // Placeholder
									statusfeed_recordAlertStatus($a_status); 									
								}
									$notifications_cache[] .= $notification_receiver; // prevent inserting multiple notifications to one user.  
							}
						} 
					}			
					// $db->query("UPDATE ".TABLE_PREFIX."users SET sf_unreadcomments=sf_unreadcomments+1 WHERE uid=".(int) $notification_receiver); 
				}
		// 	} // End native alerts

			if($reply_id != -1) {
				$db->query("UPDATE ".TABLE_PREFIX."statusfeed SET numcomments=numcomments+1 WHERE PID=$reply_id");
			}

			// If the user is posting to their own wall, update their latest status to display on posts. 
			if (($self == 1) && $reply_id == -1) {
				$postbitInserts = array('sf_currentstatus' => $status); 
				$db->update_query('users', $postbitInserts, 'uid = ' . (int) $mybb->user['uid'], 1);				
			}

			// Legacy support for previous non-ajax method. Developers: Please report a bug if you get redirected! 
			if ($mybb->input['redirect'] == "statusfeed") {
				redirect("statusfeed.php?expanded=$insert_ID#status_".(int)$insert_ID, $lang->statusfeed_update_success); // bug fix
			}

			// We're doing a comment. 
			else if ($reply_id != -1) {
				$url = $_SERVER['HTTP_REFERER'];
				$query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
				$url .= ($query ? '&' : '?') . 'expanded='.(int) $reply_id; // properly append URL
				$url .= "#status_".(int) $reply_id;

				if (isset($mybb->input['ajaxpost'])) {
					echo statusfeed_render_comments(true, "full", $reply_id,  1, $insert_ID);
					echo statusfeed_jgrowl($lang->statusfeed_comment_success);
				} else {
					redirect(htmlspecialchars($url, ENT_QUOTES), $lang->statusfeed_update_success); // bug fix
				}
			}

			// We're doing a status. 
			else {
				if (isset($mybb->input['ajaxpost'])) {
					echo statusfeed_get_ajax_SID($insert_ID);
					echo statusfeed_jgrowl($lang->statusfeed_update_success);
				} else {
					redirect(htmlspecialchars($_SERVER['HTTP_REFERER']."#status_".(int) $insert_ID, ENT_QUOTES), $lang->statusfeed_update_success);
				}
			}
		}
	}

	function statusfeed_edit () {
		global $templates, $statusfeed, $mybb, $db, $lang;
		
		if (!isset($mybb->input['status_id'])) {
			// error($lang->statusfeed_no_comment);
			echo statusfeed_jgrowl($lang->statusfeed_no_comment);
		}
		$ID = (int)$mybb->input['status_id'];
		
		// OLD: if (!isset($mybb->input['uid'])) {
			// error("no user defined");
			// die();
		// }
		$UID = (int)$mybb->input['uid'];		
		if (sf_moderator_confirm_permissions($mybb->user['usergroup'], $mybb->user['additionalgroups'], $ID) == false) {
			// error($lang->statusfeed_permission_denied);
			echo statusfeed_jgrowl($lang->statusfeed_permission_denied);
		}

		$query = $db->query("
			SELECT *
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON u.uid = s.UID
			WHERE PID=$ID 
		");

		// $data = array();
		$count = 0;
		while($querydata = $db->fetch_array($query)) {		
			$status = $querydata['status'];
			$parent = $querydata['parent'];
		}
		
		eval("\$statusfeed = \"".$templates->get("statusfeed_edit")."\";");
		return $statusfeed;
	}
	
	function statusfeed_edit_push () {
		global $templates, $statusfeed, $mybb, $db, $lang;
		verify_post_check($mybb->input['post_key']);
		
		if ($mybb->request_method != "post") {
			echo statusfeed_jgrowl($lang->statusfeed_generic_error);
			exit; 
		}	
		
		if ((strlen($mybb->input['status']) > $mybb->settings['statusfeed_maxlength']) || strlen($mybb->input['status']) > 1024) {
			// error($lang->statusfeed_comment_too_long);
			echo statusfeed_jgrowl($lang->statusfeed_comment_too_long);
			exit; 
		}

		else {
			$user = (int)$mybb->user['uid'];
			$status = htmlspecialchars($db->escape_string($mybb->input['status']));
		
			if (!isset($mybb->input['ID'])) {
				// error($lang->statusfeed_no_user);
				echo statusfeed_jgrowl($lang->statusfeed_no_user);
				exit; 
			}
			
			$ID = (int)$mybb->input['ID'];
			if (!isset($mybb->input['UID'])) {
				echo statusfeed_jgrowl($lang->statusfeed_no_user);
				exit; 
			}
			
			$UID = (int)$mybb->input['UID']; // for redirect purposes
			if (sf_moderator_confirm_permissions($mybb->user['usergroup'], $mybb->user['additionalgroups'], $ID) == false) {
				echo statusfeed_jgrowl($lang->statusfeed_permission_denied);
				exit;
				// user does not have permission to edit this status
			}
			
			// Edit the value that is stored for the postbit. Make sure it displays the correct new value. 
			// First, we need to get the uid from the announcement that we are updating. 
			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."statusfeed s
				LEFT JOIN " . TABLE_PREFIX . "users u ON u.uid = s.UID
				WHERE PID=$ID 
			");
			
			$querydata = $db->fetch_array($query);
			$mostRecent = getMostRecent((int) $querydata['UID']); // Make sure that, for this user, the status we updated is the most recent one. 
			
			if ($mostRecent['PID'] == (int) $querydata['PID']) {
				$postbitInserts = array(
					'sf_currentstatus' => $status // Already sanitized
				);
				$db->update_query('users', $postbitInserts, 'uid = ' . (int) $mostRecent['UID'], 1);				
			}

			// Now we need to see if this is a reply or if this is a root-level status. We create the redirect as such.
			$urlAppend = "";
			if ($querydata['parent'] != "-1" && $querydata['parent'] != 0) {
				$urlAppend = "&parent_status=" . (int) $querydata['parent'];
			}

			$values['status'] = $status;
			$db->update_query('statusfeed', $values, 'PID = ' . $ID, 1);
			
			if (!isset($mybb->input['ajaxedit'])) {
				redirect("statusfeed.php?sid=$ID&expanded=true".$urlAppend, $lang->statusfeed_edit_success);
			} else {
				echo statusfeed_jgrowl($lang->statusfeed_edit_success);

				if ($querydata['parent'] != "-1") {
					// Return the edited comment.
					//echo statusfeed_jgrowl( "Annnnddd the ID is: " . $ID . " and the parent is: " . $querydata['parent']);
					echo statusfeed_get_ajax_comment($querydata['parent'], $ID); // OLD. reverse parameters. 
				} else {
					// Return the edited status. 
					echo statusfeed_get_ajax_SID($ID);
				}
			}
		}	
	}
	
	function statusfeed_delete_status () {
		global $templates, $statusfeed, $mybb, $db, $lang;
		verify_post_check($mybb->input['post_key']);
		
		$ID = (int)$mybb->input['ID'];
		
		if (!isset($mybb->input['ID'])) {
			// error($lang->statusfeed_no_comment);
			echo statusfeed_jgrowl($lang->statusfeed_no_comment);
			exit; 
		}
		
		/* if ($mybb->request_method != "post") {
			// error ($lang->statusfeed_generic_error);
			echo statusfeed_jgrowl($lang->statusfeed_generic_error);
			exit;
		}	*/
		
		
		if((isset($mybb->input['reply_id'])) && ($mybb->input['reply_id'] > 0)) {
			$reply_id = (int)$mybb->input['reply_id'];
		}
		else {
			$reply_id = null;
		}

		/*if (!isset($mybb->input['UID'])) {
			// error($lang->statusfeed_no_user);
			echo statusfeed_jgrowl($lang->statusfeed_no_user);
			exit; 
		}
		$UID = (int)$mybb->input['UID'];*/ 
		if (sf_moderator_confirm_permissions($mybb->user['usergroup'], $mybb->user['additionalgroups'], $ID) == false) {
			// error($lang->statusfeed_statusfeed_permission_denied); // user does not have permission to delete this status
			echo statusfeed_jgrowl($lang->statusfeed_statusfeed_permission_denied); // user does not have permission to delete this status
			exit; 
		}

		// Get the announcement's UID for the announcement we must delete. 
		$userQuery = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed WHERE PID = ".(int)$mybb->input['ID'].";");
		$userOfAnnouncement = $db->fetch_array($userQuery);
		
		// Now we need to get rid of the most recent status in the user's postbit and reset it. 
		$mostRecent = getMostRecent((int) $userOfAnnouncement['UID']);
		$deletedMostRecent = false;

		// We need to check if this announcement if the ID of the most recent announcement we fetched is the same as the ID of what we deleted. 
		if ($mostRecent['PID'] == (int) $mybb->input['ID']) {
			$deletedMostRecent = true;
			$postbitInserts = array('sf_currentstatus' => "");
			$db->update_query('users', $postbitInserts, 'uid = ' . (int) $mostRecent['UID'], 1);				
		}
		
		// Delete a comment and decrement the number of replies. 
		$db->delete_query("statusfeed", "PID = $ID", 1);
		if(isset($reply_id)) {
			echo ("We're resetting the numcomments as we should..."); // Remove This
			$db->query("UPDATE ".TABLE_PREFIX."statusfeed SET numcomments=numcomments-1 WHERE PID=".(int) $reply_id); // fix comment count. 
		}
		else if (($reply_id < 1) || (!isset($reply_id))) {
			$db->delete_query("statusfeed", "parent = $ID", 1); // if a status update is being deleted, delete all replies. 
		}

		// Now that we've deleted the old most recent postbit status, we need to fetch the new one and set it accordingly. 
		$newMostRecent = getMostRecent((int) $userOfAnnouncement['UID']);

		// Make sure we have a recent status to push. Otherwise, leave it blank as set before. 
		if (isset($newMostRecent['status']) && $deletedMostRecent == true) {
			$postbitInserts = array(
				'sf_currentstatus' => $db->escape_string($newMostRecent['status']) // Already sanitized
			);
			$db->update_query('users', $postbitInserts, 'uid = ' . (int) $mostRecent['UID'], 1);
		}
		// if (!isset($mybb->input['ajaxdelete'])) {
		//	redirect("statusfeed.php", $lang->statusfeed_delete_success);
		// } else {
			echo statusfeed_jgrowl($lang->statusfeed_delete_success);
		// }
	}
	
	function sf_moderator_permissions ($usergroup, $additionalgroups, $status_uid) {
		global $mybb; 
		
		$mod_groups = $mybb->settings['statusfeed_moderator_groups'];
		$allowed = explode(",", $mod_groups);
		$groups = array();
		$groups[0] = (int)$usergroup; 
		$add_groups = explode(",", $additionalgroups);
		$count = 1;
		foreach($add_groups as $new_group) {
			$groups[$count] = $new_group;
			$count++;
		}
		foreach ($allowed as $allowed_group) {
			if (in_array($allowed_group, $groups)) {
				return true;
			}
		}
		
		if (($status_uid == $mybb->user['uid']) && $mybb->settings['statusfeed_useredit'] == 1) {
			return true; // user can edit or delete their own statuses.
		}

		return false;
	}
	
	function sf_moderator_confirm_permissions ($usergroup, $additionalgroups, $status_id) {
		global $mybb, $db;
		// Only users within groups defined to be moderators can edit statuses that they don't own. 
		
		$mod_groups = $mybb->settings['statusfeed_moderator_groups'];
		$allowed = explode(",", $mod_groups);
		$groups = array();
		$groups[0] = (int)$usergroup; 
		$add_groups = explode(",", $additionalgroups);
		$count = 1;
		foreach($add_groups as $new_group) {
			$groups[$count] = $new_group;
			$count++;
		}
		foreach ($allowed as $allowed_group) {
			if (in_array($allowed_group, $groups)) {
				return true;
			}
		}
		
		// Users can also edit their own status. Check to see if the user is attempting to edit a status that they authored. 
		$SID = (int)$status_id;
		$query = $db->query("
			SELECT *
			FROM ".TABLE_PREFIX."statusfeed
			WHERE PID='$SID'
		");
		while($querydata = $db->fetch_array($query)) {
			$status_uid = (int)$querydata['UID'];
		}
		if (($status_uid == $mybb->user['uid']) && $mybb->settings['statusfeed_useredit'] == 1) {
			return true; // user can edit or delete their own statuses.
		}
		return false;
	}	

		
	function statusfeed_alert () {
		global $mybb, $db, $unread_statuses, $lang;
		$unread_statuses = null;
		if ($mybb->settings['statusfeed_alerts_enable'] == 1) {
			$userwall = (int) $mybb->user['uid'];
			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."users
				WHERE uid=$userwall
			");
			while($data = $db->fetch_array($query))
				{
					$unread = $data['sf_unreadcomments'];
				}
		
			if ($unread > 0) {
				if ($unread == 1) {
					$unread_statuses = '<div class="pm_alert" id="status_notice">'.$lang->statusfeed_unread_single.'<a href="usercp.php?action=statusfeed" style="font-weight: bold;">'.$lang->statusfeed_click_view_1.'</a></div>';
				}
				else {
					$unread_statuses = '<div class="pm_alert" id="status_notice">'.$lang->statusfeed_unread_multiple_p1.$unread.$lang->statusfeed_unread_multiple_p2.'<a href="usercp.php?action=statusfeed" style="font-weight: bold;">'.$lang->statusfeed_click_view_1.'</a></div>';
				}
		
			}
			return;
		}
		else {
			return;
		}
	}

	function statusfeed_usercp () {
		global $mybb, $templates, $lang, $header, $headerinclude, $footer, $theme, $usercpnav, $db, $statusfeed;	
		if ($mybb->input['action'] == "statusfeed") {

			if (!empty($mybb->user['uid'])) {
				$values['sf_unreadcomments'] = 0;
				$userID = (int) $mybb->user['uid'];
				$db->update_query('users', $values, 'uid = ' . $userID, 1); // set unread comments count to 0. 
			}
			else {
				// error($lang->statusfeed_notifications_guest);
				echo statusfeed_jgrowl($lang->statusfeed_notifications_guest);
				exit; 
			}

			// define the number of rows per page. If no value is defined, default to 10. 
			if ($mybb->settings['statusfeed_alertsperpage'] != 0 && (int)$mybb->settings['statusfeed_alertsperpage'] != null) {
				$rowsperpage = (int)$mybb->settings['statusfeed_alertsperpage'];
			}
			else {
				$rowsperpage = 10;
			}
			$query = $db->simple_select("statusfeed_alerts", "COUNT(PID) AS nodes", "to_uid = $userID");
			$numrows = $db->fetch_field($query, "nodes");
			$totalpages = ceil($numrows / $rowsperpage);

			if (isset($mybb->input['page'])) {
				$mybb->input['page'] = $mybb->input['page'];

			}
		
			if (isset($mybb->input['page']) && is_numeric($mybb->input['page'])) {
				$currentpage = (int) $mybb->input['page'];
			} else {
				$currentpage = 1;
			} 
	
			if ($currentpage > $totalpages) {
				$currentpage = $totalpages;
			} 
			if ($currentpage < 1) {
				$currentpage = 1;
			}
			 
			$offset = ($currentpage - 1) * $rowsperpage;			
			
			$query = $db->query("
				SELECT *
				FROM ".TABLE_PREFIX."statusfeed_alerts s
				LEFT JOIN " . TABLE_PREFIX . "users u ON u.uid = s.uid
				WHERE s.to_uid='$userID'
				ORDER BY s.PID DESC
				LIMIT $offset, $rowsperpage
			");
			$data = array();
			$count = 0;
			
			while($querydata = $db->fetch_array($query)) {	
				// type 0: status posted on your profile
				// type 1: new reply to your status
				// type 2: new reply to a status you replied to (low priority)

				if($querydata['marked_read'] == 1) {
					$read = $lang->statusfeed_read;
					$mark = "<a href='statusfeed.php?action=unread&id=".$querydata['PID']."&post_key=".$mybb->post_code."'>".$lang->statusfeed_mark_unread."</a>";
					$fontweight = "normal"; 
				}
				else {
					$read = $lang->statusfeed_unread;
					$mark = "<a href='statusfeed.php?action=read&id=".$querydata['PID']."&post_key=".$mybb->post_code."'>".$lang->statusfeed_mark_read."</a>";
					$fontweight = "bold"; // unread announcements are bold. 
				}

				if ($querydata['type'] == 0) {
					$url = "statusfeed.php?sid=".(int) $querydata['sid'];
					$text = $lang->sprintf($lang->statusfeed_notification_0, $url, $querydata['username'])." ($read)";
				}	
				else if ($querydata['type'] == 1){
					$url = "statusfeed.php?sid=".(int) $querydata['parent']."&expanded=true";
					$text = $lang->sprintf($lang->statusfeed_notification_1, $url, $querydata['username'])." ($read)";					
				}
				else {
					$url = "statusfeed.php?sid=".(int) $querydata['parent']."&expanded=true";
					$text = $lang->sprintf($lang->statusfeed_notification_2, $url)." ($read)";	
				}
				
				$date = my_date($mybb->settings['dateformat'], $querydata['date']).' '.my_date($mybb->settings['timeformat'], $querydata['date']);

				$count++;
				if ($count % 2 == 0) {
					$altbg = "trow2";
				}
				else {
					$altbg = "trow1";
				}

				eval("\$notifications .= \"".$templates->get("statusfeed_notification")."\";");
			}

			if ($count == 0) {
				$notifications = '<tr><td colspan="3"><div class="pm_alert">'.$lang->statusfeed_no_notifications.'</div></td></tr>';
			}
			
			$pagination = multipage($numrows, $rowsperpage, $currentpage, "usercp.php?action=statusfeed");
			eval("\$statusfeed = \"".$templates->get("statusfeed_notifications_container")."\";");
			output_page($statusfeed);
		}
	}


	function statusfeed_render_comments($ajax = true, $style="full", $SID = null, $limit = 7, $single=0, $display=0) {
		// this function is the function that renders comments. This function is often called via ajax requests, but can be called directly as well. For example, if a new comment is posted for a status, the parent status is automatically expanded on redirect and comments are displayed. This function does not use statusfeed_statusfeed_render_status() at this time due to the altered functionality of comment rendering. Although statuses and comments are treated as one and the same by the database/code, the user will not see them as so from a standpoint of user friendliness.  
		global $mybb, $statusfeed_parser_options, $db, $templates, $lang;
		require_once MYBB_ROOT."/inc/class_parser.php";
		$parser = new postParser(); 
		
		$comment_id = "";
		$display_comment = "";
		if ($single != 0) {
			$fetch_limit = 1;
			$display_comment = ""; 

			// By default, we set single rendering to not display. we can override this when we need. 
			if ($display == 0) {
				// die("Display is 0.");
				$display_comment = "style='display: none;'";
			}

			$comment_id = "sf_last_comment"; // Used for ajax. 
		}
		else if ($limit == "all") {
			$fetch_limit = (int) $mybb->settings['statusfeed_max_comments'];
		}
		else {
			$fetch_limit = (int) $mybb->settings['statusfeed_commentsperpage']; 
		} 
		
		if ($ajax == true && $single == 0) {
			$parent = (int) $mybb->input['parent'];
		}
		else {
			$parent = (int) $SID;
		}
		
		if($parent == null) {
			echo "<em>".$lang->statusfeed_generic_error."</em>";
			return;
		}
		
		$query = $db->simple_select("statusfeed", "COUNT(PID) AS comments", "shown=1 AND parent=$parent");
		$totalcomments = $db->fetch_field($query, "comments");

		// If we're rendering an ajax comment, we need a way to select only one comment. 
		$singleBit = "";
		if ($single != 0) {
			$selectBit = " AND `PID` = " . (int) $single;
		}

	//	$offset = $totalcomments - (int) $mybb->settings['statusfeed_commentsperpage'];

		$query = $db->query("
			SELECT 
				s.*, 
				u.username AS fromusername,
				u.avatar,
				w.username AS tousername
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
			LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
			WHERE shown=1 AND parent=$parent " . $singleBit . "
			ORDER BY PID DESC
			LIMIT $fetch_limit");	
		
		$count = 0;
		$avatar_parems = statusfeed_avatar_parems("mini"); // get avatar paremeters
		
		if (empty($mybb->user['avatar'])) {
			$viewer_avatar = $mybb->settings['useravatar']; // I'm surprised this is necessary. 
		} 
		else {
			$viewer_avatar = $mybb->user['avatar'];
		}			

		while($row = $db->fetch_array($query)) {
			$results[] = $row;
		}
		
		if (!empty($results)) {
			$results = array_reverse($results); // Reverse order so that newest comments display on the bottom. 
			
			foreach ($results as $querydata)  {
				$edit = "";
				$delete = "";
				$SID = (int) $querydata['PID'];
				$UID = (int) $querydata['UID'];
				$username = $querydata['username'];
				$parent = (int) $querydata['parent'];
				
				if (sf_moderator_permissions($mybb->user['usergroup'], $mybb->user['additionalgroups'], $querydata['UID']) == true) {			
					// $edit = "<a href='statusfeed.php?uid=$UID&status_mode=edit&status_id=".$querydata['PID']."'>".$lang->statusfeed_edit."</a>";
					$edit = '<a href="javascript:;" onclick=\'$("#status_text_'.$SID.'").load("statusfeed.php?uid='.$UID.'&status_mode=edit&status_id='.$SID.'"); \'>'.$lang->statusfeed_edit.'</a> ';
					$delete = '<a href="javascript:;" onclick=\'if (confirm("'.$lang->statusfeed_delete_confirm.'")) { $("#statusfeed_outer_notification_container").load("misc.php?action=statusfeed_delete_status&ID='.(int) $SID.'&post_key='.$mybb->post_code.'&reply_id='.(int) $parent.'");   $("#status_'.(int) $SID.'").fadeOut( "slow", function() {});   }\'>'.$lang->statusfeed_delete.'</a>';
				}	
				
				if ($querydata['avatar'] != null) {
					$avatar = $querydata['avatar'];
				} 
				else {
					$avatar = $mybb->settings['useravatar'];
				}			
	
				if ($count == 0) $border_fix = "border-top: none"; // this is what happens when you nest too many tables and borders get complicated. 
				else {
					$border_fix = ""; 
				}	

				$TOUID = $querydata['wall_id'];
				$userlink = build_profile_link($querydata['fromusername'], $querydata['UID']); // build user profile link. 
				$status = $parser->parse_message($querydata['status'], $statusfeed_parser_options); 

				$date = my_date($mybb->settings['dateformat'], $querydata['date']);
				$time = my_date($mybb->settings['timeformat'], $querydata['date']);
				$comment_num = (int)$querydata['numcomments'];
				$replies = "";
				$numlikes = (int) $querydata['numlikes'];
				
				unset($querydata['numlikes']);
				unset($querydata['username']);
				unset($querydata['status']);

				$likeButtonText = $lang->statusfeed_likebutton;
				
				eval("\$likebutton = \"".$templates->get("statusfeed_likeButton")."\";");
				eval("\$reportbutton = \"".$templates->get("statusfeed_reportButton")."\";");

				
				if ($style == "mini") {
					eval("\$feed .= \"".$templates->get("statusfeed_comment_mini")."\";"); // eval("\$feed .= \"".$templates->get("statusfeed_comment_full")."\";");
				}
				else {
					eval("\$feed .= \"".$templates->get("statusfeed_comment_mini")."\";"); // eval("\$feed .= \"".$templates->get("statusfeed_comment_full")."\";");
				}
				
				$count++;	
			}
		}
		else {
			$feed = "<tr class='sf_no_comments'><td colspan='2' class='trow1' style='border-top: none; border-left: none; border-right: none; padding-top: 5px;'><div class='pm_alert'>".$lang->statusfeed_no_comments."</div></td></tr>";			
		}	

		if ($single != 0 && $display == 0) {
			return $feed; // Return the feed (a single status) for ajax. 
		}
		
		if ($totalcomments > $mybb->settings['statusfeed_commentsperpage']) {
			if ($limit != "all") {
				$viewall = '<a href="javascript:;" onclick=\'$("#comments_'.$parent.'").load("statusfeed.php?ajax=true&parent='.$parent.'&viewall=true"); \'>'.$lang->statusfeed_view_all_comments.'('.$totalcomments.')'.'</a> ';	
			}
		}
		
		$comment_parems = statusfeed_avatar_parems("mini");
		eval("\$container = \"".$templates->get("statusfeed_comments_container")."\";");

		if ($ajax == true) {
			echo $container;
			return;
		}
		return $container; // if ajax parameter is defined as false. 
	}

	
	function statusfeed_render_status ($array, $options) {
		global $mybb, $templates, $lang, $statusfeed_parser_options; 
		// this function performs basic processing on data and parses a status. This is not used to generate the query. 
		require_once MYBB_ROOT."/inc/class_parser.php";
		$parser = new postParser(); 
		
		$style = "full";
		if (isset($options['style']) && $options['style'] == "mini") {
			$style = "mini"; 
		}
		
		$class = "";
		if (isset($options['class'])) {
			$class = " class='sf_ajax_newstatus' style='display: none;'";
		}

		
		$SID = (int) $array['PID'];
		$UID = (int) $array['UID'];
		if (sf_moderator_permissions($mybb->user['usergroup'], $mybb->user['additionalgroups'], $array['UID']) == true) {			
			$edit = '<a href="javascript:;" onclick=\'$("#status_text_'.$SID.'").load("statusfeed.php?uid='.$UID.'&status_mode=edit&status_id='.$SID.'"); \'>'.$lang->statusfeed_edit.'</a> ';
			$delete = '<a href="javascript:;" onclick=\'if (confirm("'.$lang->statusfeed_delete_confirm.'")) { $("#statusfeed_outer_notification_container").load("misc.php?action=statusfeed_delete_status&ID='.(int) $SID.'&post_key='.$mybb->post_code.'");    $("#status_'.(int) $SID.'").fadeOut( "slow", function() {});		}\'>'.$lang->statusfeed_delete.'</a>';
		}	
		else {
			$delete = null; 
			$edit = null;
		}
	
		$username = htmlspecialchars($array['username']);
		$avatar_parems = statusfeed_avatar_parems ($style);
		// $comment_parems = statusfeed_avatar_parems("mini");
		
		if ($array['avatar'] != null) {
			$avatar = htmlspecialchars($array['avatar']);
		} 
		else {
			$avatar = htmlspecialchars($mybb->settings['useravatar']);
		}			
		
		$to_userlink = build_profile_link(htmlspecialchars($array['tousername']), (int) $array['wall_id']); // build user profile link. 
		$author_userlink = build_profile_link(htmlspecialchars($array['fromusername']), (int) $array['UID']); // build user profile link. 
	
		$status = $parser->parse_message($array['status'], $statusfeed_parser_options); 
		if ($array['fromusername'] != $array['tousername']) {
			$userlink = $author_userlink." → ".$to_userlink;
		}
		else {
			$userlink = $author_userlink; // initialize variable
		}
		
		$date = my_date($mybb->settings['dateformat'], $array['date']);
		$time = my_date($mybb->settings['timeformat'], $array['date']);
		$numcomments = (int)$array['numcomments'];
		$numlikes = (int)$array['numlikes'];

		if ($mybb->settings['statusfeed_comments_enable'] == 1) {
			$replies = '<a href="javascript:;" onclick=\'$("#comments_'.$SID.'").load("statusfeed.php?ajax=true&parent='.$SID.'&style='.$style.'"); $("#comments_container_'.$SID.'").toggle(425);\'>'.$lang->statusfeed_replies.' ('.$numcomments.')</a> ';
		}
		else {
			$replies = null;
		}	

		$display_comments = "none"; // default to collapsed. 
		if (isset($options['expanded']) && $options['expanded'] == true) {
			// Expand and display comments as normal. 
			if (!isset($options['single_comment'])) {
				$display_comments = "table-row"; // display as expanded
				$comments = statusfeed_render_comments (false, $style, $SID); // load comments. 
			}
			// Expand, but display only a single comment. 
			else {
				$display_comments = "table-row"; // display as expanded
				$comments = statusfeed_render_comments (false, $style, $SID, 1, 1, 1); // load comments. 
			}
		}

		$likebutton = "";
		if ($mybb->settings['statusfeed_likes_enable'] == 1) {
			$likeButtonText = $lang->statusfeed_likebutton;
			eval("\$reportbutton = \"".$templates->get("statusfeed_reportButton")."\";");
			eval("\$likebutton = \"".$templates->get("statusfeed_likeButton")."\";");
		}

		if ($style == "full") {
			eval("\$status_update = \"".$templates->get("statusfeed_post_full")."\";");
		}
		else {
			eval("\$status_update = \"".$templates->get("statusfeed_post_mini")."\";");
		}		
		
		return $status_update;
	}
	
	function statusfeed_get_ajax_SID($SID, $comment=false) {
		global $db, $mybb;
		
		$options = array(
			"expanded" => false
		);

		if ($mybb->input['template'] == "mini") {
			$options['style'] = "mini";
		} else {
			$options['style'] = "full";
		}

		$options['class'] = "sf_newstatus_ajax";

		$query = $db->query("
			SELECT 
				s.*, 
				u.username AS fromusername,
				u.avatar,
				w.username AS tousername
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
			LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
			WHERE shown=1 AND PID=". (int) $SID . ";"
		);
		$array = $db->fetch_array($query);
		return statusfeed_render_status($array, $options);
	}

	function statusfeed_get_ajax_comment($SID, $commentID) {
		global $db, $mybb;
		
/*
		// $query = $db->query("
			SELECT 
				s.*, 
				u.username AS fromusername,
				u.avatar,
				w.username AS tousername
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
			LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
			WHERE shown=1 AND PID=". (int) $SID . ";"
		); */

		// redirect(htmlspecialchars($_SERVER['HTTP_REFERER']."#status_".(int)$sid, ENT_QUOTES), "Statusfeed test");

		// $array = $db->fetch_array($query);
		// ($ajax = true, $style="full", $SID = null, $limit = 7, $single=0)
		// echo statusfeed_jgrowl("Comment posted successfully.");
		return statusfeed_render_comments(true, "full", $SID,  7, (int) $commentID);		
	}

	function statusfeed_avatar_parems ($style) {
		global $mybb;
		if (in_array($style, array("full", "mini"))) {
			$avatar_parem = explode("x", strtolower($mybb->settings['statusfeed_avatarsize_'.$style]));
			foreach ($avatar_parem as $parem) {
				if(isset($avatar_parems['width'])) {
					$avatar_parems['height'] = (int) $parem;
				} else {
					$avatar_parems['width'] = (int) $parem;
				}
			}
		}
		else {
			if($style == "comment_mini") {
				$avatar_parems['width'] = $avatar_parems['height'] = 24; 			
			}
		}	

		if (($avatar_parems['width'] == null) || ($avatar_parems['height'] == null)) {
			if ($style == "mini") {
				$avatar_parems['width'] = $avatar_parems['height'] = 32; // user initialization failed, reset to default. 
			}
			else {
				$avatar_parems['width'] = $avatar_parems['height'] = 64; // user initialization failed, reset to default. 
			}	
		}
		$avatar_parems['indent_width'] = $avatar_parems['width'] + 8 . 'px'; // account for padding by avatar container box when indenting comments. This solution is somewhat of a workaround. 
		return $avatar_parems; 
	}
    
	// Check if the status that a user is trying to edit or delete is the most recent status.
	
    function isMostRecent($wallID_pass, $userID) {
        global $db, $mybb;
        $wallID = (int) $wallID_pass;
        
        $query = $db->query("
        SELECT
            s.*,
            u.username AS fromusername,
            u.avatar,
            w.username AS tousername
        FROM ".TABLE_PREFIX."statusfeed s
        LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
        LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
        WHERE shown=1 AND (s.UID = s.wall_id) AND (s.parent = -1) AND (s.wall_id == ".$wallID.")
        ORDER BY PID DESC
        LIMIT 0, 1");
        
        $data = array();
        $count = 0;
        
        while($querydata = $db->fetch_array($query)) {
            if ($querydata['PID'] == $wallID_pass) {
				return true;     
            } 
            return false;
		}
        return false;
	}
	
	// Check if the status that a user is trying to edit or delete is the most recent status.
    function getMostRecent($wallID_pass) {
        global $db, $mybb;
        $wallID = (int) $wallID_pass;
		
		$query = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed WHERE (self = 1) AND (shown=1) AND (parent = -1)
			AND (UID = ".$wallID.")
			ORDER BY `PID` DESC
			LIMIT 1");
		
		$queryData = $db->fetch_array($query);
		return $queryData; 
    }

	function statusfeed_postbit (&$post) {
		global $mybb, $templates, $lang;
		if ($mybb->settings['statusfeed_enabled_postbit'] == 1) {

			$userstatus = getSanitizedStatusArray($post['sf_currentstatus']);
			$statusUID = (int) $post['uid'];
			
			if ($userstatus == '' || $userstatus == null) {
				$userstatus = $lang->statusfeed_no_status;
			}
			eval("\$statusfeed = \"".$templates->get("statusfeed_postbit")."\";");
			$post['statusfeed'] = $statusfeed; // $lang->statusfeed_postbit
		}
		else {
			$post['statusfeed'] = ""; // Bug fix for servers that display notices
		}
	}

	// This function cuts out unnecessary database fields and sanitizes the rest. 
	// This prevents a rogue variable from being accessed by an addition to the template. 
	function getSanitizedStatusArray($status) {
		global $mybb;
		require_once MYBB_ROOT."/inc/class_parser.php";

		$parser = new postParser(); 
		$parser_options = array(
    			'allow_html' => 0,
    			'allow_mycode' => 1,
    			'allow_smilies' => 1,
    			'allow_imgcode' => 0,
    			'filter_badwords' => 1,
    			'nl2br' => 0
		); 

		// Truncate this if it is a large status.
		$returnArray = array(); 
		if (strlen($status) > $mybb->settings['statusfeed_mini_truncate_length']) {
			$status = substr($status, 0, $mybb->settings['statusfeed_mini_truncate_length']).'...';
		}

		return $parser->parse_message($status, $parser_options);
	}

	// Returns whether a user has already liked a given status. If so, don't let them like it again. 
	function statusfeed_hasUserLiked($statusID) {
		global $db, $mybb; 

		$sid = (int) $statusID; 
		$queryData = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed_likes WHERE `sid` = ". $sid . " AND `uid` = ". (int) $mybb->user['uid'] . ";");
		$vals = $db->fetch_array($queryData); 
		
		if (isset($vals['PID'])) {
			return true;
		}
		return false; 
	}
	
	// This function increments or decrements a status ID count. 
	function statusfeed_pushlike() {
		global $db, $mybb, $lang; 

		if (!isset($mybb->input['statusid']) || empty((int) $mybb->input['statusid'])) {
			echo statusfeed_jgrowl($lang->statusfeed_nostatus_like_url);
			exit; 
		}


		// Pull status from the database. Make sure we are acting on an actual, valid status. 
		$sid = (int) $mybb->input['statusid']; 
		$q = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed WHERE `PID` = ".$sid.";");
		$queryData = $db->fetch_array($q);

		if ($queryData) {
			
			// Check to make sure the user is logged in. If not, we simply return the current like count.
			// This is required because of how the ajax is structured. It expects a response to replace the like count. 
			if (!isset($mybb->user['uid']) || empty($mybb->user['uid'])) {
				// error($lang->statusfeed_like_guesterror);
				echo statusfeed_jgrowl($lang->statusfeed_like_guesterror);
				echo (int) $queryData['numlikes']; 
				exit;
			}

			// Are we removing a like or adding it? 
			$mode = statusfeed_hasUserLiked($sid);
			if (!$mode) {
				$inserts = array(
					'sid' => (int) $sid, 
					'uid' => (int) $mybb->user['uid'],
					'type' => 0,
					'date' => time()
				);

				// Insert alert and update count. Add record to likes table. 
				$db->insert_query('statusfeed_likes', $inserts); 
				$mtext = " + 1";
				$numLikes = $queryData['numlikes'] + 1;
				$successLang = $lang->statusfeed_like_success;
			} 

			// Remove old status record. 
			else {
				$successLang = $lang->statusfeed_unlike_success;
				$mtext = " - 1";
				$numLikes = $queryData['numlikes'] - 1;
				$db->delete_query('statusfeed_likes', "`sid` = '{$sid}' AND `uid` = ". (int) $mybb->user['uid']);
			}

			// Increment or decrement status count in statusfeed table. 
			$increment = $db->query("
			UPDATE ".TABLE_PREFIX."statusfeed 
			SET `numlikes` = `numlikes` " . $mtext . "
			WHERE `PID` = ".$sid.";");

			if (isset($mybb->input['ajaxlike']) && !empty($mybb->input['ajaxlike'])) {
				echo statusfeed_jgrowl($successLang); // Return a notification instead of redirecting to a new page. 
				echo (int) $numLikes;
				return;
			}
			redirect(htmlspecialchars($_SERVER['HTTP_REFERER']."#status_".(int)$sid, ENT_QUOTES), $successLang);
		}
		else  {
			if (isset($mybb->input['ajaxlike']) && !empty($mybb->input['ajaxlike'])) {
				echo statusfeed_jgrowl($lang->statusfeed_no_exist);
				return;
			}
			error($lang->statusfeed_nostatus_like);
		}
	}


	// Process ajax/jquery popup for status feed on postbit, etc. 
	function statusfeed_popup() {
		global $mybb, $lang; 
		if ($mybb->input['action'] == "statusfeed_popup" && (isset($mybb->input['uid']) && !empty((int) $mybb->input['uid']))) {
			echo statusfeed_profile("statusfeed_popup"); 
			exit;
		}
	}

	// This function is used with ajax to generate jgrowl notifications when posts are liked, etc. 
	function statusfeed_jgrowl($message) {
		return '<script type="text/javascript">$(function() { $.jGrowl(\''.htmlspecialchars($message, ENT_QUOTES).'\', {theme: \'jgrowl_success\'}); });</script>';
	}

	// Handles inserting and removing stylesheets. 
	// No pluginlibrary required for this! 

	function statusfeed_insert_stylesheet ($stylesheet) {
		global $db;
		require_once(MYBB_ADMIN_DIR."/inc/functions_themes.php");

		$stylesheet_info = array(
			'sid' => 0,
			'name' => 'statusfeed.css',
			'tid' => '1',
			'stylesheet' => $db->escape_string($stylesheet),
			'cachefile' => 'statusfeed.css',
			'attachedto' => '', // Bug fix for forums that were not fully upgraded while running MySQL strict mode
			'lastmodified' => TIME_NOW,
		);
		$db->insert_query('themestylesheets', $stylesheet_info);
		cache_stylesheet(1, "statusfeed.css", $stylesheet);

		$query = $db->simple_select("themes");
		while($result = $db->fetch_array($query)) {
			update_theme_stylesheet_list($result['tid'], false, true);
		}

		cache_themes();
	}


	function statusfeed_remove_stylesheet () {
	    global $db;
		require_once(MYBB_ADMIN_DIR."inc/functions_themes.php");

		// removes stylesheet on plugin uninstall
		$query = $db->simple_select("themes", "tid");
		while($tid = $db->fetch_field($query, "tid")) {
			$css_file = MYBB_ROOT."cache/themes/theme{$tid}/statusfeed.css";
			if(file_exists($css_file))
				unlink($css_file);
		}
		$db->delete_query("themestylesheets", "name='statusfeed.css'");
		update_theme_stylesheet_list(1);

		$query = $db->simple_select("themes");
		while($result = $db->fetch_array($query)) {
			update_theme_stylesheet_list($result['tid']);
		}

		cache_themes();
	}

	// Can this user report the post? 
	function statusfeed_report_permissions () {
		global $mybb;
		// Check if the user is registered. If so, make sure the user isn't banned. 
		// Anyone else can report a status. 
		return (isset($mybb->user['uid']) && $mybb->user['uid'] != 0 && $mybb->usergroup["usergroup"]["isbannedgroup"] != "1");
	}


	// Inspired by MyProfile

    function statusfeed_report_type() {
        global $mybb, $db, $lang, $report_type, $report_type_db, $verified, $id, $id2, $id3, $error;
        if ($report_type == 'status') {
            if (!isset($mybb->input["sid"]) || !is_numeric($mybb->input["sid"])) {
                $error = $lang->error_invalid_report;
            } else {
                $cid = (int) $mybb->input["sid"];
                $query = $db->simple_select("statusfeed", "*", "PID = '" . $cid . "'");
                if (!$db->num_rows($query)) {
                    $error = $lang->error_invalid_report;
                } else {
                    $verified = true;
                    $comment = $db->fetch_array($query);
                    $id = $comment["PID"];
                    $id2 = $comment["wall_id"]; // user who received the comment
                    $id3 = $comment["uid"]; // user who made the comment
                    $report_type_db = "type = 'status'";
                }
            }
        }
	}
	
	// Create a link to a status based on status ID
	function build_status_link($id) {
		global $db; 
		$sid = (int) $id; 
		
		$query = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed WHERE PID = " . $sid);
		$querydata = $db->fetch_array($query);

		// Statusfeed handles comments differently than normal statuses. 
		// If the status has a parent, we'll specify this in the URL. 

		if ($querydata['parent'] > 0) {
			return "statusfeed.php?comment=1&sid=".(int)$sid;
		} else {
			return "statusfeed.php?sid=".$sid;
		}
	}

	// Taken from MyProfile (see https://github.com/mohamedbenjelloun/MyProfile/blob/master/Upload/inc/plugins/myprofile/myprofilecomments.class.php)
    function statusfeed_modcp_reports_report() {
		
        global $report, $report_data, $lang, $db;
        if ($report["type"] == "statusmanager") {
			
            $from_user = get_user($report['id3']);
            $to_user = get_user($report['id2']);
            $from_profile_link = build_profile_link(htmlspecialchars_uni($from_user["username"]), $from_user["uid"]);
            $to_profile_link = build_profile_link(htmlspecialchars_uni($to_user["username"]), $to_user["uid"]);	
			$status_link = build_status_link($report['id']); 
            $report_data["content"] = $lang->sprintf($lang->statusfeed_report_from, $status_link, $from_profile_link);
            $report_data["content"] .= $lang->sprintf($lang->statusfeed_report_to, $to_profile_link);
        }
	}
	
	// Generates a report and inserts it into the database. 
	function statusfeed_insert_report ($sid, $target_user) {
		global $mybb, $db, $lang; 

		$inserts = array(
			"id" => (int) $sid, 
			"uid" => (int) $mybb->user['uid'],
			"id3" => (int) $mybb->user['uid'],
			"id2" => (int) $target_user,
			"type" => "statusmanager",
			"reportstatus" => 0,
			"reporters" => 1, 
			"reason" => $lang->statusfeed_report_reason,
			"reasonid" => 1,
			"reports" => 1,
			"dateline" => TIME_NOW
		);

		$db->insert_query('reportedcontent', $inserts);
	}

	// Processes ajax call to generate the report. 
	function statusfeed_report_push () {
		global $db, $mybb, $lang, $cache; 

		if (!isset($mybb->input['statusid'])) {
			echo statusfeed_jgrowl($lang->statusfeed_invalid_report); 
			return; 
		} 
		$statusID = (int) $mybb->input['statusid']; 
		
		// We must check to make sure that this status exists. 
		$query = $db->simple_select("statusfeed", "COUNT(PID) AS statuses", "PID = " . (int) $statusID);
		$numrows = $db->fetch_field($query, "statuses");

		// if (!($querydata = $db->fetch_array($query))) {	
		if ($numrows == 0){
			echo statusfeed_jgrowl($lang->statusfeed_invalid_report); 
		}

		statusfeed_insert_report($statusID, 1);
		echo statusfeed_jgrowl($lang->statusfeed_report_success);
		$cache->update_reportedcontent();
		return;
	}

	function statusfeed_getLikesPopup() {
		global $lang, $mybb, $db;
		
		if (!isset($mybb->input['sid']) || !is_numeric($mybb->input['sid'])) {
			echo $lang->statusfeed_likes_invalid_status;
			exit;
		}

		$sid = (int) $mybb->input['sid'];
		$response = $lang->statusfeed_liked_by; 

		$query = $db->query("SELECT * FROM ".TABLE_PREFIX."statusfeed_likes l
		 LEFT JOIN ".TABLE_PREFIX."users u ON l.uid = u.uid 
		 WHERE l.sid = " . (int) $sid);

		while($querydata = $db->fetch_array($query)) {	
			$users[] = htmlspecialchars($querydata['username']);
		}

		if ($users) {
			foreach ($users as $user) {
				$response = $response . $user . ", ";
			}
			$response = substr($response, 0, -2); // Fix formatting
		}
		else {
			$response = $lang->statusfeed_no_likes;
		}

		echo $response;
		exit;
	}