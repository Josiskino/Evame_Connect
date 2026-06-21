import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/constants/app_fonts.dart';
import '../../../core/constants/app_images.dart';
import '../../../core/constants/design_tokens.dart';
import '../../viewmodels/auth/auth_viewmodel.dart';
import '../../viewmodels/auth/params/login_ui_params.dart';
import '../../viewmodels/auth/state/auth_state.dart';
import '../../widgets/app_snackbar.dart';
import '../../widgets/app_text_field.dart';
import '../../widgets/primary_button.dart';

/// Écran de connexion — PLACEHOLDER fonctionnel.
/// À remplacer/redesigner ; il consomme déjà [AuthViewModel].
class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController(text: 'sav@evame.com');
  final _password = TextEditingController(text: 'password');

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  void _submit() {
    ref.read(authViewModelProvider.notifier).login(
          LoginUiParams(email: _email.text.trim(), password: _password.text),
        );
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authViewModelProvider);

    ref.listen(authViewModelProvider, (_, next) {
      if (next is AuthError) AppSnackbar.error(context, next.message);
    });

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(DesignTokens.spaceXl),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Image.asset(AppImages.splash, height: 110),
                  const SizedBox(height: DesignTokens.spaceL),
                  Text(
                    'Connexion technicien',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontFamily: AppFonts.bricolage,
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: DesignTokens.spaceXxxl),
                  AppTextField(controller: _email, label: 'E-mail', keyboardType: TextInputType.emailAddress),
                  const SizedBox(height: DesignTokens.spaceL),
                  AppTextField(controller: _password, label: 'Mot de passe', obscureText: true),
                  const SizedBox(height: DesignTokens.spaceXl),
                  PrimaryButton(
                    label: 'Se connecter',
                    isLoading: state is AuthLoading,
                    onPressed: _submit,
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
