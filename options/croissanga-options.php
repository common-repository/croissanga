<?php
/*
 * croissanga-options.php
 * Description: WordPress admin options for the Croissanga plugin
 * Plugin URI: http://ryanlee.org/software/wp/croissanga/
 * Author: Ryan Lee
 * Author URI: http://ryanlee.org/
 */

function croissanga_upgrade() {
    global $croissanga_version;
    
    croissanga_install_table();
    
    if (!get_option('croissanga_version')) {
        add_option('croissanga_version', $croissanga_version, "Croissanga version number");
    } else {
        if (get_option('croissanga_version') != $croissanga_version) {
            update_option('croissanga_version', $croissanga_version);
        }
    }

    if (!get_option('croissanga_xanga_username'))
        add_option('croissanga_xanga_username', '', "Your Xanga username");
    
    if (!get_option('croissanga_xanga_password'))
        add_option('croissanga_xanga_password', '', "Your Xanga password");
        
    if (!get_option('croissanga_xanga_comments'))
        add_option('croissanga_xanga_comments', 0, "Allow comments on Xanga post");
    
    if (!get_option('croissanga_xanga_premium'))
        add_option('croissanga_xanga_premium', 0, "Account is Premium Xanga");
        
    if (!get_option('croissanga_xanga_authors'))
        add_option('croissanga_xanga_authors', 0, "Show WordPress author's username in Xanga post");

    if (!get_option('croissanga_xanga_title'))
        add_option('croissanga_xanga_title', 0, "Use Xanga's title parameter, which puts the post title above all other parts of the Xanga post");

    if (!get_option('croissanga_xanga_protected'))
        add_option('croissanga_xanga_protected', 0, "Post your WordPress password-protected posts to Xanga as a Xanga protected post");

    if (!get_option('croissanga_xanga_excerpts'))
        add_option('croissanga_xanga_excerpts', 1, "Respect the 'more' and 'page' WordPress tokens");

    if (!get_option('croissanga_future_post_workaround'))
        add_option('croissanga_future_post_workaround', 0, "If your version of WordPress is less than 2.1, choose between two evils - do not show the post on Xanga at all (0), or show it early (1)");
}

if ('process' == $_POST['stage']) {
     croissanga_update_options();
} else {
     croissanga_display_admin_page();
}

if (!get_option('croissanga_version') || get_option('croissanga_version') != $croissanga_version)
    croissanga_upgrade();

function croissanga_update_options() {
    if (isset($_POST['croissanga_xanga_username']))
        update_option('croissanga_xanga_username', $_POST['croissanga_xanga_username']);

    if (isset($_POST['croissanga_xanga_password']))
        update_option('croissanga_xanga_password', $_POST['croissanga_xanga_password']);

    if (isset($_POST['croissanga_xanga_comments']))
        update_option('croissanga_xanga_comments', $_POST['croissanga_xanga_comments']);
    else
	update_option('croissanga_xanga_comments', 0);

    if (isset($_POST['croissanga_xanga_premium']))
        update_option('croissanga_xanga_premium', $_POST['croissanga_xanga_premium']);
    else
	update_option('croissanga_xanga_premium', 0);

    if (isset($_POST['croissanga_xanga_authors']))
        update_option('croissanga_xanga_authors', $_POST['croissanga_xanga_authors']);
    else
	update_option('croissanga_xanga_authors', 0);

    if (isset($_POST['croissanga_xanga_title']))
        update_option('croissanga_xanga_title', $_POST['croissanga_xanga_title']);
    else
	update_option('croissanga_xanga_title', 0);

    if (isset($_POST['croissanga_xanga_protected']))
        update_option('croissanga_xanga_protected', $_POST['croissanga_xanga_protected']);
    else
	update_option('croissanga_xanga_protected', 0);

    if (isset($_POST['croissanga_xanga_excerpts']))
        update_option('croissanga_xanga_excerpts', $_POST['croissanga_xanga_excerpts']);
    else
	update_option('croissanga_xanga_excerpts', 0);

    if (isset($_POST['croissanga_future_post_workaround']))
        update_option('croissanga_future_post_workaround', $_POST['croissanga_future_post_workaround']);
    else
        update_option('croissanga_future_post_workaround', 0);
    
     croissanga_display_admin_page();
}

