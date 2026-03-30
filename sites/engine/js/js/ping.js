// http://stackoverflow.com/questions/4746249/get-user-timezone/10103105#10103105
$(document).ready(function () {
  function postJSON(url, data)
  {
    var options = { url: url, type: "POST", dataType: "json", contentType: "application/json; charset=UTF-8" };
    if (data !== undefined)
    {
      options.data = JSON.stringify(data);
    }
    return $.ajax(options);
  }

  function ping()
  {
    // call home and send the current local time and the timezone offset. php side stores value into session
    var localtime = new Date();
    var localtimestamp = localtime.toISOString();
    var localtimezoneoffset = localtime.getTimezoneOffset();
    var data = { localtimestamp: localtimestamp, localtimezoneoffset: localtimezoneoffset };
    
    var foo = postJSON("//www.zoidtechnologies.com/ping", data);
    
    be = getbbsengine();

    foo.success(function() { be.logentry("ping success"); });
    foo.error(function() { be.logentry("ping error"); });
    foo.complete(function() { be.logentry("ping completed"); });

    return;    
  }
  ping();
});

