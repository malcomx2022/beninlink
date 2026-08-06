import '/Screen/Widgets/constant.dart';
import 'package:flutter/material.dart';
import 'package:nb_utils/nb_utils.dart';
import '../../Controllers/auth-controller.dart';
import '../../utils/image.dart';
import '../../utils/size_config.dart';
import '../Widgets/button_global.dart';
import 'package:get/get.dart';

import '../Widgets/loader.dart';

class SignIn extends StatefulWidget {
  const SignIn({Key? key}) : super(key: key);

  @override
  State<SignIn> createState() => _SignInState();
}

class _SignInState extends State<SignIn> {
  bool isChecked = true;
  final _formKey = GlobalKey<FormState>();

  final TextEditingController _passwordController = TextEditingController();
  // final TextEditingController _emailController    = TextEditingController();
  final TextEditingController driver_idController = TextEditingController();

  AuthController authController = AuthController();

  @override
  Widget build(BuildContext context) {
    SizeConfigCustom sizeConfig = SizeConfigCustom();
    sizeConfig.init(context);
    return GetBuilder<AuthController>(
        init: AuthController(),
        builder: (auth) => SafeArea(
              child: Scaffold(
                backgroundColor: kMainColor,
                body: Stack(children: [
                  Center(
                      child: SingleChildScrollView(
                    child: Column(
                      children: [
                        const SizedBox(
                          height: 40,
                        ),
                        Center(
                          child: Container(
                            margin: EdgeInsets.only(right: 30),
                            height: 120,
                            decoration: BoxDecoration(
                              image: DecorationImage(
                                image: AssetImage(Images.appLogo),
                                fit: BoxFit.contain,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(
                          height: 5.0,
                        ),
                        Text(
                          'please_enter_your_user_information'.tr,
                          style: kTextStyle.copyWith(color: kBgColor, fontSize: 18.0),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(
                          height: 30,
                        ),
                        Container(
                          height: MediaQuery.of(context).size.height,
                          decoration: const BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.only(
                              topLeft: Radius.circular(30.0),
                              topRight: Radius.circular(30.0),
                            ),
                          ),
                          child: Padding(
                              padding: const EdgeInsets.all(10.0),
                              child: Form(
                                key: _formKey,
                                child: Column(
                                  children: [
                                    const SizedBox(height: 20.0),
                                    AppTextField(
                                      showCursor: true,
                                      controller: driver_idController,
                                      validator: (value) {
                                        if (driver_idController.text.isEmpty) {
                                          return "this_field_can_t_be_empty".tr;
                                        }
                                        return null;
                                      },
                                      cursorColor: kTitleColor,
                                      textFieldType: TextFieldType.EMAIL,
                                      decoration: kInputDecoration.copyWith(
                                        labelText: 'Driver Id*'.tr,
                                        labelStyle: kTextStyle.copyWith(color: kTitleColor),
                                        hintText: 'Driver Id',
                                        hintStyle: kTextStyle.copyWith(color: kGreyTextColor),
                                        suffixIcon: const Icon(Icons.mail, color: kGreyTextColor),
                                      ),
                                    ),
                                    const SizedBox(height: 20.0),
                                    AppTextField(
                                      showCursor: true,
                                      cursorColor: kTitleColor,
                                      controller: _passwordController,
                                      validator: (value) {
                                        if (_passwordController.text.isEmpty) {
                                          return "this_field_can_t_be_empty".tr;
                                        }
                                        return null;
                                      },
                                      textFieldType: TextFieldType.PASSWORD,
                                      decoration: kInputDecoration.copyWith(
                                        labelText: 'password'.tr,
                                        labelStyle: kTextStyle.copyWith(color: kTitleColor),
                                        hintText: '********',
                                        hintStyle: kTextStyle.copyWith(color: kGreyTextColor),
                                      ),
                                    ),
                                    const SizedBox(height: 10.0),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.start,
                                      children: [
                                        Checkbox(
                                          activeColor: kMainColor,
                                          shape: RoundedRectangleBorder(
                                            borderRadius: BorderRadius.circular(2.0),
                                          ),
                                          value: isChecked,
                                          onChanged: (val) {
                                            setState(
                                              () {
                                                isChecked = val!;
                                              },
                                            );
                                          },
                                        ),
                                        Text(
                                          'remember_me'.tr,
                                          style: kTextStyle.copyWith(color: kTitleColor, fontWeight: FontWeight.bold),
                                        ),
                                        const Spacer(),
                                      ],
                                    ),
                                    const SizedBox(height: 30.0),
                                    ButtonGlobal(
                                      buttontext: 'sign_in'.tr,
                                      buttonDecoration: BoxDecoration(borderRadius: BorderRadius.circular(30.0), color: kMainColor),
                                      onPressed: () async {
                                        if (_formKey.currentState!.validate()) {
                                          auth.loginOnTap(driver_id: driver_idController.text.toString().trim(), pass: _passwordController.text.toString().trim());
                                        }
                                      },
                                    ),
                                  ],
                                ),
                              )),
                        )
                      ],
                    ),
                  )),
                  auth.loader
                      ? Positioned(
                          child: Container(height: MediaQuery.of(context).size.height, width: MediaQuery.of(context).size.width, color: Colors.white60, child: const Center(child: LoaderCircle())),
                        )
                      : const SizedBox.shrink(),
                ]),
              ),
            ));
  }
}
