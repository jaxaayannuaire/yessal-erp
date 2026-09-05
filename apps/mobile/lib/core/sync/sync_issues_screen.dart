import 'dart:convert';

import 'package:flutter/material.dart';

import 'outbox_repository.dart';
import 'outbox_retry_policy.dart';
import 'issue_retry_controller.dart';
import 'sync_outbox_service.dart';

class SyncIssuesScreen extends StatefulWidget {
  const SyncIssuesScreen({
    super.key,
    required this.organizationId,
    required this.outbox,
    this.service,
  });

  final int organizationId;
  final OutboxRepository outbox;
  final SyncOutboxService? service;

  @override
  State<SyncIssuesScreen> createState() => _SyncIssuesScreenState();
}

class _SyncIssuesScreenState extends State<SyncIssuesScreen> {
  late Future<List<OutboxEvent>> _issues;
  OutboxStatus? _filter;

  @override
  void initState() {
    super.initState();
    _issues = widget.outbox.listIssues(widget.organizationId);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Problèmes de synchronisation')),
    body: FutureBuilder<List<OutboxEvent>>(
      future: _issues,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        final issues = snapshot.data ?? const <OutboxEvent>[];
        final filtered = _filter == null
            ? issues
            : issues.where((event) => event.status == _filter).toList();
        return Column(
          children: [
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  _filterChip(label: 'Tous', status: null),
                  _filterChip(label: 'Conflits', status: OutboxStatus.conflict),
                  _filterChip(label: 'Rejetés', status: OutboxStatus.rejected),
                  _filterChip(label: 'Erreurs', status: OutboxStatus.failed),
                ],
              ),
            ),
            Expanded(
              child: filtered.isEmpty
                  ? const Center(
                      child: Text('Aucun problème de synchronisation.'),
                    )
                  : ListView.separated(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      itemCount: filtered.length,
                      separatorBuilder: (_, _) => const Divider(),
                      itemBuilder: (context, index) {
                        final event = filtered[index];
                        return ListTile(
                          title: Text(SyncIssueLabels.status(event.status)),
                          subtitle: Text(
                            '${SyncIssueLabels.entityType(event.entityType)} • '
                            '${SyncIssueLabels.action(event.action)}\n'
                            '${SyncIssueLabels.date(event.occurredAt)}\n'
                            '${SyncIssueLabels.error(event.lastError)}',
                          ),
                          isThreeLine: true,
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () async {
                            await Navigator.of(context).push(
                              MaterialPageRoute<void>(
                                builder: (_) => SyncIssueDetailScreen(
                                  organizationId: widget.organizationId,
                                  eventId: event.id,
                                  outbox: widget.outbox,
                                  service: widget.service,
                                ),
                              ),
                            );
                            if (mounted) {
                              setState(() {
                                _issues = widget.outbox.listIssues(
                                  widget.organizationId,
                                );
                              });
                            }
                          },
                        );
                      },
                    ),
            ),
          ],
        );
      },
    ),
  );

  Widget _filterChip({required String label, required OutboxStatus? status}) =>
      Padding(
        padding: const EdgeInsets.only(right: 8),
        child: ChoiceChip(
          label: Text(label),
          selected: _filter == status,
          onSelected: (_) => setState(() => _filter = status),
        ),
      );
}

class SyncIssueDetailScreen extends StatefulWidget {
  const SyncIssueDetailScreen({
    super.key,
    required this.organizationId,
    required this.eventId,
    required this.outbox,
    this.service,
  });

  final int organizationId;
  final int eventId;
  final OutboxRepository outbox;
  final SyncOutboxService? service;

  @override
  State<SyncIssueDetailScreen> createState() => _SyncIssueDetailScreenState();
}

class _SyncIssueDetailScreenState extends State<SyncIssueDetailScreen> {
  late Future<OutboxEvent?> _event;
  IssueRetryController? _retryController;
  final _retryPolicy = const OutboxRetryPolicy();

