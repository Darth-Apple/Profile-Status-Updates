<?php
/**
 * MyBB 1.6
 * Copyright 2010 MyBB Group, All Rights Reserved
 *
 * Website: http://mybb.com
 * License: http://mybb.com/about/license
 *
 */
    
  error_reporting(E_ALL);
  ini_set('display_errors', '1');
  define("IN_MYBB", 1);
  define("IN_PORTAL", 1);
  define('THIS_SCRIPT', 'statusfeed.php');
  require_once('global.php');
  

  global $templatelist, $mybb, $templates, $lang;
  $lang->load('statusfeed');
  

  if (isset($mybb->input['ajax'])) {
	if (isset($mybb->input['viewall']) && $mybb->input['viewall'] == "true") {
			$limit = "all";
	}	
	else {
			$limit = null; // statusfeed_render_comments will default to the value defined in $mybb->settings['statusfeed_commentsperpage']
	}	
	statusfeed_render_comments(true, $mybb->input['style'], null, $limit);
	exit; 
  }

    
  $templatelist = "headerinclude, header_header, footer_footer, statusfeed_all";
  require_once MYBB_ROOT."global.php";
  require_once MYBB_ROOT."inc/functions_post.php";
  require_once MYBB_ROOT."inc/functions_user.php";
  require_once MYBB_ROOT."inc/class_parser.php";
  
  eval("\$headerinclude = \"".$templates->get("headerinclude")."\";");
  eval("\$header = \"".$templates->get("header")."\";");
  eval("\$footer = \"".$templates->get("footer")."\";");
  
  	$parser = new postParser(); 
  	$parser_options = array(
   		'allow_html' => 0,
   		'allow_mycode' => 1,
    	'allow_smilies' => 1,
    	'allow_imgcode' => 0,
    	'filter_badwords' => 1,
    	'nl2br' => 1	
  	);
	global $parser, $parser_options; 
  	$content_url = get_current_location();

	if (isset($mybb->input['sid'])) {
		$SID = (int) $mybb->input['sid'];
	}
	else {
		$SID = null; // because some servers will complain. 
	}

	if (isset ($mybb->input['status_mode']) && $mybb->input['status_mode'] == "edit") {
		statusfeed_edit_page ();
		exit();
	}

	// Are we displaying a single comment? 
	else if ($mybb->input['comment'] == 1 ){ 
		statusfeed_render_comment_single($SID);
	}

	// Are we displaying a single status? 
	else if (!empty($SID)) {
		statusfeed_display_single($SID);
	}

	else if (isset($mybb->input['action']) && $mybb->input['action'] == "pageajax") {
		echo statusfeed_ajax_profilePage();
		exit; 
	}

	else if (isset($mybb->input['action']) && $mybb->input['action'] != "") {
		verify_post_check($mybb->input['post_key']);
		
		switch ($mybb->input['action']) {
			case "read": 
				statusfeed_mark_notification($mybb->input['id'], 1);
				break;
			case "unread": 
				statusfeed_mark_notification($mybb->input['id'], 0);				
			case "mark_all":
				statusfeed_mark_all ();
				break; 
			default: 
				error ($lang->statusfeed_generic_error);
				die();
		}
	}
	else {
		status_all();
	}

	global $statusfeed;
	output_page($statusfeed);
	
  
 	function status_all () {
		global $templates, $statusfeed_profile, $db, $mybb, $header, $footer, $headerinclude, $parser, $parser_options, $statusfeed, $lang;
		add_breadcrumb($lang->community_status_feed, "statusfeed.php");
		$feed = "";
		$profile_UID = $mybb->user['uid'];
		$avatar_size = $mybb->settings['statusfeed_avatarsize_full'];
		if ($mybb->settings['statusfeed_rowsperpage_all'] != 0 && (int)$mybb->settings['statusfeed_rowsperpage_all'] != null) {
			$rowsperpage = (int)$mybb->settings['statusfeed_rowsperpage_all'];
		}
		else {
			$rowsperpage = 10;
		}

		$query = $db->simple_select("statusfeed", "COUNT(PID) AS nodes", "parent < 1");
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
			SELECT 
				s.*, 
				u.username AS fromusername,
				u.avatar,
				w.username AS tousername
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
			LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
			WHERE shown=1 AND parent <= 0
			ORDER BY PID DESC
			LIMIT $offset, $rowsperpage
			");

		$data = array();
		$count = 0;
		
		while($querydata = $db->fetch_array($query)) { 
			if ($querydata['parent'] > 0) {
				continue; // no need to fetch comments here. These are fetched by ajax. 
			}
			
			$options['style'] = "full";
			if ((isset($mybb->input['expanded'])) && ($mybb->input['expanded'] == $querydata['PID'])) {
					$options['expanded'] = true; 
			}	
			else {
					$options['expanded'] = false;
			}	
			
			$feed .= statusfeed_render_status($querydata, $options); // render the status
			$count++;	
		}
		
		if ($count == 0) {
			$feed = "<tr><td><div class='pm_alert'>".$lang->statusfeed_none_found."</div></td></tr>";
		}
		$status_updates = $feed;
		
		$pagination = multipage($numrows, $rowsperpage, $currentpage, "statusfeed.php");
		eval("\$statusfeed = \"".$templates->get("statusfeed_all")."\";");	
	}


 	function statusfeed_display_single ($SID) {
		global $templates, $lang, $statusfeed, $db, $mybb, $header, $footer, $headerinclude, $parser, $parser_options;
		add_breadcrumb($lang->community_status_feed, "statusfeed.php");
		add_breadcrumb($lang->community_status_feed_single, "statusfeed.php?expanded=true&sid=".(int)$SID);
		$feed = "";
		$profile_UID = $mybb->user['uid'];
		
		if (isset($mybb->input['parent_status']) && !empty((int) $mybb->input['parent_status'])) {
			$SID = (int) $mybb->input['parent_status']; // Correct a bug that occurs when a comment is edited. 
			// This causes the parent status to be displayed in expanded mode, rather than displaying the comment. 
		}

		$query = $db->query("
			SELECT 
				s.*, 
				u.username AS fromusername,
				u.avatar,
				w.username AS tousername
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
			LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
			WHERE shown=1 AND PID=$SID
			");

		$data = array();
		$count = 0;
		while($querydata = $db->fetch_array($query))
		{
			if ($querydata['parent'] > 0) {
				continue; // no need to fetch comments here. These are fetched by ajax. 
			}
				
			$options = array (
				"style" => "full", 
				"expanded" => "true"
			);
			
			$feed .= statusfeed_render_status($querydata, $options);
			$count++;	
		}
		if ($count == 0) {
			$feed = "<tr><td><div class='pm_alert'>".$lang->statusfeed_error_not_found."</div></td></tr>";
		}
		$status_updates = $feed;
		eval("\$statusfeed = \"".$templates->get("statusfeed_all")."\";");	
	}	


	// Due to the way Statusfeed was designed, this will be interesting. 
	// We will essentially load the parent status as normal, and then load the comment
	// without the use of ajax. This uses the old architecture from the ~2014 statusfeed, 
	// So it's sort of a workaround solution. A future version will probably "fix" this issue. 
	
	function statusfeed_render_comment_single($SID) {
			global $templates, $lang, $statusfeed, $db, $mybb, $header, $footer, $headerinclude, $parser, $parser_options;
			add_breadcrumb($lang->community_status_feed, "statusfeed.php");
			add_breadcrumb($lang->community_status_feed_single, "statusfeed.php?expanded=true&sid=".(int)$SID);
			$feed = "";
			$profile_UID = $mybb->user['uid'];
			
			if (isset($mybb->input['parent_status']) && !empty((int) $mybb->input['parent_status'])) {
				$SID = (int) $mybb->input['parent_status']; // Correct a bug that occurs when a comment is edited. 
				// This causes the parent status to be displayed in expanded mode, rather than displaying the comment. 
			} 

			// First, let's fetch the parent status and render it like normal. 
			$query = $db->query("
				SELECT 
					s.*, 
					u.username AS fromusername,
					u.avatar,
					w.username AS tousername
				FROM ".TABLE_PREFIX."statusfeed s
				LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
				LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
				WHERE shown=1 AND pid=$SID
				");
			$comment_data = $db->fetch_array($query);
			$comment_parent_id = (int) $comment_data['parent'];
			
			// Make sure the parent status hasn't been deleted. 
			if (!$comment_parent_id) {
				error($lang->statusfeed_no_parent_comment);
			}

			// Fetch the parent status. 
			$query = $db->query("
				SELECT 
					s.*, 
					u.username AS fromusername,
					u.avatar,
					w.username AS tousername
				FROM ".TABLE_PREFIX."statusfeed s
				LEFT JOIN " . TABLE_PREFIX . "users u ON (u.uid = s.UID)
				LEFT JOIN " . TABLE_PREFIX . "users AS w ON (w.uid = s.wall_id)
				WHERE shown=1 AND pid=$comment_parent_id
				");

	
			$data = array();
			$count = 0;
			while($querydata = $db->fetch_array($query)) {
				if ($querydata['parent'] > 0) {
					continue; // no need to fetch comments here. These are fetched by ajax. 
				}
				
				// Tell our status_rendering function to render one specific comment. 
				$options = array (
					"style" => "full", 
					"expanded" => "true", 
					"single_comment" => (int) $SID
				);
				
				$feed .= statusfeed_render_status($querydata, $options);
				$count++;	
			}
			if ($count == 0) {
				$feed = "<tr><td><div class='pm_alert'>".$lang->statusfeed_error_not_found."</div></td></tr>";
			}

			// $comment = statusfeed_render_comments(false, "full", $SID, 1, 1, 1); 

			$status_updates = $feed;
			eval("\$statusfeed = \"".$templates->get("statusfeed_all")."\";");	

	}
  
  
  	function statusfeed_edit_page () {
		global $templates, $statusfeed, $mybb, $db, $lang, $headerinclude, $header, $footer;
		
		if (!isset($mybb->input['status_id'])) {
			error($lang->statusfeed_no_comment_defined);
			die();
		}
		$ID = (int)$mybb->input['status_id'];
		
		if (!isset($mybb->input['uid'])) {
			// error("no user defined");
			// die();
		}
		$UID = (int)$mybb->input['uid'];		
		if (sf_moderator_confirm_permissions($mybb->user['usergroup'], $mybb->user['additionalgroups'], $ID) == false) {
			error($lang->statusfeed_permission_denied);
			die ();
		}
		$query = $db->query("
			SELECT *
			FROM ".TABLE_PREFIX."statusfeed s
			LEFT JOIN " . TABLE_PREFIX . "users u ON u.uid = s.UID
			WHERE PID=$ID 
		");
		$data = array();
		$count = 0;
		while($querydata = $db->fetch_array($query))
		{		
			$status = $querydata['status'];
			$parent = $querydata['parent'];
		}
		
		
		eval("\$statusfeed = \"".$templates->get("statusfeed_edit")."\";");
		output_page($statusfeed);
		return $statusfeed;
	}


	function statusfeed_mark_notification ($aid, $value) {
		global $mybb, $db, $lang;
		$alertID = (int) $aid;
		$query = $db->simple_select('statusfeed_alerts', '*', "PID='$alertID'");
		$querydata = $db->fetch_array($query);
		$uid = (int) $querydata['to_uid'];
		if (empty($uid)) {
			error($lang->statusfeed_no_exist);
			return;
		}

		if ($mybb->user['uid'] != $uid) {
			error($lang->statusfeed_user_mismatch); // user is attempting to mark another user's notification as read. 
			return; 
		}

		if (isset($mybb->input['page'])) {
			$pagenumber = (int) $mybb->input['page'];
			$page = "&page=".$pagenumber; // redirect to the correct page if statuses span more than one page. 
		}
		else {
			$page = ""; // because otherwise some servers might complain. Undefined index errors = great fun. 
		}

		if ($value == 1) {
			$db->query("UPDATE ".TABLE_PREFIX."users SET sf_unreadcomments=sf_unreadcomments-1 WHERE uid=$uid"); // update unread status count
			$db->query("UPDATE ".TABLE_PREFIX."statusfeed_alerts SET marked_read=1 WHERE PID=$alertID"); // mark status as read. 
			redirect('usercp.php?action=statusfeed'.$page, $lang->statusfeed_read_success);
		}
		else {
			// $db->query("UPDATE ".TABLE_PREFIX."users SET sf_unreadcomments=sf_unreadcomments+1 WHERE uid=$uid"); // update unread status count
			$db->query("UPDATE ".TABLE_PREFIX."statusfeed_alerts SET marked_read=0 WHERE PID=$alertID"); // mark status as unread. 	
			redirect('usercp.php?action=statusfeed'.$page, $lang->statusfeed_unread_success);		
		}
		redirect('usercp.php?action=statusfeed'.$page, $lang->statusfeed_read_success);
	}

	function statusfeed_mark_all () {
		global $mybb, $db, $lang;
		$userID = (int) $mybb->user['uid'];
		if(empty($userID)) {
			error($lang->statusfeed_marked_read_guest); // user is a guest?
			return; 
		}
		$db->query("UPDATE ".TABLE_PREFIX."users SET sf_unreadcomments=0 WHERE uid=$userID"); // update unread status count
		$db->query("UPDATE ".TABLE_PREFIX."statusfeed_alerts SET marked_read=1 WHERE to_uid=$userID"); // mark status as read. 
		redirect('usercp.php?action=statusfeed', $lang->statusfeed_read_all_success);
	}


	function statusfeed_ajax_profilePage() {
		global $mybb; 
		return statusfeed_profile();
	}
?>
