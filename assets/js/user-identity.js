(function($) {

$( document ).ready(function() {

   $('#id-selected').val('');
   $('#hidden-as').val('');
   $('#hidden-to').val('');
   $('#thread_id').val('');

   $('.spinner-identity').hide();

   // show pending message list when page ready
   var checkTalkFrom;
   var blog_id;
   var search_users_type;
   var pending_msg_sort = "DESC"; //most recently first
   var user_name = "";

  search_pending_message(1, pending_msg_sort);

   // Dropdown Select
   $( document.body ).on( 'click', '.dropdown-menu li', function( event ) {

       var $target = $( event.currentTarget );

       console.log($target);
       $target.closest( '.btn-group' )
          .find( '[data-bind="label"]' ).text( $target.find('a').attr('rel') )
             .end()
          .children( '.dropdown-toggle' ).dropdown( 'toggle' );

       $target.closest( '.btn-group' ).find( '[data-bind="label"]' ).attr( 'rel-id-select', $target.find('a').attr('rel-id') );

      if($target.find('a').attr('rel') == "Icebreaker"){
        $('.send-conversation').show();
        $('.icebreaker-conversation').hide();
      }

      if($target.find('a').attr('rel') == "Send"){
        $('.send-conversation').hide();
        $('.icebreaker-conversation').show();
      }

       return false;

    });

  // List tables
  $('#btn_pending_message_users').click(function(e) {
    e.preventDefault();
    $('.body-chat').html("<p class='text-center'>No conversation available...</p>");
    $( "#ajax-table-identity" ).hide( "drop", { direction: "down" }, "slow" );
    $('#div_talk_as').css('backgroundColor','#fff');
    $('#div_talk_to').css('backgroundColor','#fff');

    $('#forms_small').html(' Pending MSG Users ');
    search_pending_message(1, pending_msg_sort);
  });

  $('#btn_new_users').click(function(e) {
    e.preventDefault();
    $('.body-chat').html("<p class='text-center'>No conversation available...</p>");
    $( "#ajax-table-pending-message" ).hide( "drop", { direction: "down" }, "slow" );
    $('#div_talk_as').css('backgroundColor','#fff');
    $('#div_talk_to').css('backgroundColor','#fff');

    $('#forms_small').html(' New Users ');
    search_users_type = "new";
    search_users(1, "new");
  });

   // Ajax search Table
  $('.search-identity').click(function(e) {
      e.preventDefault();
      $('.body-chat').html("<p class='text-center'>No conversation available...</p>");
      $( "#ajax-table-pending-message" ).hide( "drop", { direction: "down" }, "slow" );
      $('#div_talk_as').css('backgroundColor','#fff');
      $('#div_talk_to').css('backgroundColor','#fff');

      $('#forms_small').html(' User Search ');
      search_users_type = "search";
      search_users(1, "search");
  });

  $('#ajax-table-pending-message').on('click', '.page-id', function(e) {
      e.preventDefault();
      search_pending_message($(this).attr('rel-page'), pending_msg_sort);
  });

  $('#ajax-table-identity').on('click', '.page-id', function(e) {
      e.preventDefault();
      search_users($(this).attr('rel-page'), search_users_type);
  });

  function search_users(page, from) {
    $web    = $('.dropdown-website').attr('rel-id-select');

    $is_fake_user_on = $('#chk_fake_users').prop('checked');

    if(from == "search") {
      $gender = $('.dropdown-gender').attr('rel-id-select');
      $text   = $('.search-field').val();
    }

    $page   = page;
    blog_id = $web;

    if($web != '' && $web != "0") {
      $('#ajax-table-identity').css( "opacity", "0.25" );
      $('.spinner-identity').css( "margin-left", "50%" );
      $('.spinner-identity').css( "margin-top", "-20%" );
      $('.spinner-identity').css( "position", "absolute" );

      $('.spinner-identity').show();

      if(from == "search") {
        var data = {
            action: 'get_users_identity',
            web: $web,
            gender: $gender,
            text: $text,
            page: $page,
            type: from,
            fake: $is_fake_user_on
        };
      } else {
        var data = {
            action: 'get_users_identity',
            web: $web,
            page: $page,
            type: from 
        };
      }

      $.post(ajaxurl, data, function(response) {
          $('#ajax-table-identity').css( "opacity", "1" );
          $('.spinner-identity').hide();
          $('#ajax-table-identity').html(response);
          $(".user-profile").hide( "drop", { direction: "down" }, "slow" );
          $('#ajax-table-identity').show("drop", { direction: "down" }, "slow");
          $('.chat-panel').show("drop", { direction: "down" }, "slow");
      });
    } else {
          alert('Please select the website to search.')
    }
  }

  $('#ajax-table-identity').on('click', '.row-users', function(e) {
      $('#id-selected').val($(this).attr('rel-id'));
      $('.btn-modal-identity').click();
  });

  function search_pending_message(page, sort) {
    $web    = $('.dropdown-website').attr('rel-id-select');

    $is_fake_user_on = $('#chk_fake_users').prop('checked');

    $page   = page;
    blog_id = $web;

    if($web != '' && $web != "0") {
      $('#ajax-table-pending-message').css( "opacity", "0.25" );
      $('.spinner-identity').css( "margin-left", "50%" );
      $('.spinner-identity').css( "margin-top", "-20%" );
      $('.spinner-identity').css( "position", "absolute" );

      $('.spinner-identity').show();

      var data = {
            action: 'get_pending_message',
            web: $web,
            page: $page,
            sort: sort,
            fake: $is_fake_user_on
        };

      $.post(ajaxurl, data, function(response) {
          $('#ajax-table-pending-message').css( "opacity", "1" );
          $('.spinner-identity').hide();
          $('#ajax-table-pending-message').html(response);
          $(".user-profile").hide( "drop", { direction: "down" }, "slow" );
          $('#ajax-table-pending-message').show("drop", { direction: "down" }, "slow");
          $('.chat-panel').show("drop", { direction: "down" }, "slow");
      });
    } else {
          alert('Please select the website to search.')
    }
  }

  $('#ajax-table-pending-message').on('click', '.row-users', function(e) {
    $('#ajax-table-pending-message').css( "opacity", "0.25" );
    $('.spinner-identity').css( "margin-left", "50%" );
    $('.spinner-identity').css( "margin-top", "-20%" );
    $('.spinner-identity').css( "position", "absolute" );

    $('.spinner-identity').show();

    var senderArr = $(this).find(".sender").attr('id').split('_');
    var receiverArr = $(this).find(".receiver").attr('id').split('_');
    var threadArr = $(this).find(".thread").attr('id').split('_');

    var sender = senderArr[1];
    var receiver = receiverArr[1];
    var thread = threadArr[1];

    $('#hidden-as').val(receiver);
    $('#hidden-to').val(sender);
    $hidden_as = $('#hidden-as').val();
    $hidden_to = $('#hidden-to').val();

    $.ajax({
      url:ajax_talk_to($hidden_to, $hidden_as, thread),
      success:function(){
        ajax_talk_as($hidden_as, $hidden_to, thread);
      }
    });

  });

  $('#ajax-table-pending-message').on('click', '.sort', function(e) {
    //sort
    if (pending_msg_sort == "ASC") {
      pending_msg_sort = "DESC";
    } else {
      pending_msg_sort = "ASC";
    }

    $('#btn_pending_message_users').click();
  });

  // Talk as Identity
  $('.talk-as').click(function(e) {

    $hidden_id = $('#id-selected').val();
    $hidden_to = $('#hidden-to').val();

    ajax_talk_as($hidden_id, $hidden_to);
  });

  // Talk to Identity
  $('.talk-to').click(function(e) {

    $hidden_id = $('#id-selected').val();
    $hidden_as = $('#hidden-as').val();

    ajax_talk_to($hidden_id, $hidden_as);

  });

  function ajax_talk_as(hidden_id, hidden_to, thread) 
  {
    $hidden_id = hidden_id;
    $hidden_to = hidden_to;
    var data = {
                    action: 'get_users_identity_info',
                    web: blog_id,
                    id: $hidden_id
                };

    if($hidden_id == $hidden_to) {

      $('#myModal').modal('hide');
      alert('Please select a different user to talk as');

    } else {
      $.post(ajaxurl, data, function(response) {
        $('#profile-identity-as').html(response);
        $('#hidden-as').val($hidden_id);
        $('#myModal').modal('hide');
        $('.remove-user-as').show();
    
        // Activate the chat
        if($('#hidden-as').val() != '' && $('#hidden-to').val() != '')
        {
          $('.chat-field').removeAttr( "disabled" );
          $('.chat-button').removeAttr( "disabled" );
          $('#dropdown-conversation').removeAttr( "disabled" );

          if (thread == "") {
            var data2 = {
                        action: 'get_conversation',
                        web: blog_id,
                        user_as: $('#hidden-as').val(),
                        user_to: $('#hidden-to').val()
                      };
          } else {
            var data2 = {
                        action: 'get_conversation',
                        web: blog_id,
                        user_as: $('#hidden-as').val(),
                        user_to: $('#hidden-to').val(),
                        thread: thread
                      };
          }

          $.post(ajaxurl, data2, function(response) {
            $json = JSON.parse(response);
            $('#thread_id').val($json[0]);
            $('.body-chat').html($json[1]);

            // Scrooling chat to down
            var psconsole = $('.body-chat');
            psconsole.scrollTop(psconsole[0].scrollHeight - psconsole.height());

            $('#ajax-table-pending-message').css( "opacity", "1" );
            $('.spinner-identity').hide();
          });
        }
      });
    }
  }

  function ajax_talk_to(hidden_id, hidden_as) 
  { 
    $hidden_id = hidden_id;
    $hidden_as = hidden_as;
    var data = {
                    action: 'get_users_identity_info',
                    web: blog_id,
                    id: $hidden_id
                };

    if($hidden_id == $hidden_as) {

      $('#myModal').modal('hide');
      alert('Please select a different user to talk to');

    } else {
      $.post(ajaxurl, data, function(response) {
        $('#profile-identity-to').html(response);
        $('#hidden-to').val($hidden_id);
        $('#myModal').modal('hide');
        $('.remove-user-to').show();

        // Activate the chat
        if($('#hidden-as').val() != '' && $('#hidden-to').val() != '')
        {
          $('.chat-field').removeAttr( "disabled" );
          $('.chat-button').removeAttr( "disabled" );
          $('#dropdown-conversation').removeAttr( "disabled" );

          var data2 = {
                          action: 'get_conversation',
                          web: blog_id,
                          user_as: $('#hidden-as').val(),
                          user_to: $('#hidden-to').val()
                      };

          $.post(ajaxurl, data2, function(response) {
            $json = JSON.parse(response);
            $('#thread_id').val($json[0]);
            $('.body-chat').html($json[1]);

            // Scrooling chat to down
            var psconsole = $('.body-chat');
            psconsole.scrollTop(psconsole[0].scrollHeight - psconsole.height());
          });

        }

      });
    }
  }

  // Show the profile - as
  $('#profile-identity-as').click(function(e) {
    e.preventDefault();
    ajax_profile_as();
    $('#forms_small').html(' Profile Identity as ');
  });

  // Show the profile - to
  $('#profile-identity-to').click(function(e) {
    e.preventDefault();
    ajax_profile_to();
    $('#forms_small').html(' Profile Identity to ');
  });

  function ajax_profile_as()
  {
    $hidden_id = $('#hidden-as').val();

    $('.nav-tabs a[href="#profile"]').tab('show');

    var data = {
                    action: 'get_users_profile',
                    web: blog_id,
                    id: $hidden_id
                };
    $.post(ajaxurl, data, function(response) {
      if($('#hidden-as').val() != "") {
        $json = JSON.parse(response);
        user_name = $json[1];
        common_actions_for_profile($json[0]);
        
        $('#tab-conversations').show();
        $('#div_talk_as').css('backgroundColor','#ecf0f1'); //change backgroud colour 
        $('#div_talk_to').css('backgroundColor','#fff');

        checkTalkFrom = "as";
      }
    }); 
  }

  function ajax_profile_to()
  {
    $hidden_id = $('#hidden-to').val();

    $('.nav-tabs a[href="#profile"]').tab('show');

    var data = {
                    action: 'get_users_profile',
                    web: blog_id,
                    id: $hidden_id
                };
    $.post(ajaxurl, data, function(response) {            
      if($('#hidden-to').val() != "") {
        $json = JSON.parse(response);
        user_name = $json[1];
        common_actions_for_profile($json[0]);

        $('#tab-conversations').hide();
        $('#div_talk_to').css('backgroundColor','#ecf0f1'); //change backgroud colour 
        $('#div_talk_as').css('backgroundColor','#fff');

        checkTalkFrom = "to";
      }
    });
  }

  function common_actions_for_profile(response)
  {
    $('#profile-form').html($json[0]);
    $( "#ajax-table-identity" ).hide( "drop", { direction: "down" }, "slow" );
    $( "#ajax-table-pending-message" ).hide( "drop", { direction: "down" }, "slow" );
    $( ".user-profile" ).show( "slow" );
    $(".separator-line").show();

    $('.datepicker').datepicker({
      format: "yyyy-mm-dd",
      startView: 2
    });
    $("input,select,textarea").not("[type=submit]").jqBootstrapValidation();

    $('#btn_change_avatar').click(function(e) {
      $('#modal_change_avatar').modal('show');

      var data = {
                    action: 'show_avatar_modal',
                    id: $hidden_id
                };
      $.post(ajaxurl, data, function(response) {
        $('#avatar_upload_form').html(response);
      });
    });
  }  

  var options = { 
      // target:        '#avatar_upload_form',      // target element(s) to be updated with server response 
      beforeSubmit:  showRequest,     // pre-submit callback 
      success:       showResponse,    // post-submit callback 
      url:    ajaxurl                 // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php     
  }; 

  function showRequest(formData, jqForm, options) {
    for (index = 0; index < formData.length; ++index) {
      if (formData[index].name == "post_id") {
          formData[index].value = $hidden_id;
          break;
      }
    }

    $('#avatar_upload_form').css( "opacity", "0.25" );
    $('.spinner-identity').css( "margin-left", "50%" );
    $('.spinner-identity').css( "margin-top", "-20%" );
    $('.spinner-identity').css( "position", "absolute" );
    $('.spinner-identity').show();
  }
  function showResponse(responseText, statusText, xhr, $form) {
    if (checkTalkFrom === "as") {
      ajax_talk_as($hidden_id, $hidden_to);
      ajax_profile_as();
    }
    else if (checkTalkFrom === "to") {
      ajax_talk_to($hidden_id, $hidden_as);
      ajax_profile_to();
    }

    $('#avatar_upload_form').css( "opacity", "1" );
    $('.spinner-identity').hide();
    alert(responseText);
  }
  // bind form using 'ajaxForm' 
  $('#avatar_upload_form').ajaxForm(options); 


  $(document).delegate('#delete_avatar_modal_button', "click", function (){
    if (confirm('Are you sure to delete the avatar?')) {
      $('#avatar_upload_form').css( "opacity", "0.25" );
      $('.spinner-identity').css( "margin-left", "50%" );
      $('.spinner-identity').css( "margin-top", "-20%" );
      $('.spinner-identity').css( "position", "absolute" );
      $('.spinner-identity').show();

      var data = {
          action: 'delete_avatar',
          id: $hidden_id,
      };

      $.post(ajaxurl, data, function(response) {
        if (checkTalkFrom === "as") {
          ajax_talk_as($hidden_id, $hidden_to);
          ajax_profile_as();
        }
        else if (checkTalkFrom === "to") {
          ajax_talk_to($hidden_id, $hidden_as);
          ajax_profile_to();
        }

        $('#avatar_upload_form').css( "opacity", "1" );
        $('.spinner-identity').hide();
        alert(response);  
      });
    }
  });

  // Remove talk as
  $('.remove-user-as').click(function(e) {
    $img = "<img src='" + $(this).attr('rel-img') + "' height='45' width='45'>";
    $('#profile-identity-as').html($img);
    $('.remove-user-as').hide();
    $('#hidden-as').val('');
    $('.chat-field').attr( "disabled", "disabled" );
    $('.chat-button').attr( "disabled", "disabled" );
    $('#dropdown-conversation').attr( "disabled", "disabled" );
    $('.body-chat').html("<p class='text-center'>No conversation available...</p>");
    $('.icebreaker-identity-as').html('');
    $('.icebreaker-identity-to').html('');
    $(".user-profile").hide( "drop", { direction: "down" }, "slow" );
    $('#div_talk_as').css('backgroundColor','#fff');
    $('#div_talk_to').css('backgroundColor','#fff');
  });

  // Remove talk to
  $('.remove-user-to').click(function(e) {
    $img = "<img src='" + $(this).attr('rel-img') + "' height='45' width='45'>";
    $('#profile-identity-to').html($img);
    $('.remove-user-to').hide();
    $('#hidden-to').val('');
    $('.chat-field').attr( "disabled", "disabled" );
    $('.chat-button').attr( "disabled", "disabled" );
    $('#dropdown-conversation').attr( "disabled", "disabled" );
    $('.body-chat').html("<p class='text-center'>No conversation available...</p>");
    $('.icebreaker-identity-as').html('');
    $('.icebreaker-identity-to').html('');
    $(".user-profile").hide( "drop", { direction: "down" }, "slow" );
    $('#div_talk_as').css('backgroundColor','#fff');
    $('#div_talk_to').css('backgroundColor','#fff');
  });

  // Send a new message or Icebreaker
  $('#btn-chat').click(function(e) {

    $text       = $('#btn-input').val();
    $hidden_as  = $('#hidden-as').val();
    $hidden_to  = $('#hidden-to').val();
    $thread_id  = $('#thread_id').val();
    $action     = $(this).attr('rel-id-select');

    if($action == "1") {

      if($text != "") {

        $('.chat-button').attr( "disabled", "disabled" );

        var data = {
                      action: 'send_message_identity',
                      web: blog_id,
                      user_as: $hidden_as,
                      user_to: $hidden_to,
                      text: $text,
                      thread_id: $thread_id
                    };

        $.post(ajaxurl, data, function(response) {
          $json = JSON.parse(response);
          $message = $json[0];
          $('#thread_id').val($json[1]);

          $('.chat-button').removeAttr( "disabled" );
          $current_text = $('.body-chat').html();

          if($current_text == '<p class="text-center">No conversation available...</p>')
            $('.body-chat').html($message);
          else
            $('.body-chat').html($current_text + $message);

          // pending message list update
          if ($('#ajax-table-pending-message').css('display') != 'none') {
            search_pending_message(1, pending_msg_sort);
            $('.remove-user-as').click();
            $('.remove-user-to').click();
          }

          // Scrooling chat to down
          var psconsole = $('.body-chat');
          psconsole.scrollTop(psconsole[0].scrollHeight - psconsole.height());

          $('#btn-input').val('');
        });

      }

    } else {

        $('.chat-button').attr( "disabled", "disabled" );

        //Get The URL to do the Icebreaker
        var data = {
                      action: 'get_url_icebreaker',
                      user_as: $hidden_as,
                      user_to: $hidden_to
                    };

        $.post(ajaxurl, data, function(response) {

          $('.chat-button').removeAttr( "disabled" );
          $('#btn-chat').attr('data-content', response);
          $('#btn-chat').popover('show');

          setTimeout(function() { $('#btn-chat').popover('hide'); }, 5000);

        });

        //alert('The Icebreaker has been sent successfully.');
    }

  });

  // PopOver
  $('#btn-chat').popover({
      placement : 'top',
      html : true,
      trigger : 'manual', //<--- you need a trigger other than manual
      content: function() {
          return $('#btn-chat').attr('data-content');
      }
  });

  // Save changes in profile tap
  $('#profile-form').submit(function(e) {
    e.preventDefault();

    $('#profile').css( "opacity", "0.25" );
    $('.spinner-identity').css( "margin-left", "50%" );
    $('.spinner-identity').css( "margin-top", "-20%" );
    $('.spinner-identity').css( "position", "absolute" );

    $('.spinner-identity').show();

    var data = {
        action: 'save_profile_data',
        web: blog_id,
        data: $("#profile-form").serializeArray(),
    };

    $.post(ajaxurl, data, function(response) {
      $('#profile').css( "opacity", "1" );
      $('.spinner-identity').hide();
      // $('#profile-form').html(response);

      if (checkTalkFrom === "as") {
        ajax_talk_as($hidden_id, $hidden_to);
      }
      else if (checkTalkFrom === "to") {
        ajax_talk_to($hidden_id, $hidden_as);
      }
      alert("Successfully saved changes.");  
    });
  });

  // Click conversations tab
  $('#tab-conversations').click(function() {
    profile_coversations(1);
  });

  $('#conversations').on('click', '.page-id', function(e) {
      e.preventDefault();
      profile_coversations($(this).attr('rel-page'));
  });

  function profile_coversations(page) {
    var data = {
                    action: 'get_user_conversations',
                    web: blog_id,
                    id: $hidden_id,
                    page: page
                };
    $.post(ajaxurl, data, function(response) {
      $('#conversations').html(response);

      //Click conversation table row
      $('#conversation-table').on('click', 'tbody tr', function(e) {
          var senderArr = $(this).find(".sender").attr('id').split('_');
          var receiverArr = $(this).find(".receiver").attr('id').split('_');
          var threadArr = $(this).find(".thread").attr('id').split('_');

          var sender = senderArr[1];
          var receiver = receiverArr[1];
          var thread = threadArr[1];

          var data2 = {
                        action: 'get_conversation',
                        web: blog_id,
                        user_as: sender,
                        user_to: receiver,
                        thread: thread
                      };

          $.post(ajaxurl, data2, function(response) {
            $('.chat-field').removeAttr( "disabled" );
            $('.chat-button').removeAttr( "disabled" );

            $json = JSON.parse(response);
            $('#thread_id').val($json[0]);
            $('.body-chat').html($json[1]);

            // Scrooling chat to down
            var psconsole = $('.body-chat');
            psconsole.scrollTop(psconsole[0].scrollHeight - psconsole.height());
          });

      });
    });
  }

  // Click Files tab
  function get_files() {
    var data = {
                    action: 'get_files',
                    id: $hidden_id
                };
    $.post(ajaxurl, data, function(response) {
      $('#media_gallary').html(response);
      $('#media_gallary').magnificPopup({
        delegate: 'a', // the selector for gallery item
        type: 'image',
        gallery:{
          enabled:true
        }
      });
    });
  }

  $('#tab-files').click(function(e) {
    get_files();
  });

  $('#btn_upload_file').click(function(e) {
    var data = {
                    action: 'get_files_upload_form',
                    id: $hidden_id
                };

    $.post(ajaxurl, data, function(response) {
      $('#files_upload').show();
      $('#files_upload').html(response);
      $('#files_form_reset').click(function(e) {
        $('#files_upload').hide();
      });

      var option_files = { 
        // target:        '#avatar_upload_form',      
        beforeSubmit:  
          function showRequest(formData, jqForm, options) {
            for (index = 0; index < formData.length; ++index) {
              if (formData[index].name == "post_id") {
                  formData[index].value = $hidden_id;
              } else if (formData[index].name == "blog_id") {
                formData[index].value = blog_id;
              }
            } 

            $('#files_upload').css( "opacity", "0.25" );
            $('.spinner-identity').css( "margin-left", "50%" );
            $('.spinner-identity').css( "margin-top", "-20%" );
            $('.spinner-identity').css( "position", "absolute" );
            $('.spinner-identity').show();
          },    
        success: 
          function showResponse(responseText, statusText, xhr, $form) {
            $('#files_upload').css( "opacity", "1" );
            $('.spinner-identity').hide();

            $('#files_upload').hide();
            alert(responseText);
            get_files();
          },   
        url:    ajaxurl                 
      }; 
      // bind form using 'ajaxForm' 
      $('#files_form').ajaxForm(option_files); 
    });
  });
  
  $(document).delegate('.btn_delete_file', "click", function (){ 
    if (confirm('Are you sure to delete the file?')) {
      var idArr = $(this).attr('id').split('_');

      var data = {
                      action: 'delete_file',
                      rt_id: idArr[1],
                      post_id: idArr[2] 
                  };
      $('#files').css( "opacity", "0.25" );
      $('.spinner-identity').css( "margin-left", "50%" );
      $('.spinner-identity').css( "margin-top", "-20%" );
      $('.spinner-identity').css( "position", "absolute" );
      $('.spinner-identity').show();

      $.post(ajaxurl, data, function(response) {
        $('#files').css( "opacity", "1" );
        $('.spinner-identity').hide();
        alert(response);

        get_files();
      });
    }
  });

  $('#change-membership').click(function(e) {
    var data = {
                    action: 'get_change_membership',
                    id: $hidden_id
                };

    $.post(ajaxurl, data, function(response) {
      $('#membership').show();
      $('#change_membership_form').html(response);
      $('.datepicker').datepicker({
        format: "yyyy-mm-dd",
      });
      $('.expires').change(function() {
        if ($('.expires').val() == "yes") {
          $('.hidden_field').show();
        } else {
          $('.hidden_field').hide();
        }
      }).trigger( "change" );

      $('#membership_form_reset').click(function(e) {
        $('#membership').hide();
      });
    });
  });

  $('#change_membership_form').submit(function(e) {
    e.preventDefault();
    $('#more').css( "opacity", "0.25" );
    $('.spinner-identity').css( "margin-left", "50%" );
    $('.spinner-identity').css( "margin-top", "-20%" );
    $('.spinner-identity').css( "position", "absolute" );

    $('.spinner-identity').show();

    var data = {
                    action: 'save_change_membership',
                    id: $hidden_id,
                    web: blog_id,
                    data: $("#change_membership_form").serializeArray(),
                };

    $.post(ajaxurl, data, function(response) {
      $('#more').css( "opacity", "1" );
      $('.spinner-identity').hide();
      $('#membership').hide();
      alert(response);
    });
  });

  $('#delete-user').click(function(e) {  
    if (confirm('Are you sure to delete ' + user_name + '?')) {
      $('#more').css( "opacity", "0.25" );
      $('.spinner-identity').css( "margin-left", "50%" );
      $('.spinner-identity').css( "margin-top", "-20%" );
      $('.spinner-identity').css( "position", "absolute" );

      $('.spinner-identity').show();

      var data = {
                      action: 'delete_user',
                      id: $hidden_id,
                      web: blog_id,
                  };

      $.post(ajaxurl, data, function(response) {
        if (checkTalkFrom === "as") {
          $('.remove-user-as').click();
        }
        else if (checkTalkFrom === "to") {
          $('.remove-user-to').click();
        }

        search_pending_message(1, pending_msg_sort);
        
        $('#more').css( "opacity", "1" );
        $('.spinner-identity').hide();

        alert(response);
      });
    }
  });

});

})( jQuery );