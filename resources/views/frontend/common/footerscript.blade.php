      <!-- Start Core Plugins
         =====================================================================-->
      <!-- jQuery -->
      <script src="{{ asset('frontend-assets/plugins/jQuery/jquery-1.12.4.min.js') }}" type="text/javascript"></script>
      <!-- jquery-ui --> 
      <script src="{{ asset('frontend-assets/plugins/jquery-ui-1.12.1/jquery-ui.min.js') }}" type="text/javascript"></script>
      <!-- Bootstrap -->
      <script src="{{ asset('frontend-assets/bootstrap/js/bootstrap.min.js') }}" type="text/javascript"></script>
      <!-- lobipanel -->
      <script src="{{ asset('frontend-assets/plugins/lobipanel/lobipanel.min.js') }}" type="text/javascript"></script>
      <!-- Pace js -->
      <script src="{{ asset('frontend-assets/plugins/pace/pace.min.js') }}" type="text/javascript"></script>
      <!-- SlimScroll -->
      <script src="{{ asset('frontend-assets/plugins/slimScroll/jquery.slimscroll.min.js') }}" type="text/javascript">    </script>
      <!-- FastClick -->
      <script src="{{ asset('frontend-assets/plugins/fastclick/fastclick.min.js') }}" type="text/javascript"></script>
      <!-- CRMadmin frame -->
      <script src="{{ asset('frontend-assets/dist/js/custom.js') }}" type="text/javascript"></script>
      <!-- End Core Plugins
         =====================================================================-->
      <!-- Start Page Lavel Plugins
         =====================================================================-->
      <!-- ChartJs JavaScript -->
      <script src="{{ asset('frontend-assets/plugins/chartJs/Chart.min.js') }}" type="text/javascript"></script>
      <!-- Counter js -->
      <script src="{{ asset('frontend-assets/plugins/counterup/waypoints.js') }}" type="text/javascript"></script>
      <script src="{{ asset('frontend-assets/plugins/counterup/jquery.counterup.min.js') }}" type="text/javascript"></script>
      <!-- Monthly js -->
      <script src="{{ asset('frontend-assets/plugins/monthly/monthly.js') }}" type="text/javascript"></script>
      <!-- End Page Lavel Plugins
         =====================================================================-->
      <!-- Start Theme label Script
         =====================================================================-->
      <!-- Dashboard js -->
      <script src="{{ asset('frontend-assets/dist/js/dashboard.js') }}" type="text/javascript"></script>
      <!-- End Theme label Script
         =====================================================================-->
      <script>
         function dash() {
         // single bar chart
         var ctx = document.getElementById("singelBarChart");
         var myChart = new Chart(ctx, {
         type: 'bar',
         data: {
         labels: ["Sun", "Mon", "Tu", "Wed", "Th", "Fri", "Sat"],
         datasets: [
         {
         label: "My First dataset",
         data: [40, 55, 75, 81, 56, 55, 40],
         borderColor: "rgba(0, 150, 136, 0.8)",
         width: "1",
         borderWidth: "0",
         backgroundColor: "rgba(0, 150, 136, 0.8)"
         }
         ]
         },
         options: {
         scales: {
         yAxes: [{
             ticks: {
                 beginAtZero: true
             }
         }]
         }
         }
         });
               //monthly calender
               $('#m_calendar').monthly({
                 mode: 'event',
                 //jsonUrl: 'events.json',
                 //dataType: 'json'
                 xmlUrl: '{{ asset('frontend-assets/plugins/monthly/events.xml') }}'
             });
         
         //bar chart
         var ctx = document.getElementById("barChart");
         var myChart = new Chart(ctx, {
         type: 'bar',
         data: {
         labels: ["January", "February", "March", "April", "May", "June", "July", "august", "september","october", "Nobemver", "December"],
         datasets: [
         {
         label: "My First dataset",
         data: [65, 59, 80, 81, 56, 55, 40, 65, 59, 80, 81, 56],
         borderColor: "rgba(0, 150, 136, 0.8)",
         width: "1",
         borderWidth: "0",
         backgroundColor: "rgba(0, 150, 136, 0.8)"
         },
         {
         label: "My Second dataset",
         data: [28, 48, 40, 19, 86, 27, 90, 28, 48, 40, 19, 86],
         borderColor: "rgba(51, 51, 51, 0.55)",
         width: "1",
         borderWidth: "0",
         backgroundColor: "rgba(51, 51, 51, 0.55)"
         }
         ]
         },
         options: {
         scales: {
         yAxes: [{
             ticks: {
                 beginAtZero: true
             }
         }]
         }
         }
         });
             //counter
             $('.count-number').counterUp({
                 delay: 10,
                 time: 5000
             });
         }
         if (document.getElementById("singelBarChart") && document.getElementById("barChart") && document.getElementById("m_calendar")) {
             dash();
         }
      </script>

<script type="text/javascript">
   $(document).ready(function() {
      $("#status").hide();
      $("#preloader").hide();
   });

   $(window).on("load", function() {
      $("#status").fadeOut();
      $("#preloader").delay(350).fadeOut("slow");
   });

   $("#frontendMobileMenuBtn").on("click", function() {
      $("body").addClass("frontend-mobile-menu-open");
   });

   $("#frontendMobileMenuClose, #frontendMobileMenuBg").on("click", function() {
      $("body").removeClass("frontend-mobile-menu-open");
   });

   $(".loomexa-datepicker").datepicker({
      dateFormat: "dd-mm-yy",
      changeMonth: true,
      changeYear: true,
      autoclose: true
   });

   $(".frontend-mobile-submenu-link").on("click", function() {
      $(this).parent("li").toggleClass("open");
   });

   function updateLoomexaFileName(fileInput) {
      var fileName = fileInput.files && fileInput.files.length ? fileInput.files[0].name : "No file selected";
      var wrapper = $(fileInput).closest(".loomexa-file-upload");

      wrapper.toggleClass("has-file", fileName !== "No file selected");
      wrapper.find(".loomexa-file-name").text(fileName);
   }

   function initLoomexaFileUploads(context) {
      $(context || document).find('input[type="file"]').each(function() {
         var input = $(this);

         if (input.closest(".loomexa-file-upload").length || input.data("loomexaFileReady")) {
            return;
         }

         var inputId = input.attr("id");
         if (!inputId) {
            inputId = "loomexa_file_" + Math.random().toString(36).slice(2);
            input.attr("id", inputId);
         }

         input.data("loomexaFileReady", true);
         input.removeClass("form-control input-sm");
         input.wrap('<label class="loomexa-file-upload" for="' + inputId + '"></label>');
         input.after(
            '<span class="loomexa-file-icon"><i class="fa fa-paperclip"></i></span>' +
            '<span class="loomexa-file-main">' +
               '<span class="loomexa-file-action">Choose file</span>' +
               '<span class="loomexa-file-name">No file selected</span>' +
            '</span>'
         );

         updateLoomexaFileName(this);
      });
   }

   $(document).on("change", '.loomexa-file-upload input[type="file"]', function() {
      updateLoomexaFileName(this);
   });

   initLoomexaFileUploads(document);
</script>





