<?php
/*
Plugin Name: User Identity Control
Plugin URI: http://rodrigvenancio.me
Description: Send private messages to the users in the network (Assume an identity).
Version: 1.0
Author: Rodrigo Venancio
Author URI: http://rodrigvenancio.me
License: GPLv2
*/

// Menu
$_id = "";

add_menu_page( 'Identity Control', 'Identity Control', 'manage_options', 'user-identity-control/index.php', '', plugins_url( 'user-identity-control/assets/img/logo-plugin.png' ), 100 );


add_action( 'wp_ajax_get_users_identity', 'get_users_identity' );

function get_users_identity() {
  global $wpdb;

  $type = $_POST['type'];
  $html = '<div class="row">
              <div class="col-lg-12">
                <div class="bs-component">
                  <table class="table table-striped table-hover ">
                    <thead>
                      <tr class="success">
                        <th>Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Register Date</th>
                        <th class="text-center">View</th>
                      </tr>
                    </thead>
                    <tbody>';
  $limit = "";
  $where = "";
  $inner = "";
  $order = "";
  $query = "";
  $fields= "u.ID, u.user_email, u.display_name, u.user_nicename, DATE_FORMAT(user_registered,'%d/%m/%Y %H:%i') as register";

  if ($type == "search")
  {
    $fields.= " , xpd.value as gender ";
    $inner = "INNER JOIN wp_".$_POST['web']."_bp_xprofile_data xpd ON u.ID = xpd.user_id AND xpd.field_id = 3";

    if($_POST["fake"] == "true")
      $where .= " um.meta_key = 'network_".$_POST['web']."_fake_user' AND um.user_id IN (SELECT user_id FROM wp_usermeta WHERE meta_key ='wp_".$_POST['web']."_capabilities' )";
    else
      $where = " um.meta_key = 'wp_".$_POST['web']."_capabilities' ";

    if($_POST["gender"] != '')
      $where .= " AND (xpd.field_id = 3 AND xpd.value = '".$_POST['gender']."') ";
    if($_POST["text"] != '')
      $where .= " AND (u.user_email like '%".$_POST['text']."%' OR u.display_name like '%".$_POST['text']."%') ";
    $order = "ORDER BY u.display_name, u.user_email ";

    $query = "SELECT ".$fields." FROM wp_users u
              INNER JOIN wp_usermeta um ON u.ID = um.user_id
              ".$inner."
              WHERE " . $where . "
              GROUP BY u.ID " .$order;

  }
  else if ($type == "new")
  {
    $fields.= " , xpd.value as gender ";
    $inner = "INNER JOIN wp_".$_POST['web']."_bp_xprofile_data xpd ON u.ID = xpd.user_id AND xpd.field_id = 3";
    $where = " um.meta_key = 'wp_".$_POST['web']."_capabilities' ";

    $order = "ORDER BY user_registered DESC ";

    $query = "SELECT ".$fields." FROM wp_users u
        INNER JOIN wp_usermeta um ON u.ID = um.user_id
        ".$inner."
        WHERE " . $where . "
        GROUP BY u.ID " .$order;
  }

  if($_POST["page"] == 1)
      $limit = "LIMIT 0, 10";
  else
      $limit = "LIMIT ".($_POST["page"] * 10 - 10).", 10";

  $query_pagination = $query;
  $query .= $limit;

  $result = $wpdb->get_results($query);

  $count = count($result);
  if($count > 0)
  {
    // Rows
    for($i = 0; $i < $count; $i++)
    {
      $is_fake_user = check_fake_user($result[$i]->ID);
      if ($is_fake_user)
        $font_color = 'style="color: #AD63DB;"';
      else
        $font_color = '';

      $html .= '<tr class="row-users" rel-id="'.$result[$i]->ID.'">

                    <td '.$font_color.'>'.$result[$i]->display_name.'</td>
                    <td>'.$result[$i]->user_email.'</td>
                    ';

      if($result[$i]->gender == 'TS') :
          $html .= '<td>TV/TS</td>';
      else :
          $html .= '<td>'.$result[$i]->gender.'</td>';
      endif;

        $html .= '<td>'.$result[$i]->register.'</td>';
        $html .= '  <td align="center"><a href="'.get_site_url($_POST["web"]).'/members/'.$result[$i]->user_nicename.'" target="_blank"><img src="'.plugins_url( 'user-identity-control/assets/img/view_profile.png' ).'"></a></td>';

        $html .= '</tr>';
    }
  } else {
      // No data found
    $colspan = 4;

    $html .= '<tr>
                <td colspan="'.$colspan.'"><p class="text-muted text-center">No matching users were found.</p></td>
              </tr>';
  }

  $html .= '</tbody>
          </table>
        <div class="btn btn-primary btn-xs" id="source-button" style="display: none;">&lt; &gt;</div></div>
      </div>
    </div>';

  // Pagination
  $html .= table_pagination($query_pagination, $_POST["page"]);

  echo($html);

  die();
}

add_action( 'wp_ajax_get_pending_message', 'get_pending_message' );

