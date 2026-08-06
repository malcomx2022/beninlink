class DashboardModel {
  DashboardModel({
      bool? success, 
      String? message, 
      DataDashboard? data,}){
    _success = success;
    _message = message;
    _data = data;
}

  DashboardModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    _data = json['data'] != null ? DataDashboard.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  DataDashboard? _data;

  bool? get success => _success;
  String? get message => _message;
  DataDashboard? get data => _data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['success'] = _success;
    map['message'] = _message;
    if (_data != null) {
      map['data'] = _data?.toJson();
    }
    return map;
  }

}

class DataDashboard {
  DataDashboard({
      List<DeliverymanAssign>? deliverymanAssign, 
      List<DeliverymanReSchedule>? deliverymanReSchedule, 
      List<ReturnToCourier>? returnToCourier,
      List<Delivered>? delivered,}){
    _deliverymanAssign = deliverymanAssign;
    _deliverymanReSchedule = deliverymanReSchedule;
    _returnToCourier = returnToCourier;
    _delivered = delivered;
}

  DataDashboard.fromJson(dynamic json) {
    if (json['deliveryman_assign'] != null) {
      _deliverymanAssign = [];
      json['deliveryman_assign'].forEach((v) {
        _deliverymanAssign?.add(DeliverymanAssign.fromJson(v));
      });
    }
    if (json['deliveryman_re_schedule'] != null) {
      _deliverymanReSchedule = [];
      json['deliveryman_re_schedule'].forEach((v) {
        _deliverymanReSchedule?.add(DeliverymanReSchedule.fromJson(v));
      });
    }
    if (json['return_to_courier'] != null) {
      _returnToCourier = [];
      json['return_to_courier'].forEach((v) {
        _returnToCourier?.add(ReturnToCourier.fromJson(v));
      });
    }
    if (json['delivered'] != null) {
      _delivered = [];
      json['delivered'].forEach((v) {
        _delivered?.add(Delivered.fromJson(v));
      });
    }
  }
  List<DeliverymanAssign>? _deliverymanAssign;
  List<DeliverymanReSchedule>? _deliverymanReSchedule;
  List<ReturnToCourier>? _returnToCourier;
  List<Delivered>? _delivered;

  List<DeliverymanAssign>? get deliverymanAssign => _deliverymanAssign;
  List<DeliverymanReSchedule>? get deliverymanReSchedule => _deliverymanReSchedule;
  List<ReturnToCourier>? get returnToCourier => _returnToCourier;
  List<Delivered>? get delivered => _delivered;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    if (_deliverymanAssign != null) {
      map['deliveryman_assign'] = _deliverymanAssign?.map((v) => v.toJson()).toList();
    }
    if (_deliverymanReSchedule != null) {
      map['deliveryman_re_schedule'] = _deliverymanReSchedule?.map((v) => v.toJson()).toList();
    }
    if (_returnToCourier != null) {
      map['return_to_courier'] = _returnToCourier?.map((v) => v.toJson()).toList();
    }
    if (_delivered != null) {
      map['delivered'] = _delivered?.map((v) => v.toJson()).toList();
    }
    return map;
  }

}

class Delivered {
  Delivered({
      int? id, 
      String? trackingId, 
      int? merchantId, 
      String? merchantName, 
      String? merchantUserName, 
      String? merchantUserEmail, 
      String? merchantMobile, 
      String? merchantAddress, 
      String? customerName, 
      String? customerPhone, 
      String? customerAddress, 
      String? totalDeliveryAmount, 
      String? vatAmount, 
      String? currentPayable, 
      String? cashCollection, 
      int? deliveryTypeId, 
      String? deliveryType, 
      int? status, 
      String? statusName, 
      String? pickupDate, 
      String? deliveryDate, 
      String? createdAt, 
      String? updatedAt, 
      String? parcelDate, 
      String? parcelTime,}){
    _id = id;
    _trackingId = trackingId;
    _merchantId = merchantId;
    _merchantName = merchantName;
    _merchantUserName = merchantUserName;
    _merchantUserEmail = merchantUserEmail;
    _merchantMobile = merchantMobile;
    _merchantAddress = merchantAddress;
    _customerName = customerName;
    _customerPhone = customerPhone;
    _customerAddress = customerAddress;
    _totalDeliveryAmount = totalDeliveryAmount;
    _vatAmount = vatAmount;
    _currentPayable = currentPayable;
    _cashCollection = cashCollection;
    _deliveryTypeId = deliveryTypeId;
    _deliveryType = deliveryType;
    _status = status;
    _statusName = statusName;
    _pickupDate = pickupDate;
    _deliveryDate = deliveryDate;
    _createdAt = createdAt;
    _updatedAt = updatedAt;
    _parcelDate = parcelDate;
    _parcelTime = parcelTime;
}

