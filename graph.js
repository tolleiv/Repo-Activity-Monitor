(function () {
    var username,
        projectname,
        cache = {},
        time = [];
    var process = function (json) {
        $("#loader").hide();
        $("#chart").html("");
        start = {h: 0, s: 1, v: 200};
        var x = 0,
            r = Raphael("chart", 8500, 1000),
	    background = r.group(),
            shapelayer = r.group(),
            textlayer = r.group(),
            labels = {},
            datelayer = r.group(),
	    releaselayer = r.group(),
            textattr = {"font": '9px "Arial"', "stroke-width": 0, fill: "#fff"},
            pathes = {},
            nmhldr = $("#name")[0],
            nmhldr2 = $("#name2")[0],
            lgnd = $("#legend")[0],
            usrnm = $("#username")[0],
            lgnd2 = $("#legend2")[0],
            usrnm2 = $("#username2")[0],
            plchldr = $("#placeholder")[0],
	    releases = [ [0,0], [1145592546, '4.0 release'], [1173160800, '4.1 release'],
			[1211605200, '4.2 release'], [1259560800, '4.3 release'], 
			[1277182800, '4.4 release'], [1296021600, '4.5 release'],
			[1319518800, '4.6 release']];
        function block() {
            var p, h;
            for (var i in json.authors) {
                var start, end;
                for (var j = json.buckets.length - 1; j >= 0; j--) {
                    var isin = false;
                    for (var k = 0, kk = json.buckets[j].i.length; k < kk; k++) {
                        isin = isin || (json.buckets[j].i[k][0] == i);
                    }
                    if (isin) {
                        end = j;
                        break;
                    }
                }
                for (var j = 0, jj = json.buckets.length; j < jj; j++) {
                    var isin = false;
                    for (var k = 0, kk = json.buckets[j].i.length; k < kk; k++) {
                        isin = isin || (json.buckets[j].i[k][0] == i);
                    };
                    if (isin) {
                        start = j;
                        break;
                    }
                }
                for (var j = start, jj = end; j < jj; j++) {
                    var isin = false;
                    for (var k = 0, kk = json.buckets[j].i.length; k < kk; k++) {
                        isin = isin || (json.buckets[j].i[k][0] == i);
                    }
                    if (!isin) {
                        json.buckets[j].i.push([i, 0]);
                    }
                }
            }
            // Generating points
	    var currentRelease = releases.pop();
            for (var j = 0, jj = json.buckets.length; j < jj; j++) {
                var users = json.buckets[j].i;
                h = 15;
                for (var i = 0, ii = users.length; i < ii; i++) {
                    p = pathes[users[i][0]];
                    if (!p) {
                        p = pathes[users[i][0]] = {f:[], b:[]};
                    }
                    p.f.push([x, h, users[i][1]]);
                    p.b.unshift([x, h += Math.max(Math.round(Math.log(users[i][1]/2) * 6), 0.8)]);
                    h += 2;
                }
                var dt = new Date(json.buckets[j].d * 1000);
                var dtext = /*dt.getDate() + " " +*/ ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"][dt.getMonth()] + " " + dt.getFullYear();
                datelayer.text(x + 25, h + 10, dtext).attr({"font": '9px "Arial"', "stroke-width": 0, fill: "#aaa"});
		if (currentRelease[0] >= parseInt(json.buckets[j].d)) {
		    //background.rect(x, 0 , 50, 800).attr('fill', '#eee').attr('stroke', '#fff');
		    releaselayer.text(x + 25, 10, currentRelease[1]).attr({"font": '10px "Arial"', "stroke-width": 0, fill: "#aaa"});
		    currentRelease = releases.pop();
		}
                x += 100;
            }
            for (var i in pathes) {
                labels[i] = textlayer.group();
                pathes[i].p = shapelayer.path({fill: getColor()});
                pathes[i].p.moveTo(pathes[i].f[0][0], pathes[i].f[0][1]).lineTo(pathes[i].f[0][0] + 50, pathes[i].f[0][1]);
                var th = Math.round(pathes[i].f[0][1] + (pathes[i].b[pathes[i].b.length - 1][1] - pathes[i].f[0][1]) / 2 + 3);
                labels[i].text(pathes[i].f[0][0] + 25, th, pathes[i].f[0][2]).attr(textattr);
                for (var j = 1, jj = pathes[i].f.length; j < jj; j++) {
                    var X = pathes[i].f[j][0],
                        Y = pathes[i].f[j][1];
                    pathes[i].p.cplineTo(X, Y, 20).lineTo(X + 50, Y);
                    th = Math.round(Y + (pathes[i].b[pathes[i].b.length - 1 - j][1] - Y) / 2 + 3);
                    if (th - 9 > Y) {
                        labels[i].text(X + 25, th, pathes[i].f[j][2]).attr(textattr);
                    }
                }
                pathes[i].p.lineTo(pathes[i].b[0][0] + 50, pathes[i].b[0][1]).lineTo(pathes[i].b[0][0], pathes[i].b[0][1]);
                for (var j = 1, jj = pathes[i].b.length; j < jj; j++) {
                    pathes[i].p.cplineTo(pathes[i].b[j][0] + 50, pathes[i].b[j][1], -20).lineTo(pathes[i].b[j][0], pathes[i].b[j][1]);
                }
                pathes[i].p.andClose();
                labels[i].hide();
                var current = null;
                (function (i) {
                    labels[i][0].onclick = pathes[i].p[0].onclick = function () {
                        if (current != null) {
                            labels[current].hide();
                        }
                        current = i;
                        labels[i].show();
                        pathes[i].p.toFront();
			//pathes[i].p.glow();
                        usrnm2.innerHTML = json.authors[i].n + " <em>(" + json.authors[i].c + " points)</em>";
                        lgnd2.style.backgroundColor = pathes[i].p.attr("fill");
                        nmhldr2.className = "";
                        plchldr.className = "hidden";
                    };
                })(i);
            }
        }
	block();
    };
    function hsv2rgb(hue, saturation, value) {
        var red,
            green,
            blue;
        if (value == 0.0) {
            red = 0;
            green = 0;
            blue = 0;
        } else {
            var i = Math.floor(hue * 6),
                f = (hue * 6) - i,
                p = value * (1 - saturation),
                q = value * (1 - (saturation * f)),
                t = value * (1 - (saturation * (1 - f)));
            [
                function () {red = value; green = t; blue = p;},
                function () {red = q; green = value; blue = p;},
                function () {red = p; green = value; blue = t;},
                function () {red = p; green = q; blue = value;},
                function () {red = t; green = p; blue = value;},
                function () {red = value; green = p; blue = q;},
                function () {red = value; green = t; blue = p;},
            ][i]();
        }
        return {r: red, g: green, b: blue};
    }

    function rgb2hsv(red, green, blue) {
        var max = Math.max(red, green, blue),
            min = Math.min(red, green, blue),
            hue,
            saturation,
            value = max;
        if (min == max) {
            hue = 0;
            saturation = 0;
        } else {
            var delta = (max - min);
            saturation = delta / max;
            if (red == max) {
                hue = (green - blue) / delta;
            } else if (green == max) {
                hue = 2 + ((blue - red) / delta);
            } else {
                hue = 4 + ((red - green) / delta);
            }
            hue /= 6;
            if (hue < 0) {
                hue += 1;
            }
            if (hue > 1) {
                hue -= 1;
            }
        }
        return {h: hue, s: saturation, v: value};
    }
    var start = {h: 0, s: 1, v: 200};
    function getColor() {
        var rgb = hsv2rgb(start.h, start.s, start.v);
        start.h += .1;
        if (start.h > 1) {
            start.h = 0;
            start.s -= .2;
            if (start.s <= 0) {
                start = {h: 0, s: 1, v: 200};
            }
        }
        // return "rgb(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ")";
        var r = Math.round(rgb.r).toString(16);
        if (r.length == 1) {
            r = "0" + r;
        }
        var g = Math.round(rgb.g).toString(16);
        if (g.length == 1) {
            g = "0" + g;
        }
        var b = Math.round(rgb.b).toString(16);
        if (b.length == 1) {
            b = "0" + b;
        }
        return "#" + r + g + b;
    }


    var call = function (e) {
        $("#loader").show();
        $.getJSON("json.php", process);
        return false;
    };
    $(window).load(call);
})();
