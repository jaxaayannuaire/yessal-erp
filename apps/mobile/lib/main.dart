import 'package:flutter/material.dart';

import 'core/services/auth_service.dart';
import 'models/user.dart';
import 'screens/auth/login_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const YessalApp());
}

class YessalApp extends StatelessWidget {
  const YessalApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Yessal ERP',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: Colors.blue,
        ),
        useMaterial3: true,
      ),
      home: const AppInitializer(),
    );
  }
}

class AppInitializer extends StatefulWidget {
  const AppInitializer({super.key});

  @override
  State<AppInitializer> createState() => _AppInitializerState();
}

class _AppInitializerState extends State<AppInitializer> {
  final _authService = AuthService();

  bool _isLoading = true;
  User? _user;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    final user = await _authService.me();

    if (!mounted) return;

    setState(() {
      _user = user;
      _isLoading = false;
    });
  }

  void _handleLogin(User user) {
    setState(() {
      _user = user;
    });
  }

  Future<void> _handleLogout() async {
    await _authService.logout();

    if (!mounted) return;

    setState(() {
      _user = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    if (_user == null) {
      return LoginScreen(
        onLogin: _handleLogin,
      );
    }

    return DashboardScreen(
      user: _user!,
      onLogout: _handleLogout,
    );
  }
}

class DashboardScreen extends StatelessWidget {
  final User user;
  final Future<void> Function() onLogout;

  const DashboardScreen({
    super.key,
    required this.user,
    required this.onLogout,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Yessal ERP'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await onLogout();
            },
          ),
        ],
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.account_circle,
                size: 100,
              ),
              const SizedBox(height: 24),
              Text(
                'Bienvenue ${user.name}',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 8),
              Text(user.email),
              const SizedBox(height: 32),
              const Text(
                'Connexion API Laravel réussie.',
              ),
            ],
          ),
        ),
      ),
    );
  }
}