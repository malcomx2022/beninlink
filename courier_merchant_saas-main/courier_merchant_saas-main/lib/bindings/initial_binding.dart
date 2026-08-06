import 'package:get/get.dart';
import 'package:we_courier_merchant_app/Controllers/dashboard_controller.dart';
import 'package:we_courier_merchant_app/Controllers/global-controller.dart';

class InitialBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut(() => DashboardController());
    Get.lazyPut(() => GlobalController());
  }
}
