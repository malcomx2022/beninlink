@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }}
@endsection
@section('maincontent')
    <!-- wrapper  -->
    <div class="container-fluid  dashboard-content">
        <!-- pageheader -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{route('parcel.index')}}" class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <section class="mt-1">
            <div class="row">
                <div class="col-md-12">
                    <input type="hidden" id="lat" name="lat" value="">
                    <input type="hidden" id="long" name="long" value="">
                    <div class="ls-inner-container fixed-map">
                        <!-- Map -->
                        <div id="fixed-map-container">
                            <div id="map" data-map-zoom="9" data-map-scroll="true">
                                <!-- map goes here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- end timeline  -->
    </div>
    <!-- end wrapper  -->
@endsection()
<!-- css  -->
@push('styles')
    <link rel="stylesheet" href="{{static_asset('backend')}}/css/logs.css">
    <link rel="stylesheet" href="{{static_asset('backend/css/map/style.css')}}">
@endpush

@push('scripts')
    <script>
        var urlImage = '{{ static_asset('images/location.png') }}';
        var parcels = @json($mapParcels);
        var parcelsLocations = @json($parcelsLocations);
        var mapLat = '';
        var mapLong = '';

    </script>
    <script type="text/javascript" src="{{ static_asset('backend/js/parcel/map/map.js') }}"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/parcel/map/typed.js') }}"></script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{googleMapSettingKey()}}&libraries=places&callback=initAutocomplete">
    </script>
    <script type="text/javascript" src="{{static_asset('backend/js/parcel/map/infobox.min.js')}}"></script>
    <script type="text/javascript" src="{{static_asset('backend/js/parcel/map/markerclusterer.js')}}"></script>
    <script type="text/javascript" src="{{static_asset('backend/js/parcel/map/locationShowMaps.js')}}"></script>
@endpush


{{-- <!DOCTYPE html>
<html>
  <head>
    <title>Optimize Route</title>
    <script src="https://maps.googleapis.com/maps/api/js?key={{googleMapSettingKey()}}"></script>
    <script>
      function initMap() {
        var directionsService = new google.maps.DirectionsService();
        var directionsRenderer = new google.maps.DirectionsRenderer();
        
        var map = new google.maps.Map(document.getElementById("map"), {
          zoom: 7,
          center: { lat: 23.8037317, lng: 90.3351273 }, // Default to Chicago
        });
        directionsRenderer.setMap(map);

        // Example: Start, waypoints, and destination
        var request = {
        //   origin:new google.maps.LatLng(39.7392, -104.9903),
        //   destination: new google.maps.LatLng(39.7479, -104.9994),
          origin:"Barossa+Valley,SA",
          destination: "McLaren+Vale,SA",
          waypoints: [
            // { location: new google.maps.LatLng(39.7392, -104.9903), stopover: true },
            // { location: new google.maps.LatLng(39.734485, -104.994313), stopover: true },
            // { location: "Barossa+Valley,SA"},
            { location: "Clare,SA"},
            { location: "Connawarra,SA"},
            { location: "Connawarra,SA"},
            // { location: "McLaren+Vale,SA"},
            // { location: "St. Louis, MO", stopover: true },
          ],
        //   optimizedIntermediateWaypointIndex: [ 
        //         0,
        //         1
        //     ],
          optimizeWaypoints: true, // Optimize the order of waypoints
          travelMode: google.maps.TravelMode.DRIVING,
        }; 

        directionsService.route(request, function (result, status) {
          if (status === google.maps.DirectionsStatus.OK) {
            directionsRenderer.setDirections(result);

            var optimizedOrder = result.routes[0].waypoint_order;
            console.log("Optimized waypoint order:", optimizedOrder);

            var optimizedStops = optimizedOrder.map(index => request.waypoints[index].location);
            console.log("Optimized stops:", optimizedStops);
          } else {
            console.error("Directions request failed due to " + status);
          }
        });
      }
    </script>
  </head>
  <body onload="initMap()">
    <div id="map" style="height: 500px; width: 100%;"></div>
  </body>
</html> --}}