  Delivered.fromJson(dynamic json) {
    _id = json['id'];
    _trackingId = json['tracking_id'];
    _merchantId = int.parse(json['merchant_id'].toString());
    _merchantName = json['merchant_name'];
    _merchantUserName = json['merchant_user_name'];
    _merchantUserEmail = json['merchant_user_email'];
    _merchantMobile = json['merchant_mobile'];
    _merchantAddress = json['merchant_address'];
    _customerName = json['customer_name'];
    _customerPhone = json['customer_phone'];
    _customerAddress = json['customer_address'];
    _totalDeliveryAmount = json['total_delivery_amount'];
    _vatAmount = json['vat_amount'];
    _currentPayable = json['current_payable'];
    _cashCollection = json['cash_collection'];
    _deliveryTypeId = int.parse(json['delivery_type_id'].toString());
    _deliveryType = json['deliveryType'];
    _status = int.parse(json['status'].toString());
    _statusName = json['statusName'];
    _pickupDate = json['pickup_date'];
    _deliveryDate = json['delivery_date'];
    _createdAt = json['created_at'];
    _updatedAt = json['updated_at'];
    _parcelDate = json['parcel_date'];
    _parcelTime = json['parcel_time'];
  }
  int? _id;
  String? _trackingId;
  int? _merchantId;
  String? _merchantName;
  String? _merchantUserName;
  String? _merchantUserEmail;
  String? _merchantMobile;
  String? _merchantAddress;
  String? _customerName;
  String? _customerPhone;
  String? _customerAddress;
  String? _totalDeliveryAmount;
  String? _vatAmount;
  String? _currentPayable;
  String? _cashCollection;
  int? _deliveryTypeId;
  String? _deliveryType;
  int? _status;
  String? _statusName;
  String? _pickupDate;
  String? _deliveryDate;
  String? _createdAt;
  String? _updatedAt;
  String? _parcelDate;
  String? _parcelTime;

  int? get id => _id;
  String? get trackingId => _trackingId;
  int? get merchantId => _merchantId;
  String? get merchantName => _merchantName;
  String? get merchantUserName => _merchantUserName;
  String? get merchantUserEmail => _merchantUserEmail;
  String? get merchantMobile => _merchantMobile;
  String? get merchantAddress => _merchantAddress;
  String? get customerName => _customerName;
  String? get customerPhone => _customerPhone;
  String? get customerAddress => _customerAddress;
  String? get totalDeliveryAmount => _totalDeliveryAmount;
  String? get vatAmount => _vatAmount;
  String? get currentPayable => _currentPayable;
  String? get cashCollection => _cashCollection;
  int? get deliveryTypeId => _deliveryTypeId;
  String? get deliveryType => _deliveryType;
  int? get status => _status;
  String? get statusName => _statusName;
  String? get pickupDate => _pickupDate;
  String? get deliveryDate => _deliveryDate;
  String? get createdAt => _createdAt;
  String? get updatedAt => _updatedAt;
  String? get parcelDate => _parcelDate;
  String? get parcelTime => _parcelTime;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['tracking_id'] = _trackingId;
    map['merchant_id'] = _merchantId;
    map['merchant_name'] = _merchantName;
    map['merchant_user_name'] = _merchantUserName;
    map['merchant_user_email'] = _merchantUserEmail;
    map['merchant_mobile'] = _merchantMobile;
    map['merchant_address'] = _merchantAddress;
    map['customer_name'] = _customerName;
    map['customer_phone'] = _customerPhone;
    map['customer_address'] = _customerAddress;
    map['total_delivery_amount'] = _totalDeliveryAmount;
    map['vat_amount'] = _vatAmount;
    map['current_payable'] = _currentPayable;
    map['cash_collection'] = _cashCollection;
    map['delivery_type_id'] = _deliveryTypeId;
    map['deliveryType'] = _deliveryType;
    map['status'] = _status;
    map['statusName'] = _statusName;
    map['pickup_date'] = _pickupDate;
    map['delivery_date'] = _deliveryDate;
    map['created_at'] = _createdAt;
    map['updated_at'] = _updatedAt;
    map['parcel_date'] = _parcelDate;
    map['parcel_time'] = _parcelTime;
    return map;
  }

}

