import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:get/get.dart';

import '../../Controllers/global-controller.dart';
import '../../services/api-list.dart';
import '../Widgets/constant.dart';

class ParcelMapShow extends StatefulWidget {
  final dynamic lat;
  final dynamic long;
  ParcelMapShow(this.lat, this.long);
  @override
  createState() => _WebViewContainerState(lat, long);
}

class _WebViewContainerState extends State<ParcelMapShow> {
  dynamic lat;
  dynamic long;
  final _key = UniqueKey();
  _WebViewContainerState(lat, long);
  late WebViewController _controller;
  late Position position;
  String currentLong = "", currentLat = "";
  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    webViewShow(widget.lat, widget.long);
  }

  getLocation() async {
    position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
    setState(() {
      webViewShow(position.latitude.toString(), position.longitude.toString());
    });
  }

  webViewShow(lat, long) {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            // print the loading progress to the console
            // you can use this value to show a progress bar if you want
            debugPrint("Loading: $progress%");
          },
          onPageStarted: (String url) {},
          onPageFinished: (String url) {},
          onWebResourceError: (WebResourceError error) {},
          onNavigationRequest: (NavigationRequest request) {
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse("${APIList.parcelMapUrl}${Get.find<GlobalController>().userId}/${lat}/${long}/7"));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(iconTheme: const IconThemeData(color: kBgColor), titleSpacing: 0, backgroundColor: kMainColor, elevation: 0.0, title: const Text('Parcel Map'), actions: <Widget>[
        Padding(
            padding: EdgeInsets.only(right: 20.0),
            child: GestureDetector(
              onTap: () {
                getLocation();
              },
              child: Icon(
                Icons.refresh,
                size: 30.0,
              ),
            )),
      ]),
      body: SizedBox(
          width: double.infinity,
          // the most important part of this example
          child: WebViewWidget(
            controller: _controller,
          )),
    );
  }
}
