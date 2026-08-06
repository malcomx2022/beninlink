class APIList {
  static String? mainUrl = "https://wemaxdevs.xyz/";
  static String? apiUrl = "wemaxdevs.xyz";
  static String? mapGoogleApiKey = "your map key";

  static String? server = "${mainUrl}api/v10/";
  static String? parcelMapUrl = "${mainUrl}deliveryMan/parcel/map/";
  static String? apiCheckKey = "123456rx-ecourier123456";
  static String? apiEndPoint = "/api/v10/";
  static String? login = "${server!}deliveryman/login";
  static String? logout = "${server!}sign-out";
  static String? refreshToken = "${server!}refresh";
  static String? otpLogin = "${server!}otp-login";
  static String? otpResend = "${server!}resend-otp";
  static String? verifyOtp = "${server!}otp-verification";
  static String? device = "${server!}device";
  static String? fcmSubscribe = "${server!}fcm-subscribe";
  static String? fcmUnSubscribe = "${server!}fcm-unsubscribe";
  static String? profile = "${server!}deliveryman/profile";
  static String? generalSettings = "${server!}general-settings";
  static String? profileUpdate = "${server!}profile/update";
  static String? passwordUpdate = "${server!}update-password";
  static String? dashboard = "${server!}deliveryman/dashboard";
  static String? paymentLogs = "${server!}deliveryman/payment-logs";
  static String? paymentParcelLogs = "${server!}deliveryman/parcel-payment-logs";
  static String? changeStatus = "${server!}deliveryman/parcel-status-update";
  static String? updateLocation = "${server!}deliveryman/parcel-location-update";

}
