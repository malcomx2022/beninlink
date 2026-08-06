import 'dart:convert';
import '/Models/dashboard_model.dart';
import 'package:flutter/cupertino.dart';
import '/services/api-list.dart';
import '/services/server.dart';
import '/services/user-service.dart';
import 'package:get/get.dart';

class DashboardController extends GetxController {
  UserService userService = UserService();
  Server server = Server();
  List<Delivered> deliveredList = <Delivered>[];
  List<DeliverymanReSchedule> deliverymanReScheduleList = <DeliverymanReSchedule>[];
  List<DeliverymanAssign> deliverymanAssignList = <DeliverymanAssign>[];
  List<ReturnToCourier> returnToCourierList = <ReturnToCourier>[];
  final TextEditingController cashController = TextEditingController();
  final TextEditingController noteController = TextEditingController();

  String? statusID = '9';
  String? userID;
  bool dashboardLoader = true;
  bool commonLoader = false;
  bool loader = false;
  late DataDashboard dashboardData;

  @override
  void onInit() {
    getDashboard();
    super.onInit();
  }

  getDashboard() {
    deliveredList = <Delivered>[];
    deliverymanReScheduleList = <DeliverymanReSchedule>[];
    deliverymanAssignList = <DeliverymanAssign>[];
    returnToCourierList = <ReturnToCourier>[];
    server.getRequest(endPoint: APIList.dashboard).then((response) {
      if (response != null && response.statusCode == 200) {
        dashboardLoader = false;
        final jsonResponse = json.decode(response.body);
        var dashboard = DashboardModel.fromJson(jsonResponse);
        dashboardData = dashboard.data!;
        deliveredList.addAll(dashboard.data!.delivered!);
        deliverymanReScheduleList.addAll(dashboard.data!.deliverymanReSchedule!);
        deliverymanAssignList.addAll(dashboard.data!.deliverymanAssign!);
        returnToCourierList.addAll(dashboard.data!.returnToCourier!);
        update();
      } else {
        dashboardLoader = false;
        update();
      }
    });
  }

  changeStatus(context,parcelId,pickedSignatureImage,image) {
    dashboardLoader = true;
    print(pickedSignatureImage);
    print(image);
    update();
    Map<String, String> body = {
      'parcel_id': parcelId,
      'status_action': statusID!,
      'cash_collection': cashController.text,
      'note': noteController.text,
    };
    print(body);
    try{
      server
          .multipartFileRequest(
          endPoint: APIList.changeStatus,
          body: body,
          filepath: image,
          pickedSignatureImage: pickedSignatureImage,
          type: true)
          .then((response) {
        if (response != null) {
          getDashboard();
          Get.rawSnackbar(
            snackPosition: SnackPosition.TOP,
            title: 'Change Status',
            message: 'Status Successfully',
            backgroundColor:CupertinoColors.activeGreen.withOpacity(.9),
            margin: const EdgeInsets.only(bottom: 20, left: 20, right: 20),
          );
          dashboardLoader = false;
          update();
        } else {
          dashboardLoader = false;
          update();
        }
      });
    }catch(e)
    {
      Get.log(e.toString());
      dashboardLoader = false;
      update();
    }

  }
}
