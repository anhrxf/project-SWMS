import 'dart:async';
import 'dart:typed_data';

import 'package:camera/camera.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';

import 'api_client.dart';
import 'waste_report.dart';

// Palette matching Tailwind CSS configuration of SWMS Web Platform
const _slate900 = Color(0xFF0F172A);
const _slate800 = Color(0xFF1E293B);
const _slate700 = Color(0xFF334155);
const _slate600 = Color(0xFF475569);
const _slate500 = Color(0xFF64748B);
const _slate400 = Color(0xFF94A3B8);
const _slate200 = Color(0xFFE2E8F0);
const _slate100 = Color(0xFFF1F5F9);
const _slate50 = Color(0xFFF8FAFC);

const _emerald700 = Color(0xFF047857);
const _emerald600 = Color(0xFF059669);
const _emerald500 = Color(0xFF10B981);
const _emerald100 = Color(0xFFD1FAE5);
const _emerald50 = Color(0xFFECFDF5);

const _amber700 = Color(0xFFB45309);
const _amber500 = Color(0xFFF59E0B);
const _amber100 = Color(0xFFFEF3C7);
const _amber50 = Color(0xFFFFFBEB);

const _sky700 = Color(0xFF0369A1);
const _sky500 = Color(0xFF0EA5E9);
const _sky100 = Color(0xFFE0F2FE);
const _sky50 = Color(0xFFF0F9FF);

const _blue700 = Color(0xFF1D4ED8);
const _blue50 = Color(0xFFEFF6FF);

const _rose700 = Color(0xFFBE123C);
const _rose500 = Color(0xFFF43F5E);
const _rose100 = Color(0xFFFFE4E6);
const _rose50 = Color(0xFFFFF1F2);

void main() {
  runApp(const SwmsApp());
}

class SwmsApp extends StatefulWidget {
  const SwmsApp({super.key});

  @override
  State<SwmsApp> createState() => _SwmsAppState();
}

class _SwmsAppState extends State<SwmsApp> {
  final _api = ApiClient();
  Map<String, dynamic>? _user;
  String? _verificationUrl;
  bool _starting = true;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    try {
      if (await _api.readToken() != null) {
        _user = await _api.profile();
      }
    } on ApiException {
      await _api.clearToken();
    } catch (error, stackTrace) {
      debugPrint('Gagal memulihkan sesi SWMS: $error\n$stackTrace');
      await _api.clearToken();
    } finally {
      if (mounted) setState(() => _starting = false);
    }
  }

  Future<void> _onAuthenticated({
    required String token,
    String? verificationUrl,
  }) async {
    await _api.saveToken(token);
    final user = await _api.profile();
    if (!mounted) return;
    setState(() {
      _user = user;
      _verificationUrl = verificationUrl;
    });
  }

  Future<void> _logout() async {
    try {
      await _api.logout();
    } catch (error, stackTrace) {
      debugPrint('Gagal mencabut token saat logout: $error\n$stackTrace');
    }
    if (!mounted) return;
    setState(() {
      _user = null;
      _verificationUrl = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SWMS Warga',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: _slate50,
        colorScheme: ColorScheme.fromSeed(
          seedColor: _emerald600,
          primary: _emerald600,
          surface: Colors.white,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.white,
          foregroundColor: _slate900,
          centerTitle: false,
          elevation: 0,
          scrolledUnderElevation: 0.5,
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 14,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: _slate200),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: _slate200),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: _emerald600, width: 1.5),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: _rose500),
          ),
        ),
      ),
      home: _starting
          ? const _StartupScreen()
          : _user == null
          ? AuthScreen(api: _api, onAuthenticated: _onAuthenticated)
          : ResidentHome(
              api: _api,
              user: _user!,
              verificationUrl: _verificationUrl,
              onLogout: _logout,
            ),
    );
  }
}

class _StartupScreen extends StatelessWidget {
  const _StartupScreen();

  @override
  Widget build(BuildContext context) => const Scaffold(
    backgroundColor: _slate50,
    body: Center(
      child: CircularProgressIndicator(color: _emerald600, strokeWidth: 2.5),
    ),
  );
}

class AuthScreen extends StatefulWidget {
  const AuthScreen({
    required this.api,
    required this.onAuthenticated,
    super.key,
  });