class DeliverymanReSchedule {
  DeliverymanReSchedule({
      int? id, 
      String? trackingId, 
      int? merchantId, 
      String? merchantName, 
      String? merchantUserName, 
      String? merchantUserEmail, 
      String? merchantMobile, 
      String? merchantAddress, 
      String? customerName, 
      String? customerPhone, 
      String? customerAddress, 
      String? totalDeliveryAmount, 
      String? vatAmount, 
      String? currentPayable, 
      String? cashCollection, 
      int? deliveryTypeId, 
      String? deliveryType, 
      int? status, 
      String? statusName, 
      String? pickupDate, 
      String? deliveryDate, 
      String? createdAt, 
      String? updatedAt, 
      String? parcelDate, 
      String? parcelTime,}){
    _id = id;
    _trackingId = trackingId;
    _merchantId = merchantId;
    _merchantName = merchantName;
    _merchantUserName = merchantUserName;
    _merchantUserEmail = merchantUserEmail;
    _merchantMobile = merchantMobile;
    _merchantAddress = merchantAddress;
    _customerName = customerName;
    _customerPhone = customerPhone;
    _customerAddress = customerAddress;
    _totalDeliveryAmount = totalDeliveryAmount;
    _vatAmount = vatAmount;
    _currentPayable = currentPayable;
    _cashCollection = cashCollection;
    _deliveryTypeId = deliveryTypeId;
    _deliveryType = deliveryType;
    _status = status;
    _statusName = statusName;
    _pickupDate = pickupDate;
    _deliveryDate = deliveryDate;
    _createdAt = createdAt;
    _updatedAt = updatedAt;
    _parcelDate = parcelDate;
    _parcelTime = parcelTime;
}

  DeliverymanReSchedule.fromJson(dynamic json) {
    _id = json['id'];
    _trackingId = json['tracking_id'];
    _merchantId = int.parse(json['merchant_id'].toString());
    _merchantName = json['merchant_name'];
    _merchantUserName = json['merchant_user_name'];
    _merchantUserEmail = json['merchant_user_email'];
    _merchantMobile = json['merchant_mobile'];
    _merchantAddress = json['merchant_address'];
    _customerName = json['customer_name'];
    _customerPhone = json['customer_phone'];
    _customerAddress = json['customer_address'];
    _totalDeliveryAmount = json['total_delivery_amount'];
    _vatAmount = json['vat_amount'];
    _currentPayable = json['current_payable'];
    _cashCollection = json['cash_collection'];
    _deliveryTypeId = int.parse(json['delivery_type_id'].toString());
    _deliveryType = json['deliveryType'];
    _status = int.parse(json['status'].toString());
    _statusName = json['statusName'];
    _pickupDate = json['pickup_date'];
    _deliveryDate = json['delivery_date'];
    _createdAt = json['created_at'];
    _updatedAt = json['updated_at'];
    _parcelDate = json['parcel_date'];
    _parcelTime = json['parcel_time'];
  }
  int? _id;
  String? _trackingId;
  int? _merchantId;
  String? _merchantName;
  String? _merchantUserName;
  String? _merchantUserEmail;
  String? _merchantMobile;
  String? _merchantAddress;
  String? _customerName;
  String? _customerPhone;
  String? _customerAddress;
  String? _totalDeliveryAmount;
  String? _vatAmount;
  String? _currentPayable;
  String? _cashCollection;
  int? _deliveryTypeId;
  String? _deliveryType;
  int? _status;
  String? _statusName;
  String? _pickupDate;
  String? _deliveryDate;
  String? _createdAt;
  String? _updatedAt;
  String? _parcelDate;
  String? _parcelTime;

