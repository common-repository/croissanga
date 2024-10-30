<?php
/*
Plugin Name: Croissanga
Plugin URI: http://ryanlee.org/software/wp/croissanga/
Description: Re-posts published posts to <a href="http://www.xanga.com/">Xanga</a> with directions back to the original Wordpress blog for updates and comments.  Also edits and deletes according to WordPress' actions.  REQUIRES the PHP CURL module!  Licensed under BSD license in LICENSE.txt.  This version is not backwards compatible with WordPress versions earlier than 1.5 due to WordPress architectural changes.  See version 1.1.1 for a WordPress 1.2 compatible version.
Version: 1.3.1
Author: Ryan Lee
Author URI: http://ryanlee.org/
*/

include('options/croissanga-db.php');

// Changes per-version
$croissanga_version = "1.3.1";

// insert the xanga ID associated with the post
function xanga_map_set_xanga_id($postID, $xangaPostID) {
	global $wpdb;
	$insert_dml = "INSERT INTO xanga_wp_post_map (post_ID, xanga_ID) VALUES ($postID, '$xangaPostID')";
	$rowcount = $wpdb->query($insert_dml);
	if ($rowcount == 1) {
		// successful insert
		return true;
	} else {
		// failure
		return false;
	}
}

// determine if the post has been placed in xanga yet
function xanga_map_xanga_id_exists($postID) {
	global $wpdb;
	$query = "SELECT COUNT(*) FROM xanga_wp_post_map WHERE post_ID = $postID";
	$exists = $wpdb->get_var($query);
	if ($exists == 1) {
		return true;
	} else {
		return false;
	}
}

// fetch the xanga ID associated with the post
function xanga_map_get_xanga_id($postID) {
	global $wpdb;
	$query = "SELECT xanga_ID FROM xanga_wp_post_map WHERE post_ID = $postID";
	$xangaID = $wpdb->get_var($query);
	return $xangaID;
}

function xanga_map_delete_map($postID) {
	global $wpdb;
	$delete_dml = "DELETE FROM xanga_wp_post_map WHERE post_ID = $postID";
	$rowcount = $wpdb->query($delete_dml);
	if ($rowcount == 1) {
		// successful deletion
		return true;
	} else {
		// failure
		return false;
	}
}

function xanga_fetch_login_key() {
	$xanga_login_page = "http://www.xanga.com/signin.aspx";

	$ch = curl_init($xanga_login_page);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$page = curl_exec($ch);
	if (ereg('__VIEWSTATE" value="([^"]*)"', $page, $out)) {
		return $out[1];
	}
}

function xanga_fetch_login_cookie($key) {
	$xanga_username = get_option('croissanga_xanga_username');
	$xanga_password = get_option('croissanga_xanga_password');

	$xanga_userparam  = "txtSigninUsername";
	$xanga_passparam  = "txtSigninPassword";
	$xanga_lkeyparam  = "__VIEWSTATE";
	$xanga_login_page = "http://www.xanga.com/signin.aspx";

	$vars = "__EVENTTARGET=&__EVENTARGUMENT=&";
	$vars .= $xanga_lkeyparam . "=" . urlencode($key) . "&";
	$vars .= $xanga_userparam . "=" . urlencode($xanga_username) . "&";
	$vars .= $xanga_passparam . "=" . urlencode($xanga_password) . "&";
	$vars .= "signInButton=Sign+In&registrationModule%24txtUsername=&registrationModule%24txtPassword1=&registrationModule%24txtPassword2=&registrationModule%24txtEmail=&registrationModule%24txtLetters=&registrationModule%24DOB_month=A&registrationModule%24DOB_day=A&registrationModule%24DOB_year=A";

	$ch = curl_init($xanga_login_page);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_COOKIE, "Referrer=http://www.xanga.com; t=1");
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);

	$page = curl_exec($ch);

	$cookie  = "";
	
	if (ereg('Set-Cookie: (u=[^;]*)', $page, $out)) {
		$cookie .= $out[1] . "; ";
	}

	if (ereg('Set-Cookie: (x=[^;]*)', $page, $out)) {
		$cookie .= $out[1] . "; ";
	}

	if (ereg('Set-Cookie: (y=[^;]*)', $page, $out)) {
		$cookie .= $out[1];
	}

	$cookie = "Referrer=http://www.xanga.com/; t=1; " . $cookie;

	return $cookie;
}