  final ApiClient api;
  final Future<void> Function({required String token, String? verificationUrl})
  onAuthenticated;

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  final _confirmation = TextEditingController();
  bool _registering = false;
  bool _busy = false;
  bool _showPassword = false;
  bool _showConfirm = false;
  String? _error;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    _confirmation.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });

    try {
      if (_registering) {
        final response = await widget.api.register(
          name: _name.text.trim(),
          email: _email.text.trim(),
          phone: _phone.text.trim(),
          password: _password.text,
          passwordConfirmation: _confirmation.text,
        );
        final data = response['data'] as Map<String, dynamic>;
        await widget.onAuthenticated(
          token: data['token'] as String,
          verificationUrl: data['verification_url'] as String?,
        );
      } else {
        final response = await widget.api.login(
          email: _email.text.trim(),
          password: _password.text,
        );
        final data = response['data'] as Map<String, dynamic>;
        await widget.onAuthenticated(token: data['token'] as String);
      }
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (error, stackTrace) {
      debugPrint('Gagal autentikasi SWMS: $error\n$stackTrace');
      if (mounted) {
        setState(
          () => _error =
              'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _slate50,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Column(
                children: [
                  // Brand Header matching Web App
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: _emerald600,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: [
                            BoxShadow(
                              color: _emerald600.withOpacity(0.25),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.recycling_rounded,
                          color: Colors.white,
                          size: 26,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Text(
                                'SWMS',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w900,
                                  color: _slate900,
                                  letterSpacing: -0.5,
                                ),
                              ),
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 7,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: _emerald50,
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(color: _emerald100),
                                ),
                                child: const Text(
                                  'AI Edition',
                                  style: TextStyle(
                                    fontSize: 10,
                                    fontWeight: FontWeight.w800,
                                    color: _emerald700,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const Text(
                            'Smart Waste Management System',
                            style: TextStyle(fontSize: 11, color: _slate500),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),

                  // Main Auth Card Container matching Web
                  Container(
                    padding: const EdgeInsets.all(26),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(color: _slate200),
                      boxShadow: [
                        BoxShadow(
                          color: _slate900.withOpacity(0.04),
                          blurRadius: 20,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _registering
                                ? 'Daftar Akun Warga'
                                : 'Masuk ke Akun Anda',
                            style: const TextStyle(
                              fontSize: 19,
                              fontWeight: FontWeight.w800,
                              color: _slate900,
                              letterSpacing: -0.4,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _registering
                                ? 'Bergabunglah untuk melaporkan dan memantau sampah lingkungan.'
                                : 'Masukkan alamat email dan kata sandi terdaftar untuk melanjutkan.',
                            style: const TextStyle(
                              fontSize: 12,
                              color: _slate500,
                              height: 1.4,
                            ),
                          ),
                          const SizedBox(height: 20),

                          if (_registering) ...[
                            const Text(
                              'Nama Lengkap *',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: _slate700,
                              ),
                            ),
                            const SizedBox(height: 5),
                            TextFormField(
                              controller: _name,
                              textCapitalization: TextCapitalization.words,
                              decoration: const InputDecoration(
                                hintText: 'Contoh: Budi Santoso',
                                prefixIcon: Icon(
                                  Icons.person_outline,
                                  size: 18,
                                  color: _slate400,
                                ),
                              ),
                              validator: (v) => v == null || v.trim().isEmpty
                                  ? 'Nama lengkap wajib diisi.'
                                  : null,
                            ),
                            const SizedBox(height: 14),
                          ],

                          const Text(
                            'Alamat Email *',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: _slate700,
                            ),
                          ),
                          const SizedBox(height: 5),
                          TextFormField(
                            controller: _email,
                            keyboardType: TextInputType.emailAddress,
                            decoration: const InputDecoration(
                              hintText: 'nama@email.com',
                              prefixIcon: Icon(
                                Icons.email_outlined,
                                size: 18,
                                color: _slate400,
                              ),
                            ),
                            validator: (v) {
                              if (v == null || v.trim().isEmpty)
                                return 'Alamat email wajib diisi.';
                              if (!v.contains('@') || !v.contains('.'))
                                return 'Format email tidak valid.';
                              return null;
                            },
                          ),
                          const SizedBox(height: 14),

                          if (_registering) ...[
                            const Text(
                              'Nomor Handphone (WhatsApp) *',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: _slate700,
                              ),
                            ),
                            const SizedBox(height: 5),
                            TextFormField(
                              controller: _phone,
                              keyboardType: TextInputType.phone,
                              decoration: const InputDecoration(
                                hintText: '081234567890',
                                prefixIcon: Icon(
                                  Icons.phone_outlined,
                                  size: 18,
                                  color: _slate400,
                                ),
                              ),
                              validator: (v) {
                                if (v == null || v.trim().isEmpty)
                                  return 'Nomor handphone wajib diisi.';
                                if (v.trim().length < 9 ||
                                    v.trim().length > 20) {
                                  return 'Nomor HP harus terdiri dari 9–20 karakter.';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 14),
                          ],

                          const Text(
                            'Kata Sandi *',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: _slate700,
                            ),
                          ),
                          const SizedBox(height: 5),
                          TextFormField(
                            controller: _password,
                            obscureText: !_showPassword,
                            decoration: InputDecoration(
                              hintText: '••••••••',
                              prefixIcon: const Icon(
                                Icons.lock_outline,
                                size: 18,
                                color: _slate400,
                              ),
                              suffixIcon: IconButton(
                                icon: Icon(
                                  _showPassword
                                      ? Icons.visibility_off_outlined
                                      : Icons.visibility_outlined,
                                  size: 18,
                                  color: _slate400,
                                ),
                                onPressed: () => setState(
                                  () => _showPassword = !_showPassword,
                                ),
                              ),
                            ),
                            validator: (v) {
                              if (v == null || v.isEmpty)
                                return 'Kata sandi wajib diisi.';
                              if (_registering && v.length < 8)
                                return 'Kata sandi minimal 8 karakter.';
                              return null;
                            },
                          ),

                          if (_registering) ...[
                            const SizedBox(height: 14),
                            const Text(
                              'Ulangi Kata Sandi *',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: _slate700,
                              ),
                            ),
                            const SizedBox(height: 5),
                            TextFormField(
                              controller: _confirmation,
                              obscureText: !_showConfirm,
                              decoration: InputDecoration(
                                hintText: '••••••••',
                                prefixIcon: const Icon(
                                  Icons.lock_reset_outlined,
                                  size: 18,
                                  color: _slate400,
                                ),
                                suffixIcon: IconButton(
                                  icon: Icon(
                                    _showConfirm
                                        ? Icons.visibility_off_outlined
                                        : Icons.visibility_outlined,
                                    size: 18,
                                    color: _slate400,
                                  ),
                                  onPressed: () => setState(
                                    () => _showConfirm = !_showConfirm,
                                  ),
                                ),
                              ),
                              validator: (v) => v != _password.text
                                  ? 'Kata sandi tidak cocok.'
                                  : null,
                            ),
                          ],

                          if (_error != null) ...[
                            const SizedBox(height: 14),
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: _rose50,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: _rose100),
                              ),
                              child: Row(
                                children: [
                                  const Icon(
                                    Icons.error_outline,
                                    size: 16,
                                    color: _rose700,
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      _error!,
                                      style: const TextStyle(
                                        fontSize: 11,
                                        color: _rose700,
                                        height: 1.3,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],

                          const SizedBox(height: 20),

                          SizedBox(
                            width: double.infinity,
                            child: FilledButton.icon(
                              onPressed: _busy ? null : _submit,
                              style: FilledButton.styleFrom(
                                backgroundColor: _emerald600,
                                foregroundColor: Colors.white,
                                minimumSize: const Size.fromHeight(50),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14),
                                ),
                                elevation: 0,
                              ),
                              icon: _busy
                                  ? const SizedBox(
                                      height: 18,
                                      width: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.login_rounded, size: 18),
                              label: Text(
                                _registering
                                    ? 'Daftar Sekarang'
                                    : 'Masuk ke Sistem',
                                style: const TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),
                  TextButton(
                    onPressed: _busy
                        ? null
                        : () => setState(() {
                            _registering = !_registering;
                            _error = null;
                          }),
                    child: RichText(
                      text: TextSpan(
                        style: const TextStyle(fontSize: 12, color: _slate500),
                        children: [
                          TextSpan(
                            text: _registering
                                ? 'Sudah memiliki akun warga? '
                                : 'Belum memiliki akun warga? ',
                          ),
                          TextSpan(
                            text: _registering
                                ? 'Masuk sekarang'
                                : 'Daftar sekarang',
                            style: const TextStyle(
                              color: _emerald600,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  const Text(
                    'Smart Waste Management System © 2026',
                    style: TextStyle(fontSize: 11, color: _slate400),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class ResidentHome extends StatefulWidget {
  const ResidentHome({
    required this.api,
    required this.user,
    required this.onLogout,
    this.verificationUrl,
    super.key,
  });

  final ApiClient api;
  final Map<String, dynamic> user;
  final String? verificationUrl;
  final Future<void> Function() onLogout;

  @override
  State<ResidentHome> createState() => _ResidentHomeState();
}

class _ResidentHomeState extends State<ResidentHome> {
  int _tab = 0;
  bool _loading = true;
  bool _resending = false;
  bool? _verifiedOverride;
  String? _error;
  List<WasteReport> _reports = [];

  bool get _verified =>
      widget.user['email_verification_required'] != true ||
      (_verifiedOverride ?? widget.user['email_verified'] == true);
  bool get _isAdmin => widget.user['role'] == 'admin';

  @override
  void initState() {
    super.initState();
    _loadReports();
  }

  Future<void> _loadReports() async {
    if (!_verified || _isAdmin) {
      if (mounted) setState(() => _loading = false);
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final rows = await widget.api.reports();
      final parsed = rows.map(WasteReport.fromJson).toList();
      if (mounted) setState(() => _reports = parsed);
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (error, stackTrace) {
      debugPrint('Gagal membaca daftar laporan: $error\n$stackTrace');
      if (mounted)
        setState(() => _error = 'Daftar laporan tidak dapat dibuka.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _resendVerification() async {
    setState(() => _resending = true);
    try {
      await widget.api.resendVerification();
      if (mounted) {
        _showMessage(
          'Tautan verifikasi dikirim. Periksa inbox atau folder spam.',
        );
      }
    } on ApiException catch (error) {
      if (mounted) _showMessage(error.message);
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  Future<void> _checkVerification() async {
    try {
      final profile = await widget.api.profile();
      if (!mounted) return;
      final verified = profile['email_verified'] == true;
      setState(() => _verifiedOverride = verified);
      if (verified) {
        _showMessage('Email sudah terverifikasi. Laporan Anda siap digunakan.');
        await _loadReports();
      } else {
        _showMessage(
          'Email belum terverifikasi. Buka tautan di email terlebih dahulu.',
        );
      }
    } on ApiException catch (error) {
      if (mounted) _showMessage(error.message);
    }
  }

  void _showMessage(String message) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _openVerification() async {
    final url = widget.verificationUrl;
    if (url == null || url.isEmpty) {
      await _resendVerification();
      return;
    }
    final opened = await launchUrl(
      Uri.parse(url),
      mode: LaunchMode.externalApplication,
    );
    if (!opened && mounted) {
      _showMessage('Tautan tidak dapat dibuka. Kirim ulang tautan verifikasi.');
    }
  }

  Future<void> _createReport() async {
    if (!_verified) {
      _showMessage('Verifikasi email terlebih dahulu sebelum membuat laporan.');
      return;
    }
    final created = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => CreateReportScreen(api: widget.api)),
    );
    if (created == true) {
      await _loadReports();
      if (mounted) setState(() => _tab = 1);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _slate50,
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: _emerald600,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.recycling_rounded,
                color: Colors.white,
                size: 20,
              ),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Text(
                      'SWMS',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w900,
                        color: _slate900,
                      ),
                    ),
                    const SizedBox(width: 5),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 5,
                        vertical: 1.5,
                      ),
                      decoration: BoxDecoration(
                        color: _emerald50,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: _emerald100),
                      ),
                      child: const Text(
                        'AI Edition',
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w800,
                          color: _emerald700,
                        ),
                      ),
                    ),
                  ],
                ),
                Text(
                  _tab == 0 ? 'Dashboard Statistik' : 'Daftar Laporan',
                  style: const TextStyle(fontSize: 10, color: _slate500),
                ),
              ],
            ),
          ],
        ),
        actions: [
          PopupMenuButton<String>(
            tooltip: 'Menu akun',
            icon: CircleAvatar(
              radius: 17,
              backgroundColor: _emerald50,
              child: Text(
                (widget.user['name']?.toString() ?? 'W')[0].toUpperCase(),
                style: const TextStyle(
                  color: _emerald700,
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                ),
              ),
            ),
            onSelected: (value) {
              if (value == 'logout') widget.onLogout();
            },
            itemBuilder: (_) => [
              PopupMenuItem(
                enabled: false,
                child: Text(
                  widget.user['name']?.toString() ?? 'Pengguna',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: _slate900,
                  ),
                ),
              ),
              PopupMenuItem(
                enabled: false,
                child: Text(
                  widget.user['email']?.toString() ?? '',
                  style: const TextStyle(fontSize: 11, color: _slate500),
                ),
              ),
              const PopupMenuDivider(),
              const PopupMenuItem(
                value: 'logout',
                child: Row(
                  children: [
                    Icon(Icons.logout, size: 16, color: _rose500),
                    SizedBox(width: 8),
                    Text('Keluar Akun', style: TextStyle(color: _rose700)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 820),
            child: RefreshIndicator(
              color: _emerald600,
              onRefresh: _loadReports,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 90),
                children: [
                  if (!_verified) ...[
                    _VerificationBanner(
                      email: widget.user['email']?.toString() ?? '',
                      busy: _resending,
                      onVerify: _openVerification,
                      onResend: _resendVerification,
                      onCheck: _checkVerification,
                    ),
                    const SizedBox(height: 14),
                  ],
                  if (_tab == 0) ...[
                    _WelcomePanel(
                      name: widget.user['name']?.toString() ?? 'Warga',
                      isAdmin: _isAdmin,
                      onCreateReport: _createReport,
                    ),
                    const SizedBox(height: 16),
                    _StatsGrid(reports: _reports),
                    const SizedBox(height: 24),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Aktivitas Laporan Terbaru',
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w800,
                            color: _slate900,
                            letterSpacing: -0.3,
                          ),
                        ),
                        TextButton(
                          onPressed: () => setState(() => _tab = 1),
                          child: const Text(
                            'Lihat Semua',
                            style: TextStyle(
                              color: _emerald600,
                              fontWeight: FontWeight.w700,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    _reportContent(limit: 3),
                  ] else ...[
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Semua Laporan (${_reports.length})',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: _slate900,
                            letterSpacing: -0.3,
                          ),
                        ),
                        IconButton(
                          tooltip: 'Muat ulang',
                          onPressed: _loadReports,
                          icon: const Icon(
                            Icons.refresh,
                            size: 20,
                            color: _slate600,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    _reportContent(),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
      floatingActionButton: _isAdmin
          ? null
          : FloatingActionButton.extended(
              onPressed: _createReport,
              backgroundColor: _emerald600,
              foregroundColor: Colors.white,
              elevation: 3,
              icon: const Icon(Icons.add_a_photo_outlined, size: 18),
              label: const Text(
                'Buat Laporan',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
              ),
            ),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: _slate200)),
        ),
        child: NavigationBar(
          backgroundColor: Colors.white,
          indicatorColor: _emerald50,
          selectedIndex: _tab,
          onDestinationSelected: (index) => setState(() => _tab = index),
          destinations: const [
            NavigationDestination(
              icon: Icon(Icons.dashboard_outlined, color: _slate500),
              selectedIcon: Icon(Icons.dashboard, color: _emerald700),
              label: 'Dashboard',
            ),
            NavigationDestination(
              icon: Icon(Icons.folder_open_outlined, color: _slate500),
              selectedIcon: Icon(Icons.folder, color: _emerald700),
              label: 'Laporan Saya',
            ),
          ],
        ),
      ),
    );
  }

  Widget _reportContent({int? limit}) {
    if (_loading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 40),
        child: Center(
          child: CircularProgressIndicator(color: _emerald600, strokeWidth: 2),
        ),
      );
    }
    if (_error != null) {
      return Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: _rose50,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: _rose100),
        ),
        child: Row(
          children: [
            const Icon(Icons.error_outline, color: _rose700, size: 20),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                _error!,
                style: const TextStyle(color: _rose700, fontSize: 12),
              ),
            ),
          ],
        ),
      );
    }
    if (_reports.isEmpty) {
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: _slate200),
        ),
        child: Column(
          children: [
            Container(
              width: 52,
              height: 52,
              decoration: BoxDecoration(
                color: _slate100,
                borderRadius: BorderRadius.circular(16),
              ),
              child: const Icon(
                Icons.folder_open_outlined,
                size: 26,
                color: _slate400,
              ),
            ),
            const SizedBox(height: 12),
            const Text(
              'Belum Ada Laporan',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                color: _slate900,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Tekan tombol Buat Laporan untuk melaporkan tumpukan sampah.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 11, color: _slate500),
            ),
          ],
        ),
      );
    }

    final items = limit == null ? _reports : _reports.take(limit).toList();
    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 12),
      itemBuilder: (_, index) => _ReportTile(report: items[index]),
    );
  }
}

