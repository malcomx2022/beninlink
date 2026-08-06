class PaymentLog {
  PaymentLog({
      bool? success, 
      String? message, 
      PaymentLogData? data,}){
    _success = success;
    _message = message;
    _data = data;
}

  PaymentLog.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    _data = json['data'] != null ? PaymentLogData.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  PaymentLogData? _data;

  bool? get success => _success;
  String? get message => _message;
  PaymentLogData? get data => _data;

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

class PaymentLogData {
  PaymentLogData({
      List<Income>? income, 
      List<Expense>? expense,}){
    _income = income;
    _expense = expense;
}

  PaymentLogData.fromJson(dynamic json) {
    if (json['income'] != null) {
      _income = [];
      json['income'].forEach((v) {
        _income?.add(Income.fromJson(v));
      });
    }
    if (json['expense'] != null) {
      _expense = [];
      json['expense'].forEach((v) {
        _expense?.add(Expense.fromJson(v));
      });
    }
  }
  List<Income>? _income;
  List<Expense>? _expense;

  List<Income>? get income => _income;
  List<Expense>? get expense => _expense;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    if (_income != null) {
      map['income'] = _income?.map((v) => v.toJson()).toList();
    }
    if (_expense != null) {
      map['expense'] = _expense?.map((v) => v.toJson()).toList();
    }
    return map;
  }

}

class Expense {
  Expense({
      int? id,
    dynamic accountHeadId,
    dynamic from,
      dynamic title, 
      dynamic userId, 
      dynamic merchantId,
    dynamic deliveryManId,
      dynamic parcelId,
    dynamic accountId,
      dynamic hubId, 
      dynamic hubUserId, 
      dynamic hubUserAccountId, 
      String? amount, 
      String? date,
    dynamic receipt,
      String? note,}){
    _id = id;
    _accountHeadId = accountHeadId;
    _from = from;
    _title = title;
    _userId = userId;
    _merchantId = merchantId;
    _deliveryManId = deliveryManId;
    _parcelId = parcelId;
    _accountId = accountId;
    _hubId = hubId;
    _hubUserId = hubUserId;
    _hubUserAccountId = hubUserAccountId;
    _amount = amount;
    _date = date;
    _receipt = receipt;
    _note = note;
}

  Expense.fromJson(dynamic json) {
    _id = json['id'];
    _accountHeadId = json['account_head_id'];
    _from = json['from'];
    _title = json['title'];
    _userId = json['user_id'];
    _merchantId = json['merchant_id'];
    _deliveryManId = json['delivery_man_id'];
    _parcelId = json['parcel_id'];
    _accountId = json['account_id'];
    _hubId = json['hub_id'];
    _hubUserId = json['hub_user_id'];
    _hubUserAccountId = json['hub_user_account_id'];
    _amount = json['amount'];
    _date = json['date'];
    _receipt = json['receipt'];
    _note = json['note'];
  }
  int? _id;
  dynamic _accountHeadId;
  dynamic _from;
  dynamic _title;
  dynamic _userId;
  dynamic _merchantId;
  dynamic _deliveryManId;
  dynamic _parcelId;
  dynamic _accountId;
  dynamic _hubId;
  dynamic _hubUserId;
  dynamic _hubUserAccountId;
  String? _amount;
  String? _date;
  dynamic _receipt;
  String? _note;

