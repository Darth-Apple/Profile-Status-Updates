<?php 

/* Most of this code was provided courtesy of MyBB's "Thank You/Like System" plugin. 
    The MyAlerts integration implentation was used with permission by WhiteNeo (https://community.mybb.com/thread-226830-post-1345030.html#pid1345030)
    Original: https://github.com/mybbgroup/Thank-you-like-system/blob/master/inc/plugins/thankyoulike.php

	A huge thank you to the developers of the TYL plugin for allowing us to base our integration 
	on their implementations. This has been immensely helpful and as made MyAlerts integration possible! 

    Many thanks to Euan T for the MyAlerts plugin, and to Whiteneo, Eldenroot, and others for the 
    integration implementations in the Thank You/Like System plugin for which this integration was based. 
    */ 

    
/**
 * Check whether a version of MyAlerts greater than 2.0.0 is present.
 * Optionally, check that it is activated too.
 * Optionally, check that the statusfeed alert type is registered too.
 * Optionally, check that any registered statusfeed alert type is also enabled.
 * @param boolean True iff an activation check should be performed.
 * @param boolean True iff a check for statusfeed alert type registration should be performed.
 * @param boolean True iff a check that any statusfeed alert type is enabled should be performed.
 * @return boolean True iff the check(s) succeeded.
 */
function statusfeed_have_myalerts($check_activated = false, $check_statusfeed_registered = false, $check_statusfeed_enabled = false)
{
	$ret = false;

	if(function_exists("myalerts_info")) {
		$myalerts_info = myalerts_info();
		if(version_compare($myalerts_info['version'], "2.0.0") >= 0
		   &&
		   (!$check_activated
		    ||
		    (function_exists("myalerts_is_activated") && myalerts_is_activated())
		   )
		  )
		{
			if (!$check_statusfeed_registered && !$check_statusfeed_enabled) {
				$ret = true;
			}
			else {
				global $cache;

				$alert_types = $cache->read('mybbstuff_myalerts_alert_types');

				if((!$check_statusfeed_registered || (isset($alert_types['statusfeed']['code'   ]) && $alert_types['statusfeed']['code'   ] == 'statusfeed'))
				   &&
				   (!$check_statusfeed_enabled    || (isset($alert_types['statusfeed']['enabled']) && $alert_types['statusfeed']['enabled'] ==    1 ))) {
					$ret = true;
				}
			}
		}

	}

	return $ret;
}



/**
 * Integrate with MyAlerts if possible.
 * @return boolean True if a successful integration was performed. False if not,
 *                 including the case that the plugin was already integrated with MyAlerts.
 */
function statusfeed_myalerts_integrate()
{
	global $db, $cache;

	$ret = false;

	// Verify that a supported version of MyAlerts is both present and activated.
	if(statusfeed_have_myalerts(true))
	{
		// Check whether the statusfeed alert type is registered.
		if(!statusfeed_have_myalerts(true, true))
		{
			// It isn't, so register it.
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
			$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
			$alertType->setCode('statusfeed');
			$alertType->setEnabled(true);
			$alertTypeManager->add($alertType);
			$ret = true;
		}
		else
		{
			// It is, so check whether it is enabled.
			if(!statusfeed_have_myalerts(true, true, true))
			{
				// It isn't, so enabled it.
				statusfeed_myalerts_set_enabled(1);
				$ret = true;
			}
		}
	}

	return $ret;
}


/**
 * Fully unintegrate from the MyAlerts system.
 * Warning: deletes ALL alerts of type statusfeed along with the statusfeed alert type itself.
 */
function statusfeed_myalerts_unintegrate()
{
	global $db;

	if(statusfeed_have_myalerts())
	{
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
		$alertType = $alertTypeManager->getByCode('statusfeed');
		if ($alertType !== null)
		{
			$id = $alertType->getId();
			if($id > 0)
			{
				// First delete the statusfeed alert type.
				$alertTypeManager->deleteById($id);

				if($db->table_exists("alerts") && $id > 0)
				{
					// Then delete all alerts of that type.
					$db->delete_query("alerts", "alert_type_id = '$id'");
				}
			}
		}
	}
}


