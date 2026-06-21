import 'package:evame_connect/main.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('L\'application démarre sans erreur', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: EvameApp()));
    expect(find.byType(EvameApp), findsOneWidget);
  });
}
