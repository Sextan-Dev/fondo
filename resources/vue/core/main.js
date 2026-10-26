/*

Main javascript functions to init most of the elements

*/

$( document ).ready(function() {

  // INIT MENU TO ACTIVATE ON HOVER
  var menu_timer;
  $(document).on('mouseenter', 'ul.main-menu > li.has-sub-menu', function(){
    var $elem = $(this);
    clearTimeout(menu_timer);
    $elem.closest('ul').addClass('has-active').find('> li').removeClass('active');
    $elem.addClass('active');
  });

  $(document).on('mouseleave', 'ul.main-menu > li.has-sub-menu', function(){
    var $elem = $(this);
    menu_timer = setTimeout(function(){
      $elem.removeClass('active').closest('ul').removeClass('has-active');
    }, 30);
  });  

  // INIT MENU TO ACTIVATE ON CLICK
  $(document).on('click', 'li.has-sub-menu > a', function(event){
    var $elem = $(this).closest('li');
    if($elem.hasClass('active')){
      $elem.removeClass('active');
    }else{
      $elem.closest('ul').find('li.active').removeClass('active');
      $elem.addClass('active');
    }
    return false; 
  });

  // CUSTOM DROPDOWNS 
  $(document).on('mouseenter', '.os-dropdown-trigger', function(){
    $(this).addClass('over');
  });
  $(document).on('mouseleave', '.os-dropdown-trigger', function(){
    $(this).removeClass('over');
  });

  // INIT MOBILE MENU TRIGGER BUTTON
  $(document).on('click', '.mobile-menu-trigger', function(){
    $('.menu-mobile .menu-and-user').slideToggle(200, 'swing');
    return false;
  });

  $(document).on('click', '.content-panel-close, .content-panel-open, .content-panel a[href]', function(){
    $('.all-wrapper').toggleClass('content-panel-active');
  });

  // Sidebar Sections foldable trigger
  $(document).on('click','.todo-sidebar-section-toggle', function(){
    $(this).closest('.todo-sidebar-section').find('.todo-sidebar-section-contents').slideToggle(100);
    return false;
  });

  // Sidebar Sub Sections foldable trigger
  $(document).on('click','.todo-sidebar-section-sub-section-toggler', function(){
    $(this).closest('.todo-sidebar-section-sub-section').find('.todo-sidebar-section-sub-section-content').slideToggle(100);
    return false;
  });

  // #14. FULL CHAT APP
  function add_full_chat_message($input){
    $('.chat-content').append('<div class="chat-message self"><div class="chat-message-content-w"><div class="chat-message-content">' + $input.val() + '</div></div><div class="chat-message-date">1:23pm</div><div class="chat-message-avatar"><img alt="" src="images/avatar1.jpg"></div></div>');
    $input.val('');
    var $messages_w = $('.chat-content-w');
    $messages_w.scrollTop($messages_w[0].scrollHeight);
  }
  $(document).on('click','.chat-btn a', function(){
    add_full_chat_message($('.chat-input input'));
    return false;
  })
  $(document).on('click','.chat-input input', function(e){
    if (e.which == 13) {
      add_full_chat_message($(this));
      return false;
    }
  });

});