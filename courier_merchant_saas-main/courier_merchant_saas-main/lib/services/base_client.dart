import 'package:dio/dio.dart';
import 'package:get_storage/get_storage.dart';

final storage = GetStorage();

class BaseClient {
  static Future<BaseOptions> getBasseOptions() async {
    final storage = GetStorage();
    BaseOptions options = BaseOptions(
      followRedirects: false,
      validateStatus: (status) {
        return status! < 500;
      },
      headers: {
        "Accept": "application/json",
        'Content-type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'apiKey': '123456rx-ecourier123456',
        'Authorization': 'Bearer ${await storage.read('token')}',
      },
    );

    return options;
  }

  static Future<dynamic> get({required String url, var payload}) async {
    var dio = Dio(await getBasseOptions());

    var response = await dio.get(url, queryParameters: payload);
    print(storage.read('token'));
    print('\nURL: $url');
    print('Request Body: $response\n');
    return response;
  }

  static Future<dynamic> post({url, payload}) async {
    var dio = Dio(await getBasseOptions());
    var response = await dio.post(url, data: payload);
    print(storage.read('token'));
    print('\nURL: $url');
    print('Request Body: $response\n');
    return response;
  }

  static Future<dynamic> postWithQP({url, queryPayload}) async {
    var dio = Dio(await getBasseOptions());
    var response = await dio.post(url, queryParameters: queryPayload);
    return response;
  }

  static Future<dynamic> put({url, payload}) async {
    var dio = Dio(await getBasseOptions());
    var response = await dio.put(url, data: payload);
    return response;
  }

  static Future<dynamic> delete({url}) async {
    var dio = Dio(await getBasseOptions());
    var response = await dio.delete(url);
    return response;
  }
}
