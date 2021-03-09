<script type="text/javascript">
// Search the text for !! to find the lines that need to be modified to display your map.
<?php
$url='https://www.movebank.org/movebank/service/json-auth?study_id=16880941&individual_local_identifiers[]=Mary&individual_local_identifiers[]=Butterball&individual_local_identifiers[]=Schaumboch&&max_events_per_individual=2000&sensor_type=gps';

// !! Modify the URL above to specify the following (replace XXX with your values):
// study_id=XXX& -- Add the Movebank ID for your study, available in the Study Details.
// individual_local_identifiers=XXX& -- Add the exact Animal IDs for the animals in the study that you want to show on the map; repeat the same text for each animal.
// max_events_per_individual=XXX& -- Change as you like to show more/fewer locations per individual; note that more locations will slow the time it takes the page to load.
// Alternatively, you can set the start and end of the period you want to display:
// timestamp_start=XXX& -- Add this with the Unix timestamp in milliseconds to limit data display to a time range.
// timestamp_end=XXX& -- Add this with the Unix timestamp in milliseconds to limit data display to a time range.
// sensor_type=XXX -- Specify the sensor type to display; options are gps, argos-doppler-shift, solar-geolocator, radio-transmitter, bird-ring, natural-mark

$user='PHPtest';
$password='T3$Tp!p';
// !! Update the two lines above with the Movebank username and password for a Movebank user that has permission to download the data that you are requesting.
// The user must not need to accept the license terms before downloading. More information at https://www.movebank.org/node/43.

$context = stream_context_create(array(
    'http' => array(
        'header'  => "Authorization: Basic " . base64_encode("$user:$password")
    )
));

$data = file_get_contents($url, false, $context);
echo "var data = " . $data . ";\n";
 ?>
