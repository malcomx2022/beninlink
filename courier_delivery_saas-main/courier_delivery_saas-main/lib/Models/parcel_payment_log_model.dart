class ParcelPaymentLogModel {
  ParcelPaymentLogModel({
      bool? success, 
      String? message, 
      PracelPaymentLogData? data,}){
    _success = success;
    _message = message;
    _data = data;
}

  ParcelPaymentLogModel.fromJson(dynamic json) {
    _success = json['success'];
    _message = json['message'];
    _data = json['data'] != null ? PracelPaymentLogData.fromJson(json['data']) : null;
  }
  bool? _success;
  String? _message;
  PracelPaymentLogData? _data;

  bool? get success => _success;
  String? get message => _message;
  PracelPaymentLogData? get data => _data;

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

class PracelPaymentLogData {
  PracelPaymentLogData({
      List<ParcelPaymentLogs>? parcelPaymentLogs,}){
    _parcelPaymentLogs = parcelPaymentLogs;
}

  PracelPaymentLogData.fromJson(dynamic json) {
    if (json['parcel_payment_logs'] != null) {
      _parcelPaymentLogs = [];
      json['parcel_payment_logs'].forEach((v) {
        _parcelPaymentLogs?.add(ParcelPaymentLogs.fromJson(v));
      });
    }
  }
  List<ParcelPaymentLogs>? _parcelPaymentLogs;

  List<ParcelPaymentLogs>? get parcelPaymentLogs => _parcelPaymentLogs;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    if (_parcelPaymentLogs != null) {
      map['parcel_payment_logs'] = _parcelPaymentLogs?.map((v) => v.toJson()).toList();
    }
    return map;
  }

}

class ParcelPaymentLogs {
  ParcelPaymentLogs({
      int? id, 
      int? type, 
      String? amount, 
      String? date, 
      String? note, 
      String? createdAt, 
      String? updatedAt,}){
    _id = id;
    _type = type;
    _amount = amount;
    _date = date;
    _note = note;
    _createdAt = createdAt;
    _updatedAt = updatedAt;
}

  ParcelPaymentLogs.fromJson(dynamic json) {
    _id = json['id'];
    _type = int.parse(json['type'].toString());
    _amount = json['amount'];
    _date = json['date'];
    _note = json['note'];
    _createdAt = json['created_at'];
    _updatedAt = json['updated_at'];
  }
  int? _id;
  int? _type;
  String? _amount;
  String? _date;
  String? _note;
  String? _createdAt;
  String? _updatedAt;

  int? get id => _id;
  int? get type => _type;
  String? get amount => _amount;
  String? get date => _date;
  String? get note => _note;
  String? get createdAt => _createdAt;
  String? get updatedAt => _updatedAt;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['id'] = _id;
    map['type'] = _type;
    map['amount'] = _amount;
    map['date'] = _date;
    map['note'] = _note;
    map['created_at'] = _createdAt;
    map['updated_at'] = _updatedAt;
    return map;
  }

}