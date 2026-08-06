import 'dart:convert';
import '../Models/statement_list_model.dart';
import '/services/api-list.dart';
import '/services/server.dart';
import '/services/user-service.dart';
import 'package:get/get.dart';

class StatementController extends GetxController {
  UserService userService = UserService();
  Server server = Server();
  RxBool loader = false.obs;
  List<Statements> statementList = <Statements>[];

  @override
  void onInit() {
    getStatement();
    super.onInit();
  }

  getStatementList() async {
    loader.value = true;
    Future.delayed(Duration(milliseconds: 10), () {
      update();
    });
    getStatement();
  }

  getStatement() {
    server.getRequest(endPoint: APIList.statementList).then((response) {
      if (response != null && response.statusCode == 200) {
        loader.value = false;
        final jsonResponse = json.decode(response.body);
        var statementData = StatementListModel.fromJson(jsonResponse);
        statementList = <Statements>[];
        statementList.addAll(statementData.data!.statements!);
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