class _WelcomePanel extends StatelessWidget {
  const _WelcomePanel({
    required this.name,
    required this.isAdmin,
    required this.onCreateReport,
  });

  final String name;
  final bool isAdmin;
  final VoidCallback onCreateReport;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _slate200),
        boxShadow: [
          BoxShadow(
            color: _slate900.withOpacity(0.02),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isAdmin
                          ? 'Panel Pengawasan Sampah'
                          : 'Panel Warga • Halo, $name',
                      style: const TextStyle(
                        color: _slate900,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.3,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      isAdmin
                          ? 'Pantau laporan timbunan sampah kota dan tugaskan armada penanganan.'
                          : 'Laporkan tumpukan sampah di lingkungan Anda dengan foto dan koordinat GPS.',
                      style: const TextStyle(
                        color: _slate500,
                        fontSize: 11,
                        height: 1.4,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (!isAdmin) ...[
            const SizedBox(height: 14),
            Row(
              children: [
                FilledButton.icon(
                  onPressed: onCreateReport,
                  style: FilledButton.styleFrom(
                    backgroundColor: _emerald600,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 9,
                    ),
                    elevation: 0,
                  ),
                  icon: const Icon(Icons.camera_alt_outlined, size: 15),
                  label: const Text(
                    'Buat Laporan',
                    style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  const _StatsGrid({required this.reports});

  final List<WasteReport> reports;

  @override
  Widget build(BuildContext context) {
    final pending = reports.where((r) => r.status == 'pending').length;
    final inProgress = reports.where((r) => r.status == 'in_progress').length;
    final completed = reports.where((r) => r.status == 'completed').length;

    return Row(
      children: [
        Expanded(
          child: _StatCard(
            label: 'Total Laporan',
            value: reports.length,
            color: _slate900,
            bg: Colors.white,
            icon: Icons.folder_open_outlined,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _StatCard(
            label: 'Menunggu',
            value: pending,
            color: _amber700,
            bg: Colors.white,
            icon: Icons.hourglass_empty_rounded,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _StatCard(
            label: 'Diproses',
            value: inProgress,
            color: _sky700,
            bg: Colors.white,
            icon: Icons.local_shipping_outlined,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: _StatCard(
            label: 'Selesai',
            value: completed,
            color: _emerald700,
            bg: Colors.white,
            icon: Icons.done_all_rounded,
          ),
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.label,
    required this.value,
    required this.color,
    required this.bg,
    required this.icon,
  });

  final String label;
  final int value;
  final Color color;
  final Color bg;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _slate200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '$value',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                  color: color,
                ),
              ),
              Icon(icon, size: 15, color: color.withOpacity(0.7)),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: _slate500,
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportTile extends StatelessWidget {
  const _ReportTile({required this.report});

  final WasteReport report;

  (Color bg, Color text, Color border, String label) _statusMeta() {
    return switch (report.status) {
      'pending' => (_amber50, _amber700, _amber100, 'Menunggu'),
      'in_progress' => (_sky50, _sky700, _sky100, 'Diproses'),
      'completed' => (_emerald50, _emerald700, _emerald100, 'Selesai'),
      'rejected' => (_rose50, _rose700, _rose100, 'Ditolak'),
      _ => (_slate50, _slate700, _slate200, report.status),
    };
  }

  (Color bg, Color text, Color border, String label) _categoryMeta() {
    return switch (report.category) {
      'organic' => (_emerald50, _emerald700, _emerald100, 'Sampah Organik'),
      'plastic' => (
        _blue50,
        _blue700,
        const Color(0xFFDBEAFE),
        'Sampah Plastik',
      ),
      'paper' => (_amber50, _amber700, _amber100, 'Sampah Kertas'),
      'other' => (
        const Color(0xFFF3E8FF),
        const Color(0xFF7E22CE),
        const Color(0xFFE9D5FF),
        'Sampah Campuran',
      ),
      _ => (_slate50, _slate700, _slate200, 'Sampah Lainnya'),
    };
  }

  @override
  Widget build(BuildContext context) {
    final imageUrl = _reportImageUrl(report.imagePath);
    final date = report.createdAt;
    final status = _statusMeta();
    final cat = _categoryMeta();

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _slate200),
        boxShadow: [
          BoxShadow(
            color: _slate900.withOpacity(0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: 140,
            width: double.infinity,
            child: Stack(
              fit: StackFit.expand,
              children: [
                imageUrl == null
                    ? Container(
                        color: _slate100,
                        child: const Icon(
                          Icons.image_outlined,
                          color: _slate400,
                          size: 40,
                        ),
                      )
                    : Image.network(
                        imageUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => Container(
                          color: _slate100,
                          child: const Icon(
                            Icons.broken_image_outlined,
                            color: _slate400,
                            size: 40,
                          ),
                        ),
                      ),
                Positioned(
                  top: 10,
                  left: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3.5,
                    ),
                    decoration: BoxDecoration(
                      color: status.$1,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: status.$3),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 5,
                          height: 5,
                          decoration: BoxDecoration(
                            color: status.$2,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          status.$4,
                          style: TextStyle(
                            color: status.$2,
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Positioned(
                  top: 10,
                  right: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3.5,
                    ),
                    decoration: BoxDecoration(
                      color: cat.$1,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: cat.$3),
                    ),
                    child: Text(
                      cat.$4,
                      style: TextStyle(
                        color: cat.$2,
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
                if (report.confidence != null)
                  Positioned(
                    bottom: 10,
                    left: 10,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: _slate900.withOpacity(0.85),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.auto_awesome,
                            color: _amber500,
                            size: 11,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            'AI ${report.confidence!.toStringAsFixed(1)}%',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (date != null)
                  Row(
                    children: [
                      const Icon(Icons.access_time, size: 11, color: _slate400),
                      const SizedBox(width: 4),
                      Text(
                        '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}, ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}',
                        style: const TextStyle(fontSize: 10, color: _slate400),
                      ),
                    ],
                  ),
                const SizedBox(height: 5),
                Text(
                  report.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: _slate900,
                  ),
                ),
                if (report.description.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(
                    report.description,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 11,
                      color: _slate600,
                      height: 1.35,
                    ),
                  ),
                ],
                const SizedBox(height: 10),
                Row(
                  children: [
                    const Icon(Icons.location_on, size: 13, color: _rose500),
                    const SizedBox(width: 3),
                    Expanded(
                      child: Text(
                        report.address?.isNotEmpty == true
                            ? report.address!
                            : (report.latitude != null
                                  ? 'GPS: ${report.latitude!.toStringAsFixed(4)}, ${report.longitude!.toStringAsFixed(4)}'
                                  : 'Lokasi tidak tersedia'),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 10, color: _slate500),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class CreateReportScreen extends StatefulWidget {
  const CreateReportScreen({required this.api, super.key});

  final ApiClient api;

  @override
  State<CreateReportScreen> createState() => _CreateReportScreenState();
}

class _CreateReportScreenState extends State<CreateReportScreen> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _address = TextEditingController();
  final _picker = ImagePicker();

  String _category = '';
  XFile? _image;
  Uint8List? _imageBytes;
  double? _latitude;
  double? _longitude;
  Map<String, dynamic>? _aiResult;
  bool _locating = false;
  bool _classifying = false;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _address.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final selected = (source == ImageSource.camera && !kIsWeb)
          ? await Navigator.of(context).push<XFile>(
              MaterialPageRoute(builder: (_) => const CameraCaptureScreen()),
            )
          : await _picker.pickImage(source: source, imageQuality: 86);
      if (selected == null) return;
      final bytes = await selected.readAsBytes();
      if (bytes.length > 10 * 1024 * 1024) {
        if (!mounted) return;
        setState(() => _error = 'Ukuran foto maksimal 10 MB.');
        return;
      }
      if (!mounted) return;
      setState(() {
        _image = selected;
        _imageBytes = bytes;
        _aiResult = null;
        _error = null;
      });
      await _classify();
    } catch (error, stackTrace) {
      debugPrint('Gagal memilih foto: $error\n$stackTrace');
      if (mounted) {
        setState(
          () => _error =
              'Foto tidak dapat dibuka. Periksa izin kamera atau galeri.',
        );
      }
    }
  }

  Future<void> _getLocation() async {
    setState(() {
      _locating = true;
      _error = null;
    });
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        throw const ApiException(
          'Aktifkan layanan lokasi GPS pada perangkat Anda.',
        );
      }
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        throw const ApiException('Izin lokasi belum diberikan.');
      }
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
      if (!mounted) return;
      setState(() {
        _latitude = position.latitude;
        _longitude = position.longitude;
        if (_address.text.isEmpty) {
          _address.text =
              'Koordinat: ${position.latitude.toStringAsFixed(5)}, ${position.longitude.toStringAsFixed(5)}';
        }
      });
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (error, stackTrace) {
      debugPrint('Gagal membaca GPS: $error\n$stackTrace');
      if (mounted) {
        setState(
          () => _error =
              'Lokasi GPS tidak dapat diperoleh. Periksa izin perangkat.',
        );
      }
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _classify() async {
    final bytes = _imageBytes;
    final image = _image;
    if (bytes == null || image == null) return;

    setState(() {
      _classifying = true;
      _error = null;
    });
    try {
      final result = await widget.api.classifyImage(
        bytes: bytes,
        filename: image.name,
      );
      if (!mounted) return;
      setState(() {
        _aiResult = result;
        if (result['category'] is String) {
          _category = result['category'] as String;
        }
      });
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (error, stackTrace) {
      debugPrint('Klasifikasi gagal: $error\n$stackTrace');
    } finally {
      if (mounted) setState(() => _classifying = false);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_image == null || _imageBytes == null) {
      setState(() => _error = 'Foto sampah wajib disertakan.');
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });
    try {
      await widget.api.createReport(
        title: _title.text.trim(),
        description: _description.text.trim(),
        category: _category,
        imageBytes: _imageBytes!,
        filename: _image!.name,
        latitude: _latitude ?? -6.2088,
        longitude: _longitude ?? 106.8456,
        locationAddress: _address.text.trim(),
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (error, stackTrace) {
      debugPrint('Gagal mengirim laporan: $error\n$stackTrace');
      if (mounted) {
        setState(
          () =>
              _error = 'Laporan belum terkirim. Periksa koneksi internet Anda.',
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _slate50,
      appBar: AppBar(
        title: const Text(
          'Buat Laporan Sampah',
          style: TextStyle(
            fontWeight: FontWeight.w800,
            fontSize: 16,
            color: _slate900,
          ),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 720),
            child: Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 40),
                children: [
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: _slate200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Foto Sampah *',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: _slate800,
                          ),
                        ),
                        const SizedBox(height: 8),
                        _PhotoUploadBox(
                          imageBytes: _imageBytes,
                          onPickCamera: () => _pickImage(ImageSource.camera),
                          onPickGallery: () => _pickImage(ImageSource.gallery),
                        ),
                        const SizedBox(height: 12),
                        _AiStatusPanel(
                          loading: _classifying,
                          result: _aiResult,
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: _slate200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Judul Laporan *',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: _slate800,
                          ),
                        ),
                        const SizedBox(height: 5),
                        TextFormField(
                          controller: _title,
                          decoration: const InputDecoration(
                            hintText: 'Contoh: Timbunan Botol Plastik di Pinggir Jalan',
                          ),
                          validator: (v) => v == null || v.trim().isEmpty
                              ? 'Judul laporan wajib diisi.'
                              : null,
                        ),
                        const SizedBox(height: 14),

                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Kategori Sampah *',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                                color: _slate800,
                              ),
                            ),
                            Text(
                              _aiResult != null
                                  ? 'Diisi oleh AI'
                                  : 'Pilih kategori',
                              style: const TextStyle(
                                fontSize: 10,
                                color: _slate400,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 5),
                        DropdownButtonFormField<String>(
                          value: _category.isEmpty ? null : _category,
                          decoration: const InputDecoration(),
                          hint: const Text('Pilih Kategori Sampah'),
                          items: const [
                            DropdownMenuItem(
                              value: 'organic',
                              child: Text(
                                'Sampah Organik (Sisa Makanan, Daun)',
                              ),
                            ),
                            DropdownMenuItem(
                              value: 'plastic',
                              child: Text(
                                'Sampah Plastik (Botol, Kresek, Cup)',
                              ),
                            ),
                            DropdownMenuItem(
                              value: 'paper',
                              child: Text(
                                'Sampah Kertas & Karton (Kardus, Koran)',
                              ),
                            ),
                            DropdownMenuItem(
                              value: 'other',
                              child: Text('Sampah Campuran / Residu Lainnya'),
                            ),
                          ],
                          onChanged: (v) => setState(() => _category = v ?? ''),
                          validator: (v) => v == null || v.isEmpty
                              ? 'Kategori sampah wajib dipilih.'
                              : null,
                        ),
                        const SizedBox(height: 14),

                        const Text(
                          'Deskripsi Kondisi Sampah *',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            color: _slate800,
                          ),
                        ),
                        const SizedBox(height: 5),
                        TextFormField(
                          controller: _description,
                          minLines: 3,
                          maxLines: 5,
                          decoration: const InputDecoration(
                            hintText: 'Jelaskan kondisi tumpukan sampah, perkiraan volume, bau, atau akses...',
                          ),
                          validator: (v) => v == null || v.trim().isEmpty
                              ? 'Deskripsi kondisi sampah wajib diisi.'
                              : null,
                        ),
                        const SizedBox(height: 14),

                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Alamat / Patokan Lokasi',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w800,
                                color: _slate800,
                              ),
                            ),
                            TextButton.icon(
                              onPressed: _locating ? null : _getLocation,
                              style: TextButton.styleFrom(
                                padding: EdgeInsets.zero,
                                minimumSize: Size.zero,
                                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                              ),
                              icon: _locating
                                  ? const SizedBox(
                                      width: 12,
                                      height: 12,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 1.5,
                                        color: _emerald600,
                                      ),
                                    )
                                  : const Icon(
                                      Icons.my_location,
                                      size: 13,
                                      color: _emerald600,
                                    ),
                              label: const Text(
                                'Gunakan GPS Saya',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: _emerald600,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 5),
                        TextFormField(
                          controller: _address,
                          decoration: const InputDecoration(
                            hintText: 'Contoh: Jl. Diponegoro No. 45, Depan Gerbang Taman',
                          ),
                        ),

                        if (_latitude != null) ...[
                          const SizedBox(height: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: _slate100,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(
                                  Icons.location_on,
                                  size: 12,
                                  color: _rose500,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  'Koordinat: ${_latitude!.toStringAsFixed(4)}, ${_longitude!.toStringAsFixed(4)}',
                                  style: const TextStyle(
                                    fontSize: 10,
                                    color: _slate600,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],

                        if (_error != null) ...[
                          const SizedBox(height: 14),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: _rose50,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: _rose100),
                            ),
                            child: Text(
                              _error!,
                              style: const TextStyle(
                                fontSize: 11,
                                color: _rose700,
                              ),
                            ),
                          ),
                        ],

                        const SizedBox(height: 22),

                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: _submitting ? null : _submit,
                            style: FilledButton.styleFrom(
                              backgroundColor: _emerald600,
                              foregroundColor: Colors.white,
                              minimumSize: const Size.fromHeight(50),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                              elevation: 0,
                            ),
                            icon: _submitting
                                ? const SizedBox(
                                    width: 18,
                                    height: 18,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Icon(Icons.send_rounded, size: 18),
                            label: Text(
                              _submitting
                                  ? 'Mengirim Laporan...'
                                  : 'Kirim Laporan Sampah',
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _PhotoUploadBox extends StatelessWidget {
  const _PhotoUploadBox({
    required this.imageBytes,
    required this.onPickCamera,
    required this.onPickGallery,
  });

  final Uint8List? imageBytes;
  final VoidCallback onPickCamera;
  final VoidCallback onPickGallery;

  @override
  Widget build(BuildContext context) {
    if (imageBytes != null) {
      return Container(
        height: 190,
        width: double.infinity,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: _slate200),
        ),
        clipBehavior: Clip.antiAlias,
        child: Stack(
          fit: StackFit.expand,
          children: [
            Image.memory(imageBytes!, fit: BoxFit.cover),
            Positioned(
              bottom: 10,
              right: 10,
              child: Row(
                children: [
                  _circleBtn(Icons.camera_alt, onPickCamera),
                  const SizedBox(width: 8),
                  _circleBtn(Icons.photo_library, onPickGallery),
                ],
              ),
            ),
          ],
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
      decoration: BoxDecoration(
        color: _slate50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _slate200, width: 1.5),
      ),
      child: Column(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: _emerald50,
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.cloud_upload_outlined,
              color: _emerald600,
              size: 24,
            ),
          ),
          const SizedBox(height: 10),
          const Text(
            'Unggah Foto Bukti Sampah',
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: _slate800,
            ),
          ),
          const SizedBox(height: 3),
          const Text(
            'JPG, PNG, atau WEBP (Maksimal 10MB)',
            style: TextStyle(fontSize: 10, color: _slate400),
          ),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              OutlinedButton.icon(
                onPressed: onPickCamera,
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: _slate200),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 8,
                  ),
                ),
                icon: const Icon(
                  Icons.camera_alt_outlined,
                  size: 15,
                  color: _emerald600,
                ),
                label: const Text(
                  'Kamera',
                  style: TextStyle(fontSize: 11, color: _slate700),
                ),
              ),
              const SizedBox(width: 8),
              OutlinedButton.icon(
                onPressed: onPickGallery,
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: _slate200),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 8,
                  ),
                ),
                icon: const Icon(
                  Icons.photo_library_outlined,
                  size: 15,
                  color: _emerald600,
                ),
                label: const Text(
                  'Galeri',
                  style: TextStyle(fontSize: 11, color: _slate700),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _circleBtn(IconData icon, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 34,
        height: 34,
        decoration: BoxDecoration(
          color: _slate900.withOpacity(0.8),
          shape: BoxShape.circle,
        ),
        child: Icon(icon, color: Colors.white, size: 16),
      ),
    );
  }
}

class _AiStatusPanel extends StatelessWidget {
  const _AiStatusPanel({required this.loading, required this.result});

  final bool loading;
  final Map<String, dynamic>? result;

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: _slate50,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: _slate200),
        ),
        child: const Row(
          children: [
            SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(
                color: _emerald600,
                strokeWidth: 2,
              ),
            ),
            SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Menganalisis citra sampah...',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: _slate800,
                    ),
                  ),
                  Text(
                    'Memproses ekstraksi fitur dan klasifikasi',
                    style: TextStyle(fontSize: 10, color: _slate500),
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

    if (result != null) {
      final category = result!['category']?.toString();
      final confidence = (result!['confidence'] as num?)?.toDouble() ?? 0.0;
      final label =
          result!['label']?.toString() ??
          WasteReport.categoryLabel(category ?? '');
      final recommendation = result!['recommendation'] as Map<String, dynamic>?;

      if (category == null) {
        return Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: _amber50,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: _amber100),
          ),
          child: const Row(
            children: [
              Icon(Icons.info_outline, color: _amber700, size: 16),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  'AI belum cukup yakin. Silakan tentukan kategori laporan secara manual.',
                  style: TextStyle(fontSize: 11, color: _amber700),
                ),
              ),
            ],
          ),
        );
      }

      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: _slate50,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: _slate200),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Hasil Klasifikasi Model AI',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: _slate800,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: _emerald100,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    '${confidence.toStringAsFixed(1)}% Keyakinan',
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      color: _emerald700,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: _slate200),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Prediksi Kategori:',
                    style: TextStyle(fontSize: 10, color: _slate500),
                  ),
                  Text(
                    label,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: _slate900,
                    ),
                  ),
                  const SizedBox(height: 6),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: confidence / 100,
                      backgroundColor: _slate100,
                      valueColor: const AlwaysStoppedAnimation<Color>(
                        _emerald600,
                      ),
                      minHeight: 4,
                    ),
                  ),
                ],
              ),
            ),
            if (recommendation != null && recommendation['action'] != null) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: _slate200),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Rekomendasi Penanganan:',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: _slate800,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      recommendation['action'].toString(),
                      style: const TextStyle(
                        fontSize: 10,
                        color: _slate600,
                        height: 1.3,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: _slate50,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: _slate200),
      ),
      child: const Row(
        children: [
          Icon(Icons.auto_awesome, color: _emerald600, size: 14),
          SizedBox(width: 8),
          Expanded(
            child: Text(
              'Unggah foto untuk mendeteksi kategori sampah secara otomatis dengan AI.',
              style: TextStyle(fontSize: 10, color: _slate500),
            ),
          ),
        ],
      ),
    );
  }
}

