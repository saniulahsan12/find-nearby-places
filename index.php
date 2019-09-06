<?php
$api_key = 'AIzaSyAFTc--sXgWPiVJcbw12LcZkjMDBMKPJrw';
$places = null;

function Adress2LatLong( $Address ) {
    $api_key = 'AIzaSyAFTc--sXgWPiVJcbw12LcZkjMDBMKPJrw';
    $Address = urlencode($Address);
    $request_url = "https://maps.googleapis.com/maps/api/geocode/xml?address=".$Address.'&key=' . $api_key;
    $xml = simplexml_load_file($request_url) or die("url not loading");
    $status = $xml->status;
    if ( $status=="OK" ) {

        $Lat = $xml->result->geometry->location->lat;
        $Lon = $xml->result->geometry->location->lng;
        $Lat = (array)$Lat[0];
        $Lon = (array)$Lon[0];

        $lat_long = array( 'latitude' => $Lat[0], 'longitude' => $Lon[0] );

        return json_encode($lat_long);
    }
}

if ( isset( $_POST['search_place'] ) ) {

    $places = Adress2LatLong( $_POST['search_place'] );
}

?>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
    <style>
        /* Always set the map height explicitly to define the size of the div
         * element that contains the map. */
        #map {
            height: 100%;
        }
        #propertymap .gm-style-iw{
            box-shadow:none;
            color:#515151;
            font-family: "Georgia", "Open Sans", Sans-serif;
            text-align: center;
            width: 100% !important;
            border-radius: 0;
            left: 0 !important;
            top: 20px !important;
        }

        #propertymap .gm-style > div > div > div > div > div > div > div {
            background: none!important;
        }

        .gm-style > div > div > div > div > div > div > div:nth-child(2) {
            box-shadow: none!important;
        }
        #propertymap .gm-style-iw > div > div{
            background: #FFF!important;
        }

        #propertymap .gm-style-iw a{
            text-decoration: none;
        }

        #propertymap .gm-style-iw > div{
            width: 245px !important
        }

        #propertymap .gm-style-iw .img_wrapper {
            height: 150px;
            overflow: hidden;
            width: 100%;
            text-align: center;
            margin: 0px auto;
        }

        #propertymap .gm-style-iw .img_wrapper > img {
            width: 100%;
            height:auto;
        }

        #propertymap .gm-style-iw .property_content_wrap {
            padding: 0px 20px;
        }

        #propertymap .gm-style-iw .property_title{
            min-height: auto;
        }
        /* Optional: Makes the sample page fill the window. */
    </style>

    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-6 col-md-push-3">
                    <form method="post" action="" id="searchFireStations">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search_place" id="search_place" placeholder="Search For Fire Stations" value="<?php echo !empty( $_POST['search_place'] ) ? $_POST['search_place'] : '';?>">
                            <div class="input-group-btn">
                                <button class="btn btn-danger" type="submit">
                                    <i class="glyphicon glyphicon-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <br>
                </div>
            </div>
        </div>
    </section>
    <section>
        <div class="container">
            <div class="row">
                <div class="col-md-12" style="width: 100%; height: 640px;">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=<?php echo $api_key;?>&libraries=places"></script>
    <script>
        let map;
        let service;
        let infowindow;
        let currentLatLong;
        let locationInfo = <?php echo empty($places) ? '{}' : $places; ?>;

        google.maps.event.addDomListener(window, 'load', function(){
            getCurrentLocation();
        });

        function getCurrentLocation(){
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                alert("Geolocation is not supported by this browser.");
                return;
            }
        }

        function showPosition(position) {

            if( Object.keys(locationInfo).length === 0 ) {
                locationInfo.latitude = position.coords.latitude;
                locationInfo.longitude = position.coords.longitude;
            }

            init();
            initMap(false);
        }

        function showError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    alert("User denied the request for Geolocation.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    alert("The request to get user location timed out.");
                    break;
                case error.UNKNOWN_ERROR:
                    alert("An unknown error occurred.");
                    break;
            }
        }

        function init() {
            let input = document.getElementById('search_place');
            let autocomplete = new google.maps.places.Autocomplete(input);
        }

        function initMap(status) {
            currentLatLong = new google.maps.LatLng(locationInfo.latitude, locationInfo.longitude);
            map = new google.maps.Map( document.getElementById('map'), {center: currentLatLong, zoom: 15} );
            infowindow = new google.maps.InfoWindow();

            let request = {
                location: currentLatLong,
                radius: '9000',
                type: ['fire_station']
            };

            service = new google.maps.places.PlacesService(map);

            if(!status)
                service.nearbySearch(request, callback);
        }

        function createMarker(place) {
            let marker = new google.maps.Marker({
                map: map,
                position: place.geometry.location
            });

            let content = "<div class='map_info_wrapper'><div class='img_wrapper'><img alt='' src="+place.icon+"></div>"+
                "<div class='property_content_wrap'>"+
                "<div class='property_title'>"+
                "<span>"+place.name+"</span>"+
                "</div>"+

                "<div class='property_price'>"+
                "<span>Address: "+place.vicinity+"</span>"+
                "</div>"+

                "<div class='property_price'>"+
                "<span>Contact Details: <a target='_blank' href=https://www.google.com/maps/search/?api=1&query=Google&query_place_id="+place.place_id+">Click Here</a></span>"+
                "</div>"+

                "<div class='property_bed_type'>"+
                "<span>Rating: "+ (typeof place.rating === 'undefined' ? 'N/A' : place.rating) +"</span>"+
                "</div>"+
                "</div></div>";

            google.maps.event.addListener(marker, 'click', function() {
                infowindow.setContent(content);
                infowindow.open(map, this);
            });
        }

        function callback(results, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                for (var i = 0; i < results.length; i++) {
                    createMarker(results[i]);
                    console.log(results[i]);

                    if(i === 0){
                        console.log(results[i].geometry.location.lat());
                        currentLatLong = new google.maps.LatLng( results[i].geometry.location.lat(), results[i].geometry.location.lng() );
                        map = new google.maps.Map( document.getElementById('map'), {center: currentLatLong, zoom: 15} );
                        initMap(true);
                    }
                }
            }
        }

        document.onkeydown=function(evt){
            let keyCode = evt ? (evt.which ? evt.which : evt.keyCode) : event.keyCode;
            if(keyCode == 13) {
                document.getElementById("searchFireStations").submit();
            }
        }
    </script>