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

const _forest = Color(0xFF173C32);
const _leaf = Color(0xFF2C735A);
const _paper = Color(0xFFF4F3ED);
const _ink = Color(0xFF202A26);

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
        scaffoldBackgroundColor: _paper,
        colorScheme: ColorScheme.fromSeed(
          seedColor: _leaf,
          primary: _forest,
          surface: _paper,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: _paper,
          foregroundColor: _ink,
          centerTitle: false,
          elevation: 0,
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 16,
            vertical: 15,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFFE2E5DF)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFFE2E5DF)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: _leaf, width: 1.5),
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
  Widget build(BuildContext context) =>
      const Scaffold(body: Center(child: CircularProgressIndicator()));
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
      debugPrint('Kesalahan autentikasi: $error\n$stackTrace');
      if (mounted) {
        setState(() => _error = 'Proses akun gagal. Silakan coba kembali.');
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String? _required(String? value, String label) =>
      value == null || value.trim().isEmpty ? '$label wajib diisi.' : null;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 430),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const _BrandMark(),
                  const SizedBox(height: 32),
                  Text(
                    _registering ? 'Buat akun warga' : 'Selamat datang kembali',
                    style: Theme.of(context).textTheme.headlineSmall
                        ?.copyWith(color: _ink, fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    _registering
                        ? 'Daftar untuk melaporkan masalah sampah di sekitar Anda.'
                        : 'Masuk untuk melihat dan mengirim laporan lingkungan.',
                    style: const TextStyle(
                      color: Color(0xFF66716B),
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 24),
                  Form(
                    key: _formKey,
                    child: Column(
                      children: [
                        if (_registering) ...[
                          TextFormField(
                            controller: _name,
                            textCapitalization: TextCapitalization.words,
                            decoration: const InputDecoration(
                              labelText: 'Nama lengkap',
                            ),
                            validator: (value) => _required(value, 'Nama'),
                          ),
                          const SizedBox(height: 13),
                        ],
                        TextFormField(
                          controller: _email,
                          keyboardType: TextInputType.emailAddress,
                          autocorrect: false,
                          decoration: const InputDecoration(
                            labelText: 'Alamat email',
                          ),
                          validator: (value) {
                            if (_required(value, 'Email') != null) {
                              return 'Email wajib diisi.';
                            }
                            if (!RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$')
                                .hasMatch(value!.trim())) {
                              return 'Email tidak valid.';
                            }
                            return null;
                          },
                        ),
                        if (_registering) ...[
                          const SizedBox(height: 13),
                          TextFormField(
                            controller: _phone,
                            keyboardType: TextInputType.phone,
                            decoration: const InputDecoration(
                              labelText: 'Nomor HP',
                            ),
                            validator: (value) {
                              if (_required(value, 'Nomor HP') != null) {
                                return 'Nomor HP wajib diisi.';
                              }
                              final phone = value!.trim();
                              if (phone.length < 9 || phone.length > 20) {
                                return 'Nomor HP harus terdiri dari 9–20 karakter.';
                              }
                              return null;
                            },
                          ),
                        ],
                        const SizedBox(height: 13),
                        TextFormField(
                          controller: _password,
                          obscureText: true,
                          decoration: const InputDecoration(
                            labelText: 'Kata sandi',
                          ),
                          validator: (value) {
                            if (_required(value, 'Kata sandi') != null) {
                              return 'Kata sandi wajib diisi.';
                            }
                            if (_registering && value!.length < 8) {
                              return 'Kata sandi minimal 8 karakter.';
                            }
                            return null;
                          },
                        ),
                        if (_registering) ...[
                          const SizedBox(height: 13),
                          TextFormField(
                            controller: _confirmation,
                            obscureText: true,
                            decoration: const InputDecoration(
                              labelText: 'Ulangi kata sandi',
                            ),
                            validator: (value) => value != _password.text
                                ? 'Kata sandi tidak cocok.'
                                : null,
                          ),
                        ],
                        if (_error != null) ...[
                          const SizedBox(height: 14),
                          _InlineMessage(message: _error!, isError: true),
                        ],
                        const SizedBox(height: 20),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: _busy ? null : _submit,
                            style: FilledButton.styleFrom(
                              backgroundColor: _forest,
                              minimumSize: const Size.fromHeight(52),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                            child: _busy
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : Text(_registering ? 'Buat akun' : 'Masuk'),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  Center(
                    child: TextButton(
                      onPressed: _busy
                          ? null
                          : () => setState(() {
                              _registering = !_registering;
                              _error = null;
                            }),
                      child: Text(
                        _registering
                            ? 'Sudah punya akun? Masuk'
                            : 'Belum punya akun? Daftar warga',
                      ),
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
      if (mounted) {
        setState(() {
          _loading = false;
          _error = null;
        });
      }
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
      if (mounted) {
        setState(() => _error = 'Daftar laporan tidak dapat dibuka.');
      }
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
      _showMessage(
        'Tautan tidak dapat dibuka. Kirim ulang tautan verifikasi melalui email.',
      );
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
    final title = _tab == 0 ? 'Ringkasan warga' : 'Laporan saya';

    return Scaffold(
      appBar: AppBar(
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w800)),
        actions: [
          PopupMenuButton<String>(
            tooltip: 'Menu akun',
            icon: const CircleAvatar(
              radius: 17,
              backgroundColor: Color(0xFFDCE8DF),
              child: Icon(Icons.person_outline, color: _forest, size: 19),
            ),
            onSelected: (value) {
              if (value == 'logout') widget.onLogout();
            },
            itemBuilder: (_) => [
              PopupMenuItem(
                enabled: false,
                child: Text(widget.user['email']?.toString() ?? 'Akun SWMS'),
              ),
              const PopupMenuDivider(),
              const PopupMenuItem(
                value: 'logout',
                child: Text('Keluar dari akun'),
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
              onRefresh: _loadReports,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
                children: [
                  if (!_verified) ...[
                    _VerificationBanner(
                      email: widget.user['email']?.toString() ?? '',
                      busy: _resending,
                      onVerify: _openVerification,
                      onResend: _resendVerification,
                      onCheck: _checkVerification,
                    ),
                    const SizedBox(height: 18),
                  ],
                  if (_tab == 0) ...[
                    _WelcomePanel(
                      name: widget.user['name']?.toString() ?? 'Warga',
                      isAdmin: _isAdmin,
                    ),
                    const SizedBox(height: 18),
                    _StatsRow(reports: _reports),
                    const SizedBox(height: 25),
                    _SectionHeading(
                      title: 'Aktivitas terbaru',
                      action: TextButton(
                        onPressed: () => setState(() => _tab = 1),
                        child: const Text('Semua laporan'),
                      ),
                    ),
                    const SizedBox(height: 8),
                    _reportContent(limit: 3),
                  ] else ...[
                    _SectionHeading(
                      title: 'Riwayat laporan',
                      action: IconButton(
                        tooltip: 'Muat ulang',
                        onPressed: _loadReports,
                        icon: const Icon(Icons.refresh),
                      ),
                    ),
                    const SizedBox(height: 12),
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
              backgroundColor: _forest,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add_a_photo_outlined),
              label: const Text('Buat laporan'),
            ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (index) => setState(() => _tab = index),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Beranda',
          ),
          NavigationDestination(
            icon: Icon(Icons.receipt_long_outlined),
            selectedIcon: Icon(Icons.receipt_long),
            label: 'Laporan',
          ),
        ],
      ),
    );
  }

  Widget _reportContent({int? limit}) {
    if (_isAdmin) {
      return const _InfoPanel(
        message:
            'Akun Admin menggunakan dashboard website untuk memantau laporan.',
      );
    }
    if (_loading && _reports.isEmpty) {
      return const Padding(
        padding: EdgeInsets.all(32),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (_error != null && _reports.isEmpty) {
      return _ErrorPanel(message: _error!, onRetry: _loadReports);
    }
    if (_reports.isEmpty) return const _EmptyReports();

    final shown = limit == null ? _reports : _reports.take(limit).toList();
    return Column(
      children: [
        for (final report in shown) ...[
          _ReportTile(report: report),
          const SizedBox(height: 10),
        ],
      ],
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
  XFile? _image;
  Uint8List? _imageBytes;
  String _category = '';
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
          'Aktifkan layanan lokasi pada perangkat Anda.',
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
      });
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (error, stackTrace) {
      debugPrint('Gagal membaca lokasi GPS: $error\n$stackTrace');
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
    if (bytes == null || image == null) {
      setState(() => _error = 'Pilih foto sebelum menjalankan deteksi AI.');
      return;
    }
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
      debugPrint('Klasifikasi foto gagal: $error\n$stackTrace');
      if (mounted) {
        setState(
          () => _error = 'Foto tidak berhasil dianalisis. Anda tetap dapat memilih kategori.',
        );
      }
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
    if (_latitude == null || _longitude == null) {
      setState(
        () => _error = 'Lokasi GPS wajib diambil sebelum mengirim laporan.',
      );
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
        latitude: _latitude,
        longitude: _longitude,
        locationAddress: _address.text,
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on ApiException catch (error) {
      if (mounted) setState(() => _error = error.message);
    } catch (error, stackTrace) {
      debugPrint('Gagal mengirim laporan: $error\n$stackTrace');
      if (mounted) {
        setState(
          () => _error =
              'Laporan belum terkirim. Periksa koneksi lalu coba kembali.',
        );
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'Buat laporan',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 720),
            child: Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
                children: [
                  const Text(
                    'Ceritakan masalahnya',
                    style: TextStyle(
                      fontSize: 23,
                      fontWeight: FontWeight.w800,
                      color: _ink,
                    ),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Sertakan foto, ambil lokasi GPS, dan jelaskan kondisi agar petugas dapat menindaklanjuti laporan.',
                    style: TextStyle(color: Color(0xFF66716B), height: 1.45),
                  ),
                  const SizedBox(height: 20),
                  _PhotoPicker(
                    image: _image,
                    onCamera: () => _pickImage(ImageSource.camera),
                    onGallery: () => _pickImage(ImageSource.gallery),
                  ),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: _classifying || _image == null
                        ? null
                        : _classify,
                    icon: _classifying
                        ? const SizedBox(
                            width: 17,
                            height: 17,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.auto_awesome_outlined),
                    label: Text(
                      _classifying
                          ? 'Menganalisis foto...'
                          : 'Deteksi kategori dengan AI',
                    ),
                  ),
                  if (_aiResult != null) ...[
                    const SizedBox(height: 10),
                    _AiResultCard(result: _aiResult!),
                  ],
                  const SizedBox(height: 20),
                  TextFormField(
                    controller: _title,
                    maxLength: 255,
                    decoration: const InputDecoration(
                      labelText: 'Judul laporan',
                    ),
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Judul laporan wajib diisi.'
                        : null,
                  ),
                  const SizedBox(height: 14),
                  DropdownButtonFormField<String>(
                    initialValue: _category.isEmpty ? null : _category,
                    decoration: const InputDecoration(
                      labelText: 'Kategori sampah',
                    ),
                    items: const [
                      DropdownMenuItem(
                        value: 'organic',
                        child: Text('Organik'),
                      ),
                      DropdownMenuItem(
                        value: 'plastic',
                        child: Text('Plastik'),
                      ),
                      DropdownMenuItem(value: 'paper', child: Text('Kertas')),
                      DropdownMenuItem(value: 'other', child: Text('Lainnya')),
                    ],
                    onChanged: (value) =>
                        setState(() => _category = value ?? ''),
                    validator: (value) =>
                        value == null ? 'Pilih kategori sampah.' : null,
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    controller: _description,
                    minLines: 3,
                    maxLines: 5,
                    decoration: const InputDecoration(
                      labelText: 'Keterangan',
                      hintText: 'Jelaskan lokasi dan kondisi tumpukan sampah.',
                      alignLabelWithHint: true,
                    ),
                    validator: (value) => value == null || value.trim().isEmpty
                        ? 'Keterangan wajib diisi.'
                        : null,
                  ),
                  const SizedBox(height: 18),
                  OutlinedButton.icon(
                    onPressed: _locating ? null : _getLocation,
                    icon: _locating
                        ? const SizedBox(
                            width: 17,
                            height: 17,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.my_location_outlined),
                    label: Text(
                      _latitude == null
                          ? 'Gunakan lokasi GPS'
                          : 'Lokasi ditemukan (${_latitude!.toStringAsFixed(4)}, ${_longitude!.toStringAsFixed(4)})',
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _address,
                    maxLength: 500,
                    textCapitalization: TextCapitalization.sentences,
                    decoration: const InputDecoration(
                      labelText: 'Alamat atau patokan (opsional)',
                      hintText: 'Contoh: dekat gerbang taman',
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 8),
                    _InlineMessage(message: _error!, isError: true),
                  ],
                  const SizedBox(height: 18),
                  FilledButton.icon(
                    onPressed: _submitting ? null : _submit,
                    style: FilledButton.styleFrom(
                      backgroundColor: _forest,
                      minimumSize: const Size.fromHeight(52),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
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
                        : const Icon(Icons.send_outlined),
                    label: Text(
                      _submitting ? 'Mengirim laporan...' : 'Kirim laporan',
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
      if (cameras.isEmpty) {
        throw StateError('Tidak ada kamera yang tersedia.');
      }
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
    } on CameraException catch (error, stackTrace) {
      debugPrint('Gagal menginisialisasi kamera: $error\n$stackTrace');
      await controller?.dispose();
      if (mounted) {
        setState(() {
          _initializing = false;
          _error = 'Kamera tidak dapat dibuka. Periksa izin kamera perangkat.';
        });
      }
    } catch (error, stackTrace) {
      debugPrint('Gagal menginisialisasi kamera: $error\n$stackTrace');
      await controller?.dispose();
      if (mounted) {
        setState(() {
          _initializing = false;
          _error = 'Kamera tidak tersedia pada perangkat ini.';
        });
      }
    }
  }

  Future<void> _switchCamera() async {
    final controller = _controller;
    if (controller == null || _initializing || _capturing) return;
    final currentDirection = controller.description.lensDirection;
    final otherCameras = _cameras
        .where((camera) => camera.lensDirection != currentDirection)
        .toList();
    if (otherCameras.isEmpty) return;

    setState(() {
      _controller = null;
      _initializing = true;
    });
    await controller.dispose();
    await _initializeCamera(preferredCamera: otherCameras.first);
  }

  Future<void> _capturePhoto() async {
    final controller = _controller;
    if (controller == null || _capturing || _initializing) return;
    setState(() => _capturing = true);
    try {
      final image = await controller.takePicture();
      if (mounted) Navigator.of(context).pop(image);
    } on CameraException catch (error, stackTrace) {
      debugPrint('Gagal mengambil foto: $error\n$stackTrace');
      if (mounted) {
        setState(() {
          _capturing = false;
          _error = 'Foto gagal diambil. Silakan coba kembali.';
        });
      }
    } catch (error, stackTrace) {
      debugPrint('Gagal mengambil foto: $error\n$stackTrace');
      if (mounted) {
        setState(() {
          _capturing = false;
          _error = 'Foto gagal diambil. Silakan coba kembali.';
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;
    final canFlip = _cameras.any(
      (camera) =>
          controller != null &&
          camera.lensDirection != controller.description.lensDirection,
    );

    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        title: const Text('Ambil foto'),
        actions: [
          if (canFlip)
            IconButton(
              tooltip: 'Balik kamera',
              onPressed: _initializing || _capturing ? null : _switchCamera,
              icon: const Icon(Icons.flip_camera_android_outlined),
            ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: Center(
                child: _initializing
                    ? const CircularProgressIndicator(color: Colors.white)
                    : controller != null
                    ? Transform.flip(
                        flipX:
                            !kIsWeb &&
                            controller.description.lensDirection ==
                                CameraLensDirection.front,
                        child: CameraPreview(controller),
                      )
                    : Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              _error ?? 'Kamera tidak tersedia.',
                              textAlign: TextAlign.center,
                              style: const TextStyle(color: Colors.white),
                            ),
                            const SizedBox(height: 16),
                            FilledButton.icon(
                              onPressed: _initializeCamera,
                              icon: const Icon(Icons.refresh),
                              label: const Text('Coba lagi'),
                            ),
                          ],
                        ),
                      ),
              ),
            ),
            if (_error != null && controller != null)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Text(
                  _error!,
                  style: const TextStyle(color: Colors.white),
                  textAlign: TextAlign.center,
                ),
              ),
            Padding(
              padding: const EdgeInsets.all(24),
              child: IconButton.filled(
                tooltip: 'Ambil foto',
                onPressed: _initializing || _capturing || controller == null
                    ? null
                    : _capturePhoto,
                icon: _capturing
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.camera_alt_outlined),
                iconSize: 32,
                style: IconButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: _forest,
                  disabledBackgroundColor: Colors.white38,
                  minimumSize: const Size(68, 68),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PhotoPicker extends StatelessWidget {
  const _PhotoPicker({
    required this.image,
    required this.onCamera,
    required this.onGallery,
  });

  final XFile? image;
  final VoidCallback onCamera;
  final VoidCallback onGallery;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: const Color(0xFFE8ECE5),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFDDE2D9)),
      ),
      child: Column(
        children: [
          if (image != null)
            FutureBuilder<Uint8List>(
              future: image!.readAsBytes(),
              builder: (context, snapshot) => snapshot.hasData
                  ? Image.memory(
                      snapshot.data!,
                      height: 220,
                      width: double.infinity,
                      fit: BoxFit.cover,
                    )
                  : const SizedBox(
                      height: 220,
                      child: Center(child: CircularProgressIndicator()),
                    ),
            )
          else
            const SizedBox(
              height: 164,
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.photo_camera_back_outlined,
                      size: 38,
                      color: _leaf,
                    ),
                    SizedBox(height: 10),
                    Text('Tambahkan foto kondisi sampah'),
                    SizedBox(height: 4),
                    Text(
                      'JPG, PNG, atau WEBP · Maks. 10 MB',
                      style: TextStyle(fontSize: 12),
                    ),
                  ],
                ),
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(10),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: onCamera,
                    icon: const Icon(Icons.camera_alt_outlined),
                    label: const Text('Kamera'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: onGallery,
                    icon: const Icon(Icons.photo_library_outlined),
                    label: const Text('Galeri'),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _AiResultCard extends StatelessWidget {
  const _AiResultCard({required this.result});

  final Map<String, dynamic> result;

  @override
  Widget build(BuildContext context) {
    final category = result['category']?.toString() ?? '';
    final confidence = (result['confidence'] as num?)?.toDouble();
    final recommendation = result['recommendation'];

    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: const Color(0xFFE6F0E9),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Prediksi: ${WasteReport.categoryLabel(category)}${confidence == null ? '' : ' · ${confidence.toStringAsFixed(1)}%'}',
            style: const TextStyle(fontWeight: FontWeight.w700, color: _forest),
          ),
          if (recommendation is Map<String, dynamic> &&
              recommendation['action'] != null) ...[
            const SizedBox(height: 6),
            Text(
              recommendation['action'].toString(),
              style: const TextStyle(color: Color(0xFF48584E), height: 1.4),
            ),
          ],
        ],
      ),
    );
  }
}

class _WelcomePanel extends StatelessWidget {
  const _WelcomePanel({required this.name, required this.isAdmin});

  final String name;
  final bool isAdmin;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: _forest,
        borderRadius: BorderRadius.circular(22),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isAdmin ? 'Ruang pemantauan' : 'Halo, $name',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 21,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 7),
                Text(
                  isAdmin
                      ? 'Pantau laporan warga melalui dashboard web admin.'
                      : 'Lingkungan bersih dimulai dari laporan yang jelas.',
                  style: const TextStyle(
                    color: Color(0xFFD7E5D9),
                    height: 1.45,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 16),
          const Icon(Icons.eco_outlined, color: Color(0xFFA8D7B5), size: 44),
        ],
      ),
    );
  }
}

class _StatsRow extends StatelessWidget {
  const _StatsRow({required this.reports});

  final List<WasteReport> reports;

  @override
  Widget build(BuildContext context) {
    final pending = reports
        .where((report) => report.status == 'pending')
        .length;
    final completed = reports
        .where((report) => report.status == 'completed')
        .length;

    return Row(
      children: [
        Expanded(
          child: _StatCard(
            label: 'Semua laporan',
            value: reports.length,
            icon: Icons.receipt_long_outlined,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _StatCard(
            label: 'Menunggu',
            value: pending,
            icon: Icons.hourglass_bottom_rounded,
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _StatCard(
            label: 'Selesai',
            value: completed,
            icon: Icons.task_alt_rounded,
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
    required this.icon,
  });

  final String label;
  final int value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE6E8E2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 19, color: _leaf),
          const SizedBox(height: 12),
          Text(
            '$value',
            style: const TextStyle(fontSize: 23, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 3),
          Text(
            label,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 11, color: Color(0xFF66716B)),
          ),
        ],
      ),
    );
  }
}

class _SectionHeading extends StatelessWidget {
  const _SectionHeading({required this.title, this.action});

  final String title;
  final Widget? action;

  @override
  Widget build(BuildContext context) => Row(
    mainAxisAlignment: MainAxisAlignment.spaceBetween,
    children: [
      Text(
        title,
        style: const TextStyle(
          fontSize: 17,
          fontWeight: FontWeight.w800,
          color: _ink,
        ),
      ),
      ?action,
    ],
  );
}

class _ReportTile extends StatelessWidget {
  const _ReportTile({required this.report});

  final WasteReport report;

  Color _statusColor() => switch (report.status) {
    'pending' => const Color(0xFF8A6514),
    'in_progress' => const Color(0xFF2D6383),
    'completed' => const Color(0xFF2F704D),
    'rejected' => const Color(0xFF9A4542),
    _ => _ink,
  };

  @override
  Widget build(BuildContext context) {
    final imageUrl = _reportImageUrl(report.imagePath);
    final date = report.createdAt;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE6E8E2)),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: SizedBox(
              width: 68,
              height: 68,
              child: imageUrl == null
                  ? const ColoredBox(
                      color: Color(0xFFE8ECE5),
                      child: Icon(Icons.delete_outline, color: _leaf),
                    )
                  : Image.network(
                      imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) => const ColoredBox(
                        color: Color(0xFFE8ECE5),
                        child: Icon(Icons.broken_image_outlined, color: _leaf),
                      ),
                    ),
            ),
          ),
          const SizedBox(width: 13),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  report.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: _ink,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  '${WasteReport.categoryLabel(report.category)}${date == null ? '' : ' · ${date.day}/${date.month}/${date.year}'}',
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF707A74),
                  ),
                ),
                const SizedBox(height: 7),
                DecoratedBox(
                  decoration: BoxDecoration(
                    color: _statusColor().withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(30),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 9,
                      vertical: 4,
                    ),
                    child: Text(
                      WasteReport.statusLabel(report.status),
                      style: TextStyle(
                        color: _statusColor(),
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
              ],
            ),
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
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF3D9),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFEBD9AA)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Verifikasi email untuk melanjutkan',
            style: TextStyle(
              fontWeight: FontWeight.w800,
              color: Color(0xFF6D5016),
            ),
          ),
          const SizedBox(height: 5),
          Text(
            'Tautan sudah dikirim ke $email. Laporan baru dan riwayat laporan tersedia setelah verifikasi.',
            style: const TextStyle(
              fontSize: 12,
              height: 1.45,
              color: Color(0xFF6D5E3E),
            ),
          ),
          const SizedBox(height: 9),
          Wrap(
            spacing: 8,
            children: [
              if (busy)
                const Padding(
                  padding: EdgeInsets.all(8),
                  child: SizedBox(
                    width: 17,
                    height: 17,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                )
              else ...[
                FilledButton.tonal(
                  onPressed: onVerify,
                  child: const Text('Buka tautan verifikasi'),
                ),
                TextButton(
                  onPressed: onResend,
                  child: const Text('Kirim ulang'),
                ),
                TextButton(
                  onPressed: onCheck,
                  child: const Text('Saya sudah verifikasi'),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _EmptyReports extends StatelessWidget {
  const _EmptyReports();

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(26),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(18),
      border: Border.all(color: const Color(0xFFE6E8E2)),
    ),
    child: const Column(
      children: [
        Icon(Icons.inbox_outlined, size: 34, color: _leaf),
        SizedBox(height: 9),
        Text(
          'Belum ada laporan',
          style: TextStyle(fontWeight: FontWeight.w700),
        ),
        SizedBox(height: 4),
        Text(
          'Laporan yang Anda kirim akan tampil di sini.',
          style: TextStyle(fontSize: 12, color: Color(0xFF707A74)),
        ),
      ],
    ),
  );
}

class _ErrorPanel extends StatelessWidget {
  const _ErrorPanel({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(18),
    decoration: BoxDecoration(
      color: const Color(0xFFFFEDEC),
      borderRadius: BorderRadius.circular(16),
    ),
    child: Column(
      children: [
        Text(
          message,
          textAlign: TextAlign.center,
          style: const TextStyle(color: Color(0xFF853D38)),
        ),
        const SizedBox(height: 8),
        TextButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh),
          label: const Text('Coba lagi'),
        ),
      ],
    ),
  );
}

class _InfoPanel extends StatelessWidget {
  const _InfoPanel({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(18),
    decoration: BoxDecoration(
      color: const Color(0xFFE6F0E9),
      borderRadius: BorderRadius.circular(16),
    ),
    child: Text(
      message,
      textAlign: TextAlign.center,
      style: const TextStyle(color: _forest, height: 1.45),
    ),
  );
}

class _InlineMessage extends StatelessWidget {
  const _InlineMessage({required this.message, required this.isError});

  final String message;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    final color = isError ? const Color(0xFF9A4542) : _leaf;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(message, style: TextStyle(color: color, fontSize: 13)),
    );
  }
}

class _BrandMark extends StatelessWidget {
  const _BrandMark();

  @override
  Widget build(BuildContext context) => Row(
    children: [
      Container(
        width: 44,
        height: 44,
        decoration: BoxDecoration(
          color: _forest,
          borderRadius: BorderRadius.circular(14),
        ),
        child: const Icon(Icons.recycling, color: Colors.white),
      ),
      const SizedBox(width: 11),
      const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'SWMS',
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w900,
              color: _forest,
            ),
          ),
          Text(
            'Smart Waste · Warga',
            style: TextStyle(fontSize: 11, color: Color(0xFF66716B)),
          ),
        ],
      ),
    ],
  );
}
