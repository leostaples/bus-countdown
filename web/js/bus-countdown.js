var BusCountdown = function () {
        var minuteScale = 28;
        var maxMins = 10;
	    var busHeight = 13;

        function displayResult(data, stopId) {
            var stop = $("#stop" + stopId);
            var busList = stop.find("ul.buses");
            var scaleList = stop.find("ul.scale");
            
            stop.find(".updated").html(data.lastUpdated);
            busList.empty();
            busList.css("margin-top", busHeight + "px");
            var maxClashes = 0;
            
            $.each(data.arrivals, function (i, item) {
                var mins = item.estimatedWait;
                mins = mins.slice(0, -4);
                if (mins > maxMins) return false;
                var left = mins * minuteScale;
                var top = 0;
               	var numClashes = 0;
               	
               	$.each(busList.children(), function(index){
               		var currentLeft = parseFloat($(this).css('left')) || 0;
               		var currentTop = parseFloat($(this).css('top')) || 0;
               		if (currentLeft == left && currentTop == top){
               			top = currentTop - busHeight;
               			numClashes++;
               		}
               	});
    
   				maxClashes = numClashes > maxClashes ? numClashes : maxClashes;

               $("<li>").text(item.routeId).css("left", left).css("top", top).appendTo(busList).attr({
                    "class": "bus"
                })
                
            });
            
            var marginTop = parseInt(busList.css("margin-top"), 10);
            marginTop = marginTop + maxClashes * busHeight + "px";
            busList.css("margin-top", marginTop);
            
            if (scaleList.children().length == 0) {
            	for (var i = maxMins; i >= 0; i--) {
            		$("<li/>").text(i).css("left", i * minuteScale).width(minuteScale).appendTo(scaleList)
            	}
            }
        }
        
        function displayMap(data, lat, lng) {                        
            var locations = new Array;
            $.each(data.markers, function (i, stop) {
                locations.push(new MM.Location(stop.lat, stop.lng))
            });
            
            var currentLoc = new MM.Location(lat, lng);
            
            locations.push(currentLoc);
            
            $('<div class="map-wrapper"><div id="map"></div></div>').insertBefore("#wrapper");
            var layer = new MM.StamenTileLayer("toner");
            var map = new MM.Map(document.getElementById("map"),layer,,null);
            map.setExtent(locations);
            
            locations.pop();

            new MM.Follower(map, currentLoc, "X");
            $.each(locations, function (i, location) {
                new MM.Follower(map, location, i + 1)
            });
            displayStops(data)
        }
        
        function displayStops(data) {
            $.each(data.markers, function (i, stop) {
                $("<div/>").appendTo("#wrapper").html("<h4>" + parseInt(i + 1) + ". " + stop.name + " <span> towards " + stop.towards + '</span></h4><div class="subhead">Updated:<span class="updated"></span></div><ul class="buses"></ul><ul class="scale"></ul>').attr({
                    "class": "stop",
                    "id": "stop" + stop.id
                });
                
                BusCountdown.getCountdownData(stop.id)
            })
        }
        
        return {
            getCountdownData: function (stopId) {
                $("#stop" + stopId).addClass("updating");
                $.ajax({
                    url: "/bus/countdown/" + stopId,
                    success: function (json) {
                        $("#stop" + stopId).removeClass("updating");
                        displayResult(json, stopId);
                        setTimeout("BusCountdown.getCountdownData(" + stopId + ")", 30000)
                    }
                })
            },
            getNearestStops: function (lat, lng) {
                $.ajax({
                    url: "/bus/nearest/" + lat + "/" + lng,
                    success: function (json) {
                        displayMap(json, lat, lng)
                    }
                })
            }
        }
    }();