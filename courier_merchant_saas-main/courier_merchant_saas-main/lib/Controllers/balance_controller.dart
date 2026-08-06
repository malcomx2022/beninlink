import 'dart:convert';
import '/Models/balance_detials_model.dart';
import '/services/api-list.dart';
import '/services/server.dart';
import '/services/user-service.dart';
import 'package:get/get.dart';

class BalanceController extends GetxController {
  UserService userService = UserService();
  Server server = Server();
  RxBool loader = false.obs;
  BalanceDetailsModel balanceDetails = BalanceDetailsModel();

  @override
  void onInit() {
    getBalanceDetails();
    super.onInit();
  }

  getBalanceDetails() {
    server.getRequest(endPoint: APIList.balanceDetails).then((response) {
      if (response != null && response.statusCode == 200) {
        loader.value = false;
        final jsonResponse = json.decode(response.body);
        print(jsonResponse);
        balanceDetails = BalanceDetailsModel.fromJson(jsonResponse);
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      } else {
        loader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      }
    });
  }
}
