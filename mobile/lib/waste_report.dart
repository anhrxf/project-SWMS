class WasteReport {
  const WasteReport({
    required this.id,
    required this.title,
    required this.description,
    required this.category,
    required this.status,
    required this.imagePath,
    required this.createdAt,
    this.address,
    this.latitude,
    this.longitude,
    this.confidence,
  });

  final int id;
  final String title;
  final String description;
  final String category;
  final String status;
  final String imagePath;
  final DateTime? createdAt;
  final String? address;
  final double? latitude;
  final double? longitude;
  final double? confidence;

  factory WasteReport.fromJson(Map<String, dynamic> json) {
    final id = json['id'];
    if (id is! int) throw const FormatException('ID laporan tidak valid.');

    return WasteReport(
      id: id,
      title: json['title']?.toString() ?? 'Laporan tanpa judul',
      description: json['description']?.toString() ?? '',
      category: json['waste_category']?.toString() ?? 'other',
      status: json['status']?.toString() ?? 'pending',
      imagePath: json['image_url']?.toString() ?? json['image_path']?.toString() ?? '',
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? ''),
      address: json['location_address']?.toString(),
      latitude: _asDouble(json['latitude']),
      longitude: _asDouble(json['longitude']),
      confidence: _asDouble(json['ai_confidence']),
    );
  }

  static double? _asDouble(dynamic value) {
    if (value is num) return value.toDouble();
    return double.tryParse(value?.toString() ?? '');
  }

  static String categoryLabel(String category) => switch (category) {
    'organic' => 'Organik',
    'plastic' => 'Plastik',
    'paper' => 'Kertas',
    _ => 'Lainnya',
  };

  static String statusLabel(String status) => switch (status) {
    'pending' => 'Menunggu',
    'in_progress' => 'Diproses',
    'completed' => 'Selesai',
    'rejected' => 'Ditolak',
    _ => status,
  };
}
