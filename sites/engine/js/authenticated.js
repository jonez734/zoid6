function updateauthenticated() {
  authdiv = $("div#topbar .authenticated");

  $.ajax({
    async: false,
    type: "GET",
    dataType: "jsonp",
    url: "//engine.zoidtechnologies.com/get-topbar-authenticated?callback=?",
    error: function(jqxjr, error) {
      var err = jqxjr.textStatus + ', ' + error;
      be.logentry("updateauthenticated.error: " + err);
      authdiv.html(err);
    }, /* end 'error' */
    complete: function(payload) {
      authdiv = $("div#topbar .authenticated");
      oldfragment = authdiv;
      fragment = null;

//      be.logentry("notify.complete.200: " + JSON.stringify(notifydiv));
      var a = authdiv.clone();
//      be.logentry("a="+JSON.stringify(a));
      var b = a.wrap("<div>");
//      be.logentry("b="+JSON.stringify(b));
      var c = b.parent();
//      be.logentry("c="+JSON.stringify(c)); // "c="+JSON.stringify(c));
      var d = c.html();
//      be.logentry("d="+JSON.stringify(d));
      oldfragment = d;
      fragment = payload.responseJSON.fragment.trim();

      if (fragment != oldfragment) {
        be.logentry("fragment="+JSON.stringify(fragment));
        be.logentry("oldfragment="+JSON.stringify(oldfragment));
        be.logentry("updateauthenticated.complete.100: fragment != oldfragment. updating");
        authdiv.fadeOut({
          duration: 500,
          complete: function(data) {
            be.logentry("authdiv.fadeout.complete: oldfragment="+JSON.stringify(oldfragment));
            
            authdiv.replaceWith(fragment);
            authdiv.fadeIn(500);
            
            oldfragment = fragment;
          }
        });
      } /* oldfragment != fragment */
    } /* end 'complete' */
  }); /* .ajax */
  return result;
}

$(document).ready(function() {
//  be.addinterval(5000, "update topbar.authenticated", function() {
//    be.updatetopbaritem("authenticated", $("div#topbar .authenticated"), "//engine.zoidtechnologies.com/get-topbar-authenticated?callback=?");
//  });
}); /* end document.ready() */