  int? get id => _id;
  String? get trackingId => _trackingId;
  int? get merchantId => _merchantId;
  String? get merchantName => _merchantName;
  String? get merchantUserName => _merchantUserName;
  String? get merchantUserEmail => _merchantUserEmail;
  String? get merchantMobile => _merchantMobile;
  String? get merchantAddress => _merchantAddress;
  String? get customerName => _customerName;
  String? get customerPhone => _customerPhone;
  String? get customerAddress => _customerAddress;
  String? get totalDeliveryAmount => _totalDeliveryAmount;
  String? get vatAmount => _vatAmount;
  String? get currentPayable => _currentPayable;
  String? get cashCollection => _cashCollection;
  int? get deliveryTypeId => _deliveryTypeId;
  String? get deliveryType => _deliveryType;
  int? get status => _status;
  String? get statusName => _statusName;
  String? get pickupDate => _pickupDate;
  String? get deliveryDate => _deliveryDate;
  String? get createdAt => _createdAt;
  String? get updatedAt => _updatedAt;
  String? get parcelDate => _parcelDate;
  String? get parcelTime => _parcelTime;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['tracking_id'] = _trackingId;
    map['merchant_id'] = _merchantId;
    map['merchant_name'] = _merchantName;
    map['merchant_user_name'] = _merchantUserName;
    map['merchant_user_email'] = _merchantUserEmail;
    map['merchant_mobile'] = _merchantMobile;
    map['merchant_address'] = _merchantAddress;
    map['customer_name'] = _customerName;
    map['customer_phone'] = _customerPhone;
    map['customer_address'] = _customerAddress;
    map['total_delivery_amount'] = _totalDeliveryAmount;
    map['vat_amount'] = _vatAmount;
    map['current_payable'] = _currentPayable;
    map['cash_collection'] = _cashCollection;
    map['delivery_type_id'] = _deliveryTypeId;
    map['deliveryType'] = _deliveryType;
    map['status'] = _status;
    map['statusName'] = _statusName;
    map['pickup_date'] = _pickupDate;
    map['delivery_date'] = _deliveryDate;
    map['created_at'] = _createdAt;
    map['updated_at'] = _updatedAt;
    map['parcel_date'] = _parcelDate;
    map['parcel_time'] = _parcelTime;
    return map;
  }

}

class DeliverymanAssign {
  DeliverymanAssign({
      int? id, 
      String? trackingId, 
      int? merchantId, 
      String? merchantName, 
      String? merchantUserName, 
      String? merchantUserEmail, 
      String? merchantMobile, 
      String? merchantAddress, 
      String? customerName, 
      String? customerPhone, 
      String? customerAddress, 
      String? totalDeliveryAmount, 
      String? vatAmount, 
      String? currentPayable, 
      String? cashCollection, 
      int? deliveryTypeId, 
      String? deliveryType, 
      String? priorityTypeId,
      int? status,
      String? statusName, 
      String? pickupDate, 
      String? deliveryDate, 
      String? createdAt, 
      String? updatedAt, 
      String? parcelDate, 
      String? parcelTime,}){
    _id = id;
    _trackingId = trackingId;
    _merchantId = merchantId;
    _merchantName = merchantName;
    _merchantUserName = merchantUserName;
    _merchantUserEmail = merchantUserEmail;
    _merchantMobile = merchantMobile;
    _merchantAddress = merchantAddress;
    _customerName = customerName;
    _customerPhone = customerPhone;
    _customerAddress = customerAddress;
    _totalDeliveryAmount = totalDeliveryAmount;
    _vatAmount = vatAmount;
    _currentPayable = currentPayable;
    _cashCollection = cashCollection;
    _deliveryTypeId = deliveryTypeId;
    _deliveryType = deliveryType;
    _priorityTypeId = priorityTypeId;
    _status = status;
    _statusName = statusName;
    _pickupDate = pickupDate;
    _deliveryDate = deliveryDate;
    _createdAt = createdAt;
    _updatedAt = updatedAt;
    _parcelDate = parcelDate;
    _parcelTime = parcelTime;
}

  DeliverymanAssign.fromJson(dynamic json) {
    _id = json['id'];
    _trackingId = json['tracking_id'];
    _merchantId = int.parse(json['merchant_id'].toString());
    _merchantName = json['merchant_name'];
    _merchantUserName = json['merchant_user_name'];
    _merchantUserEmail = json['merchant_user_email'];
    _merchantMobile = json['merchant_mobile'];
    _merchantAddress = json['merchant_address'];
    _customerName = json['customer_name'];
    _customerPhone = json['customer_phone'];
    _customerAddress = json['customer_address'];
    _totalDeliveryAmount = json['total_delivery_amount'];
    _vatAmount = json['vat_amount'];
    _currentPayable = json['current_payable'];
    _cashCollection = json['cash_collection'];
    _deliveryTypeId = int.parse(json['delivery_type_id'].toString());
    _deliveryType = json['deliveryType'];
    _priorityTypeId = json['priorityTypeId'];
    _status = int.parse(json['status'].toString());
    _statusName = json['statusName'];
    _pickupDate = json['pickup_date'];
    _deliveryDate = json['delivery_date'];
    _createdAt = json['created_at'];
    _updatedAt = json['updated_at'];
    _parcelDate = json['parcel_date'];
    _parcelTime = json['parcel_time'];
  }
  int? _id;
  String? _trackingId;
  int? _merchantId;
  String? _merchantName;
  String? _merchantUserName;
  String? _merchantUserEmail;
  String? _merchantMobile;
  String? _merchantAddress;
  String? _customerName;
  String? _customerPhone;
  String? _customerAddress;
  String? _totalDeliveryAmount;
  String? _vatAmount;
  String? _currentPayable;
  String? _cashCollection;
  int? _deliveryTypeId;
  String? _deliveryType;
  String? _priorityTypeId;
  int? _status;
  String? _statusName;
  String? _pickupDate;
  String? _deliveryDate;
  String? _createdAt;
  String? _updatedAt;
  String? _parcelDate;
  String? _parcelTime;

