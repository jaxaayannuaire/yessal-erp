import 'dart:async';

import 'package:flutter/material.dart';

import 'app/app_session.dart';
import 'core/api/api_client.dart';
import 'core/auth/auth_repository.dart';
import 'core/bootstrap/bootstrap_repository.dart';
import 'core/database/app_database.dart';
import 'core/database/local_repositories.dart';
import 'core/models/caisse_models.dart';
import 'core/storage/local_cache_store.dart';
import 'core/storage/token_storage.dart';
import 'features/catalogue/local_catalogue_screens.dart';
import 'features/sales/sale_repository.dart';
import 'features/sales/sales_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  final tokens = TokenStorage();
  final api = ApiClient(tokenStorage: tokens);
  final cache = LocalCacheStore();
  final database = AppDatabase.defaults();
  final session = AppSession(
    api,
    AuthRepository(api, tokens),
    BootstrapRepository(api, BootstrapLocalRepository(database)),
    cache,
  );
  api.onUnauthorized = session.expireSession;

  runApp(
    YessalCaisseApp(
      session: session,
      database: database,
      sales: SaleRepository(api),
    ),
  );
}

class YessalCaisseApp extends StatefulWidget {
  const YessalCaisseApp({
    super.key,
    required this.session,
    required this.database,
    required this.sales,
  });

  final AppSession session;
  final AppDatabase database;
  final SaleRepository sales;

  @override
  State<YessalCaisseApp> createState() => _YessalCaisseAppState();
}

class _YessalCaisseAppState extends State<YessalCaisseApp> {
  @override
  void initState() {
    super.initState();
    widget.session.restore();
  }

  @override
  void dispose() {
    widget.session.dispose();
    unawaited(widget.database.close());
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.session,
      builder: (_, _) {
        return MaterialApp(
          title: 'Yessal Caisse',
          debugShowCheckedModeBanner: false,
          theme: ThemeData(
            colorScheme: ColorScheme.fromSeed(
              seedColor: const Color(0xff0b6b53),
            ),
            useMaterial3: true,
          ),
          home: _route(widget.session),
        );
      },
    );
  }

  Widget _route(AppSession session) {
    if (session.isLoading) {
      return const _Loading();
    }
    if (session.user == null) {
      return LoginScreen(session: session);
    }
    if (session.organization == null) {
      return SelectionScreen(
        title: 'Organisation',
        items: session.organizations,
        choose: (item) => session.selectOrganization(item as Organization),
      );
    }
    if (session.shop == null) {
      return FutureSelectionScreen(
        title: 'Boutique',
        load: session.availableShops,
        choose: (item) => session.selectShop(item as CaisseEntity),
      );
    }
    if (session.terminal == null) {
      return FutureSelectionScreen(
        title: 'Terminal',
        load: session.availableTerminals,
        choose: (item) => session.selectTerminal(item as CaisseEntity),
      );
    }

    return HomeScreen(
      session: session,
      database: widget.database,
      sales: widget.sales,
    );
  }
}

class _Loading extends StatelessWidget {
  const _Loading();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.session});

  final AppSession session;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController();
  final password = TextEditingController();
  bool loading = false;

  @override
  void dispose() {
    email.dispose();
    password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.point_of_sale, size: 64),
                const SizedBox(height: 16),
                Text(
                  'Yessal Caisse',
                  style: Theme.of(context).textTheme.headlineMedium,
                ),
                const SizedBox(height: 24),
                TextField(
                  controller: email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'E-mail'),
                ),
                TextField(
                  controller: password,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'Mot de passe'),
                ),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: loading ? null : _login,
                  child: Text(loading ? 'Connexion…' : 'Se connecter'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _login() async {
    setState(() => loading = true);
    try {
      await widget.session.login(email.text.trim(), password.text);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.toString())));
      }
    } finally {
      if (mounted) {
        setState(() => loading = false);
      }
    }
  }
}

class SelectionScreen extends StatelessWidget {
  const SelectionScreen({
    super.key,
    required this.title,
    required this.items,
    required this.choose,
  });

  final String title;
  final List<dynamic> items;
  final Future<void> Function(dynamic) choose;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Choisir $title')),
      body: ListView(
        children: items
            .map(
              (item) => ListTile(
                title: Text(item.name),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => choose(item),
              ),
            )
            .toList(),
      ),
    );
  }
}

class FutureSelectionScreen extends StatefulWidget {
  const FutureSelectionScreen({
    super.key,
    required this.title,
    required this.load,
    required this.choose,
  });

  final String title;
  final Future<List<dynamic>> Function() load;
  final Future<void> Function(dynamic) choose;

  @override
  State<FutureSelectionScreen> createState() => _FutureSelectionScreenState();
}

class _FutureSelectionScreenState extends State<FutureSelectionScreen> {
  late final Future<List<dynamic>> future = widget.load();

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<dynamic>>(
      future: future,
      builder: (_, snapshot) {
        if (snapshot.hasError) {
          return Scaffold(
            body: Center(
              child: Text(
                'Impossible de charger ${widget.title.toLowerCase()}',
              ),
            ),
          );
        }
        if (!snapshot.hasData) {
          return const _Loading();
        }

        return SelectionScreen(
          title: widget.title,
          items: snapshot.data!,
          choose: widget.choose,
        );
      },
    );
  }
}

class HomeScreen extends StatelessWidget {
  const HomeScreen({
    super.key,
    required this.session,
    required this.database,
    required this.sales,
  });

  final AppSession session;
  final AppDatabase database;
  final SaleRepository sales;

  @override
  Widget build(BuildContext context) {
    final isCaisseAllowed =
        session.bootstrapData?.entitlements.allowed ?? false;
    final statusLabel = session.isOffline
        ? 'Mode hors ligne limité'
        : isCaisseAllowed
        ? 'Caisse autorisée'
        : 'Accès Caisse bloqué';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Yessal Caisse'),
        actions: [
          IconButton(icon: const Icon(Icons.logout), onPressed: session.logout),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              session.organization!.name,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            Text('${session.shop!.name} • ${session.terminal!.name}'),
            const SizedBox(height: 12),
            Chip(label: Text(statusLabel)),
            const SizedBox(height: 20),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _shortcut(
                  context,
                  'Vente',
                  () => _open(
                    context,
                    SalesScreen(
                      session: session,
                      database: database,
                      repository: sales,
                    ),
                  ),
                ),
                _shortcut(
                  context,
                  'Produits',
                  () => _open(
                    context,
                    ProductListScreen(database: database, scope: _scope()),
                  ),
                ),
                _shortcut(
                  context,
                  'Clients',
                  () => _open(
                    context,
                    CustomerListScreen(database: database, scope: _scope()),
                  ),
                ),
                _shortcut(
                  context,
                  'Stock',
                  () => _open(
                    context,
                    StockScreen(database: database, scope: _scope()),
                  ),
                ),
                _shortcut(context, 'Caisse'),
                _shortcut(context, 'Rapports'),
              ],
            ),
          ],
        ),
      ),
    );
  }

  LocalScope _scope() => LocalScope(
    organizationId: session.organization!.id,
    shopId: session.shop!.id,
    isOffline: session.isOffline,
    onRefresh: session.bootstrap,
  );

  ActionChip _shortcut(
    BuildContext context,
    String label, [
    VoidCallback? onPressed,
  ]) => ActionChip(
    label: Text(label),
    onPressed: onPressed ?? () => _notReady(context, label),
  );

  void _open(BuildContext context, Widget screen) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => screen));
  }

  void _notReady(BuildContext context, String label) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$label sera disponible dans un prochain lot.')),
    );
  }
}
