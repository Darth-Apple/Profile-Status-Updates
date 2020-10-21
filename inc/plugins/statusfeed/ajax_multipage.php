<?php

/* This file provides the MyBB mutlipage function, adapted for ajax use. */ 

/**
 * Generate a listing of page - pagination
 *
 * @param int $count The number of items
 * @param int $perpage The number of items to be shown per page
 * @param int $page The current page number
 * @param string $url The URL to have page numbers tacked on to (If {page} is specified, the value will be replaced with the page #)
 * @param boolean $breadcrumb Whether or not the multipage is being shown in the navigation breadcrumb
 * @return string The generated pagination
 */
function multipage_ajax($count, $perpage, $page, $url, $profile_UID, $breadcrumb=false) {
	global $theme, $templates, $lang, $mybb;

	if($count <= $perpage) {
		return '';
	}

	$page = (int)$page;
	$url = str_replace("&amp;", "&", $url);
	$url = htmlspecialchars_uni($url);
	$pages = ceil($count / $perpage);
	$prevpage = '';

	if($page > 1) {
		$prev = $page-1;
        $page_url = fetch_page_url($url, $prev);
        
        $onclickElPrev = ' onclick=\'$("#status_container")' . '.hide().load("statusfeed.php?action=pageajax&uid='.(int) $profile_UID.'&page='.(int)$prev.'").fadeIn(525); \'';
        $multipage_prevpage = "<a href='javascript:;' class='pagination_previous'";
        $multipage_prevpage_suffix = $onclickElPrev.">{$lang->previous}</a>";
        $prevpage = $multipage_prevpage . $multipage_prevpage_suffix;

		//eval("\$prevpage = \"".$templates->get("multipage_prevpage")."\";");
	}

	// Maximum number of "page bits" to show
	if(!$mybb->settings['maxmultipagelinks']) {
		$mybb->settings['maxmultipagelinks'] = 5;
	}

	$from = $page-floor($mybb->settings['maxmultipagelinks']/2);
	$to = $page+floor($mybb->settings['maxmultipagelinks']/2);

	if($from <= 0) {
		$from = 1;
		$to = $from+$mybb->settings['maxmultipagelinks']-1;
	}

	if($to > $pages) {
		$to = $pages;
		$from = $pages-$mybb->settings['maxmultipagelinks']+1;
		if($from <= 0) {
			$from = 1;
		}
	}

	if($to == 0) {
		$to = $pages;
	}

	$start = '';
	if($from > 1) {
		if($from-1 == 1) {
			$lang->multipage_link_start = '';
		}

		$page_url = fetch_page_url($url, 1);
		eval("\$start = \"".$templates->get("multipage_start")."\";");
	}

	$mppage = '';
	for($i = $from; $i <= $to; ++$i) {
       // MyBB only has a URL field available for URL parameters. we're gonna create our own links. 
       // This allows us to 
        // $postCode = $mybb->post_code;

        $onclickEl = ' onclick=\'$("#status_container")' . '.hide().load("statusfeed.php?action=pageajax&uid='.(int) $profile_UID.'&page='.(int)$i.'").fadeIn(525); \'';

        $multipage = "<a href='javascript:;' class='pagination_page'";
        $multipage_current = "<a href='javascript:;' class='pagination_current' ";
        $multipage_nextpage = "<a href='javascript:;' class='pagination_next' ";
        $multipage_end = "{$lang->multipage_link_end} <a href='javascript:;' class='pagination_last' ";
        
        $multipage_suffix = $onclickEl.">{$i}</a>";
        $multipage_suffix_end = $onclickEl.">{$pages}</a>";
        $multipage_suffix_nextpage = $onclickEl.">{$lang->next} </a>";

       // $multipage_current = ' <span class="pagination_current">{$i}</span>';
        if($page == $i) {
			/*if($breadcrumb == true)
			{
				eval("\$mppage .= \"".$templates->get("multipage_page_link_current")."\";");
			}
			else
            {*/
                $mppage .= $multipage_current . $multipage_suffix;
				// eval("\$mppage .= \"".$templates->get("multipage_page_current")."\";");
			/* }*/
		}
		else {
            $mppage .= $multipage . $multipage_suffix;
			// eval("\$mppage .= \"".$templates->get("multipage_page")."\";");
		}
	}

	$end = '';
	if($to < $pages) {
		if($to+1 == $pages) {
			$lang->multipage_link_end = '';
		}

		// Bug fix
		$onclickEl = ' onclick=\'$("#status_container")' . '.hide().load("statusfeed.php?action=pageajax&uid='.(int) $profile_UID.'&page='.(int)$pages.'").fadeIn(525); \'';
        $multipage_suffix = $onclickEl.">{$pages}</a>";
        $multipage_suffix_end = $onclickEl.">{$pages}</a>";
        // $page_url = fetch_page_url($url, $pages);
		$mppage .= $multipage_end . $multipage_suffix_end;
	
		// eval("\$end = \"".$templates->get("multipage_end")."\";");
	}

	$nextpage = '';
	if($page < $pages) {
		$next = $page+1;
        $page_url = fetch_page_url($url, $next); // old: next
        $nextpage = $multipage_nextpage . $multipage_suffix_nextpage; 
		// eval("\$nextpage = \"".$templates->get("multipage_nextpage")."\";");
	}
	
	$jumptopage = '';
	if($pages > ($mybb->settings['maxmultipagelinks']+1) && $mybb->settings['jumptopagemultipage'] == 1) {
		// When the second parameter is set to 1, fetch_page_url thinks it's the first page and removes it from the URL as it's unnecessary
		$jump_url = fetch_page_url($url, $pages);
		eval("\$jumptopage = \"".$templates->get("multipage_jump_page")."\";");
	}

	$multipage_pages = $lang->sprintf($lang->multipage_pages, $pages);

	/*if($breadcrumb == true)
	{
		eval("\$multipage = \"".$templates->get("multipage_breadcrumb")."\";");
	}
	else
	{*/
		eval("\$multipage = \"".$templates->get("multipage")."\";");
	// }

	return $multipage;
}


?>