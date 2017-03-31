<?php

  $blog_list  = get_blog_list( 0, 'all' );

?>
<link rel="stylesheet" href="<?php echo plugins_url( 'user-identity-control/assets/css/bootstrap.css'); ?>">
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="<?php echo plugins_url( 'user-identity-control/assets/css/datepicker3.css'); ?>">
<link rel="stylesheet" href="<?php echo plugins_url( 'user-identity-control/assets/css/magnific-popup.css'); ?>">
<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css" rel="stylesheet">
<div class="body-user-identity">
  <!-- Title -->
  <div class="row">
    <div class="col-lg-12">
      <div class="page-header">
        <div class = "col-lg-6">
          <h4 id="forms" style="padding-top: 10px;" >User Identity Control > <small id="forms_small" style="color: #2C3E50;"> Pending MSG Users</small> </h4>
        </div>
        <div class = "col-lg-6 text-right" style="padding-right: 0px; ">
          <form role="form" class="form-inline">
            <div class="checkbox" style="margin: 0px 10px;">
              <label>
                <input type="checkbox" id= "chk_fake_users"> <b>Fake Users</b>
              </label>
            </div>
            <div class="btn-group text-center">
              <button data-bind="label" class="btn btn-primary dropdown-website" rel-id-select="2" type="button"><?php echo $blog_list[1]['domain']; ?></button>
              <button data-toggle="dropdown" class="btn btn-primary dropdown-toggle" type="button"><span class="caret"></span></button>
              <ul class="dropdown-menu">
                <li><a href="#" rel="Website" rel-id="0">&nbsp;</a></li>
                <?php for($i = 0; $i < count($blog_list); $i++) : ?>
                  <?php if ($i != "1") : ?>
                    <li><a href="#" rel="<?php echo $blog_list[$i]['domain'] ?>" rel-id="<?php echo $blog_list[$i]['blog_id'] ?>"><?php echo($blog_list[$i]['domain']); ?></a></li>
                  <?php endif; ?>
                <?php endfor; ?>
              </ul>
            </div>
            <button class="btn btn-primary" id = "btn_pending_message_users" type="button" style="margin: 0px 10px;">Pending MSG Users</button>
            <button class="btn btn-primary" id = "btn_new_users" type="button" >New Users </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Search Fields  -->
  <div class="row text-center">
    <div class="col-lg-3 text-center" id="div_talk_as">
      <div class="well bs-component text-left about-profile">
        <div class="col-lg-10" style="padding-left: 0px; padding-right: 0px">
          <div id="profile-identity-as">
            <img src="<?php echo plugins_url( 'user-identity-control/assets/img/missing_photo.png'); ?>" height="40" width="40">
          </div>
        </div>
        <div class="col-lg-2 remove-user-as" rel-img="<?php echo plugins_url( 'user-identity-control/assets/img/missing_photo.png'); ?>">
          <img alt="Remove this user from identity" title="Remove this user from identity" src="<?php echo plugins_url( 'user-identity-control/assets/img/remove_user.png'); ?>" height="24" width="26">
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="well bs-component">
        <form class="form-inline" role="form" style="line-height: 43px;">
             <div class="btn-group">
              <button data-bind="label" class="btn btn-primary dropdown-gender" rel-id-select="" type="button">Gender</button>
              <button data-toggle="dropdown" class="btn btn-primary dropdown-toggle" type="button"><span class="caret"></span></button>
              <ul class="dropdown-menu">
                <li><a href="#" rel-id="" rel="Gender">&nbsp;</a></li>
                <li><a href="#" rel-id="Man" rel="Male">Male</a></li>
                <li><a href="#" rel-id="Female" rel="Female">Female</a></li>
                <li><a href="#" rel-id="Couple" rel="Couple">Couple</a></li>
                <li><a href="#" rel-id="TS" rel="TV/TS">TV/TS</a></li>
              </ul>
            </div>

            <input type="text" placeholder="Email / Username" id="inputEmail" class="form-control search-field">

            <!-- <button type="submit" class="btn btn-primary btn-sm search-identity">Search</button> -->
            <button type="submit" class="btn btn-primary search-identity"><span class="glyphicon glyphicon-search"></span></button>
        </form>
      <div class="btn btn-primary btn-xs" id="source-button" style="display: none;">&lt; &gt;</div></div>
    </div>

    <div class="col-lg-3 text-center" id="div_talk_to">
      <div class="well bs-component text-left about-profile">
        <div class="col-lg-10" style="padding-left: 0px; padding-right: 0px">
          <div id="profile-identity-to">
            <img src="<?php echo plugins_url( 'user-identity-control/assets/img/missing_photo.png'); ?>" height="40" width="40">
          </div>
        </div>
        <div class="col-lg-2 remove-user-to" rel-img="<?php echo plugins_url( 'user-identity-control/assets/img/missing_photo.png'); ?>">
          <img alt="Remove this user from identity" title="Remove this user from identity" src="<?php echo plugins_url( 'user-identity-control/assets/img/remove_user.png'); ?>" height="28" width="30">
        </div>
      </div>
    </div>
  </div>

  <div id="ajax-table-identity"></div>
  <div id="ajax-table-pending-message"></div>

  <div class="row user-profile">
    <div class="col-lg-12" style="padding-bottom: 15px; background-color: #ecf0f1;">
      <div class="bs-component">
        <ul style="margin-bottom: 15px;" class="nav nav-tabs" id="tap-ul">
          <li class="active" id="tap-active"><a data-toggle="tab" href="#profile" id = "tab-profile">Profile</a></li>
          <li><a data-toggle="tab" href="#conversations" id = "tab-conversations">Conversations</a></li>
          <li><a data-toggle="tab" href="#files" id = "tab-files">Files</a></li>
          <li><a data-toggle="tab" href="#more" id = "tab-more">More...</a></li>
        </ul>
        <div class="tab-content" id="myTabContent">

          <!-- Profile -->
          <div id="profile" class="tab-pane fade active in">
              <form id = "profile-form" role="form">
              </form>
          </div>

          <!-- Conversations -->
          <div id="conversations" class="tab-pane fade" >
          </div>
          <!--  -->

          <!-- Files -->
          <div id="files" class="tab-pane fade">
            <div class="col-lg-12 text-right">
              <button type="button" class="btn btn-default" id ="btn_upload_file" >Upload file</button>
            </div>
            <div class="col-lg-12 text-center" id = "files_upload">
            </div>
            <div class = "col-lg-12" id = "media_gallary">
            </div>
          </div>
          <!--  -->

          <!-- More... -->
          <div id="more" class="tab-pane fade">
            <div class="col-lg-12">

                <div class="col-lg-12" style="padding-bottom: 15px;">
                  <button class="btn btn-primary" id="change-membership">Change membership...</button>
                </div>

                <div class="col-lg-12" id="membership">
                  <form id="change_membership_form" role="form">
                  </form>
                </div>
                <div class="col-lg-12">
                  &nbsp;
                </div>

                <div class="col-lg-12">
                  <button class="btn btn-danger" id="delete-user">Delete User</button>
                </div>
            </div>
          </div>
          <!--  -->

        </div>
      <div class="btn btn-primary btn-xs" id="source-button" style="display: none;">&lt; &gt;</div></div>
    </div>
  </div>

  <div class="separator-line"></div>

  <!-- Chat -->
  <div class="container chat-panel clearfix" style="max-width: 720px;">
    <div >
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h3 class="panel-title">Conversation</h3>
        </div>
        <div class="panel-body well body-chat">
          <p class='text-center'>No conversation available...</p>
        </div>
        <div class="panel-body">
          <div class="col-lg-9">
            <textarea id="btn-input" class="form-control chat-field" placeholder="Type your message here..." disabled="disabled" style="margin-bottom:10px"></textarea>
          </div>
            <div class="col-lg-3 text-center" style="min-width:152px;">
            <span class="input-group-btn">
                <div class="btn-group">
                  <button data-bind="label" data-container="body" data-toggle="popover" data-placement="top" data-content="" class="btn btn-primary chat-button" id="btn-chat" rel-id-select="1" type="button" disabled="disabled">Send</button>
                  <button id="dropdown-conversation" data-toggle="dropdown" class="btn btn-primary dropdown-toggle" type="button" disabled="disabled"><span class="caret"></span></button>
                  <ul class="dropdown-menu">
                    <li class="send-conversation"><a rel="Send" rel-id="1" href="#">Send</a></li>
                    <li class="icebreaker-conversation"><a rel="Icebreaker" rel-id="2" href="#">Icebreaker</a></li>
                  </ul>
                </div>
            </span>
            </div>
          </div>
      </div>
    </div>

  </div>

  <p class="text-muted text-center spinner-identity"><img src="<?php echo plugins_url( 'user-identity-control/assets/img/ajax-loader.gif'); ?>"></p>

  <!-- Button trigger modal -->
  <button class="btn btn-primary btn-lg btn-modal-identity" data-toggle="modal" data-target="#myModal">Launch demo modal</button>

  <!-- Modal -->
  <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title" id="myModalLabel">User Identity - Type</h4>
        </div>
        <div class="modal-body text-center">
          <button type="button" class="btn btn-info talk-as">Talk As...</button>
          <button type="button" class="btn btn-warning talk-to">Talk To...</button>
          <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>

 <!-- Change photo Modal -->
  <div class="modal fade" id="modal_change_avatar" tabindex="-1" role="dialog" aria-labelledby="modal_change_avatar_label" aria-hidden="true" style="top: 30%;">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title" id="myModalLabel">Change Avatar</h4>
        </div>
        <div class="modal-body">
          <form id="avatar_upload_form" method="post" action="#" enctype="multipart/form-data" >
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- input controls -->
  <input type="hidden" id="id-selected" value="">
  <input type="hidden" id="hidden-as" value="">
  <input type="hidden" id="hidden-to" value="">
  <input type="hidden" id="thread_id" value="">
</div>
<script src="https://code.jquery.com/jquery-2.1.0.min.js"></script>
<script src="<?php echo plugins_url( 'user-identity-control/assets/js/jquery.magnific-popup.js'); ?>"></script>
<script src="<?php echo plugins_url( 'user-identity-control/assets/js/bootstrap.js'); ?>"></script>
<script src="<?php echo plugins_url( 'user-identity-control/assets/js/user-identity.js'); ?>"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
<script src="<?php echo plugins_url( 'user-identity-control/assets/js/bootstrap-datepicker.js'); ?>"></script>
<script src="<?php echo plugins_url( 'user-identity-control/assets/js/jqBootstrapValidation.js'); ?>"></script>
<script src="http://malsup.github.io/min/jquery.form.min.js"></script>

