import 'dart:convert';
import 'package:we_courier_merchant_app/services/base_client.dart';

import '../Models/dashboard_model.dart';
import '../Models/news_offers_model.dart';
import '/services/api-list.dart';
import '/services/server.dart';
import '/services/user-service.dart';
import 'package:get/get.dart';
import 'package:dio/dio.dart' as dio;

class DashboardController extends GetxController {
  UserService userService = UserService();
  Server server = Server();

  String? userID;

  RxBool dashboardLoader = false.obs;
  RxBool commonloader = false.obs;
  RxBool loader = false.obs;
  var dashboardData = DataDashboard().obs;
  var offersList = <NewsOffers>[].obs;

  @override
  void onInit() {
    getDashboard();
    getOfferList();
    super.onInit();
  }

  getDashboard() async {
    dashboardLoader.value = true;
    dio.Response response = await BaseClient.get(url: APIList.dashboard!);

    if (response.statusCode == 200) {
      var dashboard = DashboardModel.fromJson(response.data);
      dashboardData.value = dashboard.data!;
      Future.delayed(Duration(milliseconds: 10), () {
        update();
      });
      dashboardLoader.value = false;
    } else {
      dashboardLoader.value = false;
      Future.delayed(Duration(milliseconds: 10), () {
        update();
      });
    }
  }

  getOfferList() {
    dashboardLoader.value = false;
    server.getRequest(endPoint: APIList.offerList).then((response) {
      if (response != null && response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        var offers = NewsOffersModel.fromJson(jsonResponse);
        offersList.value = offers.data!.newsOffers!;
        dashboardLoader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      } else {
        dashboardLoader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      }
    });
  }
}
