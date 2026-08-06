import 'dart:convert';
import '/Models/payment_log.dart';
import '/services/api-list.dart';
import '/services/server.dart';
import '/services/user-service.dart';
import 'package:get/get.dart';

class PaymentLogController extends GetxController {
  UserService userService = UserService();
  Server server = Server();
  List<Income> incomeList = <Income>[];
  List<Expense> expenseList = <Expense>[];
  bool loader = true;

  @override
  void onInit() {
    getPaymentLog();
    super.onInit();
  }

  getPaymentLog() {
    incomeList = <Income>[];
    expenseList = <Expense>[];
    server.getRequest(endPoint: APIList.paymentLogs).then((response) {
      print(">> payment log${response.statusCode}");
      print(" >> paymnet log${response}");
      if (response != null && response.statusCode == 200) {
        loader = false;
        final jsonResponse = json.decode(response.body);
        var paymentLog = PaymentLog.fromJson(jsonResponse);
        incomeList.addAll(paymentLog.data!.income!);
        expenseList.addAll(paymentLog.data!.expense!);
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      } else {
        loader = false;
        Future.delayed(Duration(milliseconds: 10), () {
          update();
        });
      }
    });
  }
}
