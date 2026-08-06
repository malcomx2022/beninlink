import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter_svg/svg.dart';
import 'package:nb_utils/nb_utils.dart';
import 'package:shimmer/shimmer.dart';

import '../../Controllers/global-controller.dart';
import '../../Controllers/profile_controller.dart';
import '../../utils/image.dart';
import '../../utils/style.dart';
import '../Widgets/constant.dart';
import 'package:get/get.dart';

import '../Widgets/profile_card.dart';
import '../Widgets/shimmer/profile_shimmer.dart';
import 'change_language_view.dart';
import 'change_password_view.dart';
import 'edit_profile_view.dart';


class Profile extends StatefulWidget {
  const Profile({Key? key}) : super(key: key);

  @override
  State<Profile> createState() => _ProfileState();
}

class _ProfileState extends State<Profile> {
  ProfileController profileController = Get.put(ProfileController());

  @override
  Widget build(BuildContext context) {

    return Scaffold(
      appBar: AppBar(
        backgroundColor: kMainColor,
        title:  Text(
          'profile'.tr,
          style: kTextStyle.copyWith(
              color: kBgColor,
              fontSize: 18.0,
              fontWeight: FontWeight.bold),
        ),
        titleSpacing: 25,
        elevation: 0.0,
        iconTheme: const IconThemeData(color: kBgColor),

      ),
      body: GetBuilder<ProfileController>(
    init: ProfileController(),
    builder: (profile) => SingleChildScrollView(
      child: Container(child:
      Column(
        children: [
          const SizedBox(height: 20.0),
          Column(
            children: [
              profile.profileLoader ?ProfileShimmer():
              Padding(
                  padding: const EdgeInsets.only(left: 10,right: 10,bottom: 10),
                  child:
                  SizedBox(
                    child: Column(
                      children: [
                        SizedBox(
                          child: Column(children: [
                            const SizedBox(
                              height: 10,
                            ),
                            Stack(
                              clipBehavior: Clip.none,
                              children: [
                                SizedBox(
                                  width: 100.w,
                                  height: 100.h,
                                  child: Center(
                                    child:
                                    CachedNetworkImage(
                                      imageUrl:profile.profileUser.user!.image.toString(),
                                      imageBuilder: (context, imageProvider) =>
                                          CircleAvatar(
                                            radius: 50.0,
                                            backgroundColor: Colors.transparent,
                                            backgroundImage:imageProvider,
                                          ),

                                      placeholder: (context, url) => Shimmer.fromColors(
                                        child: CircleAvatar(radius: 50.0),
                                        baseColor: Colors.grey[300]!,
                                        highlightColor: Colors.grey[400]!,
                                      ),
                                      errorWidget: (context, url, error) =>
                                          Icon(CupertinoIcons.person,size: 50,),
                                    ),
                                  ),
                                ),
                                Positioned(
                                  top: 75,
                                  right: 20,
                                  child: InkWell(
                                    onTap: (() => Get.to( EditProfileView())),
                                    child: SizedBox(
                                      width: 30.w,
                                      height: 30.h,
                                      child: CircleAvatar(
                                        backgroundColor: Colors.white,
                                        child: SizedBox(
                                          width: 30.w,
                                          height: 30.h,
                                          child: CircleAvatar(
                                            backgroundColor: darkGray,
                                            child: Image.asset(
                                              Images.iconEdit,
                                              fit: BoxFit.cover,
                                              height: 22.h,
                                              width: 22.w,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(
                              height: 16,
                            ),
                            Text(
                              profile.profileUser.user!.name.toString(),
                              style: TextStyle(
                                fontFamily: 'Rubik',
                                fontSize: 16,
                                color: fontColor,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            SizedBox(
                              height: 5.h,
                            ),
                            Text(
                              profile.profileUser.user!.email.toString(),
                              style: TextStyle(
                                fontFamily: 'Rubik',
                                fontSize: 14,
                                color: grayColor,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            SizedBox(
                              height: 2.h,
                            ),
                            Text(
                              profile.profileUser.user!.phone.toString(),
                              style: TextStyle(
                                fontFamily: 'Rubik',
                                fontSize: 14,
                                color: grayColor,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            SizedBox(
                              height: 2.h,
                            ),
                            Text(
                              profile.profileUser.user!.address.toString(),
                              style: TextStyle(
                                fontFamily: 'Rubik',
                                fontSize: 14,
                                color: grayColor,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ]),
                        ),
                        SizedBox(height: 30,),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children:  [
                            SizedBox(width: 10),
                            Tracker(
                              trackNo:profile.profileUser.deliveryInProgress.toString(),
                              track: "progress".tr,
                            ),
                            SizedBox(width: 20),
                            Tracker(
                              trackNo: profile.profileUser.completedDelivered.toString(),
                              track: "delivered_completed".tr,
                            ),
                            SizedBox(width: 20),
                            Tracker(
                              trackNo: profile.profileUser.canceledDelivered.toString(),
                              track: "canceled".tr,
                            ),
                            SizedBox(width: 10),
                          ],
                        ),
                        SizedBox(height: 20,),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.start,
                          children: [

                            ProfileCard(
                              page: false,
                              topic: 'balance'.tr,
                              amount: ' ${Get.find<GlobalController>()
                                  .currency!} ${profile.profileUser.currentBalance.toString()}',
                              imgUrl: Images.logo,
                              cardColor: kSecondaryColor,
                            ),
                            const SizedBox(
                              width: 10,
                            ),
                            ProfileCard(
                              page: true,
                              topic: "earning".tr,
                              amount: ' ${Get.find<GlobalController>()
                                  .currency!} ${profile.profileUser.deliverymanEarn.toString()}',
                              imgUrl: Images.logo,
                              cardColor: kMainColor,
                            ),
                            const SizedBox(
                              width: 10,
                            ),
                            ProfileCard(
                              page: true,
                              topic: "total_cod".tr,
                              amount: ' ${Get.find<GlobalController>()
                                  .currency!}${profile.profileUser.totalCod.toString()}',
                              imgUrl: Images.logo,
                              cardColor: titleColor,
                            ),
                          ],
                        ),
                        SizedBox(height: 30,),
                        profileItem(EditProfileView(), Images.iconEditProfile,
                            "edit_profile".tr),
                        profileItem(ChangePasswordView(), Images.iconChangePass,
                            "change_password".tr),
                        profileItem(ChangeLanguageView(), Images.iconChangeLang,
                            "change_language".tr),
                        SizedBox(height: 14,),
                        InkWell(
                          onTap: () {
                            Get.find<GlobalController>().userLogout();
                          },
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  SvgPicture.asset(
                                    Images.iconLogout,
                                    height: 16.h,
                                    width: 16.w,
                                    fit: BoxFit.cover,
                                  ),
                                  SizedBox(
                                    width: 16.w,
                                  ),
                                  Text(
                                    "log_out".tr,
                                    style: fontProfile,
                                  ),
                                ],
                              ),
                              SizedBox(
                                height: 14.h,
                              ),
                            ],
                          ),
                        ),
                        SizedBox(height: 90,),
                      ],
                    ),
                  )

              ),
            ],
          ),
        ],
      ),
      ),
    ),
      ),
    );
  }

  InkWell profileItem(route, icon, textValue) {
    return InkWell(
      onTap: () => Get.to(route),
      child:
      Column(
        children: [
          SizedBox(
            height: 10.h,
          ),
          Row(
            children: [
              SvgPicture.asset(
                icon,
                height: 18.h,
                width: 18.w,
                fit: BoxFit.cover,
              ),
              SizedBox(
                width: 16.h,
              ),
              Text(
                "$textValue",
                style: fontProfile,
              ),
            ],
          ),
          SizedBox(
            height: 14.h,
          ),
          const Divider(
            thickness: 1,
            endIndent: 10,
            color: dividerColor,
          ),
        ],
      ),
    );
  }

}


class Tracker extends StatelessWidget {
  const Tracker({Key? key, required this.trackNo, required this.track})
      : super(key: key);
  final String trackNo;
  final String track;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 15, horizontal: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.4),

            spreadRadius: 3,
            blurRadius: 4,
            offset: const Offset(1, 1), // changes position of shadow
          ),
        ],
      ),
      child: Column(
        children: [
          Text(
            trackNo,
            style: const TextStyle(
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            track,
            style: const TextStyle(
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }
}