  int? get id => _id;
  String? get trackingId => _trackingId;
  int? get merchantId => _merchantId;
  String? get merchantName => _merchantName;
  String? get merchantUserName => _merchantUserName;
  String? get merchantUserEmail => _merchantUserEmail;
  String? get merchantMobile => _merchantMobile;
  String? get merchantAddress => _merchantAddress;
  String? get customerName => _customerName;
  String? get customerPhone => _customerPhone;
  String? get customerAddress => _customerAddress;
  String? get totalDeliveryAmount => _totalDeliveryAmount;
  String? get vatAmount => _vatAmount;
  String? get currentPayable => _currentPayable;
  String? get cashCollection => _cashCollection;
  String? get priorityTypeId => _priorityTypeId;
  int? get deliveryTypeId => _deliveryTypeId;
  String? get deliveryType => _deliveryType;
  int? get status => _status;
  String? get statusName => _statusName;
  String? get pickupDate => _pickupDate;
  String? get deliveryDate => _deliveryDate;
  String? get createdAt => _createdAt;
  String? get updatedAt => _updatedAt;
  String? get parcelDate => _parcelDate;
  String? get parcelTime => _parcelTime;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['tracking_id'] = _trackingId;
    map['merchant_id'] = _merchantId;
    map['merchant_name'] = _merchantName;
    map['merchant_user_name'] = _merchantUserName;
    map['merchant_user_email'] = _merchantUserEmail;
    map['merchant_mobile'] = _merchantMobile;
    map['merchant_address'] = _merchantAddress;
    map['customer_name'] = _customerName;
    map['customer_phone'] = _customerPhone;
    map['customer_address'] = _customerAddress;
    map['total_delivery_amount'] = _totalDeliveryAmount;
    map['vat_amount'] = _vatAmount;
    map['current_payable'] = _currentPayable;
    map['cash_collection'] = _cashCollection;
    map['delivery_type_id'] = _deliveryTypeId;
    map['priorityTypeId'] = priorityTypeId;
    map['deliveryType'] = _deliveryType;
    map['status'] = _status;
    map['statusName'] = _statusName;
    map['pickup_date'] = _pickupDate;
    map['delivery_date'] = _deliveryDate;
    map['created_at'] = _createdAt;
    map['updated_at'] = _updatedAt;
    map['parcel_date'] = _parcelDate;
    map['parcel_time'] = _parcelTime;
    return map;
  }

}

class ReturnToCourier {
  ReturnToCourier({
      int? id,
      String? trackingId,
      int? merchantId,
      String? merchantName,
      String? merchantUserName,
      String? merchantUserEmail,
      String? merchantMobile,
      String? merchantAddress,
      String? customerName,
      String? customerPhone,
      String? customerAddress,
      String? totalDeliveryAmount,
      String? vatAmount,
      String? currentPayable,
      String? cashCollection,
      int? deliveryTypeId,
      String? deliveryType,
      int? status,
      String? statusName,
      String? pickupDate,
      String? deliveryDate,
      String? createdAt,
      String? updatedAt,
      String? parcelDate,
      String? parcelTime,}){
    _id = id;
    _trackingId = trackingId;
    _merchantId = merchantId;
    _merchantName = merchantName;
    _merchantUserName = merchantUserName;
    _merchantUserEmail = merchantUserEmail;
    _merchantMobile = merchantMobile;
    _merchantAddress = merchantAddress;
    _customerName = customerName;
    _customerPhone = customerPhone;
    _customerAddress = customerAddress;
    _totalDeliveryAmount = totalDeliveryAmount;
    _vatAmount = vatAmount;
    _currentPayable = currentPayable;
    _cashCollection = cashCollection;
    _deliveryTypeId = deliveryTypeId;
    _deliveryType = deliveryType;
    _status = status;
    _statusName = statusName;
    _pickupDate = pickupDate;
    _deliveryDate = deliveryDate;
    _createdAt = createdAt;
    _updatedAt = updatedAt;
    _parcelDate = parcelDate;
    _parcelTime = parcelTime;
}