  @override
  void initState() {
    super.initState();
    _event = widget.outbox.findById(
      organizationId: widget.organizationId,
      id: widget.eventId,
    );
    if (widget.service != null) {
      _retryController = IssueRetryController(widget.service!);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Détail de synchronisation')),
    body: FutureBuilder<OutboxEvent?>(
      future: _event,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        final event = snapshot.data;
        if (event == null) {
          return const Center(
            child: Text('Événement de synchronisation introuvable.'),
          );
        }
        if (!SyncIssueLabels.isIssue(event.status)) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Text(
                event.status == OutboxStatus.applied
                    ? 'Synchronisation réussie.'
                    : event.lastError ??
                          'La synchronisation a été remise en attente.',
                textAlign: TextAlign.center,
              ),
            ),
          );
        }
        final serverDetails = SyncIssueLabels.serverDetails(
          event.serverResultJson,
        );
        final decision = _retryPolicy.evaluate(event);
        return AnimatedBuilder(
          animation: _retryController ?? Listenable.merge(const []),
          builder: (context, _) => ListView(
            padding: const EdgeInsets.all(20),
            children: [
              _field('Statut', SyncIssueLabels.status(event.status)),
              _field(
                'Type d’opération',
                SyncIssueLabels.entityType(event.entityType),
              ),
              _field('Action', SyncIssueLabels.action(event.action)),
              _field(
                'Date de l’opération',
                SyncIssueLabels.date(event.occurredAt),
              ),
              _field('Tentatives', '${event.attemptCount}'),
              _field(
                'Dernière tentative',
                SyncIssueLabels.dateOrUnavailable(event.lastAttemptAt),
              ),
              _field(
                'Message d’erreur',
                SyncIssueLabels.error(event.lastError),
              ),
              const SizedBox(height: 16),
              const Text(
                'Détails techniques',
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              _field('Identifiant de synchronisation', event.eventUuid),
              _field('Entity ID', event.entityId),
              _field('Shop ID', '${event.shopId}'),
              _field('Device ID', '${event.deviceId}'),
              for (final detail in serverDetails.entries)
                _field(detail.key, detail.value),
              const SizedBox(height: 16),
              const Text(
                'Reprise',
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              Text(decision.reason),
              if (decision.allowed && _retryController != null) ...[
                const SizedBox(height: 8),
                FilledButton.icon(
                  onPressed: _retryController!.state == IssueRetryState.retrying
                      ? null
                      : () => _confirmRetry(event),
                  icon: _retryController!.state == IssueRetryState.retrying
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.refresh),
                  label: Text(
                    _retryController!.state == IssueRetryState.retrying
                        ? 'Nouvelle tentative en cours…'
                        : 'Réessayer cette opération',
                  ),
                ),
              ],
              if (_retryController?.state == IssueRetryState.error)
                Text(_retryController!.error ?? 'La reprise a échoué.'),
            ],
          ),
        );
      },
    ),
  );

  Future<void> _confirmRetry(OutboxEvent event) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Réessayer cette opération ?'),
        content: const Text(
          'Le même identifiant de synchronisation et les mêmes données seront renvoyés au serveur.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Réessayer'),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      final result = await _retryController!.retry(
        organizationId: widget.organizationId,
        outboxId: event.id,
      );
      if (result != null && mounted) {
        setState(() {
          _event = widget.outbox.findById(
            organizationId: widget.organizationId,
            id: event.id,
          );
        });
      }
    }
  }

  Widget _field(String label, String value) => Padding(
    padding: const EdgeInsets.only(bottom: 12),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 2),
        Text(value),
      ],
    ),
  );
}

class SyncIssueLabels {
  static bool isIssue(OutboxStatus status) =>
      status == OutboxStatus.conflict ||
      status == OutboxStatus.rejected ||
      status == OutboxStatus.failed;

  static String status(OutboxStatus status) => switch (status) {
    OutboxStatus.conflict => 'Conflit',
    OutboxStatus.rejected => 'Rejeté',
    OutboxStatus.failed => 'Erreur',
    _ => status.name,
  };

  static String entityType(String value) => switch (value) {
    'sale' => 'Vente',
    'payment' => 'Paiement',
    'customer' => 'Client',
    'stock' => 'Stock',
    _ => value,
  };

  static String action(String value) => switch (value) {
    'create' => 'Création',
    'update' => 'Modification',
    'delete' => 'Suppression',
    'reverse' => 'Annulation/contrepassation',
    _ => value,
  };

  static String error(String? value) => value == null || value.trim().isEmpty
      ? 'Aucun détail supplémentaire n’a été fourni.'
      : value;

  static String dateOrUnavailable(DateTime? value) =>
      value == null ? 'Non disponible' : date(value);

  static String date(DateTime value) {
    String two(int number) => number.toString().padLeft(2, '0');
    return '${two(value.toLocal().day)}/${two(value.toLocal().month)}/'
        '${value.toLocal().year} ${two(value.toLocal().hour)}:'
        '${two(value.toLocal().minute)}';
  }

  static Map<String, String> serverDetails(String? raw) {
    if (raw == null || raw.trim().isEmpty) return const {};
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map<String, dynamic>) return const {};
      final details = <String, String>{};
      for (final key in ['code', 'status']) {
        final value = decoded[key];
        if (value != null && value is! Map && value is! List) {
          details['Serveur $key'] = '$value';
        }
      }
      return details;
    } on FormatException {
      return const {};
    }
  }
}