class CameraCaptureScreen extends StatefulWidget {
  const CameraCaptureScreen({super.key});

  @override
  State<CameraCaptureScreen> createState() => _CameraCaptureScreenState();
}

class _CameraCaptureScreenState extends State<CameraCaptureScreen> {
  List<CameraDescription> _cameras = [];
  CameraController? _controller;
  bool _initializing = true;
  bool _capturing = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initializeCamera();
  }

  @override
  void dispose() {
    unawaited(_controller?.dispose());
    super.dispose();
  }

  Future<void> _initializeCamera({CameraDescription? preferredCamera}) async {
    setState(() {
      _initializing = true;
      _error = null;
    });
    CameraController? controller;
    try {
      final cameras = _cameras.isEmpty ? await availableCameras() : _cameras;
      if (cameras.isEmpty) throw StateError('Tidak ada kamera yang tersedia.');
      _cameras = cameras;
      final selected =
          preferredCamera ??
          cameras.firstWhere(
            (camera) => camera.lensDirection == CameraLensDirection.back,
            orElse: () => cameras.first,
          );
      controller = CameraController(
        selected,
        ResolutionPreset.high,
        enableAudio: false,
      );
      await controller.initialize();
      if (!mounted) {
        await controller.dispose();
        return;
      }
      setState(() {
        _controller = controller;
        _initializing = false;
      });
    } catch (error) {
      await controller?.dispose();
      if (mounted) {
        setState(() {
          _initializing = false;
          _error = 'Kamera tidak dapat diakses.';
        });
      }
    }
  }

  Future<void> _capturePhoto() async {
    final controller = _controller;
    if (controller == null || _capturing || _initializing) return;
    setState(() => _capturing = true);
    try {
      final image = await controller.takePicture();
      if (mounted) Navigator.of(context).pop(image);
    } catch (e) {
      if (mounted) setState(() => _capturing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: const Text('Ambil Foto Sampah', style: TextStyle(fontSize: 14)),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: Center(
                child: controller != null && controller.value.isInitialized
                    ? CameraPreview(controller)
                    : const CircularProgressIndicator(color: _emerald600),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(20),
              child: IconButton.filled(
                onPressed: _capturing ? null : _capturePhoto,
                iconSize: 32,
                style: IconButton.styleFrom(
                  backgroundColor: _emerald600,
                  foregroundColor: Colors.white,
                  minimumSize: const Size(64, 64),
                ),
                icon: const Icon(Icons.camera_alt),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _VerificationBanner extends StatelessWidget {
  const _VerificationBanner({
    required this.email,
    required this.busy,
    required this.onVerify,
    required this.onResend,
    required this.onCheck,
  });

  final String email;
  final bool busy;
  final VoidCallback onVerify;
  final VoidCallback onResend;
  final VoidCallback onCheck;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _amber50,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _amber100),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.mark_email_unread_outlined,
                color: _amber700,
                size: 18,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  'Verifikasi Email Diperlukan ($email)',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                    color: _amber700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          const Text(
            'Buka tautan verifikasi di email Anda sebelum membuat laporan.',
            style: TextStyle(fontSize: 11, color: _amber700),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              FilledButton(
                onPressed: busy ? null : onVerify,
                style: FilledButton.styleFrom(
                  backgroundColor: _amber700,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                ),
                child: const Text(
                  'Buka Tautan',
                  style: TextStyle(fontSize: 11),
                ),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: busy ? null : onResend,
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                ),
                child: const Text(
                  'Kirim Ulang',
                  style: TextStyle(fontSize: 11),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

String? _reportImageUrl(String path) {
  if (path.isEmpty) return null;
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  final base = Uri.parse(apiBaseUrl);
  final origin = base.replace(path: '', query: null, fragment: null);
  return origin.resolve('/storage/${Uri.encodeFull(path)}').toString();
}
