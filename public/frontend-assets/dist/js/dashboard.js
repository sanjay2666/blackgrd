$(function() {
    "use strict"; // Start of use strict
    //back to top
    $('body').append('<div id="toTop" class="btn back-top"><span class="ti-arrow-up"></span></div>');
    $(window).on("scroll", function () {
        if ($(this).scrollTop() !== 0) {
            $('#toTop').fadeIn();
        } else {
            $('#toTop').fadeOut();
        }
    });
    $('#toTop').on("click", function () {
        $("html, body").animate({scrollTop: 0}, 600);
        return false;
    });

    //lobipanel controls disabled. Panels will show normally without edit/drag/minimize/close icons.
    //search
    $('a[href="#search"]').on('click', function(event) {
        event.preventDefault();
        $('#search').addClass('open');
        $('#search > form > input[type="search"]').focus();
    });
    $('#search, #search button.close').on('click keyup', function(event) {
        if (event.target == this || event.target.className == 'close' || event.keyCode == 27) {
            $(this).removeClass('open');
        }
    });
    //preloader
    // makes sure the whole site is loaded
         $( window ).on( "load", function() {
             // will first fade out the loading animation
             jQuery("#status").fadeOut();
             // will fade out the whole DIV that covers the website.
             jQuery("#preloader").delay(1000).fadeOut("slow");
         });  
    
});
 