function xanga_fetch_posting_key($cookie) {
	$xanga_premium = get_option('croissanga_xanga_premium');

	$xanga_post_page  = "http://www.xanga.com/private/editorplain.aspx";
	if ($xanga_premium == 1) {
		$xanga_post_page = "http://premium.xanga.com/private/editorplain.aspx?plain=1";
	}

	$ch = curl_init($xanga_post_page);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_COOKIE, $cookie);

	$page = curl_exec($ch);
	$key  = "";

	if (ereg('__VIEWSTATE" value="([^"]*)"', $page, $out)) {
		$key .= $out[1];
	}

	return urlencode($key);
}

// prototypical function for xanga posting actions
function xanga_post_action($action, $ID, $pw_protected) {
    global $post;
	$xanga_title = get_option('croissanga_xanga_title');
	$xanga_comments = get_option('croissanga_xanga_comments');
	$xanga_premium = get_option('croissanga_xanga_premium');
    $xanga_excerpts = get_option('croissanga_xanga_excerpts');

	$xanga_commentparam = "";
	if ($xanga_comments == 1) {
		$xanga_commentparam = "chkComments=on&";
	}

	if ($action == 'delete') {
		$actionvar = "btnDelete=Delete";
		$isNewVar = "IsBrandNew=0";
		if (!xanga_map_xanga_id_exists($ID)) {
			return;
		}
		$xangaID = xanga_map_get_xanga_id($ID);
		$uniqueparam = "weuniqueid=" . $xangaID;
	} elseif ($action == 'edit') {
		$actionvar = "btnSave=Save+Changes";
		$isNewVar = "IsBrandNew=0";
		if (!xanga_map_xanga_id_exists($ID)) {
			return;
		}
		$xangaID = xanga_map_get_xanga_id($ID);
		$uniqueparam = "weuniqueid=" . $xangaID;
	} elseif ($action == 'post') {
		$actionvar = "btnSave=Save+Changes";
		$isNewVar = "IsBrandNew=1";
		$xangaID = "";
		$uniqueparam = "";
	}

	$cookie = xanga_fetch_login_cookie(xanga_fetch_login_key());
	$key    = xanga_fetch_posting_key($cookie); // already urlencoded
	$uid    = "";

	if (ereg('u=([0-9]*)', $cookie, $out)) {
		$uid = $out[1];
	}

	$xanga_post_page  = "http://www.xanga.com/private/editorplain.aspx";
	if ($xanga_premium == 1) {
		$xanga_post_page = "http://premium.xanga.com/private/editorplain.aspx";
	}

	$xanga_lkeyparam  = "__VIEWSTATE";
	$xanga_bodyparam  = "bodyvalue";
	$xanga_titleparam = "txtTitle";
	$xanga_accsparam  = "weprivacy";
	// 1 = public access (default)
	// 2 = private access
	// 3 = protected access (triggered only if post is password protected
	//     AND xanga_protected option is 1)
	$xanga_access = "1";
	if ($pw_protected == 1) {
		$xanga_access = "3";
	}

	if ($action == 'delete' || $action == 'edit') {
		$xanga_post_page .= "?uid=" . $xangaID;
	}

	if ($action == 'edit' || $action == 'post') {
		query_posts('p=' . $ID);
		the_post();
		$title = the_title('', '', false);
		if ($xanga_title != 1) {
			$head = "<h3>" . $title . "</h3>\n";
			$title = "";
		}
		$head .= "<p><em>This entry was <a href=\"" . get_permalink($ID) . "\">originally published</a>";
		if ($xanga_authors == 1) {
			$head .= " by " . the_author();
		}
		$head .= " at <a href=\"" . get_settings('home') . "\">" . get_settings('blogname') . "</a></em></p>\n";
		$foot = "";
		if ($xanga_comments != 1) {
			$foot = "<p><em><a href=\"" . get_permalink($ID) . "#comments\">Leave / read comments</a></em></p>\n";
		}

		if ($xanga_excerpts == 1) {
			global $page, $more;
			$page = 1;
			$more = 0;
			$content = get_the_content('[read the remainder of this post]', 0);
		} else {
			$content = get_the_content();
		}
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		$entry = $head . $content . $foot;
	} elseif ($action == 'delete') {
		$entry = "gone";
	}

	$vars  = "__EVENTTARGET=&__EVENTARGUMENT=&";
	$vars .= $xanga_lkeyparam . "=" . $key . "&";
	$vars .= $xanga_titleparam . "=" . urlencode($title) . "&";
	$vars .= "spellcheckbtn=false&";
	$vars .= $xanga_bodyparam . "=" . urlencode($entry) . "&";
	$vars .= "tnames=&ptag=tagname&pusertag=usertagname&proftitle0=&";
	$vars .= "proftitle1=&proftitle3=&";
	$vars .= "xztitle1=&xztitle2=&xzasin1=&xzextravlue=&";
	$vars .= $xanga_accsparam . "=" . $xanga_access . "&";
	$vars .= "txtRating=&pblogring=blogringname&";
	$vars .= $actionvar;
	$vars .= "epropcurstate=0&commentcnt=0&webgcolor=&";
	$vars .= "webdcolor=&tpref2=&tpref3=&tpref4=&tpref5=&cancel=&";
	$vars .= "postvalues=&photovalues=&videovalues=&audiovalues=&";
	$vars .= "mediaargs=&" . $isNewVar . "&";
	$vars .= "tagsoriginal=&tagsoriginalu=&tagstodelete=%2C&";
	$vars .= "tagstoadd=%2C&mediadateformatstr=M%2Fd%2Fyyyy&";
	$vars .= "usertagsoriginal=&usertagstodelete=%2C&";
	$vars .= "usertagstoadd=%2C&blogringsoriginal=&";
	$vars .= "blogringstodelete=%3C%3E&blogringstoadd=%3C%3E&";
	$vars .= "autoaddedblogring=&";
	$vars .= $xanga_commentparam;
	$vars .= "tprefid=" . $uid . "&" . $uniqueparam;

	$ch = curl_init($xanga_post_page);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
	curl_setopt($ch, CURLOPT_HEADER, TRUE);

	$page = curl_exec($ch);
}

