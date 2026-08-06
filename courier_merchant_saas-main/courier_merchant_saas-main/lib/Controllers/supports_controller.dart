import 'dart:convert';
import 'package:flutter/material.dart';
import '../Models/support_list.dart';
import '/services/api-list.dart';
import '/services/server.dart';
import '/services/user-service.dart';
import 'package:get/get.dart';

class SupportsController extends GetxController {
  UserService userService = UserService();
  Server server = Server();
  RxBool loader = false.obs;
  var supportList = <Supports>[].obs;

  @override
  void onInit() {
    getSupport();
    super.onInit();
  }

  Future getSupportsList() async {
    loader.value = true;
    Future.delayed(Duration(milliseconds: 10), () {
      update();
    });
    getSupport();
    loader.value = false;
  }

  getSupport() async {
    loader.value = true;
    server.getRequest(endPoint: APIList.supportList).then((response) async {
      if (response != null && response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        print(jsonResponse);
        var supportData = SupportList.fromJson(jsonResponse);
        supportList.addAll(supportData.data!.supports!);
        print(" '''${supportList.length}");
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
        loader.value = false;
      } else {
        print(response.toString() + 'iiuigugy');
        loader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      }
    });
  }

  supportDelete(id) async {
    loader.value = true;
    server.deleteRequest(endPoint: APIList.supportRemoveUrl! + id.toString()).then((response) async {
      final jsonResponse = json.decode(response.body);
      print(jsonResponse);
      if (response != null && response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        loader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
        supportList.clear();
        await getSupportsList();
        Get.back();
        Get.rawSnackbar(message: "${jsonResponse['message']}", backgroundColor: Colors.green, snackPosition: SnackPosition.TOP);
      } else {
        loader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
        Get.rawSnackbar(message: 'Please enter valid input');
      }
    });
  }

  supportPost(String departmentID, service, priority, subject, date, description) {
    loader.value = true;
    Future.delayed(Duration(milliseconds: 10), () {
      update();
    });
    Map body = {
      'department_id': departmentID,
      'service': service,
      'priority': priority,
      'subject': subject,
      'date': date,
      'description': description,
    };
    String jsonBody = json.encode(body);
    print(jsonBody);
    server.postRequestWithToken(endPoint: APIList.supportAdd, body: jsonBody).then((response) async {
      final jsonResponse = json.decode(response.body);
      print(jsonResponse);
      if (response != null && response.statusCode == 200) {
        final jsonResponse = json.decode(response.body);
        print(jsonResponse);
        loader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
        supportList.clear();
        await getSupportsList();
        Get.back();
        Get.rawSnackbar(message: "${jsonResponse['message']}", backgroundColor: Colors.green, snackPosition: SnackPosition.TOP);
      } else if (response != null && response.statusCode == 422) {
        if (jsonResponse['data']['message']['name'] != null) {
          Get.rawSnackbar(message: jsonResponse['data']['message']['name'].toString());
        } else if (jsonResponse['data']['message']['phone'] != null) {
          Get.rawSnackbar(message: jsonResponse['data']['message']['phone'].toString());
        } else if (jsonResponse['data']['message']['details'] != null) {
          Get.rawSnackbar(message: jsonResponse['data']['message']['details'].toString());
        }
        loader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      } else {
        loader.value = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
        Get.rawSnackbar(message: 'Please enter valid input');
      }
    });
  }
}
