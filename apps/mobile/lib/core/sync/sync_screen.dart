import 'package:flutter/material.dart';

import 'manual_sync_controller.dart';
import 'outbox_repository.dart';
import 'sync_issues_screen.dart';
import 'sync_outbox_service.dart';

class SyncIndicator extends StatelessWidget {
  const SyncIndicator({
    super.key,
    required this.organizationId,
    required this.outbox,
    required this.service,
  });

  final int organizationId;
  final OutboxRepository outbox;
  final SyncOutboxService service;

  @override
  Widget build(BuildContext context) => FutureBuilder<List<OutboxEvent>>(
    future: outbox.listByOrganization(organizationId),
    builder: (_, snapshot) {
      final events = snapshot.data ?? const <OutboxEvent>[];
      final queued = events
          .where((event) => event.status == OutboxStatus.queued)
          .length;
      final intervention = events
          .where(
            (event) =>
                event.status == OutboxStatus.conflict ||
                event.status == OutboxStatus.rejected ||
                event.status == OutboxStatus.failed,
          )
          .length;
      return ActionChip(
        avatar: Icon(intervention > 0 ? Icons.error_outline : Icons.sync),
        label: Text(
          'Sync : $queued en attente${intervention > 0 ? ' • $intervention à traiter' : ''}',
        ),
        onPressed: () => Navigator.of(context).push(
          MaterialPageRoute<void>(
            builder: (_) => SyncScreen(
              organizationId: organizationId,
              outbox: outbox,
              service: service,
            ),
          ),
        ),
      );
    },
  );
}

class SyncScreen extends StatefulWidget {
  const SyncScreen({
    super.key,
    required this.organizationId,
    required this.outbox,
    required this.service,
  });

  final int organizationId;
  final OutboxRepository outbox;
  final SyncOutboxService service;

  @override
  State<SyncScreen> createState() => _SyncScreenState();
}

class _SyncScreenState extends State<SyncScreen> {
  late final ManualSyncController controller = ManualSyncController(
    widget.service,
    widget.outbox,
  );

  @override
  void initState() {
    super.initState();
    controller.load(widget.organizationId);
  }

  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Synchronisation')),
    body: Padding(
      padding: const EdgeInsets.all(20),
      child: AnimatedBuilder(
        animation: controller,
        builder: (_, _) => Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('État : ${_label(controller.state)}'),
            const SizedBox(height: 16),
            _row('En attente', controller.queued),
            _row('Conflits', controller.conflict),
            _row('Rejetés', controller.rejected),
            _row('Erreurs', controller.failed),
            const SizedBox(height: 20),
            const Text(
              'Éléments à traiter',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            if (_issueCount == 0)
              const Text('Aucun problème de synchronisation.')
            else
              OutlinedButton.icon(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(
                    builder: (_) => SyncIssuesScreen(
                      organizationId: widget.organizationId,
                      outbox: widget.outbox,
                    ),
                  ),
                ),
                icon: const Icon(Icons.error_outline),
                label: Text('Voir les $_issueCount éléments à traiter'),
              ),
            const SizedBox(height: 12),
            if (controller.queued == 0)
              const Text('Aucune opération en attente'),
            FilledButton.icon(
              onPressed: controller.isSyncing || controller.queued == 0
                  ? null
                  : () => controller.sync(widget.organizationId),
              icon: controller.isSyncing
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.sync),
              label: const Text('Synchroniser maintenant'),
            ),
            if (controller.error != null) ...[
              const SizedBox(height: 12),
              Text(
                controller.error!.contains('connexion') ||
                        controller.error!.contains('Connexion')
                    ? 'Synchronisation interrompue. Vérifiez votre connexion et réessayez.'
                    : controller.error!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            ],
          ],
        ),
      ),
    ),
  );

  Widget _row(String label, int value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 4),
    child: Text('$label      $value'),
  );

  int get _issueCount =>
      controller.conflict + controller.rejected + controller.failed;

  String _label(ManualSyncState state) => switch (state) {
    ManualSyncState.idle => 'Prêt',
    ManualSyncState.syncing => 'En cours',
    ManualSyncState.success => 'Terminé',
    ManualSyncState.error => 'Erreur',
  };
}