function xanga_post_entry($ID, $pw_protected) {
	$xanga_list_page  = "http://www.xanga.com/private/yourhome.aspx";

	xanga_post_action('post', $ID, $pw_protected);

	// pick up the latest post ID; this isn't guaranteed to be
	// correct, but it's highly unlikely to be wrong
	$cookie = xanga_fetch_login_cookie(xanga_fetch_login_key());
	$ch = curl_init($xanga_list_page);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	$page = curl_exec($ch);

	$xangaID = "";
	if (ereg('\.aspx\?uid=([^"]*)">edit it', $page, $out)) {
		$xangaID .= $out[1];
	}

	xanga_map_set_xanga_id($ID, $xangaID);	

	return $ID;
}

// edit a post already existing in Xanga
function xanga_edit_post($ID, $pw_protected) {
	xanga_post_action('edit', $ID, $pw_protected);
	return $ID;
}

// delete an existing entry if the Xanga ID exists
function xanga_delete_post($ID) {
    if (croissanga_check_configuration()) return;
    
	xanga_post_action('delete', $ID, 0);
	xanga_map_delete_map($ID);
	return $ID;
}

// if, on editing a post, it gains password protection and
// was previously posted to xanga, delete it; if it lost it,
// xanga_dispatch will cover that cases
// also, if the status has changed to something other than
// 'publish,' delete from xanga
function xanga_edit_dispatch($ID) {
	global $wpdb;
    if (croissanga_check_configuration()) return;
	    
	$xanga_protected = get_option('croissanga_xanga_protected');
	$deleted = false;
    $pw_len = strlen($wpdb->get_var("SELECT post_password FROM $wpdb->posts WHERE id = '$ID';"));
	if ($pw_len > 0 && $xanga_protected == 0) {
		if (xanga_map_xanga_id_exists($ID)) {
			xanga_delete_post($ID);
			$deleted = true;
		}
	}
	if ($wpdb->get_var("SELECT post_status FROM $wpdb->posts WHERE id = '$ID';") != "publish" && !$deleted) {
		if (xanga_map_xanga_id_exists($ID)) {
			xanga_delete_post($ID);
		}
	}
	return $ID;
}

