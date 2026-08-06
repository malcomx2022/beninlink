<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,  minimum-scale=0.8, maximum-scale = 0.8, user-scalable = no , shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <!-- Bootstrap CSS -->
    <style>
        #dvMap {
            height: 100%;
        }


        /* Optional: Makes the sample page fill the window. */

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body >
<!-- main wrapper -->
<div id="dvMap"></div>
<!-- Replace the value of the key parameter with your own API key. -->
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{googleMapSettingKey()}}&callback=initMap">
</script>

<script>
    var parcels = @json($mapParcels);
    var urlImageBike = '{{ static_asset('backend/images/default/mapIcon1.png') }}';
    var urlImage = '{{ static_asset('backend/images/default/mapIcon3.png') }}';
    var latitude = '{{ $lat }}';
    var longitude = '{{ $long }}';


    function initMap() {

        var mapOptions = {
            center: new google.maps.LatLng(latitude, longitude),
            zoom: 8,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map(document.getElementById("dvMap"), mapOptions);
        var infoWindow = new google.maps.InfoWindow();
        var lat_lng = new Array();
        var latlngbounds = new google.maps.LatLngBounds();
        var myLatlng = new google.maps.LatLng(latitude, longitude);
        lat_lng.push(myLatlng);

        var marker = new google.maps.Marker({
            position: myLatlng,
            map: map,
            title: 'Delivery',
            icon: urlImageBike,
        });
        // console.log(i)

        latlngbounds.extend(marker.position);
        (function (marker, data) {
            google.maps.event.addListener(marker, "click", function (e) {
                infoWindow.setContent('Delivery');
                infoWindow.open(map, marker);
            });
        })(marker, data);

        for (i = 0; i < parcels.length; i++) {
            var data = parcels[i];
            var myLatlng = new google.maps.LatLng(data.latitude, data.longitude);
            lat_lng.push(myLatlng);
            var marker = new google.maps.Marker({
                position: myLatlng,
                map: map,
                title: data.customer_name,
                icon: urlImage,
            });
            // console.log(i)

            latlngbounds.extend(marker.position);
            (function (marker, data) {
                google.maps.event.addListener(marker, "click", function (e) {
                    infoWindow.setContent(data.customer_name);
                    infoWindow.open(map, marker);
                });
            })(marker, data);
        }
        map.setCenter(latlngbounds.getCenter());
        map.fitBounds(latlngbounds);

        //***********ROUTING****************//


        //Initialize the Direction Service
        var service = new google.maps.DirectionsService();


        //Loop and Draw Path Route between the Points on MAP
        for (var i = 0; i < lat_lng.length; i++) {
            if ((i + 1) < lat_lng.length) {
                var src = lat_lng[i];
                var des = lat_lng[i + 1];
                // path.push(src);

                service.route({
                    origin: src,
                    destination: des,
                    travelMode: google.maps.DirectionsTravelMode.WALKING
                }, function (result, status) {
                    if (status == google.maps.DirectionsStatus.OK) {

                        //Initialize the Path Array
                        var path = new google.maps.MVCArray();
                        //Set the Path Stroke Color
                        var poly = new google.maps.Polyline({
                            map: map,
                            strokeColor: '#4986E7'
                        });
                        poly.setPath(path);
                        for (var i = 0, len = result.routes[0].overview_path.length; i < len; i++) {
                            path.push(result.routes[0].overview_path[i]);
                        }
                    }
                });
            }
        }
    }

</script>


</body>
</html>



