import 'dart:async';

import 'package:we_courier_merchant_app/Controllers/dashboard_controller.dart';

import '/utils/image.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../Controllers/auth-controller.dart';
import '../../Controllers/global-controller.dart';
import '../Authentication/sign_in.dart';
import '../Widgets/constant.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({Key? key}) : super(key: key);

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  final AuthController _authController = AuthController();
  final DashboardController dashboardController = Get.put(DashboardController(), permanent: true);

  @override
  void initState() {
    FirebaseMessaging.instance.getInitialMessage().then((RemoteMessage? message) {});
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {});
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {});
    FirebaseMessaging.instance.getToken().then((token) {
      update(token!);
    });
    Timer(
      const Duration(seconds: 2),
      () => logInCheck(),
    );
    super.initState();
  }

  update(String token) async {
    SharedPreferences storage = await SharedPreferences.getInstance();
    await storage.setString('deviceToken', token);
    print('fcm token===========>');
    print(token);
  }

  logInCheck() async {
    if (Get.find<GlobalController>().isUser) {
      _authController.refreshToken();
    } else {
      Get.off(() => const SignIn());
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kMainColor,
      body: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Center(
            child: Image(
              image: AssetImage(Images.appLogo),
            ),
          ),
        ],
      ),
    );
  }
}