function xanga_certain_dispatch($ID) {
	global $wpdb;
	$xanga_protected = get_option('croissanga_xanga_protected');

    if (croissanga_check_configuration()) return;

	$pw_len = strlen($wpdb->get_var("SELECT post_password FROM $wpdb->posts WHERE id = '$ID';"));

    if ($pw_len == 0) {
		if (xanga_map_xanga_id_exists($ID)) {
			xanga_edit_post($ID, 0);
		} else {
			xanga_post_entry($ID, 0);
		}
	} elseif ($pw_len > 0 && $xanga_protected == 1) {
		if (xanga_map_xanga_id_exists($ID)) {
			xanga_edit_post($ID, 1);
		} else {
			xanga_post_entry($ID, 1);
		}
	}

	return $ID;
}

// post a new entry or edit an existing one depending on
// the existence of a map for the post ID to a Xanga ID
function xanga_dispatch($ID) {
    // return if a future post
    if (croissanga_post_in_future($ID))
        return;
    
    return xanga_certain_dispatch($ID);
}

function croissanga_post_in_future($ID) {
    global $wpdb;

    if ($wpdb->get_var("SELECT post_status FROM $wpdb->posts WHERE id = '$ID'") == "future")
	return true;

    // if the option is 0, future post in pre-WP 2.1 versions
    // will not post at all without manual intervention
    // if the option is 1, future post in pre-WP 2.1 versions
    // will post immediately
    if ($wpdb->get_var("SELECT now() < post_date FROM $wpdb->posts WHERE id = '$ID'") == 1 && get_option('croissanga_future_post_workaround') == 0)
	return true;

    return false;
}

function croissanga_check_configuration() {
    return (!get_option('croissanga_xanga_username') || !get_option('croissanga_xanga_password'));
}

function croissanga_add_options_page() {
    add_options_page(__("Croissanga Options"), __("Croissanga"), 'manage_options', 'croissanga/options/croissanga-options.php');
}

function croissanga_init() {
    add_action('admin_head', 'croissanga_add_options_page');
    
// register for publish_post; this may delay seeing the posting result as it
// must access four pages in sequence before the post to Xanga is made
// (this seems to take care of edits too)
    add_action('publish_post', 'xanga_dispatch', 9);
    add_action('publish_future_post', 'xanga_certain_dispatch', 9);

// register for delete_post
    add_action('delete_post', 'xanga_delete_post');

// register for change in password protection
    add_action('edit_post', 'xanga_edit_dispatch');
}

add_action('init', 'croissanga_init');


/* example for other, external uses (I would change post_entry to accept and
   return something other than the Wordpress entryID for other applications)
 $cookie = xanga_fetch_login_cookie(xanga_fetch_login_key());
 $key    = xanga_fetch_posting_key($cookie);
 $page   = xanga_post_entry($entryID);
*/

?>
