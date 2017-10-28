<?php

function Curl_Data( $url ) {

    $data = file_get_contents($url);
    $data = json_decode( $data );
    return $data;

}

function LatLang2Address( $latitude, $longitude ) {

    $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude.'&sensor=true';
    $data = Curl_Data( $url );
    $data = (array)$data;
    $data = (array)$data['results'][0];
    return $data['formatted_address'];
}

function Adress2LatLong( $Address ) {
    $lat_long = array();
    $Address = urlencode($Address);
    $request_url = "http://maps.googleapis.com/maps/api/geocode/xml?address=".$Address."&sensor=true";
    $xml = simplexml_load_file($request_url) or die("url not loading");
    $status = $xml->status;
    if ( $status=="OK" ) {

        $Lat = $xml->result->geometry->location->lat;
        $Lon = $xml->result->geometry->location->lng;
        $Lat = (array)$Lat[0];
        $Lon = (array)$Lon[0];

        $lat_long = array( 'latitude' => $Lat[0], 'longitude' => $Lon[0] );

        return $lat_long;
    }
}

function GetDistance( $current_address, $destination_address, $mode ) {

    $query_string = array(
      'origins'       =>  $current_address,
      'destinations'  =>  $destination_address,
      'mode'          =>  $mode,
      'sensor'        =>  'true'
    );

    $url = http_build_query( $query_string );
    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?'.$url;
    $distance_time = array();

    $data = Curl_Data( $url );

    $data = (array)$data;
    $data = $data['rows'][0];
    $data = (array)$data;
    $data = (array)$data['elements'][0];

    $distance_time = array( 'distance' => $data["distance"]->text, 'duration' => $data["duration"]->text );

    return $distance_time;

}

function GetPlaces( $search_point, $duration, $transport_type ) {

    $place_name = array();

    $query_string = array(
      'location'           =>  implode( ',', Adress2LatLong( $search_point ) ),
      'radius'             =>  $duration,
      'mode'               => 'transit',
      'transit_type'       =>  $transport_type,
      'key'                =>  'AIzaSyAFTc--sXgWPiVJcbw12LcZkjMDBMKPJrw'
    );

    $url = http_build_query( $query_string );
    $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?'.$url;
    $data = Curl_Data( $url );
    $data = (array)$data;
    $data = $data['results'];
    foreach ( $data as $place ) {
        if( !empty( $place->vicinity ) ) {
            $place_name[] = $place->vicinity;
        }
    }

    return $place_name;
}

if ( isset( $_POST['search_place'] ) && !empty( $_POST['duration'] ) && !empty( $_POST['transport_type'] ) ) {

    $places = GetPlaces( $_POST['search_place'], $_POST['duration'], $_POST['transport_type'] );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Places Nearby</title>
    <link rel="stylesheet" type="text/css" href="http://getbootstrap.com/dist/css/bootstrap.min.css">
</head>
<body>
    <section>
        <h1 class="text-center">Search Places Nearby</h1>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <form method="post" action="">
                      <div class="form-group">
                        <label for="search_place">Search Places</label>
                        <input type="text" class="form-control" name="search_place" id="search_place" placeholder="Search For Places">
                      </div>
                      <div class="form-group">
                        <label for="duration">Max Travel Time</label>
                        <select name="duration" id="duration" class="form-control">
                            <option value="900">15 minutes</option>
                            <option value="1800">30 minutes</option>
                            <option value="2700">45 minutes</option>
                            <option value="3600">1 hour</option>
                            <option value="4500">1 ¼ hour</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="transport_type">Method of transport</label>
                        <select name="transport_type" id="transport_type" class="form-control" onchange="CalculateDuration();">
                            <option value="driving">Driving</option>
                            <option value="walking" selected>Walking</option>
                            <option value="train">Train</option>
                            <option value="bus">Bus</option>
                        </select>
                      </div>
                      <button type="submit" class="btn btn-success" name="get_distances">Search Places</button>
                    </form>
                </div>
                <?php if( !empty($places) ) : ?>
                    <div class="col-md-6">
                       <?php foreach( $places as $place ) : ?>
                        <div class="alert alert-info"><?php echo $place; ?></div>
                       <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&key=AIzaSyAFTc--sXgWPiVJcbw12LcZkjMDBMKPJrw&libraries=places"></script>
    <script type="text/javascript">

        function init() {
            var input = _id('search_place');
            var autocomplete = new google.maps.places.Autocomplete(input);
        }
        google.maps.event.addDomListener(window, 'load', init);

        function _id ( argument ) {
            return document.getElementById( argument );
        }
        function ChangeValues ( value1, value2, value3, value4, value5 ) {
            _id("duration").options[0].value = value1;
            _id("duration").options[1].value = value2;
            _id("duration").options[2].value = value3;
            _id("duration").options[3].value = value4;
            _id("duration").options[4].value = value5;
        }
        function CalculateDuration () {
            var transport_type = _id("transport_type").value;

            if ( transport_type == 'walking' ) {
                ChangeValues ( '900', '1800', '2700', '3600', '4500' );
            }
            else if ( transport_type == 'driving' ) {
                ChangeValues ( '10000', '20000', '30000', '40000', '50000' );
            }
            else if ( transport_type == 'train' ) {
                ChangeValues ( '6250', '12500', '18750', '25000', '31250' );
            }
            else if ( transport_type == 'bus' ) {
                ChangeValues ( '4500', '9000', '13500', '18000', '22500' );
            }
            else{}
        }
    </script>
</body>
</html>
