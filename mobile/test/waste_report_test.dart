import 'package:flutter_test/flutter_test.dart';

import 'package:swms_mobile/waste_report.dart';

void main() {
  test('parses API report fields and numeric strings', () {
    final report = WasteReport.fromJson({
      'id': 42,
      'title': 'Sampah di taman',
      'description': 'Dekat pintu masuk',
      'waste_category': 'plastic',
      'status': 'in_progress',
      'image_path': 'reports/42.jpg',
      'created_at': '2026-01-15T10:30:00Z',
      'latitude': '-6.2',
      'longitude': 106.8,
      'ai_confidence': '0.94',
    });

    expect(report.id, 42);
    expect(report.category, 'plastic');
    expect(report.status, 'in_progress');
    expect(report.latitude, -6.2);
    expect(report.longitude, 106.8);
    expect(report.confidence, 0.94);
    expect(report.createdAt, DateTime.utc(2026, 1, 15, 10, 30));
  });

  test('rejects a report without a valid id', () {
    expect(
      () => WasteReport.fromJson({'id': 'not-an-id'}),
      throwsFormatException,
    );
  });

  test('prefers the temporary image URL returned by the API', () {
    final report = WasteReport.fromJson({
      'id': 7,
      'image_path': 'reports/7.jpg',
      'image_url': 'https://swms.example/reports/7/image?signature=temporary',
    });

    expect(report.imagePath, startsWith('https://swms.example/'));
  });

  test('provides Indonesian labels for known statuses and categories', () {
    expect(WasteReport.categoryLabel('paper'), 'Kertas');
    expect(WasteReport.categoryLabel('other'), 'Campuran');
    expect(WasteReport.statusLabel('completed'), 'Selesai');
  });
}