  ReturnToCourier.fromJson(dynamic json) {
    _id = json['id'];
    _trackingId = json['tracking_id'];
    _merchantId = int.parse(json['merchant_id'].toString());
    _merchantName = json['merchant_name'];
    _merchantUserName = json['merchant_user_name'];
    _merchantUserEmail = json['merchant_user_email'];
    _merchantMobile = json['merchant_mobile'];
    _merchantAddress = json['merchant_address'];
    _customerName = json['customer_name'];
    _customerPhone = json['customer_phone'];
    _customerAddress = json['customer_address'];
    _totalDeliveryAmount = json['total_delivery_amount'];
    _vatAmount = json['vat_amount'];
    _currentPayable = json['current_payable'];
    _cashCollection = json['cash_collection'];
    _deliveryTypeId = int.parse(json['delivery_type_id'].toString());
    _deliveryType = json['deliveryType'];
    _status = int.parse(json['status'].toString());
    _statusName = json['statusName'];
    _pickupDate = json['pickup_date'];
    _deliveryDate = json['delivery_date'];
    _createdAt = json['created_at'];
    _updatedAt = json['updated_at'];
    _parcelDate = json['parcel_date'];
    _parcelTime = json['parcel_time'];
  }
  int? _id;
  String? _trackingId;
  int? _merchantId;
  String? _merchantName;
  String? _merchantUserName;
  String? _merchantUserEmail;
  String? _merchantMobile;
  String? _merchantAddress;
  String? _customerName;
  String? _customerPhone;
  String? _customerAddress;
  String? _totalDeliveryAmount;
  String? _vatAmount;
  String? _currentPayable;
  String? _cashCollection;
  int? _deliveryTypeId;
  String? _deliveryType;
  int? _status;
  String? _statusName;
  String? _pickupDate;
  String? _deliveryDate;
  String? _createdAt;
  String? _updatedAt;
  String? _parcelDate;
  String? _parcelTime;

  int? get id => _id;
  String? get trackingId => _trackingId;
  int? get merchantId => _merchantId;
  String? get merchantName => _merchantName;
  String? get merchantUserName => _merchantUserName;
  String? get merchantUserEmail => _merchantUserEmail;
  String? get merchantMobile => _merchantMobile;
  String? get merchantAddress => _merchantAddress;
  String? get customerName => _customerName;
  String? get customerPhone => _customerPhone;
  String? get customerAddress => _customerAddress;
  String? get totalDeliveryAmount => _totalDeliveryAmount;
  String? get vatAmount => _vatAmount;
  String? get currentPayable => _currentPayable;
  String? get cashCollection => _cashCollection;
  int? get deliveryTypeId => _deliveryTypeId;
  String? get deliveryType => _deliveryType;
  int? get status => _status;
  String? get statusName => _statusName;
  String? get pickupDate => _pickupDate;
  String? get deliveryDate => _deliveryDate;
  String? get createdAt => _createdAt;
  String? get updatedAt => _updatedAt;
  String? get parcelDate => _parcelDate;
  String? get parcelTime => _parcelTime;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['tracking_id'] = _trackingId;
    map['merchant_id'] = _merchantId;
    map['merchant_name'] = _merchantName;
    map['merchant_user_name'] = _merchantUserName;
    map['merchant_user_email'] = _merchantUserEmail;
    map['merchant_mobile'] = _merchantMobile;
    map['merchant_address'] = _merchantAddress;
    map['customer_name'] = _customerName;
    map['customer_phone'] = _customerPhone;
    map['customer_address'] = _customerAddress;
    map['total_delivery_amount'] = _totalDeliveryAmount;
    map['vat_amount'] = _vatAmount;
    map['current_payable'] = _currentPayable;
    map['cash_collection'] = _cashCollection;
    map['delivery_type_id'] = _deliveryTypeId;
    map['deliveryType'] = _deliveryType;
    map['status'] = _status;
    map['statusName'] = _statusName;
    map['pickup_date'] = _pickupDate;
    map['delivery_date'] = _deliveryDate;
    map['created_at'] = _createdAt;
    map['updated_at'] = _updatedAt;
    map['parcel_date'] = _parcelDate;
    map['parcel_time'] = _parcelTime;
    return map;
  }

}