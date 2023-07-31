// https://forum.jquery.com/topic/using-jquery-ui-tooltip-with-dynamic-ajax-content-on-click-instead-of-hover
$(document).ready(function () {
  var i = 1;
  $("a.memberdetail").each(function () {
    $(this).prop("id","memberdetail_"+i);

    var elem = $(this);
    
    var url = elem.attr("rel");
    var memberdetailtip = $('<div id="memberdetailtip_'+i+'" style="display: none;"></div>').appendTo("body");
    $('#'+memberdetailtip.attr('id')).load(url);
    elem.tooltip({
      position: { my: "left center", at: "right+20% center", collision: "flipfit" },
      //Make the items attach to the element (elem is specific $(this) is not since it is attached to the class)
      items: elem,
      track: false,
      show: "fadeIn",
      content: function(callback) {
        var data = memberdetailtip.html();
        callback(data);
      },
      close: function(event, ui) {
//        var me = this;
        ui.tooltip.hover(
          function () {
            $(this).stop(true).fadeTo(400, 1); 
          },
          function () {
            $(this).fadeOut(400, function() {
              $(this).remove();
            });
          }
        ); // tooltip.hover
//        ui.tooltip.on("remove", function() {
//          $(me).tooltip("destroy");
//        });
      }
    }); // tooltip
    i++;
  }); // each
}); // document.ready
