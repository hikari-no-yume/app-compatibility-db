(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var times = document.getElementsByTagName("time");
        for (var i = 0; i < times.length; i++) {
            // Convert to local timezone, but with ISO 8601 format because
            // the locale date formats suck.
            var date = new Date(times[i].dateTime);
            var local = "";
            local += date.getFullYear().toString();
            local += "-";
            local += ("00" + (date.getMonth() + 1)).substr(-2);
            local += "-";
            local += ("00" + date.getDate()).substr(-2);
            local += " ";
            local += ("00" + date.getHours()).substr(-2);
            local += ":";
            local += ("00" + date.getMinutes()).substr(-2);
            local += ":";
            local += ("00" + date.getSeconds()).substr(-2);
            times[i].textContent = local;
            // Let the user see the original date by hovering over it.
            times[i].title = times[i].dateTime;
        }
    });
}());
