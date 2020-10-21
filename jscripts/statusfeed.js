/* When the user clicks on the button,
toggle between hiding and showing the dropdown content */
function statusfeed_dropdown(sid) {
    document.getElementById("sf_dropdown" + sid).classList.toggle("show");
  }
  
  // Close the dropdown menu if the user clicks outside of it
  window.onclick = function(event) {
    if (!event.target.matches('.dropbtn')) {
      var dropdowns = document.getElementsByClassName("dropdown-content");
      var i;
      for (i = 0; i < dropdowns.length; i++) {
        var openDropdown = dropdowns[i];
        if (openDropdown.classList.contains('show')) {
          openDropdown.classList.remove('show');
        }
      }
    }
  }

/* 
	$(function () {
    $('#sf_delete_form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
        type: 'post',
        url: 'misc.php?action=statusfeed_delete_status&ajaxdelete=true',
        data: $('#sf_delete_form').serialize(),
        success: function (data) {
          $( data ).insertAfter( $( "#statusfeed_outer_notification_container" ));
          // $("#sf_delete_form").closest('tbody').fadeOut(500).remove();
          
          $("#sf_delete_form").closest('tbody').fadeOut("normal", function() {
            $(this).remove();
            });
          
          $("#statusfeed_header").get(0).scrollIntoView();
          $('.sf_ajax_newstatus').fadeIn(550);
        }
        });
      });
    });	
  
    // Old: 
    /* 
    $(function () {
    $('#sf_edit_form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
        type: 'post',
        url: 'misc.php?action=edit_status&ajaxedit=true',
        data: $('#sf_edit_form').serialize(),
        success: function (data) {
          // $( data ).insertAfter( $( "#statusfeed_outer_notification_container" ));
          // $("#sf_edit_form").closest('tbody').replaceWith(data);
          $( "#status_{$ID}" ).replaceWith("<tbody>" + data + "</tbody>");
          $(".sf_last_comment").fadeIn(400);
          // $( ".comment_{$ID}" ).replaceWith(data);
          $(".sf_ajax_newstatus").fadeIn(500);
          // $( "#status_{$ID}" ).html(data);
          // $( "#status_{$ID}" ).find('tbody').empty().append(data);
          // alert(data);
  
        }
        });
      });
    });	*/ 

/* 
    $(function () {
      $('#sf_edit_form').on('submit', function (e) {
          e.preventDefault();
          $.ajax({
          type: 'post',
          url: 'misc.php?action=edit_status&ajaxedit=true',
          data: $('#sf_edit_form').serialize(),
          success: function (data) {
            // $( data ).insertAfter( $( "#statusfeed_outer_notification_container" ));
            // $("#sf_edit_form").closest('tbody').replaceWith(data);
            $( "#status_{$ID}" ).replaceWith("<tbody>" + data + "</tbody>");
            $(".sf_last_comment").fadeIn(400);
            // $( ".comment_{$ID}" ).replaceWith(data);
            $(".sf_ajax_newstatus").fadeIn(500);
            // $( "#status_{$ID}" ).html(data);
            // $( "#status_{$ID}" ).find('tbody').empty().append(data);
            // alert(data);
    
          }
          });
        });
      });	*/ 
      /*
*/ 

      $(function () {
        $('#sf_edit_form').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
            type: 'post',
            url: 'misc.php?action=edit_status&ajaxedit=true',
            data: $('#sf_edit_form').serialize(),
            success: function (data) {
              // $( data ).insertAfter( $( "#statusfeed_outer_notification_container" ));
              // $("#sf_edit_form").closest('tbody').replaceWith(data);
              $( "#status_{$ID}" ).replaceWith("<tbody>" + data + "</tbody>");
              $(".sf_last_comment").fadeIn(400);
              // $( ".comment_{$ID}" ).replaceWith(data);
              $(".sf_ajax_newstatus").fadeIn(500);
              // $( "#status_{$ID}" ).html(data);
              // $( "#status_{$ID}" ).find('tbody').empty().append(data);
              // alert(data);
      
            }
            });
          });
        });	

  /* Statusfeed_portal */ 
    /*
  $(function () {
    $('#sf_form').on('submit', function (e) {
      e.preventDefault();
      $.ajax({
        type: 'post',
        url: 'misc.php?action=update_status&ajaxpost=true&template={$statusStyle}',
        data: $('#sf_form').serialize(),
        success: function (data) {
          $( data ).insertAfter( $( "#statusfeed_header" ));
          $("#statusfeed_header").get(0).scrollIntoView({behavior: 'smooth' });
          $('.sf_ajax_newstatus').fadeIn(550);
          $(".sf_nonefound").remove();
        }
      });
    });
  });
*/ 

