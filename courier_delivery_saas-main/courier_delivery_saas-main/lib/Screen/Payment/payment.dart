
import 'package:flutter/material.dart';

import '../../Controllers/global-controller.dart';
import '../../Controllers/payment_log_controller.dart';
import '../Widgets/constant.dart';
import 'package:get/get.dart';

import '../Widgets/shimmer/payment_log_shimmer.dart';


class PaymentPage extends StatefulWidget {
  const PaymentPage({Key? key}) : super(key: key);

  @override
  State<PaymentPage> createState() => _PaymentState();
}

class _PaymentState extends State<PaymentPage> {
  final PaymentLogController paymentLogController = PaymentLogController();
 bool active = true;
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'payment_log'.tr,
          style: kTextStyle.copyWith(
              color: kBgColor, fontWeight: FontWeight.bold, fontSize: 18.0),
        ),

        backgroundColor: kMainColor,
        elevation: 0.0,
        iconTheme: const IconThemeData(color: kBgColor),
      ),
      body:
      GetBuilder<PaymentLogController>(
          init: PaymentLogController(),
          builder: (controller) =>
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 5),
                child: Column(
                  children: [

                    Container(
                      margin: const EdgeInsets.all(10),
                      padding:
                      const EdgeInsets.symmetric(vertical: 2, horizontal: 0),
                      height: 40,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(20),
                        color: const Color(0xffE9F8FF),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          InkWell(
                            onTap: () {
                              active = true;
                              (context as Element).markNeedsBuild();
                            },
                            child: Container(
                              padding:
                              const EdgeInsets.symmetric(horizontal: 35.0),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(20),
                                color: active == true
                                    ? kMainColor
                                    : Colors.transparent,
                              ),
                              child: Center(
                                  child: Text(
                                    'income'.tr,
                                    style: TextStyle(
                                      color: active == true
                                          ? kBgColor
                                          : Colors.grey,
                                      fontSize: 14,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  )),
                            ),
                          ),
                          InkWell(
                            onTap: () {
                              active = false;
                              (context as Element).markNeedsBuild();
                            },
                            child: Container(
                              padding:
                              const EdgeInsets.symmetric(horizontal: 35.0),
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(20),
                                color: active == true
                                    ? Colors.transparent
                                    : kMainColor,
                              ),
                              child: Center(
                                  child: Text(
                                    'expense'.tr,
                                    style: TextStyle(
                                      color: active == true
                                          ? Colors.grey
                                          : kBgColor,
                                      fontSize: 14,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  )),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 10),

                    headline(),
                    const Divider(height: 1, color: Colors.grey),

                    /// data section
                    controller.loader ? PaymentLogShimmer():
                    data(controller,active),
                  ],
                ),
              )),


    );
  }

  /// headline section
  Widget headline() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0, top: 10, left: 10, right: 10),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children:  [
          Text(
            "date".tr,
            style: TextStyle(
              fontSize: 18,
            ),
          ),
          Text(
            "note".tr,
            style: TextStyle(
              fontSize: 18,
            ),
          ),
          Text(
            "amount".tr,
            style: TextStyle(
              fontSize: 18,
            ),
          ),
        ],
      ),
    );
  }

  /// data Section
  Widget data(PaymentLogController controller,t) {
    return
      Expanded(
        child: active ?
        controller.incomeList.length ==0 ? Center(
            child: Text('no_item_found'.tr,style: TextStyle(
              fontSize: 24,
                fontWeight: FontWeight.bold
            ),)):
        ListView.separated(
          itemCount: controller.incomeList.length,
          itemBuilder: (context, index) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 18.0, horizontal: 10),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  SizedBox(
                    width: 88,
                    child:  Text(
                      controller.incomeList[index].date.toString(),
                      style: const TextStyle(
                        fontSize: 14,
                      ),
                    ),
                  ),
                  SizedBox(
                    width: 190,
                    child: controller.incomeList[index].note == '' ?
                    Center(
                      child: Text(
                          'income'.tr,
                          style: const TextStyle(
                            fontSize: 14,
                          )
                      ),
                    ):
                    Text(
                        controller.incomeList[index].note.toString(),
                        style: const TextStyle(
                          fontSize: 14,
                        )
                    ),
                  ),
                  SizedBox(
                    width: 80,
                    child:
                    Text(
                      '${Get.find<GlobalController>()
                          .currency!}${controller.incomeList[index].amount.toString()}',
                      style: const TextStyle(
                        fontSize: 14,
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
          separatorBuilder: (context, index) {
            return Divider(height: 1, color: Colors.grey.withOpacity(.5));
          },
        )
            :
        controller.expenseList.length ==0 ? Center(
            child: Text('no_item_found'.tr,style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold
            ),)):
    ListView.separated(
          itemCount: controller.expenseList.length,
          itemBuilder: (context, index) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: 18.0, horizontal: 10),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  SizedBox(
                    width: 88,
                    child:  Text(
                      controller.expenseList[index].date.toString(),
                      style: const TextStyle(
                        fontSize: 14,
                      ),
                    ),
                  ),
                  SizedBox(
                    width: 190,
                    child: controller.expenseList[index].note == '' ?
                    Center(
                      child: Text(
                          'expense'.tr,
                          style: const TextStyle(
                            fontSize: 14,
                          )
                      ),
                    ):
                    Text(
                        controller.expenseList[index].note.toString(),
                        style: const TextStyle(
                          fontSize: 14,
                        )
                    ),
                  ),
                  SizedBox(
                    width: 80,
                    child:
                    Text(
                      '${Get.find<GlobalController>()
                          .currency!}${controller.expenseList[index].amount.toString()}',
                      style: const TextStyle(
                        fontSize: 14,
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
          separatorBuilder: (context, index) {
            return Divider(height: 1, color: Colors.grey.withOpacity(.5));
          },
        ),
      );
  }
}