function get_pending_message() {
  global $wpdb;

  $html = '<div class="row">
              <div class="col-lg-12">
                <div class="bs-component">
                  <table class="table table-striped table-hover ">
                    <thead>
                      <tr class="success">
                        <th class="sort" style="cursor: pointer;" >Date <span class="glyphicon glyphicon-sort"></span> </th>
                        <th>To (Talk as)</th>
                        <th>From (Talk to)</th>
                        <th>Subject</th>
                        <th style="min-width:50%" >Text</th>
                        <th class="text-center">View</th>
                      </tr>
                    </thead>
                    <tbody>';
  $limit = "";
  $where = "";
  $inner = "";
  $order = "";
  $query = "";

  if($_POST["fake"] == "true")
    $where .= " AND um.meta_key = 'network_".$_POST['web']."_fake_user' AND um.user_id IN (SELECT user_id FROM wp_usermeta WHERE meta_key ='wp_".$_POST['web']."_capabilities' )";
  else
    $where = " AND um.meta_key = 'wp_".$_POST['web']."_capabilities' ";

  $query = "SELECT m.id, r.user_id, m.sender_id, m.thread_id, MAX(m.date_sent) AS date_sent
      FROM wp_".$_POST['web']."_bp_messages_messages m, wp_".$_POST['web']."_bp_messages_recipients r
      INNER JOIN wp_usermeta um ON r.user_id = um.user_id
      WHERE m.thread_id = r.thread_id  AND r.is_deleted = 0 AND r.unread_count > 0 ".$where."
      GROUP BY m.thread_id
      ORDER BY date_sent ".$_POST['sort']." ";

  if($_POST["page"] == 1)
      $limit = "LIMIT 0, 10";
  else
      $limit = "LIMIT ".($_POST["page"] * 10 - 10).", 10";

  $query_pagination = $query;
  $query .= $limit;

  $result = $wpdb->get_results($query);

  $j = 0;
  $count = count($result);
  if($count > 0)
  {
    // Rows
    for($i = 0; $i < $count; $i++)
    {
      $j++;
      $receiver = get_user_email_and_display_name($result[$i]->user_id);
      $content_message = get_last_message_data($result[$i]->date_sent, $_POST['web']);
      $sender = get_user_email_and_display_name($content_message->sender_id);

      if (check_fake_user($result[$i]->user_id))
        $font_color_to = 'style="color: #AD63DB;"';
      else
        $font_color_to = '';

      if (check_fake_user($content_message->sender_id))
        $font_color_from = 'style="color: #AD63DB;"';
      else
        $font_color_from = '';

      $html .= '<tr class="row-users" rel-id="'.$result[$i]->ID.'">
                  <td class = "thread" id = "thread_'.$result[$i]->thread_id.'">'.$result[$i]->date_sent.'</td>
                  <td class = "receiver" id = "receiver_'.$result[$i]->user_id.'" '.$font_color_to.'>'.$receiver->display_name.'</td>
                  <td class = "sender" id = "sender_'.$content_message->sender_id.'" '.$font_color_from.'>'.$sender->display_name.'</td>
                  <td>'.stripslashes($content_message->subject).'</td>
                  <td>'.stripslashes($content_message->message).'</td> ';

        $html .= '  <td align="center"><a href="'.get_site_url($_POST["web"]).'/members/'.$receiver->user_nicename.'" target="_blank"><img src="'.plugins_url( 'user-identity-control/assets/img/view_profile.png' ).'"></a></td>';

        $html .= '</tr>';
    }
  }
  else
  {
      // No data found
    $colspan = 6;

    $html .= '<tr>
                <td colspan="'.$colspan.'"><p class="text-muted text-center">No matching users were found.</p></td>
              </tr>';
  }

  $html .= '</tbody>
          </table>
        <div class="btn btn-primary btn-xs" id="source-button" style="display: none;">&lt; &gt;</div></div>
      </div>
    </div>';
  // Pagination
  $html .= table_pagination($query_pagination, $_POST["page"]);

  echo($html);

  die();
}

function table_pagination ($query, $page)
{
  global $wpdb;
  $html = "";
  $result2 = $wpdb->get_results($query);

  if(count($result2) > 10)
  {
    $pages = ceil(count($result2) / 10);

    $dot = false;
    $min = 1;
    $max = 8;
    $reverse = false;

    if($page != 1)
      $min =  $page - 1;

    if(($pages > $max) && ( $page < $pages))
    {
        if($page < ($pages - $max)) {
          if($page == 1) {
            $max = 9;
          } else {
            $max = ($max - 1) +  $page;
          }

          $dot = true;
        } else {
          //fixed showing negative page number when totalP is 6 and click 3 or 4 page
          if($page -$max > 0) {
            $min =  $page - $max;
          } else {
            $min = 1;
          }

          //fixed showing negative page number when totalP is 6 and click 3 or 4 page
          if ($page < $max) {
            $max = 1 + $page;
          } else {
            $max = $page + 1;
          }
          $dot = true;
      }
    }
    else {
      if($pages > $max) {
        $reverse = true;
        $min = $page - ($max - 1);
      } else {
        $min = 1;
      }
      $max = $pages + 1;
    }

    $html .= '<div class="row text-center">
                <div class="col-lg-12">
                  <div class="bs-component">
                    <ul class="pagination text-center">';

    if($page == 1)
      $html .= '<li class="disabled"><a href="#" rel-page="1">«</a></li>';
    else
      $html .= '<li><a href="#" class="page-id" rel-page="1">«</a></li>';

    if($reverse) {
      $html .= '<li><a href="#" class="page-id" rel-page="1">1</a></li>';
      $html .= '<li><a href="#">...</a></li>';
    }

    for($i = $min; $i < $max; $i++) {

        if($i == $page)
            $activeP = 'class="active"';
        else
            $activeP = '';

        $html .= '<li '.$activeP.'><a href="#" class="page-id" rel-page="'.($i).'">'.($i).'</a></li>';

    }

    if($dot) {
      $html .= '<li><a href="#">...</a></li>';
      $html .= '<li><a href="#" class="page-id" rel-page="'.$pages.'">'.$pages.'</a></li>';
    }

    if($pages == $page)
      $html .= '<li class="disabled"><a href="#" rel-page="'.$pages.'">»</a></li>';
    else
      $html .= '<li><a href="#" class="page-id" rel-page="'.$pages.'">»</a></li>';

    $html .= '</ul>
          </div>
        </div>
      </div>';
  }

  return $html;
}

function check_fake_user($user_id) {
  global $wpdb;
  $sql = "SELECT * FROM wp_usermeta
          WHERE meta_key ='network_".$_POST['web']."_fake_user' AND user_id = '".$user_id."' ";
  $row = $wpdb->get_var($sql);

  return $row;
}

add_action( 'wp_ajax_get_users_identity_info', 'get_users_identity_info' );

