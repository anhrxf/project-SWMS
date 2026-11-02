import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

const apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://127.0.0.1:8000/api/v1',
);

class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({http.Client? client, FlutterSecureStorage? storage})
    : _client = client ?? http.Client(),
      _storage = storage ?? const FlutterSecureStorage();

  static const _tokenKey = 'swms_api_token';
  final http.Client _client;
  final FlutterSecureStorage _storage;

  Uri _uri(String path) =>
      Uri.parse('${apiBaseUrl.replaceFirst(RegExp(r'/+$'), '')}/$path');

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  Future<void> clearToken() => _storage.delete(key: _tokenKey);

  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, dynamic>? body,
    bool authenticated = false,
  }) async {
    final headers = <String, String>{'Accept': 'application/json'};
    if (body != null) headers['Content-Type'] = 'application/json';
    if (authenticated) {
      final token = await readToken();
      if (token == null || token.isEmpty) {
        throw const ApiException('Sesi berakhir. Silakan masuk kembali.');
      }
      headers['Authorization'] = 'Bearer $token';
    }

    try {
      final request = http.Request(method, _uri(path))..headers.addAll(headers);
      if (body != null) request.body = jsonEncode(body);
      final streamed = await _client
          .send(request)
          .timeout(const Duration(seconds: 30));
      final response = await http.Response.fromStream(streamed);
      final decoded = response.body.isEmpty
          ? <String, dynamic>{}
          : jsonDecode(response.body);
      if (decoded is! Map<String, dynamic>) {
        throw ApiException(
          'Respons server tidak dikenali.',
          statusCode: response.statusCode,
        );
      }
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw _apiException(response.statusCode, decoded);
      }
      return decoded;
    } on ApiException {
      rethrow;
    } on http.ClientException {
      throw const ApiException(
        'Tidak dapat terhubung ke server. Pastikan server Laravel aktif dan alamat API benar.',
      );
    } on FormatException {
      throw const ApiException('Server mengirim respons yang tidak valid.');
    }
  }

  ApiException _apiException(int statusCode, Map<String, dynamic> body) {
    final errors = body['errors'] is Map<String, dynamic>
        ? body['errors'] as Map<String, dynamic>
        : null;
    final firstError = errors?.values
        .whereType<List<dynamic>>()
        .expand((messages) => messages)
        .whereType<String>()
        .firstOrNull;

    final message = switch (statusCode) {
      401 =>
        body['message']?.toString() ??
            'Sesi tidak valid. Silakan masuk kembali.',
      403 when body['message']?.toString().contains('verified') == true =>
        'Verifikasi email terlebih dahulu untuk menggunakan laporan.',
      403 => body['message']?.toString() ?? 'Akses ditolak.',
      404 => 'Data tidak ditemukan.',
      422 =>
        firstError ??
            body['message']?.toString() ??
            'Data yang dimasukkan tidak valid.',
      _ => body['message']?.toString() ?? 'Terjadi kesalahan pada server.',
    };

    return ApiException(message, statusCode: statusCode, errors: errors);
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    return _request(
      'POST',
      'auth/register',
      body: {
        'name': name,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
    );
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    return _request(
      'POST',
      'auth/login',
      body: {
        'email': email,
        'password': password,
        'device_name': 'swms_flutter_app',
      },
    );
  }

  Future<Map<String, dynamic>> profile() async =>
      (await _request('GET', 'auth/profile', authenticated: true))['data']
          as Map<String, dynamic>;

  Future<void> resendVerification() async {
    await _request(
      'POST',
      'auth/email/verification-notification',
      authenticated: true,
    );
  }

  Future<void> logout() async {
    try {
      await _request('POST', 'auth/logout', authenticated: true);
    } finally {
      await clearToken();
    }
  }

  Future<List<Map<String, dynamic>>> reports() async {
    final response = await _request('GET', 'reports', authenticated: true);
    final page = response['data'];
    if (page is! Map<String, dynamic> || page['data'] is! List<dynamic>) {
      throw const ApiException(
        'Format daftar laporan dari server tidak valid.',
      );
    }
    return (page['data'] as List<dynamic>)
        .whereType<Map<String, dynamic>>()
        .toList();
  }

  Future<Map<String, dynamic>> classifyImage({
    required Uint8List bytes,
    required String filename,
  }) async {
    final request = http.MultipartRequest('POST', _uri('ai/classify'))
      ..headers['Accept'] = 'application/json'
      ..files.add(
        http.MultipartFile.fromBytes('image', bytes, filename: filename),
      );

    try {
      final response = await http.Response.fromStream(
        await _client.send(request).timeout(const Duration(seconds: 45)),
      );
      final decoded = jsonDecode(response.body);
      if (decoded is! Map<String, dynamic>) {
        throw ApiException(
          'Respons klasifikasi tidak dikenali.',
          statusCode: response.statusCode,
        );
      }
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw _apiException(response.statusCode, decoded);
      }
      return decoded['data'] as Map<String, dynamic>;
    } on ApiException {
      rethrow;
    } on http.ClientException {
      throw const ApiException('Layanan klasifikasi tidak dapat dihubungi.');
    } on FormatException {
      throw const ApiException('Respons klasifikasi tidak valid.');
    }
  }

  Future<Map<String, dynamic>> createReport({
    required String title,
    required String description,
    required String category,
    required Uint8List imageBytes,
    required String filename,
    double? latitude,
    double? longitude,
    String? locationAddress,
  }) async {
    final token = await readToken();
    if (token == null || token.isEmpty) {
      throw const ApiException('Sesi berakhir. Silakan masuk kembali.');
    }

    final request = http.MultipartRequest('POST', _uri('reports'))
      ..headers.addAll({
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      })
      ..fields.addAll({
        'title': title,
        'description': description,
        'waste_category': category,
      })
      ..files.add(
        http.MultipartFile.fromBytes('image', imageBytes, filename: filename),
      );

    if (latitude != null) request.fields['latitude'] = latitude.toString();
    if (longitude != null) request.fields['longitude'] = longitude.toString();
    if (locationAddress?.trim().isNotEmpty == true) {
      request.fields['location_address'] = locationAddress!.trim();
    }
    try {
      final response = await http.Response.fromStream(
        await _client.send(request).timeout(const Duration(seconds: 60)),
      );
      final decoded = jsonDecode(response.body);
      if (decoded is! Map<String, dynamic>) {
        throw ApiException(
          'Respons pembuatan laporan tidak dikenali.',
          statusCode: response.statusCode,
        );
      }
      if (response.statusCode < 200 || response.statusCode >= 300) {
        throw _apiException(response.statusCode, decoded);
      }
      return decoded['data'] as Map<String, dynamic>;
    } on ApiException {
      rethrow;
    } on http.ClientException {
      throw const ApiException(
        'Laporan gagal dikirim karena server tidak dapat dihubungi.',
      );
    } on FormatException {
      throw const ApiException('Respons pembuatan laporan tidak valid.');
    }
  }

  void close() => _client.close();
}

extension _FirstOrNull<T> on Iterable<T> {
  T? get firstOrNull => isEmpty ? null : first;
}