</script>
        <script type="text/javascript">
            var colors = ['#FFFF00', '#0099FF', '#FF00FF']; 
            // !! Specify colors for the list of Animal IDs above, or use "null" to use default colors. Make sure # colors matches # animals.
        </script>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css" />
        <style>
            .ui-datepicker-trigger {
                margin-left: 5px;
                margin-top: 8px;
                margin-bottom: -3px;
                background: transparent;
            }
            
            .ui-datepicker {
                font-size: 10pt;
            }
        </style>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js" type="text/javascript"></script>
        // Google now requires an API key to use Google maps without a "for development purposes only" watermark. Create an API key (see https://developers.google.com/maps/documentation/javascript/get-api-key) and add it to the line below replacing MY-API-KEY with your key.
        <script src="https://maps.googleapis.com/maps/api/js?key=MY-API-KEY&v=3.exp&sensor=false&libraries=visualization,geometry"></script>
        <script type="text/javascript">
            var data;

            google.maps.event.addDomListener(window, 'load', initialize);

            function initialize() {
                map = new google.maps.Map(document.getElementById('map-canvas'), {
                    mapTypeId: google.maps.MapTypeId.SATELLITE
                });

                movebankLogo = document.createElement('div');
                movebankLogo.innerHTML = '<a href="https://www.movebank.org"><img src="http://strd.de/logo_movebank_gmap6.png" height=23px/></a>';
                map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(movebankLogo);

                timeDisplay = document.getElementById("time-display-div");
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(timeDisplay);

                for (i = 0; i < data.individuals.length; i++) {
                    data.individuals[i].color = colors[i];
                }
                setBounds();
                createMarkers();
                createPolylines();
                //createPolylines2();

                startDate = null;
                endDate = null;
                for (i = 0; i < data.individuals.length; i++) {
                    for (j = 0; j < data.individuals[i].locations.length; j++) {
                        ts = data.individuals[i].locations[j].timestamp;
                        if (startDate != null) {
                            startDate = Math.min(startDate, ts);
                            endDate = Math.max(endDate, ts);
                        } else {
                            startDate = ts;
                            endDate = ts;
                        }
                    }
                }
                $(function () {
                    maxDate = new Date(endDate);
                    maxDate = new Date(maxDate.getFullYear(), maxDate.getMonth(), maxDate.getDate() + 1);
                    $('#time-display').datepicker({
                        showOn: "button",
                        buttonImageOnly: true,
                        buttonImage: "https://www.google.com/help/hc/images/sites_icon_calendar_small.gif",
                        showButtonPanel: false,
                        minDate: new Date(startDate),
                        maxDate: maxDate,
                        dateFormat: 'yy-mm-dd',
                        onSelect: function (dateText) {
                            date = $(this).datepicker('getDate');
                            t = new Date(date.getTime() + date.getTimezoneOffset() * 60 * 1000).getTime();
                            for (i = 0; i < data.individuals.length; i++)
                                showClosestPointInTime(data.individuals[i], t);
                            $(this).data('datepicker').inline = true;                                      
                        },
                        onClose: function() {
                            $(this).data('datepicker').inline = false;
                        }                        
                    });
                    $('#time-display').datepicker('setDate', new Date(endDate));
                });
                for (i = 0; i < data.individuals.length; i++)
                    showClosestPointInTime(data.individuals[i], endDate);
                document.getElementById('time-display').readOnly = true;
                document.getElementById('time-display').style.width = 75;
            

                function setBounds() {
                    var bounds = new google.maps.LatLngBounds();
                    for (i = 0; i < data.individuals.length; i++) {
                        for (j = 0; j < data.individuals[i].locations.length; j++) {
                            bounds.extend(new google.maps.LatLng(
                                data.individuals[i].locations[j].location_lat,
                                data.individuals[i].locations[j].location_long));
                        }
                    }
                    map.fitBounds(bounds);
                }

                function showClosestPointInSpace(individual, latLng, snapToPoint) {
                    var distCurr = 1000 * 1000 * 1000 * 1000;
                    var indexCurr;
                    for (j = 0; j < individual.locations.length; j++) {
                        latLng0 = new google.maps.LatLng(
                            individual.locations[j].location_lat,
                            individual.locations[j].location_long);
                        dist0 = google.maps.geometry.spherical.computeDistanceBetween(
                            latLng, latLng0);
                        if (dist0 < distCurr) {
                            indexCurr = j;
                            distCurr = dist0;
                        }
                    }
                    if (indexCurr == 0)
                        indexStart = 1;
                    else
                        indexStart = indexCurr - 1;
                    if (indexCurr == individual.locations.length - 1)
                        indexEnd = individual.locations.length - 2;
                    else
                        indexEnd = indexCurr + 1;
                    indexClosest = indexCurr;
                    distCurr = 1000 * 1000 * 1000 * 1000;
                    for (j = indexStart; j <= indexEnd; j += 2) {
                        latLng0 = new google.maps.LatLng(
                            individual.locations[j].location_lat,
                            individual.locations[j].location_long);
                        dist0 = google.maps.geometry.spherical.computeDistanceBetween(
                            latLng, latLng0);
                        if (dist0 < distCurr) {
                            indexCurr = j;
                            distCurr = dist0;
                        }
                    }
                    indexSecondClosest = indexCurr;
                    if (snapToPoint)
                        indexSecondClosest = indexClosest;
                    pointOnLine = getPointClosestToLine(
                        individual.locations[indexClosest].location_long,
                        individual.locations[indexClosest].location_lat,
                        individual.locations[indexSecondClosest].location_long,
                        individual.locations[indexSecondClosest].location_lat,
                        latLng.lng(), latLng.lat());
                    individual.marker.setPosition(new google.maps.LatLng(pointOnLine.y,
                        pointOnLine.x));
                    if (individual.marker.getMap() == null)
                        individual.marker.setMap(map);
                    latLngClosest = new google.maps.LatLng(
                        individual.locations[indexClosest].location_lat,
                        individual.locations[indexClosest].location_long);
                    distClosest = google.maps.geometry.spherical
                        .computeDistanceBetween(latLng, latLngClosest);
                    latLngSecondClosest = new google.maps.LatLng(
                        individual.locations[indexSecondClosest].location_lat,
                        individual.locations[indexSecondClosest].location_long);
                    distSecondClosest = google.maps.geometry.spherical
                        .computeDistanceBetween(latLng, latLngSecondClosest);
                    t = (individual.locations[indexClosest].timestamp * distSecondClosest + individual.locations[indexSecondClosest].timestamp * distClosest) / (distClosest + distSecondClosest);
                    individual.marker.timestamp = t;
                    for (i = 0; i < data.individuals.length; i++)
                        if (data.individuals[i] != individual)
                            showClosestPointInTime(data.individuals[i], t);
                    $('#time-display').datepicker('setDate', new Date(t));
                }

                function getPointClosestToLine(x1, y1, x2, y2, x3, y3) {
                    dx = x2 - x1;
                    dy = y2 - y1;
                    if ((dx == 0) && (dy == 0)) {
                        x0 = x1;
                        y0 = y1;
                    } else {
                        t = ((x3 - x1) * dx + (y3 - y1) * dy) / (dx * dx + dy * dy);
                        t = Math.min(Math.max(0, t), 1);
                        x0 = x1 + t * dx;
                        y0 = y1 + t * dy;
                    }
                    return {
                        x: x0,
                        y: y0
                    };
                }

                function formatTimestamp(timestamp) {
                    var date = new Date(timestamp);
                    var ss = date.getSeconds();
                    var mi = date.getMinutes();
                    var hh = date.getHours();
                    var dd = date.getDate();
                    var mm = date.getMonth() + 1;
                    var yyyy = date.getFullYear();
                    if (ss < 10) {
                        ss = '0' + ss;
                    }
                    if (mi < 10) {
                        mi = '0' + mi;
                    }
                    if (hh < 10) {
                        hh = '0' + hh;
                    }
                    if (dd < 10) {
                        dd = '0' + dd;
                    }
                    if (mm < 10) {
                        mm = '0' + mm;
                    }
                    return yyyy + "-" + mm + "-" + dd + " " + hh + ":" + mi + ":" + ss;
                }

                function formatDate(timestamp) {
                    var date = new Date(timestamp);
                    var dd = date.getDate();
                    var mm = date.getMonth() + 1;
                    var yyyy = date.getFullYear();
                    if (dd < 10) {
                        dd = '0' + dd;
                    }
                    if (mm < 10) {
                        mm = '0' + mm;
                    }
                    return yyyy + "-" + mm + "-" + dd;
                }

                function showClosestPointInTime(individual, t) {
                    var distCurr = 1000 * 1000 * 1000 * 1000;
                    var indexCurr;
                    for (j = 0; j < individual.locations.length; j++) {
                        dist0 = Math.abs(t - individual.locations[j].timestamp);
                        if (dist0 < distCurr) {
                            indexCurr = j;
                            distCurr = dist0;
                        }
                    }
                    if (indexCurr == 0)
                        indexStart = 1;
                    else
                        indexStart = indexCurr - 1;
                    if (indexCurr == individual.locations.length - 1)
                        indexEnd = individual.locations.length - 2;
                    else
                        indexEnd = indexCurr + 1;
                    indexClosest = indexCurr;
                    distClosest = distCurr;
                    distCurr = 1000 * 1000 * 1000;
                    for (j = indexStart; j <= indexEnd; j += 2) {
                        dist0 = Math.abs(t - individual.locations[j].timestamp);
                        if (dist0 < distCurr) {
                            indexCurr = j;
                            distCurr = dist0;
                        }
                    }
                    indexSecondClosest = indexCurr;
                    distSecondClosest = distCurr;
                    x0 = individual.locations[indexClosest].location_long;
                    y0 = individual.locations[indexClosest].location_lat;
                    x1 = individual.locations[indexSecondClosest].location_long;
                    y1 = individual.locations[indexSecondClosest].location_lat;
                    x = (x0 * distSecondClosest + x1 * distClosest) / (distClosest + distSecondClosest);
                    y = (y0 * distSecondClosest + y1 * distClosest) / (distClosest + distSecondClosest);
                    individual.marker.setPosition(new google.maps.LatLng(y, x));
                    individual.marker.timestamp = t;
                    if (individual.marker.getMap() == null)
                        individual.marker.setMap(map);
                    gracePeriod = 1000 * 60 * 60 * 24 * 2;
                    if (t + gracePeriod < individual.locations[0].timestamp || t - gracePeriod > individual.locations[individual.locations.length - 1].timestamp)
                        individual.marker.setMap(null);
                }

                function createMarkers() {
                    for (i = 0; i < data.individuals.length; i++) {
                        data.individuals[i].marker = new google.maps.Marker({
                            clickable: true,
                            draggable: true,
                            icon: {
                                path: google.maps.SymbolPath.CIRCLE,
                                fillOpacity: 1.0,
                                fillColor: data.individuals[i].color,
                                strokeWeight: 0,
                                scale: 5
                            },
                            optimized: true
                        });
                        data.individuals[i].marker.setPosition(new google.maps.LatLng());
                        data.individuals[i].marker.setTitle(data.individuals[i].individual_local_identifier);
                        google.maps.event.addListener(data.individuals[i].marker, "click", (function (individual) {
                            return function () {
                                showInfo(individual);
                            };
                        })(data.individuals[i]));
                        google.maps.event.addListener(data.individuals[i].marker,
                            'drag', (function (individual) {
                            return function (e) {
                                hideInfos();
                                showClosestPointInSpace(individual, e.latLng,
                                    false);
                            };
                        })(data.individuals[i]));
                    }
                }

                function showInfo(individual) {
                    wasOpen = (individual.info != null);
                    hideInfos();
                    if (!wasOpen && individual.marker) {
                        individual.info = new google.maps.InfoWindow();
                        updateInfo(individual);
                        individual.info.open(map, individual.marker);
                    }
                }

                function hideInfos() {
                    for (i = 0; i < data.individuals.length; i++) {
                        if (data.individuals[i].info) {
                            data.individuals[i].info.close();
                            data.individuals[i].info = null;
                        }
                    }
                }

                function updateInfo(individual) {
                    if (individual.info && individual.marker) {
                        tsFirst = individual.locations[0].timestamp;
                        tsLast = individual.locations[individual.locations.length - 1].timestamp;
                        ts = individual.marker.timestamp;
                        if (ts < tsFirst)
                            ts = tsFirst;
                        if (ts > tsLast)
                            ts = tsLast;
                        individual.info.setContent("<b>" + individual.individual_local_identifier + "</b>" + "<br>" +
                            "Recorded at " + formatTimestamp(ts));
                    }
                }

                function createPolylines() {
                    for (i = 0; i < data.individuals.length; i++) {
                        var track = [];
                        for (j = 0; j < data.individuals[i].locations.length; j++) {
                            track[j] = new google.maps.LatLng(
                                data.individuals[i].locations[j].location_lat,
                                data.individuals[i].locations[j].location_long);
                        }
                        icons = [{
                                icon: {
                                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW
                                },
                                offset: 0,
                                repeat: '100px'
                            }
                        ];
                        var polyline = new google.maps.Polyline({
                            path: track,
                            clickable: true,
                            strokeColor: data.individuals[i].color,
                            strokeOpacity: 0.7,
                            strokeWeight: 2
                            //icons : icons
                        });
                        polyline.setMap(map);
                        google.maps.event.addListener(polyline, 'click', (function (
                            individual) {
                            return function (e) {
                                showClosestPointInSpace(individual, e.latLng, true);
                                showInfo(individual);
                            };
                        })(data.individuals[i]));
                    }
                }

                function createPolylines2() {
                    icons = [{
                            icon: {
                                path: google.maps.SymbolPath.CIRCLE
                            },
                            offset: 0
                        }
                    ];
                    for (i = 0; i < data.individuals.length; i++) {
                        for (j = 0; j < data.individuals[i].locations.length - 1; j++) {
                            new google.maps.Polyline({
                                path: [new google.maps.LatLng(
                                        data.individuals[i].locations[j].location_lat,
                                        data.individuals[i].locations[j].location_long),
                                        new google.maps.LatLng(
                                        data.individuals[i].locations[j].location_lat,
                                        data.individuals[i].locations[j].location_long)
                                ],
                                clickable: false,
                                strokeColor: data.individuals[i].color,
                                strokeOpacity: 1,
                                strokeWeight: 2,
                                icons: icons
                            }).setMap(map);
                        }
                    }
                }

            }
        </script>
        <div id="map-canvas" style="width: 600px; height: 700px"></div> <!-- !! can modify to change map dimensions, border etc. -->
        <div id="time-display-div">
            <input type="text" id="time-display"
                style="border: none; font-weight: bold; color: #FFFFFF; background: transparent; margin-top: 7px; width: 0px"></input>
        </div>