/**
 * Enables or disables the statusfeed alert type.
 * When disabling, existing statusfeed alerts aren't deleted from the database,
 * but become invisible to users unless/until the statusfeed alert type is re-enabled.
 * @param integer 0 or 1. 0 to disable; 1 to enable.
 */
function statusfeed_myalerts_set_enabled($enabled)
{
	global $db;

	if ($db->table_exists("alert_types"))
	{
		$db->update_query('alert_types', array('enabled' => $enabled), "code='statusfeed'");
		if (function_exists('reload_mybbstuff_myalerts_alert_types'))
		{
			reload_mybbstuff_myalerts_alert_types();
		}
	}
}


/**
 * If this plugin and MyAlerts are both enabled and integrated, then add an alert for this statusfeed of this post.
 */
function statusfeed_recordAlertStatus($status) {
	global $db, $lang, $mybb, $alert, $post;
	$prefix = 'statusfeed_';
	$lang->load("statusfeed", false, true);

	if($mybb->settings[$prefix.'enabled'] == "1" && statusfeed_have_myalerts(true, true, true)) {
		$uid = (int)$status['uid'];
		$tid = (int)$status['tid'];
		$pid = (int)$status['pid'];
		$subject = htmlspecialchars_uni($status['subject']);
		$comment = (int)$status['comment'];

		$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('statusfeed');

		// Check if already alerted
		$query = $db->simple_select(
			'alerts',
			'id',
			'object_id = ' .$pid . ' AND uid = ' . $uid . ' AND unread = 1 AND alert_type_id = ' . $alertType->getId() . ''
		);

		if ($db->num_rows($query) == 0) {
			$alert = new MybbStuff_MyAlerts_Entity_Alert($uid, $alertType, $pid, $mybb->user['uid']);
			$alert->setExtraDetails(
				array(
					'tid'       => $tid,
					'pid'       => $pid,
					't_subject' => $subject,
					'comment'       => $comment
				)
			);
			MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
		}
	}
}



/**
 * Defines the statusfeed alert formatter class and registers it with the MyAlerts plugin.
 * Assumes that checks for the presence of and integration with MyAlerts
 * have already been successfully performed.
 */
function statusfeed_myalerts_formatter_load()
{
	global $mybb, $lang;

    // die("In formatter");
	if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') &&
	    class_exists('MybbStuff_MyAlerts_AlertFormatterManager'))
	{
        // die("In first if");
		class StatusfeedAlertFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
		{
			public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
			{
				$alertContent = $alert->getExtraDetails();
                $postLink = $this->buildShowLink($alert);
               // 
				return $this->lang->sprintf(
					$this->lang->statusfeed_myalert,
					$outputAlert['from_user'],
					$alertContent['t_subject']
				);
            }
            
            

			public function init()
			{
				if(!$this->lang->statusfeed) {
					$this->lang->load('statusfeed');
				}
			}

			public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
			{
				$alertContent = $alert->getExtraDetails();
				$postLink = $this->mybb->settings['bburl'] . '/' . statusfeed_build_status_link((int)$alertContent['pid'], $alertContent['comment']);

				return $postLink;
			}
		}

		$code = 'statusfeed';
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if (!$formatterManager)
		{
		        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		if ($formatterManager)
		{
			$formatterManager->registerFormatter(new StatusfeedAlertFormatter($mybb, $lang, $code));
		}
	}
}

function statusfeed_build_status_link($sid, $comment) {
    if ($comment == 0) {
        return "statusfeed.php?sid=" . (int) $sid;
    } else {
        return "statusfeed.php?comment=1&sid=" . (int) $sid;
    }
}