function croissanga_display_admin_page() {
    global $wpdb;
    
    $location = get_option('siteurl') . '/wp-admin/admin.php?page=croissanga/options/croissanga-options.php';
    
    $croissanga_xanga_username = get_option('croissanga_xanga_username');
    $croissanga_xanga_password = get_option('croissanga_xanga_password');
    $croissanga_xanga_comments = get_option('croissanga_xanga_comments');
    $croissanga_xanga_premium = get_option('croissanga_xanga_premium');
    $croissanga_xanga_authors = get_option('croissanga_xanga_authors');
    $croissanga_xanga_title = get_option('croissanga_xanga_title');
    $croissanga_xanga_protected = get_option('croissanga_xanga_protected');
    $croissanga_xanga_excerpts = get_option('croissanga_xanga_excerpts');
    $croissanga_future_post_workaround = get_option('croissanga_future_post_workaround');
?>

    <div class="wrap">
     <h2>Croissanga Options</h2>
     <form name="croissanga-options" method="post" action="<?php echo $location ?>&amp;updated=true">
        <input type="hidden" name="stage" value="process" />
        <fieldset class="options">
         <p class="submit"><input type="submit" name="Submit" value="<?php echo "Update Options"; ?> &raquo;" /></p>
         <table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
          <tbody>
           <tr>
            <th scope="row"><label for="croissanga_xanga_username">Xanga Username</label></th>
            <td><input type="text" name="croissanga_xanga_username" value="<?php echo $croissanga_xanga_username; ?>"/></td>
           </tr>
           <tr>
            <th scope="row"><label for="croissanga_xanga_password">Xanga Password</label></th>
            <td><input type="password" name="croissanga_xanga_password" value="<?php echo $croissanga_xanga_password; ?>"/></td>
           </tr>
          </tbody>
         </table>
         
         <table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform">
          <tbody>
           <tr>
            <td><input type="checkbox" name="croissanga_xanga_comments" value="1" <?php if ($croissanga_xanga_comments == 1) echo "checked='checked'"; ?> /></td>
            <td><label for="croissanga_xanga_comments">Enable Xanga comments</label></td>
            <td><input type="checkbox" name="croissanga_xanga_premium" value="1" <?php if ($croissanga_xanga_premium == 1) echo "checked='checked'"; ?> /></td>
            <td><label for="croissanga_xanga_premium">Premium Xanga account</label></td>
           </tr>
           <tr>
            <td><input type="checkbox" name="croissanga_xanga_authors" value="1" <?php if ($croissanga_xanga_authors == 1) echo "checked='checked'"; ?> /></td>
            <td><label for="croissanga_xanga_authors">Include WordPress author's username in Xanga post</label></td>
            <td><input type="checkbox" name="croissanga_xanga_title" value="1" <?php if ($croissanga_xanga_title == 1) echo "checked='checked'"; ?> /></td>
            <td><label for="croissanga_xanga_title">Use Xanga post title</label></td>
           </tr>
           <tr>
            <td><input type="checkbox" name="croissanga_xanga_protected" value="1" <?php if ($croissanga_xanga_protected == 1) echo "checked='checked'"; ?> /></td>
            <td><label for="croissanga_xanga_protected">Publish password-protected posts as Xanga protected post</label></td>
            <td><input type="checkbox" name="croissanga_xanga_excerpts" value="1" <?php if ($croissanga_xanga_excerpts == 1) echo "checked='checked'"; ?> /></td>
            <td><label for="croissanga_xanga_excerpts">Respect <code>&lt;--more--&gt;</code> and <code>&lt;--page--&gt;</code> tokens</label></td>
           </tr>
	   <tr>
            <td><input type="checkbox" name="croissanga_future_post_workaround" value="1" <?php if ($croissanga_future_post_workaround == 1) echo "checked='checked'"; ?> /></td>
            <td colspan="3"><label for="croissanga_future_post_workaround">If your WP version implements passive future versioning (pre-2.1), you need to choose one of two evils: publish future posts to Xanga early (checked), do not publish them to Xanga automatically, requiring manual editing after the post date (unchecked).  Safely ignore this option in WP versions 2.1 and above.</label></td>
	   </tr>
          </tbody>
         </table>
         <p class="submit"><input type="submit" name="Submit" value="<?php echo "Update Options"; ?> &raquo;" /></p>
        </fieldset>
     </form>
    </div>
<?php
}
?>