// Uncomment the above? I don't know what {$statusStyle} means

  /* Statusfeed_profile OLD */ 
  
  /*
  $(function () {
    $('#sf_form_profile').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
          type: 'post',
          url: 'misc.php?action=update_status&ajaxpost=true&template=full',
          data: $('#sf_form').serialize(),
          success: function (data) {
      $( data ).insertAfter( $( "#statusfeed_header" ));
      $("#statusfeed_header").get(0).scrollIntoView({behavior: 'smooth' });
      $(".sf_nonefound").remove();
      $('.sf_ajax_newstatus').fadeIn(550);
    }
         });
      });
    });
    */ 

   $(function () {
    $('#sf_form_profile').on('submit', function (e) {
          e.preventDefault();
          $.ajax({
            type: 'post',
            url: 'misc.php?action=update_status&ajaxpost=true&template=full',
            data: $('#sf_form_profile').serialize(),
            success: function (data) {
                $( data ).insertAfter( $( "#statusfeed_header" ));
                $("#statusfeed_header").get(0).scrollIntoView({behavior: 'smooth' });
                $(".sf_nonefound").remove();
                $('.sf_ajax_newstatus').fadeIn(550);
            }
           });
      });
    });


    /* Statusfeed_all */ 
    
    $(function () {
      $('#sf_form_all').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
          type: 'post',
          url: 'misc.php?action=update_status&ajaxpost=true&template=full',
          data: $('#sf_form_all').serialize(),
          success: function (data) {
            $(data).insertAfter($("#statusfeed_header"));
            $("#statusfeed_header").get(0).scrollIntoView({behavior: 'smooth' });
            $(".sf_nonefound").remove();
            $('.sf_ajax_newstatus').fadeIn(550);
          }
        });
      });
    });

/* For statusfeed portal, both mini and full forms. 
We need both forms because forums with the statusfeed on the index may decide to use a different template. */ 

$(function () {
  $('#sf_form_full').on('submit', function (e) {
      e.preventDefault();
      $.ajax({
          type: 'post',
          url: 'misc.php?action=update_status&ajaxpost=true&template=full',
          data: $('#sf_form_full').serialize(),
          success: function (data) {
              $( data ).insertAfter( $( "#statusfeed_header" ));
              $("#statusfeed_header").get(0).scrollIntoView({behavior: 'smooth' });
              $('.sf_ajax_newstatus').fadeIn(550);
              $(".sf_nonefound").remove();
          }
      });
  });
});

$(function () {
  $('#sf_form_mini').on('submit', function (e) {
      e.preventDefault();
      $.ajax({
          type: 'post',
          url: 'misc.php?action=update_status&ajaxpost=true&template=mini',
          data: $('#sf_form_mini').serialize(),
          success: function (data) {
              $( data ).insertAfter( $( "#statusfeed_header" ));
              $("#statusfeed_header").get(0).scrollIntoView({behavior: 'smooth' });
              $('.sf_ajax_newstatus').fadeIn(550);
              $(".sf_nonefound").remove();
          }
      });
  });
});
  // Source: https://makitweb.com/dynamically-show-data-in-the-tooltip-using-ajax/


 //  $(document).ready(function(){
  jQuery( document ).ready(function( $ ) {
    // initialize tooltip
    $( ".statusfeed_likebutton_link" ).tooltip({
      track:true,
      open: function( event, ui ) {
      var id = this.id;
      var split_id = id.split('_');
      var statusid = split_id[1];
    
      $.ajax({
       url:'misc.php?action=getLikesPopup&sid=' + parseInt(statusid),
       type:'post',
       data:{statusid:statusid},
       success: function(response){
    
       // Setting content option
      // $("#"+id).tooltip('option','content',response);
      $("#"+id).tooltip({
        tooltipClass: 'likeButton_tooltip',
        position: { 
          my: 'center+50 bottom', 
          at: 'center top-20',
          of: '.statusfeed_likebutton_span'
        },
        content: response
      });
      }
     });
     }
    });
   
    $(".statusfeed_likebutton_link").mouseout(function(){
      // re-initializing tooltip
      $(this).attr('title','Please wait...');
      $(this).tooltip();
      $(".ui-helper-hidden-accessible").hide();
      $('.ui-tooltip').hide();
    });
   
   });