function get_users_identity_info() {
  global $wpdb;
  $sql = "SELECT xd.value, u.display_name
          FROM wp_".$_POST['web']."_bp_xprofile_data xd
          INNER JOIN wp_users u ON xd.user_id = u.ID
          WHERE xd.user_id = ".$_POST['id']." AND xd.field_id = 3";
  $row = $wpdb->get_row($sql);

  //Photo DIR
  $dir = wp_upload_dir();
  $files = scandir($dir['basedir'] . '/avatars/' . $_POST['id']);

  if($files[3] != '')
    echo('<div class = "col-lg-2" style="padding-left: 0px; padding-right: 0px"> <img src="'.$dir['baseurl'] . '/avatars/' . $_POST['id'].'/'.$files[3].'" height="40" width="40"></div>
      <div class="col-lg-9 name-profile-identity">
        <ul>
          <li><b>'.$row->display_name.'</b></li>
          <li>('.$row->value.')</li>
        </ul></div>');
  else
    echo('<div class = "col-lg-2" style="padding-left: 0px; padding-right: 0px"> <img src="'.plugins_url( 'user-identity-control/assets/img/missing_photo.png').'" height="40" width="40"></div>
      <div class="col-lg-9 name-profile-identity">
        <ul>
          <li><b>'.$row->display_name.'</b></li>
          <li>('.$row->value.')</li>
        </ul></div>');

  die();
}

add_action( 'wp_ajax_get_conversation', 'get_conversation' );

function get_conversation() {

  global $wpdb;
  $user_as = $_POST['user_as'];
  $user_to = $_POST['user_to'];

  $thread = $_POST['thread'];
  if ($thread)
  {
    $sql = "SELECT wu.ID, wu.display_name, bmm.message, DATE_FORMAT(bmm.date_sent,'%d/%m/%Y %H:%i') as date_sent, bmm.date_sent as d_sent, bmm.thread_id
            FROM wp_".$_POST['web']."_bp_messages_messages bmm
            INNER JOIN wp_users wu ON bmm.sender_id = wu.ID
            WHERE thread_id = ".$thread."
            ORDER BY d_sent";
  }
  else
  {
    $sql = "SELECT wu.ID, wu.display_name, bmm.message, DATE_FORMAT(bmm.date_sent,'%d/%m/%Y %H:%i') as date_sent, bmm.date_sent as d_sent, bmm.thread_id
            FROM wp_".$_POST['web']."_bp_messages_messages bmm
            INNER JOIN wp_users wu ON bmm.sender_id = wu.ID
            WHERE thread_id
              IN (SELECT thread_id FROM
                    (SELECT item_id FROM wp_".$_POST['web']."_bp_notifications
                     WHERE (component_name = 'messages' AND component_action = 'new_message') AND
                           ((user_id  = ".$user_as." AND secondary_item_id = ".$user_to.") OR (user_id  = ".$user_to." AND secondary_item_id = ".$user_as."))
                     ORDER BY date_notified DESC
                     LIMIT 1) as bn
                  JOIN wp_".$_POST['web']."_bp_messages_messages bm ON bm.id = bn.item_id)
            ORDER BY d_sent";
  }

  $rows = $wpdb->get_results($sql);
  $html = "";
  $return = array();
  $return[0] = "";
  if(count($rows) > 0) {
    for($i = 0; $i < count($rows); $i++) {
      $return[0] = $rows[$i]->thread_id;
      if($_POST['user_as'] == $rows[$i]->ID) {
        $html .= "<div class='col-lg-12'><div class='col-lg-9'><p><b>".$rows[$i]->display_name." (".$rows[$i]->date_sent."): </b>".nl2br(stripcslashes($rows[$i]->message))."</p></div><div class='col-lg-3'>&nbsp;</div></div>";
      } else {
        $html .= "<div class='col-lg-12'><div class='col-lg-3'>&nbsp;</div><div class='col-lg-9'><p class='text-right'><b style='color:#76828E'>".$rows[$i]->display_name." (".$rows[$i]->date_sent."): </b>".nl2br(stripcslashes($rows[$i]->message))."</p></div></div>";
      }
    }
  } else {
    $html = "<p class='text-center'>No conversation available...</p>";
  }

  $return[1] = $html;
  $return[2] = $sql;

  echo json_encode($return);

  die();
}

add_action( 'wp_ajax_send_message_identity', 'send_message_identity' );

function send_message_identity() {

  global $wpdb;

  $return = array();

  if($_POST['thread_id'] != "") {

    $sql = "SELECT subject FROM wp_".$_POST['web']."_bp_messages_messages WHERE thread_id = " . $_POST['thread_id'] . " ORDER BY date_sent DESC LIMIT 1";
    $row = $wpdb->get_row($sql);

    $wpdb->query("INSERT INTO wp_".$_POST['web']."_bp_messages_messages VALUES ('', ".$_POST['thread_id'].", ".$_POST['user_as'].", '".$row->subject."', '".$_POST['text']."', '".date("Y-m-d H:i:s")."')");
    $id_message = $wpdb->insert_id;

    $wpdb->update( "wp_".$_POST['web']."_bp_messages_recipients", array( "unread_count" => "0" ), array( "user_id" => $_POST['user_as'],  "thread_id" => $_POST['thread_id']) );
    $wpdb->query("INSERT INTO wp_".$_POST['web']."_bp_messages_recipients VALUES ('', ".$_POST['user_to'].", '".$_POST['thread_id']."', 1, 0, 0)");

    $wpdb->query("INSERT INTO wp_".$_POST['web']."_bp_notifications VALUES ('', ".$_POST['user_to'].", ".$id_message.", ".$_POST['user_as'].", 'messages', 'new_message', '".date("Y-m-d H:i:s")."', 0)");

    $thread_id = $_POST['thread_id'];

  } else {

    $sql = "SELECT thread_id FROM wp_".$_POST['web']."_bp_messages_messages ORDER BY thread_id DESC LIMIT 1";
    $row = $wpdb->get_row($sql);

    $thread_id = $row->thread_id + 1;

    $wpdb->query("INSERT INTO wp_".$_POST['web']."_bp_messages_messages VALUES ('', ".$thread_id.", ".$_POST['user_as'].", '', '".$_POST['text']."', '".date("Y-m-d H:i:s")."')");
    $id_message = $wpdb->insert_id;

    $wpdb->query("INSERT INTO wp_".$_POST['web']."_bp_messages_recipients VALUES ('', ".$_POST['user_to'].", '".$thread_id."', 1, 0, 0)");
    $wpdb->query("INSERT INTO wp_".$_POST['web']."_bp_messages_recipients VALUES ('', ".$_POST['user_as'].", '".$thread_id."', 0, 1, 0)");

    $wpdb->query("INSERT INTO wp_".$_POST['web']."_bp_notifications VALUES ('', ".$_POST['user_to'].", ".$id_message.", ".$_POST['user_as'].", 'messages', 'new_message', '".date("Y-m-d H:i:s")."', 0)");

  }

  $sql2 = "SELECT display_name FROM wp_users WHERE ID = " . $_POST['user_as'];
  $row2 = $wpdb->get_row($sql2);

  $return[0] = "<div class='col-lg-12'><div class='col-lg-9'><p><b>".$row2->display_name." (".date("d/m/Y H:i")."): </b>".nl2br(stripcslashes($_POST['text']))."</p></div><div class='col-lg-3'>&nbsp;</div></div>";
  $return[1] = $thread_id;

  $html = file_get_contents(get_theme_root() . '/swingers-local/newsletters/message.html');

  $user = get_user_by( 'id', $_POST['user_to'] );
  $html = str_replace('£', $user->display_name, $html);
  $html = str_replace('%4$s', get_site_url(2).'/members/'.$user->user_nicename . '/messages/', $html);
  add_filter( 'wp_mail_content_type', 'set_html_content_type' );
  wp_mail( $user->user_email, '[SwingSwap] New message from ' . $row2->display_name, $html );
  remove_filter( 'wp_mail_content_type', 'set_html_content_type'  );

  echo json_encode($return);

  die();
}

add_action( 'wp_ajax_get_url_icebreaker', 'get_url_icebreaker' );

function get_url_icebreaker() {

  $user = get_user_by( 'id', $_POST["user_to"]);

  if(!bp_poke_can_user_poke($_POST["user_as"], $_POST["user_to"])) {

    if( !bp_poke_can_user_poke_back($_POST["user_as"], $_POST["user_to"] ) ){

      echo('You have already sent the icebreaker to ' . $user->display_name);

    } else {

        if(!bp_poke_can_user_poke($_POST["user_as"], $_POST["user_to"])) {

          //we need to delete the pokes of the user whom the current user poked back, in current user;s meta.
          $logged_pokes = bp_get_user_meta( $_POST['user_as'], 'pokes', true );

          //unset the poke from the user whom we just poked back
          //delete the old poke info
          unset( $logged_pokes[$_POST['user_to']] );

          //now store back the updated pokes to current users meta

           bp_update_user_meta( $_POST['user_as'], 'pokes', $logged_pokes );

          //update for the user whom we have poked

          $time = current_time('timestamp', 1);

          //get past poke details for this user
          $pokes = bp_get_user_meta( $_POST['user_to'], 'pokes', true );

          //assuming one user can poke only once
          $pokes[$_POST['user_as']] = array( 'poked_by' => $_POST['user_as'], 'time' => $time );

          bp_update_user_meta( $_POST['user_to'], 'pokes', $pokes );

          bp_core_add_notification( $_POST['user_as'], $_POST['user_to'], $component, $action );

          //Send Email
          $html = file_get_contents(get_theme_root() . '/swingers-local/newsletters/icebreaker.html');
          $html = str_replace('£', $user->display_name, $html);
          $html = str_replace('§', $user->user_nicename, $html);

          add_filter( 'wp_mail_content_type', 'set_html_content_type' );
          wp_mail( $user->user_email, '[SwingSwap] You have recieved an ICEBREAKER', $html );
          remove_filter( 'wp_mail_content_type', 'set_html_content_type'  );


        echo('You have sent the icebreaker back to ' . $user->display_name);

      } else {

        echo('You have already sent the icebreaker to ' . $user->display_name);

      }

    }

  } else {

    $time = current_time('timestamp', 1);

    //get past poke details for this user
    $pokes = bp_get_user_meta( $_POST["user_to"], 'pokes', true );

    //assuming one user can poke only once
    $pokes[$_POST["user_as"]] = array( 'poked_by' => $_POST["user_as"], 'time' => $time );

    bp_update_user_meta( $_POST["user_to"], 'pokes', $pokes );

    bp_core_add_notification( $_POST["user_as"], $_POST["user_to"], 'poke', 'user_poked' );

    $html = file_get_contents(get_theme_root() . '/swingers-local/newsletters/icebreaker.html');
    $html = str_replace('£', $user->display_name, $html);
    $html = str_replace('§', $user->user_nicename, $html);

    add_filter( 'wp_mail_content_type', 'set_html_content_type' );
    wp_mail( $user->user_email, '[SwingSwap] You have recieved an ICEBREAKER', $html );
    remove_filter( 'wp_mail_content_type', 'set_html_content_type'  );

    echo('You have sent the icebreaker to ' . $user->display_name);

  }

  die();
}

/* funtions for user profile */

function get_user_profile_fields($groupID, $blog_id) {
  global $wpdb;

  $feild_sql = "SELECT * FROM wp_".$blog_id."_bp_xprofile_fields
                WHERE parent_id = '0' AND group_id = '".$groupID."'
                ORDER BY field_order ASC";
  $field_row = $wpdb->get_results($feild_sql);

  return $field_row;
}

function get_user_profile_data($userID, $fieldID, $blog_id) {
  global $wpdb;

  $sql = "SELECT * FROM wp_".$blog_id."_bp_xprofile_data xd
        INNER JOIN wp_".$blog_id."_bp_xprofile_fields xf ON xd.field_id = xf.id
        WHERE user_id = ".$userID."
        AND xd.field_id = ".$fieldID." ";
  $row = $wpdb->get_row($sql);
  return $row;
}

function get_user_profile_options($fieldID, $blog_id) {
  global $wpdb;

  $option_sql = "SELECT name FROM wp_".$blog_id."_bp_xprofile_fields
                 WHERE parent_id = ".$fieldID." ";
  $option_row = $wpdb->get_results($option_sql);
  return $option_row;
}

function get_user_email_and_display_name($userID) {
  global $wpdb;

  $sql = "SELECT *
          FROM wp_users u
          WHERE id = ".$userID;
  $row = $wpdb->get_row($sql);
  return $row;
}

function get_user_has_avatar($id) {
  global $wpdb;
  // Photo DIR
  $dir = wp_upload_dir();
  $files = scandir($dir['basedir'] . '/avatars/' . $id);

  $image_src = "";
  if ($files[3] != '')
    $image_src = $dir['baseurl'] . '/avatars/' . $id.'/'.$files[3];

  return $image_src;
}

add_action( 'wp_ajax_get_users_profile', 'get_users_profile' );

function get_users_profile() {
  global $wpdb;

  $image_src = get_user_has_avatar($_POST['id']);

  if ($image_src == '')
    $image_src = plugins_url( 'user-identity-control/assets/img/missing_photo_profile.png');

  $field_row = "";
  $group_sql = "SELECT id FROM wp_".$_POST['web']."_bp_xprofile_groups";
  $group_row = $wpdb->get_results($group_sql);

  for ($k = 0; $k < count($group_row); $k++)
  {
    $field_row = get_user_profile_fields($group_row[$k]->id, $_POST['web']);

    if($group_row[$k]->id == "1")
    {
      $html .= '<div class = "col-lg-12" style = "padding-bottom: 15px;">
              <label>About Me</label>
            </div>
            <div class = "col-lg-3 text-center">
              <p> <img src = "'.$image_src.'" height = "200" width = "200" style = "align: center;"> </p>
              <p> <button type="button" class="btn btn-default" id ="btn_change_avatar" data-target="#modal_change_avatar">Change photo</button> </p>
            </div>';
    }
    else if($group_row[$k]->id == "3")
    {
      $html .= '<div class = "col-lg-12" style = "padding-top: 30px;">
              <label>Looking For</label>
            </div>';
    }

    $arrVisibleFields = array();

    for ($i = 0; $i < count($field_row); $i++)
    {
      if($field_row[$i]->name == "I am 18 or over" || $field_row[$i]->name == "I have read and accept the terms and conditions")
        continue;

      $arrVisibleFields[] = $field_row[$i];
    }

    $field_row = $arrVisibleFields;
    $field_count = count($field_row);

    if ($group_row[$k]->id == "1") {
      $colBreak = ceil($field_count/3);
    }
    if ($group_row[$k]->id == "3") {
      $colBreak = ceil($field_count/4);
    }

    for ($i = 0; $i < $field_count; $i++)
    {
      $is_required = "";
      if ($field_row[$i]->is_required)
        $is_required = "required";

      if ($i % $colBreak == 0 )
        $html .= '<div class="col-lg-3">';

      $row = get_user_profile_data($_POST['id'], $field_row[$i]->id, $_POST['web']);
      $option_row = get_user_profile_options($field_row[$i]->id, $_POST['web']);

      $html .='<div class = "form-horizontal">
                <div class = "form-group">';

      if ($field_row[$i]->name == "Summary")
        $html .= '<label class = "col-sm-4 control-label">Email</label>';
      else
        $html .= '<label class = "col-sm-4 control-label">'.$field_row[$i]->name.'</label>';

      $html .='<div class="col-sm-8 controls ">';

      switch ($field_row[$i]->type) {
        case 'textbox':
          $html .= '<input type = "text" class = "form-control" value= "'.$row->value.'" name = "'.$field_row[$i]->id.'" '.$is_required.'>';

          break;
        case 'textarea':
          if ($field_row[$i]->name == "Summary") //email field
          {
            $email_row = get_user_email_and_display_name($_POST['id']);
            $html .= '<input type = "email" class = "form-control" value = "'.$email_row->user_email.'" name = "user_email" required>';
          }
          else
            $html .= '<textarea class = "form-control" rows = "3" name = "'.$field_row[$i]->id.'" '.$is_required.'>'.$row->value.'</textarea>';
          break;
        case 'selectbox':
          $html .= '<select class = "form-control" name = "'.$field_row[$i]->id.'" '.$is_required.'>';
          $html .= '<option value=""> --- </option>';
          foreach ($option_row as $value) {
            if($row->value == $value->name) {
              $html .= '<option value="'.$value->name.'" selected>'.$value->name.'</option>';
            } else {
              $html .= '<option value="'.$value->name.'">'.$value->name.'</option>';
            }
          }
          $html .= '</select>';

          break;
        case 'multiselectbox':
          $html .= '<select multiple class = "form-control" name = "'.$field_row[$i]->id.'" '.$is_required.'>';
          $html .= '<option value=""> --- </option>';
          foreach ($option_row as $value) {
            if($row->value == $value->name) {
              $html .= '<option value="'.$value->name.'" selected>'.$value->name.'</option>';
            } else {
              $html .= '<option value="'.$value->name.'">'.$value->name.'</option>';
            }
          }
          $html .= '</select>';

          break;
        case 'radio':
            foreach ($option_row as $value) {
              if($row->value == $value->name) {
                $html .= '<input type = "radio" value = "'.$value->name.'" name = "'.$field_row[$i]->id.'" checked >'.$value->name.'</input>';
              } else {
                $html .= '<input type = "radio" value = "'.$value->name.'" name = "'.$field_row[$i]->id.'" >'.$value->name.'</input>';
              }
            }
            break;
        case 'checkbox':
            $arr_data = unserialize($row->value);
            foreach ($option_row as $value) {
              $html .= '<input type = "checkbox" class = "profile-checkbox" value="'.$value->name.'" name = "'.$field_row[$i]->id.'" ';
              if($arr_data) {
                foreach ($arr_data as $data) {
                  if($data == $value->name) {
                    $html .= 'checked';
                  }
                }
              }
              $html .= '>&nbsp&nbsp<small>'.$value->name.'</small></input> <br/>';
            }

            break;
        case 'datebox':
           $html .= '<input type = "text" class="form-control datepicker" value="'.$row->value.'" name = "'.$field_row[$i]->id.'" '.$is_required.'>';
          break;
      }
      $html .= '<p class="help-block"></p>';
      $html .= '</div>
                </div>
              </div>';

      if($i%$colBreak == $colBreak-1 || $i == $field_count-1)
        $html .=  '</div>';
    }

    if($group_row[$k]->id == "1")
    {
      //summary 323 => ID summary
      $row = get_user_profile_data($_POST['id'], '323', $_POST['web']);
      $html .= '<div class = "col-lg-12" style = "padding-bottom: 15px;">
                <label>Summary</label>
                <textarea class = "form-control" rows = "4" name = "323" style="min-height: 65px;">'.$row->value.'</textarea>
              </div>';
    }

  }

  $html .= '<input type = "hidden" name="id" value = "'.$_POST['id'].'" >';
  $html .= '<div class="col-lg-12 form-actions" style = "padding-top: 15px; align: center">
                <button type="submit" class = "btn btn-primary center-block" id = "profile-save-button" >Save changes</button>
              </div>';


  $display_name = get_user_email_and_display_name($_POST['id'])->display_name;
  $return[0] = $html;
  $return[1] = $display_name;

  echo json_encode($return);
  // echo $html;

  die();
}

add_action( 'wp_ajax_save_profile_data', 'save_profile_data' );

function save_profile_data() {
  global $wpdb;

  $data = $_POST['data'];

  // var_dump($data);
  $xprofile_data = array();
  $user_data = array();
  $user_id = "";

  //for checkbox data to array
  $arrTemp = array();
  foreach ($data as $arrItem)
  {
    $name = $arrItem['name'];
    $value = $arrItem['value'];

    $arrTemp[$name][] = $value;
  }

  $arrResult = array();
  foreach ($arrTemp as $key => $arrValues)
  {
    $sql = "SELECT type FROM wp_".$_POST['web']."_bp_xprofile_fields
        WHERE id= ".$key." ";
    $row = $wpdb->get_row($sql);

    $arrResult[] = array(
      'name' => "$key",
      'value' => $row->type == "checkbox" ? $arrValues : $arrValues[0]
    );
  }
  // var_dump($arrResult);

  //separate data to save in DB
  for ($i = 0; $i < count($arrResult); $i++)
  {
    if ($arrResult[$i]['name'] == "1") //add display_name same as name filed in xprofile_data
    {
      $value = $arrResult[$i]['value'];
      $user_data[] = (object)array(
        'name' => "display_name",
        'value' => $value
      );
    }

    if ($arrResult[$i]['name'] == "user_email")
      $user_data[] = (object)$arrResult[$i];
    else if ($arrResult[$i]['name'] == "id")
      $user_id = $arrResult[$i]['value'];
    else
      $xprofile_data[] = (object)$arrResult[$i];
  }

  //update data in wp_users
  for ($i = 0; $i < count($user_data); $i++)
  {
    $table = 'wp_users';
    if ($user_data[$i]->value != "" && $user_data[$i]->value != null)
      $wpdb->update( $table, array( $user_data[$i]->name => $user_data[$i]->value ), array( 'id' => $user_id ) );
  }

  // delete empty checkbox data before saving
  $sql = "SELECT xd.id FROM wp_".$_POST['web']."_bp_xprofile_data xd
          INNER JOIN wp_".$_POST['web']."_bp_xprofile_fields xf ON xd.field_id = xf.id
          WHERE user_id = ".$user_id."
          AND xf.type = 'checkbox' AND xf.is_required = 0 ";
  $results =  $wpdb->get_results($sql);
  var_dump($results);
  for ($i = 0; $i < count($results); $i++)
  {
    $table =  "wp_".$_POST['web']."_bp_xprofile_data";
    $wpdb->delete( $table, array( 'id' => $results[$i]->id ) );
  }

  // update data in wp_DD_bp_xprofile_data
  for ($i = 0; $i < count($xprofile_data); $i++)
  {
    $table =  "wp_".$_POST['web']."_bp_xprofile_data";
    $sql = "SELECT count(*) FROM ".$table.
           " WHERE field_id = ".$xprofile_data[$i]->name." AND user_id = ".$user_id;

    $profile_count = $wpdb->get_var($sql);
    $value = is_array($xprofile_data[$i]->value) ? serialize($xprofile_data[$i]->value) : $xprofile_data[$i]->value;

    if ($xprofile_data[$i]->value != "" && $xprofile_data[$i]->value != null)
    {
      if ($profile_count == 0)
        $wpdb->insert( $table, array( 'value' => $value, 'field_id' => $xprofile_data[$i]->name, 'user_id' => $user_id) );
      else
        $wpdb->update( $table, array( 'value' => $value ), array( 'field_id' => $xprofile_data[$i]->name, 'user_id' => $user_id ) );
    }
    else
    {
      if ($profile_count > 0)
        $wpdb->delete( $table, array( 'field_id' => $xprofile_data[$i]->name, 'user_id' => $user_id ) );
    }
  }

  die();
}

add_action( 'wp_ajax_show_avatar_modal', 'show_avatar_modal' );

function show_avatar_modal() {
  $html = ' <p>Click below to select a JPG, GIF or PNG format photo from your computer and then click \'Change Avatar\' to proceed.</p>
            <input type="file" name="file" id="thumbnail"/>
            <input type="hidden" name="post_id" id="post_id" value="POSTID">
            <input type="hidden" name="action" id="action" value="my_upload_action">
            <button type="submit" class = "btn btn-primary" id = "submit-ajax" name = "submit-ajax" >Change Avatar</button> <br/><br/>';

  if ( get_user_has_avatar($_POST['id']) != '')
  {
    $html .= '<p>If you\'d like to delete your current avatar but not upload a new one, please use the delete avatar button.</p>
              <button type="button" class="btn btn-danger text-right" id="delete_avatar_modal_button" >Delete Avatar</button>';
  }

  echo $html;
  die();
}


add_action('wp_ajax_my_upload_action', 'change_avatar');

function change_avatar() {
  $dir = wp_upload_dir();
  $upload_dir = $dir['basedir'].'/avatars/'.$_POST['post_id'].'/';

  $allowedExts = array("gif", "jpeg", "jpg", "png");
  $temp = explode(".", $_FILES["file"]["name"]);
  $extension = end($temp);
  if ((($_FILES["file"]["type"] == "image/gif")
  || ($_FILES["file"]["type"] == "image/jpeg")
  || ($_FILES["file"]["type"] == "image/jpg")
  || ($_FILES["file"]["type"] == "image/pjpeg")
  || ($_FILES["file"]["type"] == "image/x-png")
  || ($_FILES["file"]["type"] == "image/png"))
  && in_array($extension, $allowedExts))
  {
    if ($_FILES["file"]["error"] > 0)
    {
      echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
    }
    else
    {
      if(!is_dir ($upload_dir))
      {
        mkdir($upload_dir);
      }

      $filename = $_FILES["file"]["name"];
      $ext = strtolower(substr(strrchr($filename, '.'), 1)); //Get extension
      $name = uniqid().'-bpfull';
      $name2 = uniqid().'-bpthumb';
      $image_name = $name . '.' . $ext; //New image name
      $image_name2 = $name2 . '.' . $ext; //New image name

      $files = glob( $upload_dir.'*' ); // get all file names
      foreach($files as $file){ // iterate files
        if(is_file($file))
          unlink($file); // delete file
      }
      //and save files
      move_uploaded_file($_FILES["file"]["tmp_name"],
      $upload_dir.$image_name);

      $file = $upload_dir.$image_name;
      $newfile = $upload_dir.$image_name2;

      if (!copy($file, $newfile)) {
          echo "failed to copy";
      }

      echo "Changed your avatar successfully.";
    }
  }
  else
  {
      echo "Invalid file";
  }

  die();
}

add_action( 'wp_ajax_delete_avatar', 'delete_avatar' );

function delete_avatar() {
  $dir = wp_upload_dir();
  $upload_dir = $dir['basedir'].'/avatars/'.$_POST['id'].'/';

  $files = glob( $upload_dir.'*' ); // get all file names
  foreach($files as $file){ // iterate files
    if(is_file($file))
      unlink($file); // delete file
  }

  echo "Deleted the avatar successfully.";
  die();
}

function get_messages($userID, $blog_id, $limit) {
  global $wpdb;

  $sql = "SELECT m.id, r.user_id, m.sender_id, m.subject, m.message, m.thread_id, MAX(m.date_sent) AS date_sent
          FROM wp_".$blog_id."_bp_messages_recipients r, wp_".$blog_id."_bp_messages_messages m
          WHERE m.thread_id = r.thread_id  AND (m.sender_id = ".$userID." OR r.user_id = ".$userID.") AND r.is_deleted = 0
          GROUP BY m.thread_id
          ORDER BY date_sent ".$limit;

  if ($limit == "") //for pagination
    return $sql;
  else
    $row = $wpdb->get_results($sql);

  return $row;
}

function get_last_message_data($date_sent, $blog_id) {
  global $wpdb;

  $sql = "SELECT *
          FROM wp_".$blog_id."_bp_messages_messages m
          WHERE m.date_sent = '".$date_sent."' ";
  $row = $wpdb->get_row($sql);

  return $row;
}

add_action( 'wp_ajax_get_user_conversations', 'get_user_conversations' );

function get_user_conversations() {

  if($_POST["page"] == 1)
      $limit = "LIMIT 0, 10";
  else
      $limit = "LIMIT ".($_POST["page"] * 10 - 10).", 10";

  $message_data = get_messages($_POST['id'], $_POST['web'], $limit);

  $html = '<div class="col-lg-12">
              <table class="table table-striped table-hover" id="conversation-table" >
                <thead>
                  <tr class="success">
                    <th style="min-width: 60px;" >#</th>
                    <th class="col-sm-2">From</th>
                    <th class="col-sm-2">To</th>
                    <th class="col-sm-2">Subject</th>
                    <th class="col-sm-6">Text</th>
                  </tr>
                </thead>
                <tbody>';

  for ($i=0; $i < count($message_data) ; $i++)
  {
    if (check_fake_user($message_data[$i]->user_id))
      $font_color_to = 'style="color: #AD63DB;"';
    else
      $font_color_to = '';

    if (check_fake_user($message_data[$i]->sender_id))
      $font_color_from = 'style="color: #AD63DB;"';
    else
      $font_color_from = '';

    $sender = get_user_email_and_display_name($message_data[$i]->sender_id);
    $receiver = get_user_email_and_display_name($message_data[$i]->user_id);
    $content_message = get_last_message_data($message_data[$i]->date_sent, $_POST['web']);

    if( $receiver != "" && $sender != "" )
    {
      $count = $i + 1;
      $html .= '<tr class="row-users">
                  <td class = "thread" id = "thread_'.$message_data[$i]->thread_id.'">'.$count.'</td>
                  <td class = "sender" '.$font_color_from.' id = "sender_'.$message_data[$i]->sender_id.'">'.$sender->display_name.'</td>
                  <td class = "receiver" '.$font_color_to.' id = "receiver_'.$message_data[$i]->user_id.'">'.$receiver->display_name.'</td>
                  <td>'.stripslashes($content_message->subject).'</td>
                  <td>'.stripslashes($content_message->message).'</td>
                </tr>';
    }
  }


  $html .= '    </tbody>
              </table>
            </div>';

  // Pagination
  $html .= table_pagination( get_messages($_POST['id'], $_POST['web'], ""), $_POST["page"]);

  echo $html;

  die();
}

function get_media($userID) {
  global $wpdb;

  $sql = "SELECT * FROM wp_rt_rtm_media
          WHERE media_author = $userID AND (media_type = 'photo' OR media_type = 'video')";

  $row = $wpdb->get_results($sql);

  return $row;
}

add_action( 'wp_ajax_get_files', 'get_files' );

function get_files() {

  $media_data = get_media($_POST['id']);
  $user = get_user_by( 'id', $_POST['id'] );

  // var_dump($media_data);
  $html = "";

  for ($i=0; $i < count($media_data) ; $i++)
  {
    // var_dump(wp_get_attachment_url( $media_data[$i]->media_id ));
    $html .= '<div class = "col-lg-2 text-center" style = "margin-top: 20px;" >';
    if ($media_data[$i]->media_type == "photo")
    {
      $src = wp_get_attachment_image_src($media_data[$i]->media_id,'rt_media_thumbnail');
      $src_large = wp_get_attachment_image_src($media_data[$i]->media_id,'large');
      $html .= '<a href="'.$src_large[0].'" class="popup-link">';
      $html .= '<img src="'.$src[0].'" alt="'.$media_data[$i]->media_title.'" width="140px" height="140px">';
    }
    else if ($media_data[$i]->media_type == "video")
    {
      $src = wp_get_attachment_url( $media_data[$i]->media_id );
      $html .= '<a href="'.$src.'" class="mfp-iframe popup-link">';
      if($row->cover_art != '')
        $html .= '<img src="'.$media_data[$i]->cover_art.'" class="img-thumb-videos" width="140px" height="140px">';
      else
        $html .= '<img src="'.plugins_url( 'user-identity-control/assets/img/video_thumb.png').'" class="img-thumb-videos" width="140px" height="140px" >';
        // $html .= '<video src="' . wp_get_attachment_url( $media_data[$i]->media_id ) . '" type="video/mp4" preload="auto" width="160px" height="160px" controls></video>';
    }
    $html .= '</a> <br/>';
    $html .= '<button type="button" class="btn btn-danger btn-sm btn_delete_file" id="media_'.$media_data[$i]->id.'_'.$media_data[$i]->media_id.'" style = "margin-top: 10px;" >Delete</button>';
    $html .= '</div>';
  }

  echo $html;

  die();
}

add_action( 'wp_ajax_get_files_upload_form', 'get_files_upload_form' );

function get_files_upload_form() {
  $html = '<form id="files_form" method="post" action="#" enctype="multipart/form-data" >
            <input type="file" name="file" id="thumbnail" style="display: inline; margin:0px 5px;" />
            <input type="hidden" name="post_id" value="POSTID">
            <input type="hidden" name="action" value="files_upload_action">
            <input type="hidden" name="blog_id" value="">
            <button type="submit" class = "btn btn-primary btn-sm" id = "submit-ajax" name = "submit-ajax" style="margin:0px 2px;"> Save </button>
            <button type="button" class = "btn btn-warning btn-sm" id = "files_form_reset" name = "reset" style="margin:0px 2px;"> x Cancle </button>
          </form>
          <div class = "col-lg-12 line-separator"></div>';

  echo $html;
  die();
}

add_action('wp_ajax_files_upload_action', 'save_media_file');

function save_media_file() {
  global $wpdb;

  if ($_FILES["file"]["error"] > 0)
  {
    echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
  }
  else
  {
    //save a file in server
    $GLOBALS['_id'] =  $_POST['post_id'];
    $upload = wp_upload_dir();
    add_filter('upload_dir', 'my_upload_dir');

    if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
    $uploadedfile = $_FILES['file'];
    $upload_overrides = array( 'test_form' => false );
    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

    //save data in DB (wp_DD_posts, wp_rt_rtm_media)
    if ( isset( $movefile['file'] ) )
    {
      $file_loc = $movefile['file'];
      $file_name = basename( $_FILES['file']['name'] );
      $file_type = $movefile['type'];
      $post_title = preg_replace( '/\.[^.]+$/', '', basename( $file_name ) );
      $file_url = $movefile['url'];
      $album_id = "895"; // wall posts id

      $attachment = array(
        'post_mime_type' => $file_type,
        'guid' => $file_url,
        'post_title' => $post_title,
        'post_content' => '',
        'post_parent' => $album_id,
        'post_author' => $_POST['post_id']
      );

      require_once ( ABSPATH . 'wp-admin/includes/media.php' );
      $attach_id = wp_insert_attachment( $attachment, $file_loc );
      $attach_data = wp_generate_attachment_metadata( $attach_id, $file_loc );
      wp_update_attachment_metadata( $attach_id, $attach_data );

      $table = "wp_rt_rtm_media";
      $album_id = "2"; //wall posts
      $context = "profile";
      $mime_type = explode ( '/', $file_type );
      $currnet_time = date("Y-m-d H:i:s",time());

      $media = array(
          'blog_id' => $_POST['blog_id'],
          'media_id' => $attach_id,
          'album_id' => $album_id,
          'media_author' => $_POST['post_id'],
          'media_title' => $file_name,
          'media_type' => set_media_type($mime_type[0]),
          'context' => $context,
          'context_id' => $_POST['post_id'],
          'privacy' => "0",
          'file_size' => $_FILES["file"]["size"],
          'upload_date' => $currnet_time
        );

      $wpdb->insert( $table, $media );
      echo "Successfully uploaded.";
    }
    else
    {
      echo "Invalid file";
    }
  }

  remove_filter('upload_dir', 'my_upload_dir');

  die();
}

add_action('wp_ajax_delete_file', 'delete_file');

function delete_file() {
  //Delete fields in DB table (wp_DD_posts, wp_rt_rtm_media) and files in the server
  if ( false === wp_delete_attachment( $_POST['post_id'] ) )
    echo "Falied deleting the file!";
  else
    echo "Deleted the file successfully.";
  die();
}

function set_media_type ( $mime_type ) {
  switch ( $mime_type ) {
    case 'image':
      return 'photo';
      break;
    case 'audio':
      return 'music';
      break;
    case 'video':
      return 'video';
      break;
    default:
      return '';
      break;
  }
}

function my_upload_dir($upload) {
  global $_id;
  $upload['subdir'] = '/rtMedia/users/' . $_id . $upload['subdir'];
  $upload['path']   = $upload['basedir'] . $upload['subdir'];
  $upload['url']    = $upload['baseurl'] . $upload['subdir'];
  return $upload;
}

function get_membership_levels() {
  global $wpdb;

  $sql = "SELECT * FROM wp_2_pmpro_membership_levels";

  $row = $wpdb->get_results($sql);

  return $row;
}

function get_membership_user($user_id) {
  global $wpdb;

  $sql = "SELECT * FROM wp_2_pmpro_memberships_users
          WHERE status = 'active' ";

  if ($user_id)
  {
    $sql .= "AND user_id = ".$user_id." ";
  }

  $row = $wpdb->get_row($sql);

  return $row;
}

add_action('wp_ajax_get_change_membership', 'get_change_membership');

function get_change_membership() {
  $id = $_POST['id'];
  $membership_user = get_membership_user($id);
  $end_date = $membership_user->enddate;
  $value_date = "";

  if ($end_date != "" and $end_date != null)
    $value_date = $end_date;
  else
    $value_date = date("Y-m-d H:i:s");

  $html = '<div class="col-lg-3">
            <div class = "form-horizontal">
              <div class = "form-group">
                <label class = "col-sm-4 control-label">Current Level</label>
                <div class="col-sm-8 controls ">
                  <select class = "form-control" name = "current_level">
                    <option value="0"> None </option>';

  $level_arr = get_membership_levels();
  for ($i = 0; $i < count($level_arr); $i++)
  {
    if ($membership_user->membership_id == $level_arr[$i]->id)
      $html .=      '<option value="'.$level_arr[$i]->id.'" selected> '.$level_arr[$i]->name.' </option>';
    else
      $html .=      '<option value="'.$level_arr[$i]->id.'"> '.$level_arr[$i]->name.' </option>';
  }

  $html .=        '</select>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3">
            <div class = "form-horizontal">
              <div class = "form-group">
                <label class = "col-sm-4 control-label">Expires</label>
                <div class="col-sm-8 controls ">
                  <select class = "form-control expires" name = "expires">
                    <option value=""> --- </option>';
  if ($end_date != "" and $end_date != null)
    $html .=        '<option class = "show_date" value="yes" selected> YES </option>
                     <option value="no"> NO </option>';
  else
    $html .=        '<option class = "show_date" value="yes" selected> YES </option>
                     <option value="no" selected> NO </option>';
    $html .= '
                  </select>
                </div>
              </div>
            </div>
          </div>';
  if ($end_date != "" and $end_date != null)
    $html .=
          '<div class="col-lg-3 hidden_field" style = "display: block;">';
  else
    $html .=
          '<div class="col-lg-3 hidden_field" style = "display: none;">';
    $html .=
            '<div class = "form-horizontal">
              <div class = "form-group">
                <label class = "col-sm-4 control-label">Expired date</label>
                <div class="col-sm-8 controls ">
                  <input type = "text" class="form-control datepicker" value="'.$value_date.'" name = "end_date">
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3">
            <button type="submit" class="btn btn-primary" id="membership_form_submit"> Save </button>
            <button type="button" class = "btn btn-warning" id = "membership_form_reset" name = "reset" style="margin:0px 2px;"> x Cancle </button>
          </div>
          <div class = "col-lg-12 line-separator"></div>';
  echo $html;
  die();
}

add_action('wp_ajax_save_change_membership', 'save_change_membership');

function save_change_membership() {
  global $wpdb;

  $data = $_POST['data'];
  $id = $_POST['id'];
  $membership_user = get_membership_user($id);
  $level_value = $data[0]['value'];
  $expires_value = $data[1]['value'];
  if ($data[1]['value'] == "yes")
    $end_date = $data[2]['value'];

  $table = "wp_".$_POST['web']."_pmpro_memberships_users";

  //insert,update in wp_2_pmpro_memberships_users table
  if ($membership_user)
  {
    $wpdb->update( $table, array( 'status' => 'inactive', 'enddate' => current_time( 'mysql' )), array( 'user_id' => $id) );
  }

  if ($level_value != 0)
  {
    if ($end_date != null and $end_date != "")
      $wpdb->insert( $table, array( 'user_id' => $id, 'membership_id' => $level_value, 'status' => 'active', 'startdate' => current_time( 'mysql' ), 'enddate' => $end_date ) );
    else
      $wpdb->insert( $table, array( 'user_id' => $id, 'membership_id' => $level_value, 'status' => 'active', 'startdate' => current_time( 'mysql' ) ) );
  }

  echo ("Successfully changed membership.");
  die();
}

add_action('wp_ajax_delete_user', 'delete_user');

function delete_user() {
  global $wpdb;

  $table = "wp_usermeta";
  $blog_id = $_POST['web'];
  $user_id = $_POST['id'];
  $table2 = "wp_".$blog_id."_pmpro_memberships_users";

  $wpdb->delete( $table, array( 'meta_key' => 'wp_'.$blog_id.'_user_level', 'user_id' => $user_id ) );
  $wpdb->delete( $table, array( 'meta_key' => 'wp_'.$blog_id.'_capabilities', 'user_id' => $user_id ) );
  $wpdb->update( $table, array( 'meta_value' => ''), array( 'user_id' => $user_id, 'meta_key' => 'source_domain' ) );
  $wpdb->update( $table, array( 'meta_value' => ''), array( 'user_id' => $user_id, 'meta_key' => 'primary_blog' ) );
  $wpdb->update( $table, array( 'meta_value' => 'false'), array( 'user_id' => $user_id, 'meta_key' => 'photo_uploaded' ) );
  $wpdb->update( $table2, array( 'status' => 'inactive', 'enddate' => current_time( 'mysql' )), array( 'user_id' => $user_id) );

  // wp_delete_user( $_POST['id'] ); //delete date from user table and user meta table

  echo ("Successfully delete the user.");
    die();
}
?>