  int? get id => _id;
  dynamic get accountHeadId => _accountHeadId;
  dynamic get from => _from;
  dynamic get title => _title;
  dynamic get userId => _userId;
  dynamic get merchantId => _merchantId;
  dynamic get deliveryManId => _deliveryManId;
  dynamic get parcelId => _parcelId;
  dynamic get accountId => _accountId;
  dynamic get hubId => _hubId;
  dynamic get hubUserId => _hubUserId;
  dynamic get hubUserAccountId => _hubUserAccountId;
  String? get amount => _amount;
  String? get date => _date;
  dynamic get receipt => _receipt;
  String? get note => _note;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['account_head_id'] = _accountHeadId;
    map['from'] = _from;
    map['title'] = _title;
    map['user_id'] = _userId;
    map['merchant_id'] = _merchantId;
    map['delivery_man_id'] = _deliveryManId;
    map['parcel_id'] = _parcelId;
    map['account_id'] = _accountId;
    map['hub_id'] = _hubId;
    map['hub_user_id'] = _hubUserId;
    map['hub_user_account_id'] = _hubUserAccountId;
    map['amount'] = _amount;
    map['date'] = _date;
    map['receipt'] = _receipt;
    map['note'] = _note;
    return map;
  }

}

class Income {
  Income({
      int? id,
    dynamic accountHeadId,
    dynamic from,
      dynamic title, 
      dynamic userId, 
      dynamic merchantId,
    dynamic deliveryManId,
      dynamic parcelId,
    dynamic accountId,
      dynamic hubId, 
      dynamic hubUserId, 
      dynamic hubUserAccountId, 
      String? amount, 
      String? date,
    dynamic receipt,
      String? note,}){
    _id = id;
    _accountHeadId = accountHeadId;
    _from = from;
    _title = title;
    _userId = userId;
    _merchantId = merchantId;
    _deliveryManId = deliveryManId;
    _parcelId = parcelId;
    _accountId = accountId;
    _hubId = hubId;
    _hubUserId = hubUserId;
    _hubUserAccountId = hubUserAccountId;
    _amount = amount;
    _date = date;
    _receipt = receipt;
    _note = note;
}

  Income.fromJson(dynamic json) {
    _id = json['id'];
    _accountHeadId = json['account_head_id'];
    _from = json['from'];
    _title = json['title'];
    _userId = json['user_id'];
    _merchantId = json['merchant_id'];
    _deliveryManId = json['delivery_man_id'];
    _parcelId = json['parcel_id'];
    _accountId = json['account_id'];
    _hubId = json['hub_id'];
    _hubUserId = json['hub_user_id'];
    _hubUserAccountId = json['hub_user_account_id'];
    _amount = json['amount'];
    _date = json['date'];
    _receipt = json['receipt'];
    _note = json['note'];
  }
  int? _id;
  dynamic _accountHeadId;
  dynamic _from;
  dynamic _title;
  dynamic _userId;
  dynamic _merchantId;
  dynamic _deliveryManId;
  dynamic _parcelId;
  dynamic _accountId;
  dynamic _hubId;
  dynamic _hubUserId;
  dynamic _hubUserAccountId;
  String? _amount;
  String? _date;
  dynamic _receipt;
  String? _note;

  int? get id => _id;
  dynamic get accountHeadId => _accountHeadId;
  dynamic get from => _from;
  dynamic get title => _title;
  dynamic get userId => _userId;
  dynamic get merchantId => _merchantId;
  dynamic get deliveryManId => _deliveryManId;
  dynamic get parcelId => _parcelId;
  dynamic get accountId => _accountId;
  dynamic get hubId => _hubId;
  dynamic get hubUserId => _hubUserId;
  dynamic get hubUserAccountId => _hubUserAccountId;
  String? get amount => _amount;
  String? get date => _date;
  dynamic get receipt => _receipt;
  String? get note => _note;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['account_head_id'] = _accountHeadId;
    map['from'] = _from;
    map['title'] = _title;
    map['user_id'] = _userId;
    map['merchant_id'] = _merchantId;
    map['delivery_man_id'] = _deliveryManId;
    map['parcel_id'] = _parcelId;
    map['account_id'] = _accountId;
    map['hub_id'] = _hubId;
    map['hub_user_id'] = _hubUserId;
    map['hub_user_account_id'] = _hubUserAccountId;
    map['amount'] = _amount;
    map['date'] = _date;
    map['receipt'] = _receipt;
    map['note'] = _note;
    return map;
  }

}