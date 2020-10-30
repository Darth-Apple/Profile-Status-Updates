<?php


// ☰
if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed. Please make sure IN_MYBB is defined.");
}

function statusfeed_install() {	
    global $db, $lang;
    $lang->load("statusfeed");

    if(!$db->table_exists($prefix.'statusfeed')) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."statusfeed (
            `PID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `status` VARCHAR(1025) NOT NULL DEFAULT '',
            `title` VARCHAR(100) NOT NULL DEFAULT '',
            `UID` INT NOT NULL DEFAULT -1,
            `wall_id` INT NOT NULL,
            `shown` INT UNSIGNED NOT NULL DEFAULT 0,
            `self` INT UNSIGNED NOT NULL DEFAULT 0,
            `parent` INT DEFAULT -1,
            `numcomments` INT DEFAULT 0,				
            `date` INT(10) NOT NULL,
            `numlikes` INT DEFAULT 0,
              PRIMARY KEY (PID)
            ) ENGINE=MyISAM
            ".$db->build_create_table_collation().";");
    }

    if(!$db->table_exists($prefix.'statusfeed_alerts')) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."statusfeed_alerts (
            `PID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `sid` INT NOT NULL,
            `parent` INT DEFAULT -1, 
            `uid` INT NOT NULL,
            `to_uid` INT NOT NULL,
            `marked_read` INT DEFAULT 0,
            `type` INT NOT NULL,				
            `date` INT(10) NOT NULL,
              PRIMARY KEY (PID)
            ) ENGINE=MyISAM
            ".$db->build_create_table_collation().";");
    }

    if(!$db->table_exists($prefix.'statusfeed_likes')) {
        $db->query("CREATE TABLE ".TABLE_PREFIX."statusfeed_likes (
            `PID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `sid` INT NOT NULL, 
            `uid` INT NOT NULL,
            `type` INT DEFAULT 0,				
            `date` INT(10) NOT NULL,
              PRIMARY KEY (PID)
            ) ENGINE=MyISAM
            ".$db->build_create_table_collation().";");
    }

    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `sf_unreadcomments` INT(10) NOT NULL DEFAULT '0';");
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` ADD `sf_currentstatus` VARCHAR(1025) DEFAULT '';");
            
    $template = array();

    $template['statusfeed_post_mini'] = '
    <tbody id="status_{$SID}" {$class}>
    <tr>
        <td class="trow2 statusfeed_avatarmini_td" rowspan="2" align="center" width="3%" valign="top">
            <img src="{$avatar}" alt="Avatar of User" width="{$avatar_parems[\'width\']}" height="{$avatar_parems[\'height\']}" class="statusfeed_avatar_img">
        </td>
    </tr>
    <tr>
        <td class="trow2 statusfeed_mini_textbit" valign="top">
            <div class="smalltext statusfeed_postedby_mini">
                {$userlink}
                <span class="smalltext float_right">
                    {$date} 
                    <div class="dropdown dropdown_sf_fix">
                          <a href=\'javascript:;\' onclick="statusfeed_dropdown({$SID})" class="dropbtn dropbtn_sf">…</a>
                          <div id="sf_dropdown{$SID}" class="dropdown-content">
                            {$edit}
                            {$reportbutton}
                            {$delete}
                          </div>
                    </div>		
                </span>
            </div>
            <div class="smalltext statusfeed_mini_text" id="status_text_{$SID}">
                {$status}
            </div>
            <div class="smalltext statusfeed_mini_info">
                {$likebutton} {$replies} &nbsp; 
                

            </div>
        </td>
    </tr>
    
    
    <tr id="comments_container_{$SID}" style="display: {$display_comments};" class="statusfeed_mini_comments" colspan="2">
        <td colspan="2" class="trow1 statusfeed_mini_comments_inner">
            <div id="comments_{$SID}">
                {$comments}
            </div>
        </td>
    </tr>
    
    <tr>
        <td class="trow2 statusfeed_mini_comments_borderbottom_td" colspan="2">
            <div class="statusfeed_mini_comments_borderbottom_div"></div>
        </td>
    </tr>
</tbody>
';	


    
$template['statusfeed_post_full'] = '
<tbody id="status_{$SID}" {$class}>
<tr>
        <td class="trow2 statusfeed_avatarfull_td" rowspan="3" align="center" width="3%" valign="top">
            <img src="{$avatar}" alt="Avatar of User" width="{$avatar_parems[\'width\']}" height="{$avatar_parems[\'height\']}" class="statusfeed_avatar_img">
    </td>
</tr>
<tr>
    <td class="trow2 statusfeed_full_textbit" valign="top">
        
        <div class="statusfeed_full_info">
            <div class="smalltext statusfeed_postedby">
                    {$lang->statusfeed_posted_by} {$userlink}
                    <span class="smalltext float_right">
                    {$date}&nbsp;&nbsp; 

<div class="dropdown_sf_fix">
<a href=\'javascript:;\' onclick="statusfeed_dropdown({$SID})" class="dropbtn dropbtn_sf">…</a>
<div id="sf_dropdown{$SID}" class="dropdown-content">
{$edit}
{$reportbutton}
{$delete}
</div>
</div>		
                </span>

            </div>
            <div class="statusfeed_full_text" id="status_text_{$SID}">
                    {$status}
            </div>
        </div>
    </td>
</tr>

<tr>
        <td class="trow2 statusfeed_full_bottom" valign="bottom">         
        <div class="smalltext statusfeed_full_bottomtext" >
                {$likebutton} {$replies}
        </div>
    </td>
</tr>
  
    
<tr id="comments_container_{$SID}" style="display: {$display_comments};  ">
    <td class="trow2 sf_trow2"> </td>
      <td class="trow1 statusfeed_commentlink">
        <div id="comments_{$SID}">
            {$comments}
        </div>
      </td> 
</tr>
</tbody>
';
    
$template['statusfeed_comments_container'] = '

<script>
$(function () {
$(\'.sf_comment_form_{$parent}\').on(\'submit\', function (e) {
     e.preventDefault();
     $.ajax({
       type: \'post\',
       url: \'misc.php?action=update_status&ajaxpost=true\',
       data: $(\'#sf_comment_form_{$parent}\').serialize(),
       success: function (data) {
           $(".sf_last_comment").fadeIn(550);
           $( data ).insertAfter( $( ".sf_comment_form_place_{$parent}" ));
           $(".sf_new_comment_input_{$parent}").val("");
           $(".sf_no_comments").remove();
           $("#comments_{$SID}").fadeIn(400);
           $(".sf_last_comment").fadeIn(550);
       }
      });
 });
});
</script>

<table border="0" cellspacing="0" cellpadding="2" class="sf_comment_container_{$parent} statusfeed_commentcontainer_table">
	{$viewall}
	{$feed}
	<tbody colspan="1" style="display: none;" class="sf_comment_form_place_{$parent}"></tbody>
  	<tr colspan="1" class="comment_form_row">
		<td class="trow1 statusfeed_commentcontainer_td1" rowspan="1" align="center" width="3%">
		<img src="{$viewer_avatar}" width="{$comment_parems[\'width\']}" height="{$comment_parems[\'height\']}" class="statusfeed_comment_full_img"/>
	</td>
		<td colspan="1" class="trow1 statusfeed_noborder_left_min">
			<form id="sf_comment_form_{$parent}" class="sf_comment_form_{$parent}">
				<input name="status" rows="2" value="{$lang->statusfeed_post_comment_textbox}" style="line-height: {$avatar_parems[\'height\']}px; " onfocus="if(this.value == \'{$lang->statusfeed_post_comment_textbox}\') {this.value=\'\';}" onblur="if(this.value==\'\') {this.value=\'{$lang->statusfeed_post_comment_textbox}\';}" class="sf_new_comment_input_{$parent} statusfeed_commentcontainer_input"><br />
				<input type="hidden" name="reply_id" value="{$parent}">
				<input type="hidden" name="post_key" value="{$mybb->post_code}">
			</form>
		</td>
	</tr>
</table>';
    
    $template['statusfeed_comment_mini'] = '
    <tbody id="status_{$SID}">
	<tr  class="status_{$SID} {$comment_id}" {$display_comment}>
		<td class="trow1 statusfeed_noborder" rowspan="2" align="center" width="2%" valign="top">
			<img src="{$avatar}" alt="Avatar of User" width="24" height="24"  class="statusfeed_avatar_img">
		</td>
	</tr>
	
	<tr class="{$comment_id}" {$display_comment}>
		<td class="trow1 statusfeed_mini_textbit" valign="top">
            <div class="smalltext statusfeed_postedby_mini">
                {$userlink}
                <span class="smalltext float_right">
                    {$date}&nbsp;						
                    <div class="dropdown_sf_fix">
                        <a href=\'javascript:;\' onclick="statusfeed_dropdown({$SID})" class="dropbtn dropbtn_sf">…</a>
                            <div id="sf_dropdown{$SID}" class="dropdown-content">
                                    {$edit}
                                    {$reportbutton}
                                    {$delete}
                            </div>
                    </div>	
                </span>
            </div>
            <div class="smalltext statusfeed_mini_text" id="status_text_{$SID}">
                {$status}
            </div>
            <div class="smalltext statusfeed_mini_info">
                    {$likebutton} 
            </div>
	    </td>
	</tr>
	
	<tr>
		<td class="trow1 statusfeed_full_comment_bottom" colspan="2">
			<div class="statusfeed_mini_comments_borderbottom_div"></div>
		</td>
	</tr>
</tbody>';

    $template['statusfeed_comment_full'] = '
<tr class="status_{$SID}" {$display_comment}>
    <td class="trow1 statusfeed_noborder_right" rowspan="2" align="center" width="2%" valign="top" style="{$border_fix}">
        <img src="{$avatar}" alt="Avatar of User" width="{$avatar_parems[\'width\']}" height="{$avatar_parems[\'height\']}" class="statusfeed_comment_full_img">
    </td>
</tr>
<tr class="{$comment_id}" {$display_comment}>
    <td class="trow1 statusfeed_noborder_left" style="{$border_fix}" valign="top">
        <div class="smalltext statusfeed_postedby_mini">
        {$userlink}
                    <span class="smalltext float_right">
                    {$reportbutton} {$likebutton} {$edit} &nbsp; {$date} 
            </span>
        </div>
        <div class="smalltext statusfeed_full_comment_text" id="status_text_{$SID}">
        {$status}
        </div>
        <div class="smalltext statusfeed_comment_padding">
            
        </div>
    </td>
</tr>
<tr>
    <td class="trow1 statusfeed_full_comment_bottom" colspan="2">
        <div class="statusfeed_full_comment_border"></div>
    </td>
</tr>  
';

    $template['statusfeed_likeButton'] = '
<a href="javascript:;" onclick=\'$("#numlikes_{$SID}").load("misc.php?action=like&statusid={$SID}&ajaxlike=true");\' class="statusfeed_likebutton_link"  title="{$likeButtonText}" id="likebutton_{$SID}"></a>
<div class="statusfeed_likebutton_span" style="display: inline-block;" id="numlikes_{$SID}"><span id="likespan_{$SID}">{$numlikes}</span></div>
';

    
    $template['statusfeed_reportButton'] = '
<a href="javascript:;" onclick=\'if (confirm("{$lang->statusfeed_report_confirm}")) { $("#statusfeed_outer_notification_container").load("misc.php?action=report&statusid={$SID}&ajaxreport=true");}\'>{$lang->statusfeed_reportbutton}</a>
    ';
/*
    $template['statusfeed_reportButton'] = '
<a href="javascript:;" onclick=\'$("#report_{$SID}").load("misc.php?action=report&statusid={$SID}&ajaxreport=true");\'  title="{$lang->statusfeed_reportbutton_text}">{$lang->statusfeed_reportbutton}</a>
    ';*/

    $template['statusfeed_postbit'] = '
	<div class="statusfeed_postbit wrap_text">$userstatus</div><br /><a href="#" onclick="MyBB.popupWindow(\'misc.php?action=statusfeed_popup&uid={$statusUID}\', null, true); return false;">{$lang->statusfeed_viewpopup}</a>
';	
    
    $template['statusfeed_edit'] = '

    <script>
	$(function () {
	$(\'#sf_delete_form\').on(\'submit\', function (e) {
			e.preventDefault();
			$.ajax({
			type: \'post\',
			url: \'misc.php?action=statusfeed_delete_status&ajaxdelete=true\',
			data: $(\'#sf_delete_form\').serialize(),
			success: function (data) {
				$( data ).insertAfter( $( "#statusfeed_outer_notification_container" ));
				$("#sf_delete_form").closest(\'tbody\').fadeOut("normal", function() {
					$(this).remove();
					});
				
				$("#statusfeed_header").get(0).scrollIntoView();
				$(\'.sf_ajax_newstatus\').fadeIn(550);
			}
			});
		});
	});	

	$(function () {
	$(\'#sf_edit_form\').on(\'submit\', function (e) {
			e.preventDefault();
			$.ajax({
			type: \'post\',
			url: \'misc.php?action=edit_status&ajaxedit=true\',
			data: $(\'#sf_edit_form\').serialize(),
			success: function (data) {
				$( "#status_{$ID}" ).replaceWith("<tbody>" + data + "</tbody>");
				$(".sf_last_comment").fadeIn(400);
				$(".sf_ajax_newstatus").fadeIn(500);
			}
			});
		});
	});	
</script>

    <form id="sf_edit_form" style="display: inline;">
    <textarea type="text" name="status" class="statusfeed_edit_textfield">{$status}</textarea><br />
    <input type="hidden" name="ID" value="{$ID}">
            <input type="hidden" name="UID" value="{$UID}">
            <input type="hidden" name="post_key" value="{$mybb->post_code}"><br />
            <input type="submit" style="display: inline;" value="{$lang->statusfeed_submit}" class="button">
</form>
                
<form id="sf_delete_form" style="display: inline;">
                <input type="hidden" name="ID" value="{$ID}">
                <input type="hidden" name="UID" value="{$UID}">
                <input type="hidden" name="post_key" value="{$mybb->post_code}">					
                <input type="hidden" name="reply_id" value="{$parent}">
                <input type="submit" value="{$lang->statusfeed_delete}" onclick="return confirm(\'{$lang->statusfeed_delete_confirm}\');" class="button">
</form>
        ';	

    $template['statusfeed_profile'] = '
    <div id="statusfeed_outer_notification_container"></div>
    <table border="0" cellspacing="0" cellpadding="4" class="tborder" id="status_container">

<tbody id="statusfeed_header">
    <tr>
        <td colspan="2" class="thead">
            <div>
                <strong>{$lang->statusfeed_updates}</strong>
            </div>
        </td>
    </tr>
</tbody>
    <div id="statusfeed_notification_container" style="display: table-row-group"></div>
    {$status_updates} 
    <tr>
        <td class="trow2" colspan="2">
        {$pagination}
            <form id="sf_form_profile">
                <div style="statusfeed_portal_formcontainer">
<textarea name="status" rows="2" class="statusfeed_all_textarea" onfocus="if(this.value == \'{$lang->statusfeed_update_status_textbox}\') {this.value=\'\';}" onblur="if(this.value==\'\') {this.value=\'{$lang->statusfeed_update_status_textbox}\';}">{$lang->statusfeed_update_status_textbox}</textarea>
                    <input type="hidden" name="wall_id" value="{$profile_UID}">
                    <input type="hidden" name="post_key" value="{$mybb->post_code}">
<input type="submit" value="{$lang->statusfeed_update_status}" class="statusfeed_block_submit button">
                </div>
            </form>
        </td>
    </tr>
</table>
<br />
';
    
    $template['statusfeed_all'] = '
    <html>
    <head>
        <title>{$mybb->settings[\'bbname\']}</title>
        {$headerinclude}	

    </head>
    <body>
        {$header} 
        <br />
        <div id="statusfeed_outer_notification_container"></div>
        <table border="0" cellspacing="0" cellpadding="4" class="tborder">
            <tbody  id="statusfeed_header">
            <tr>
                <td colspan="2" class="thead">
                    <div>
                        <strong>{$lang->statusfeed_updates}</strong>
                    </div>
                </td>
            </tr>
            </tbody>
            {$status_updates} 
            <tr>
                <td class="trow2" colspan="2">
                    {$pagination}
                    <br />
                    <form id="sf_form_all">
                        <div class="statusfeed_portal_formcontainer">
                            <textarea name="status" rows="2" class="statusfeed_all_textarea" onfocus="if(this.value == \'{$lang->statusfeed_update_status_textbox}\') {this.value=\'\';}" onblur="if(this.value==\'\') {this.value=\'{$lang->statusfeed_update_status_textbox}\';}">{$lang->statusfeed_update_status_textbox}</textarea>
                            <input type="hidden" name="wall_id" value="{$profile_UID}">
                            <input type="hidden" name="reply_id" value="-1">
                            <input type="hidden" name="post_key" value="{$mybb->post_code}">
                            <input type="submit" value="{$lang->statusfeed_update_status}" class="button statusfeed_all_submitbutton">
                        </div>
                    </form>
                </td>
            </tr>
        </table>
        {$footer}
    </body>
</html>';

    $template['statusfeed_portal'] = '	
<div id="statusfeed_outer_notification_container"></div>
<table border="0" cellspacing="0" cellpadding="3" class="tborder tborder_portal statusfeed_portal_table" id="statusfeed">
        
<tbody id="statusfeed_header">
    <tr>
        <td colspan="2" class="thead">
            <div>
                <strong>{$lang->statusfeed_updates}</strong>

            </div>
        </td>
    </tr>
</tbody>
    {$status_updates} 
    <tr>
        <td class="trow1" colspan="2">
        <form id="sf_form_{$statusStyle}">
                <div class="statusfeed_portal_formcontainer">
                    <textarea name="status" rows="2" class="statusfeed_portal_textarea" onfocus="if(this.value == \'{$lang->statusfeed_update_status_textbox}\') {this.value=\'\';}" onblur="if(this.value==\'\') {this.value=\'{$lang->statusfeed_update_status_textbox}\';}">{$lang->statusfeed_update_status_textbox}</textarea>
                    <input type="hidden" name="reply_id" value="-1">
                    <input type="hidden" name="post_key" value="{$mybb->post_code}">
<input type="submit" value="{$lang->statusfeed_update_status}" class="button statusfeed_portal_submit">
                </div>
            </form>
        {$statusfeed_viewall}
        </td>
    </tr>
</table> 
<br />
';

// Old: Remove the _{$StatusStyle} from the form IDs and uncomment the script. 

    $template['statusfeed_popup'] = '
    <div class=\'modal\'>
	<div style=\'overflow-y: auto; max-height: 500px;\'>
		<script>
			$(function () {
				$(\'#sf_form\').on(\'submit\', function (e) {
					e.preventDefault();
					$.ajax({
						type: \'post\',
						url: \'misc.php?action=update_status&ajaxpost=true&template={$statusStyle}\',
						data: $(\'#sf_form\').serialize(),
						success: function (data) {
							$( data ).insertAfter( $( "#statusfeed_header" ));
							$( "#statusfeed_form_marker" ).fadeOut( "slow" );
							$("#statusfeed_header").get(0).scrollIntoView();
							$(\'.sf_ajax_newstatus\').fadeIn(550);
						}
					});
				});
			});
		</script>
				
		<table border="0" cellspacing="0" cellpadding="4" class="tborder" style="margin-top: 0px;" id="status_container">
			<tbody id="statusfeed_header">
				<tr>
					<td colspan="2" class="thead thead_index">
						<div>
							<strong>{$lang->statusfeed_updates}</strong>
						</div>
					</td>
				</tr>
			</tbody>
				{$status_updates}
				<tr>
					<td class="trow2" colspan="2">
					{$pagination}
						<form id = "sf_form">
							<div class="statusfeed_portal_formcontainer">
		<textarea name="status" rows="2" class="statusfeed_portal_textarea" placeholder = "{$lang->statusfeed_write_status_popup}">{$lang->statusfeed_write_status_popup}</textarea>
								<input type="hidden" name="wall_id" value="{$profile_UID}">
								<input type="hidden" name="post_key" value="{$mybb->post_code}">
		<input type="submit" value="{$lang->statusfeed_update_status}" style="width: 100%; " class="button statusfeed_portal_submit">
							</div>
						</form>
					</td>
				</tr>
		</table>
	</div>
</div>	';

    $template['statusfeed_notifications_container'] = '
    <html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->statusfeed_usercp_link}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<form action="statusfeed.php?action=mark_all" method="post">
			<table width="100%" border="0" align="center">
				<tr>
					{$usercpnav}
					<td valign="top">
						{$pagination}
						<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
							<tr>
								<td class="thead" colspan="3"><strong>{$lang->statusfeed_usercp_link}</strong></td>
							</tr>
							<tr>
								<td class="tcat" width="70%"><strong>{$lang->statusfeed_alert}</strong></td>
								<td class="tcat" width="18%"><strong><center>{$lang->statusfeed_date}</center></strong></td>
								<td class="tcat" width="12%"><strong><center>{$lang->statusfeed_actions}</center></strong></td>
							</tr>
							{$notifications}
						</table>
						<br />
						<div align="center">
							<input type="hidden" name="post_key" value="{$mybb->post_code}">
							<input type="submit" class="button" name="submit" value="{$lang->statusfeed_mark_all}" />
						</div>
					</td>
				</tr>
		    </table>
		</form>
	{$footer}
	</body>
</html>
    ';	

    $template['statusfeed_notification'] = '
<tr>
	<td class="{$altbg}" style="font-weight: {$fontweight};">
		{$text}
	</td>
	<td class="{$altbg}">
		<center>{$date}</center>
	</td>
	<td class="{$altbg}">
		<center>{$mark}</center>
	</td>
</tr>';

    foreach($template as $title => $template_new){
        $template = array('title' => $db->escape_string($title), 'template' => $db->escape_string($template_new), 'sid' => '-1', 'version' => '140', 'dateline' => TIME_NOW);
        $db->insert_query('templates', $template);
    }


    $new_groupconfig = array(
        'name' => 'statusfeed', 
        'title' => $db->escape_string($lang->statusfeed_setting_group),
        'description' => $db->escape_string($lang->statusfeed_setting_group_desc),
        'disporder' => $rows+2,
        'isdefault' => 0
    );

    $group['gid'] = $db->insert_query("settinggroups", $new_groupconfig);
    $new_config = array();

    $new_config[] = array(
        'name' => 'statusfeed_enabled',
        'title' => $db->escape_string($lang->statusfeed_enabled),
        'description' => $db->escape_string($lang->statusfeed_enabled_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 0,
        'isdefault' => 1,
        'gid' => $group['gid']
    );

    $new_config[] = array(
        'name' => 'statusfeed_alerts_enable',
        'title' => $db->escape_string($lang->statusfeed_alerts_enable),
        'description' =>  $db->escape_string($lang->statusfeed_alerts_enable_desc),
        'optionscode' => 'select
2= '.$db->escape_string($lang->statusfeed_alerts_enable_myalerts).'
1= '.$db->escape_string($lang->statusfeed_alerts_enable_native).'
0= '.$db->escape_string($lang->statusfeed_alerts_enable_disable),
        'value' => '1',
        'disporder' => 1,
        'gid' => $group['gid']
    ); // Bad indentation required because of formatting issues. 

    $new_config[] = array(
        'name' => 'statusfeed_alertsperpage',
        'title' => $db->escape_string($lang->statusfeed_alertsperpage),
        'description' => $db->escape_string($lang->statusfeed_alertsperpage_desc),
        'optionscode' => 'text',
        'value' => '20',
        'disporder' => 2,
        'gid' => $group['gid']
    );	

    $new_config[] = array(
        'name' => 'statusfeed_enabled_profile',
        'title' => $db->escape_string($lang->statusfeed_enabled_profile),
        'description' => $db->escape_string($lang->statusfeed_enabled_profile_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 3,
        'isdefault' => 1,
        'gid' => $group['gid']
    );	

    $new_config[] = array(
        'name' => 'statusfeed_enabled_portal',
        'title' => $db->escape_string($lang->statusfeed_enabled_portal),
        'description' => $db->escape_string($lang->statusfeed_enabled_portal_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 5,
        'isdefault' => 1,
        'gid' => $group['gid']
    );
    
    $new_config[] = array(
        'name' => 'statusfeed_enabled_postbit',
        'title' => $db->escape_string($lang->statusfeed_enabled_postbit),
        'description' => $db->escape_string($lang->statusfeed_enabled_postbit_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 7,
        'isdefault' => 1,
        'gid' => $group['gid']
    );	

    $new_config[] = array(
        'name' => 'statusfeed_enabled_index',
        'title' => $db->escape_string($lang->statusfeed_enabled_index),
        'description' => $db->escape_string($lang->statusfeed_enabled_index_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 9,
        'isdefault' => 1,
        'gid' => $group['gid']
    );	
    
    $new_config[] = array(
        'name' => 'statusfeed_rowsperpage',
        'title' => $db->escape_string($lang->statusfeed_rowsperpage),
        'description' => $db->escape_string($lang->statusfeed_rowsperpage_desc),
        'optionscode' => 'text',
        'value' => '5',
        'disporder' => 15,
        'gid' => $group['gid']
    );
    
    $new_config[] = array(
        'name' => 'statusfeed_rowsperpage_all',
        'title' => $db->escape_string($lang->statusfeed_rowsperpage_all),
        'description' => $db->escape_string($lang->statusfeed_rowsperpage_all_desc),
        'optionscode' => 'text',
        'value' => '20',
        'disporder' => 17,
        'gid' => $group['gid']
    );		

    $new_config[] = array(
        'name' => 'statusfeed_likes_enable',
        'title' => $db->escape_string($lang->statusfeed_enable_likebutton),
        'description' => $db->escape_string($lang->statusfeed_enable_likebutton_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 19,
        'gid' => $group['gid']
    );

    $new_config[] = array(
        'name' => 'statusfeed_useredit',
        'title' => $db->escape_string($lang->statusfeed_useredit),
        'description' => $db->escape_string($lang->statusfeed_useredit_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 21,
        'isdefault' => 1,
        'gid' => $group['gid']
    );

    $new_config[] = array(
        'name' => 'statusfeed_comments_enable',
        'title' => $db->escape_string($lang->statusfeed_comments_enable),
        'description' => $db->escape_string($lang->statusfeed_comments_enable_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 23,
        'gid' => $group['gid']
    );		


    $new_config[] = array(
        'name' => 'statusfeed_commentsperpage',
        'title' => $db->escape_string($lang->statusfeed_commentsperpage),
        'description' => $db->escape_string($lang->statusfeed_commentsperpage_desc),
        'optionscode' => 'text',
        'value' => '7',
        'disporder' => 25,
        'gid' => $group['gid']
    );
    
    $new_config[] = array(
        'name' => 'statusfeed_moderator_groups',
        'title' => $db->escape_string($lang->statusfeed_moderator_usergroups),
        'description' => $db->escape_string($lang->statusfeed_moderator_usergroups_desc),			
        'optionscode' => 'groupselect',
        'value' => '3, 4',
        'disporder' => 27,
        'gid' => $group['gid']
    );

    $new_config[] = array(
        'name' => 'statusfeed_maxlength',
        'title' => $db->escape_string($lang->statusfeed_maxlength),
        'description' => $db->escape_string($lang->statusfeed_maxlength_desc),
        'optionscode' => 'text',
        'value' => '512',
        'disporder' => 29,
        'gid' => $group['gid']
    );

    $new_config[] = array(
        'name' => 'statusfeed_mini_truncate',
        'title' => $db->escape_string($lang->statusfeed_mini_truncate),
        'description' => $db->escape_string($lang->statusfeed_mini_truncate_desc),
        'optionscode' => 'yesno',
        'value' => '1',
        'disporder' => 31,
        'isdefault' => 1,
        'gid' => $group['gid']
    );

    $new_config[] = array(
        'name' => 'statusfeed_mini_truncate_length',
        'title' => $db->escape_string($lang->statusfeed_mini_truncate_length),
        'description' => $db->escape_string($lang->statusfeed_mini_truncate_length_desc),
        'optionscode' => 'text',
        'value' => '144',
        'disporder' => 33,
        'gid' => $group['gid']
    );
    
    $new_config[] = array(
        'name' => 'statusfeed_avatarsize_full',
        'title' => $db->escape_string($lang->statusfeed_avatarsize_full),
        'description' => $db->escape_string($lang->statusfeed_avatarsize_full_desc),
        'optionscode' => 'text',
        'value' => '48x48',
        'disporder' => 35,
        'gid' => $group['gid']
    );
    
    $new_config[] = array(
        'name' => 'statusfeed_avatarsize_mini',
        'title' => $db->escape_string($lang->statusfeed_avatarsize_mini),
        'description' => $db->escape_string($lang->statusfeed_avatarsize_mini_desc),
        'optionscode' => 'text',
        'value' => '32x32',
        'disporder' => 37,
        'gid' => $group['gid']
    );	

    $new_config[] = array(
        'name' => 'statusfeed_max_comments',
        'title' => $db->escape_string($lang->statusfeed_max_comments),
        'description' => $db->escape_string($lang->statusfeed_max_comments_desc),
        'optionscode' => 'text',
        'value' => '50',
        'disporder' => 39,
        'gid' => $group['gid']
    );		

    foreach($new_config as $array => $content) {
        $db->insert_query("settings", $content);
    }
    rebuild_settings();

} // end install



function statusfeed_uninstall () {
    global $db;
    $info = statusfeed_info();
    
    if($db->table_exists('statusfeed')) {
        $db->drop_table('statusfeed');
    }
    
    if($db->table_exists('statusfeed_alerts')) {
        $db->drop_table('statusfeed_alerts');
    }

    if($db->table_exists('statusfeed_likes')) {
        $db->drop_table('statusfeed_likes');
    }
    
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `sf_unreadcomments`;");
    $db->write_query("ALTER TABLE `".TABLE_PREFIX."users` DROP `sf_currentstatus`;");
    
    $templates_to_remove = array('statusfeed_portal', 'statusfeed_popup', 'statusfeed_profile', 'statusfeed_comment_mini', 'statusfeed_comment_full', 'statusfeed_likeButton', 'statusfeed_reportButton', 'statusfeed_postbit', 'statusfeed_notifications_container', 'statusfeed_notification', 'statusfeed_postbit', 'statusfeed_edit', 'statusfeed_all', 'statusfeed_post_full', 'statusfeed_post_mini', 'statusfeed_comments_container');
    foreach($templates_to_remove as $data) {
        $db->delete_query('templates', "title = '{$data}'");
    }

    $query = $db->simple_select('settinggroups', 'gid', 'name = "statusfeed"');
    $groupid = $db->fetch_field($query, 'gid');
    $db->delete_query('settings','gid = "'.$groupid.'"');
    $db->delete_query('settinggroups','gid = "'.$groupid.'"');
    rebuild_settings();		
} // end uninstall

function statusfeed_activate () {
    global $db;
    require MYBB_ROOT.'/inc/adminfunctions_templates.php'; 

    find_replace_templatesets('member_profile', '#{\$adminoptions}#', '{\$adminoptions}<!-- StatusFeed -->{$statusfeed_profile}<!-- /StatusFeed -->');
    find_replace_templatesets('portal', '#{\$pms}#', '{\$pms}<!-- StatusFeed -->{$statusfeed}<!-- /StatusFeed -->');
    find_replace_templatesets('header', '#{\$unreadreports}#', '{$unreadreports}<!-- StatusFeed --> {$unread_statuses} <!-- /StatusFeed -->');
    find_replace_templatesets('usercp_nav_misc', '#{\$lang->ucp_nav_editlists}</a></td></tr>#', '{\$lang->ucp_nav_editlists}</a></td></tr><!-- StatusFeed --><tr><td class="trow1 smalltext"><a href="usercp.php?action=statusfeed" class="usercp_nav_item usercp_nav_viewprofile">{\$lang->statusfeed_usercp_link}</a></td></tr><!-- /StatusFeed -->');
    find_replace_templatesets('index', '#{\$forums}#', '{$forums}<!-- StatusFeed --> {$statusfeed} <!-- /StatusFeed -->');
    find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'groupimage\']}').'#', '{$post[\'groupimage\']}{$post[\'statusfeed\']}');	
    find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'groupimage\']}').'#', '{$post[\'groupimage\']}{$post[\'statusfeed\']}');		
    
    // Headerinclude for javascript: 
    find_replace_templatesets('headerinclude', '#{\$stylesheets}#', '
    <!-- StatusFeed --> 
    <script type="text/javascript" src="{$mybb->asset_url}/jscripts/statusfeed.js?ver=1824"></script>
    <script type="text/javascript" src="{$mybb->asset_url}/jscripts/jquery-ui-tooltip.min.js?ver=1824"></script> 
    <!-- /StatusFeed -->
    {$stylesheets}');

    $stylesheet = "
    .statusfeed_all_textarea {
        width: 100%; color: #636363; width: 100%; -webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px;
    }
    
    .statusfeed_all_submit {
        -webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px;
    }
    
    .statusfeed_block_submit {
        -webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px; width: 100%; 
    }
    
    .statusfeed_avatarfull_td {
        border-right: none; padding-top: 0px;
    }
    
    .statusfeed_avatarmini_td {
        border: none; padding-top: 0px;
    }
    
    .statusfeed_avatar_img {
        border: 1px solid #b6b6b6; border-radius: 3px; -moz-border-radius: 3px; padding: 2px; margin: 2px;
    }
    
    .statusfeed_comment_full_img {
        border: 1px solid #b6b6b6; border-radius: 3px; -moz-border-radius: 3px; padding: 2px;
    }
    
    .statusfeed_postedby {
        font-size: 10px; border: none; color: #4A4A4A; padding-top: 2px;
    }
    
    .statusfeed_postedby_mini {
        font-size: 10px; border: none; color: #4A4A4A;
    }
    
    .statusfeed_full_info {
        position:relative; height: 100%;
    }
    
    .statusfeed_mini_info {
        padding-top: 1px; padding-bottom: 3px;
    }
    
    .statusfeed_full_bottom {
        padding-bottom: 0px; border-top: none; border-left: none;
    }
    
    .statusfeed_full_bottomtext {
        padding-top: 1px; padding-bottom: 5px;
    }
    
    .statusfeed_commentlink {
        padding: 0px; padding-left: 4px;
    }
    
    .statusfeed_full_text {	
        padding-top: 1px; font-size: 13px;
    }
    
    .statusfeed_mini_text {
        padding-top: 2px;	
    }
    
    .statusfeed_full_comment_text {
        padding-top: 2px; font-size: 12px;
    }
    
    .statusfeed_full_textbit {
        padding-bottom: 0px; border-left: none; border-bottom: none;
    }
    
    .statusfeed_mini_textbit {
        padding-bottom: 0px; border: none;
    }
    
    .statusfeed_mini_comments {
        border: none; border-top: 1px solid #d6d6d6;
    }
    
    .statusfeed_mini_comments_inner {
        border-right: none; border-bottom: none; border-top: 1px solid#d6d6d6; padding-left: 5px;
    }
    
    .statusfeed_mini_comments_borderbottom_td, statusfeed_full_comment_bottom {
        padding: 0px; border: none;
    }
    
    .statusfeed_full_comment_border {
        border-bottom: 1px solid #d6d6d6;
    }
    
    .statusfeed_mini_comments_borderbottom_div {
        border-bottom: 1px solid #d6d6d6;
    }
    
    .statusfeed_noborder_right {
        border-right: none; 
    }
    
    .statusfeed_noborder_left {
        padding-bottom: 0px; border-left: none; padding-right: 15px; 
    }
    
    .statusfeed_noborder {
        border: none;
    }
    
    .statusfeed_noborder_left_min {
        border-left: none; padding-right: 15px;
    }
    
    .statusfeed_comment_padding {
        padding-top: 1px; padding-bottom: 3px;
    }
    
    .statusfeed_portal_table {
        background: #ffffff; min-width: 230px;
    }
    
    .statusfeed_portal_textarea {
        width: 100%; color: #636363; width: 100%; -webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 0px;
    }
    
    .statusfeed_portal_submit {	
        width: 100%; -webkit-border-radius: 2px; -moz-border-radius: 2px; border-radius: 2px; 
    }
    
    .statusfeed_portal_formcontainer {
        padding: 10px;
    }
    
    .statusfeed_likebutton_span {
        margin-left: 3px; margin-right: 7px;
    }
    
    .statusfeed_likebutton_link {
        padding: 1px 0 1px 16px; background-image: url(images/usercp_sprite.png); background-repeat: no-repeat; background-position: 0 -280px;
    }

    .statusfeed_reportbutton_link {
        padding: 1px 0 1px 16px; background-image: url(images/usercp_sprite.png); background-repeat: no-repeat; background-position: 0 -80px; margin-right: -3px;
    }
    
    .statusfeed_commentcontainer_table {
        width: 100%; width: 100%; border-left: 3px solid #E2E2E2; padding-left: 5px;
    }
    
    .statusfeed_commentcontainer_td1 {
        padding: 4px; border-right: none; 
    }
    
    .statusfeed_commentcontainer_input {
        -webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 0px; color: #636363; width: 100%; 
    }
    
    .statusfeed_edit_textfield {
        width: 75%; height: 40px;
    }	

    /* Dropdown Button */
    .dropbtn {
      cursor: pointer;
    }
    
    /* Beta 2: Remove this: */ 
    /* Dropdown button on hover & focus */
    .dropbtn:hover, .dropbtn:focus {
      background-color: #2980B9;
    }
    
    /* Dropdown button on hover & focus */
    .dropbtn_sf:hover, .dropbtn_sf:focus {
      background-color: unset !important;
      color: unset !important;
    }

    /* The container <div> - needed to position the dropdown content */
    .dropdown {
      position: relative;
      display: inline-block;
    }

    /* Fix for some themes */
    .dropdown_sf_fix {
        width: unset; 
        display: inline-block; 
    }
    
    /* Dropdown Content (Hidden by Default) */
    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #f1f1f1;
      min-width: 110px;
      right: 4px; 
      box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
      z-index: 1;
    }
    
    /* Links inside the dropdown */
    .dropdown-content a {
      color: black;
      padding: 6px 10px;
      text-decoration: none;
      display: block;
    }
    
    /* Change color of dropdown links on hover */
    .dropdown-content a:hover {background-color: #ddd}
    
    /* Show the dropdown menu (use JS to add this class to the .dropdown-content container when the user clicks on the dropdown button) */
    .show {display:block;}

	.likeButton_tooltip {
		background: #000000 !important;
		width: auto !important; 
		max-width: 300px !important;
		color: #ffffff !important; 
		border-radius: 4px !important; 
		padding: 4px !important;
		border: 1px solid #dddddd !important; 
    }

    .statusfeed_commentcontainer_table .trow1, .statusfeed_commentcontainer_table .trow2 {
        padding: 3px; 
    }


    .sf_trow2 {
        background: #f1f1f1 !important;
    }
    
    .sf_trow2, .statusfeed_commentlink {
        border-bottom: 1px solid #dddddd !important;
        border-radius: 0px !important;
    }
    
    /*! jQuery UI - v1.12.1 - 2020-10-29
    * http://jqueryui.com
    * Includes: core.css, tooltip.css
    * Copyright jQuery Foundation and other contributors; Licensed MIT */
    
    /* Layout helpers
    ----------------------------------*/
    .ui-helper-hidden {
        display: none;
    }
    .ui-helper-hidden-accessible {
        border: 0;
        clip: rect(0 0 0 0);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
    }
    .ui-helper-reset {
        margin: 0;
        padding: 0;
        border: 0;
        outline: 0;
        line-height: 1.3;
        text-decoration: none;
        font-size: 100%;
        list-style: none;
    }
    .ui-helper-clearfix:before,
    .ui-helper-clearfix:after {
        content: '';
        display: table;
        border-collapse: collapse;
    }
    .ui-helper-clearfix:after {
        clear: both;
    }
    .ui-helper-zfix {
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        position: absolute;
        opacity: 0;
        filter:Alpha(Opacity=0); /* support: IE8 */
    }
    
    .ui-front {
        z-index: 100;
    }
    
    
    /* Interaction Cues
    ----------------------------------*/
    .ui-state-disabled {
        cursor: default !important;
        pointer-events: none;
    }
    
    
    /* Icons
    ----------------------------------*/
    .ui-icon {
        display: inline-block;
        vertical-align: middle;
        margin-top: -.25em;
        position: relative;
        text-indent: -99999px;
        overflow: hidden;
        background-repeat: no-repeat;
    }
    
    .ui-widget-icon-block {
        left: 50%;
        margin-left: -8px;
        display: block;
    }
    
    /* Misc visuals
    ----------------------------------*/
    
    /* Overlays */
    .ui-widget-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    .ui-tooltip {
        padding: 8px;
        position: absolute;
        z-index: 9999;
        max-width: 300px;
    }
    body .ui-tooltip {
        border-width: 2px;
    }
    ";

    statusfeed_insert_stylesheet($stylesheet); // No plugin library required! 
    statusfeed_myalerts_integrate(); // See myalerts_functions.php. 
}

function statusfeed_deactivate() {
    global $db, $stylesheet;
    require MYBB_ROOT.'/inc/adminfunctions_templates.php';

    find_replace_templatesets('member_profile', '#\<!--\sStatusFeed\s--\>\{\$([a-zA-Z_]+)?\}<!--\s/StatusFeed\s--\>#is', '', 0);
    find_replace_templatesets('portal', '#\<!--\sStatusFeed\s--\>\{\$([a-zA-Z_]+)?\}<!--\s/StatusFeed\s--\>#is', '', 0);
    find_replace_templatesets('usercp_nav_misc', '#\<!--\sStatusFeed\s--\>(.+)\<!--\s/StatusFeed\s--\>#is', '', 0);
    find_replace_templatesets('header', '#\<!--\sStatusFeed\s--\>(.+)\<!--\s/StatusFeed\s--\>#is', '', 0);
    find_replace_templatesets('headerinclude', '#\<!--\sStatusFeed\s--\>(.+)\<!--\s/StatusFeed\s--\>#is', '', 0);
    find_replace_templatesets('index', '#\<!--\sStatusFeed\s--\>(.+)\<!--\s/StatusFeed\s--\>#is', '', 0);

    find_replace_templatesets("postbit_classic", '#'.preg_quote('{$post[\'statusfeed\']}').'#', '',0);
    find_replace_templatesets("postbit", '#'.preg_quote('{$post[\'statusfeed\']}').'#', '',0);
    
    statusfeed_remove_stylesheet($sf_stylesheet);
    statusfeed_myalerts_unintegrate(); // See myalerts_functions.php
}

function statusfeed_is_installed() {
    global $db;
    if($db->table_exists('statusfeed')) {
        return true;
    }
    return false;
}