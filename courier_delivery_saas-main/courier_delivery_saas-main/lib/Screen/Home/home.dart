import 'package:bottom_bar/bottom_bar.dart';
import '/Screen/Home/dashboard.dart';
import '/Screen/Payment/payment.dart';
import '/Screen/Profile/profile.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:get/get.dart';

import '../Payment/payment_parcel_history.dart';
import '../Widgets/constant.dart';

class Home extends StatefulWidget {
  const Home({Key? key}) : super(key: key);

  @override
  State<Home> createState() => _HomeState();
}

class _HomeState extends State<Home> {
  bool isSelected = true;
  int _currentPage = 0;

  static const List<Widget> _widgetOptions = <Widget>[
    DashBoard(),
    PaymentPage(),
    PaymentParcelHistoryPage(),
    Profile(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBgColor,
      body: _widgetOptions.elementAt(_currentPage),
      bottomNavigationBar: BottomBar(
        backgroundColor: Color(0xFFf9f9fe),
        items: [
          /// Home
          BottomBarItem(
            icon: const Icon(FontAwesomeIcons.house, size: 16.0),
            title: Text(
              "home".tr,
              style: kTextStyle.copyWith(color: kGreyTextColor),
            ),
            activeColor: kGreyTextColor,
            inactiveColor: kTitleColor,
          ),

          /// History
          BottomBarItem(
            icon: const Icon(FontAwesomeIcons.clipboardList, size: 16.0),
            title: Text(
              "payment_log".tr,
              style: kTextStyle.copyWith(color: kGreyTextColor),
            ),
            activeColor: kGreyTextColor,
            inactiveColor: kTitleColor,
          ),

          /// Orders
          BottomBarItem(
            backgroundColorOpacity: 0.1,
            icon: const Icon(
              FontAwesomeIcons.paperPlane,
              size: 16.0,
            ),
            title: Text(
              "Payment History".tr,
              style: kTextStyle.copyWith(color: kGreyTextColor),
            ),
            activeColor: kGreyTextColor,
            inactiveColor: kTitleColor,
          ),

          /// Profile
           BottomBarItem(
            icon: Icon(FontAwesomeIcons.user, size: 16.0),
            title: Text("profile".tr),
            activeColor: kGreyTextColor,
            inactiveColor: kTitleColor,
          ),
        ],
        onTap: (int index) {
          setState(() => _currentPage = index);
        },
        selectedIndex: _currentPage,
      ),
    );
  }
}
