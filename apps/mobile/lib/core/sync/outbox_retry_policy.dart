import 'outbox_repository.dart';

class OutboxRetryDecision {
  const OutboxRetryDecision({required this.allowed, required this.reason});

  final bool allowed;
  final String reason;
}

class OutboxRetryPolicy {
  const OutboxRetryPolicy();

  OutboxRetryDecision evaluate(OutboxEvent event) {
    if (event.status == OutboxStatus.conflict) {
      return const OutboxRetryDecision(
        allowed: false,
        reason: 'Ce conflit nécessite une correction métier avant toute nouvelle tentative.',
      );
    }
    if (event.status == OutboxStatus.rejected) {
      return const OutboxRetryDecision(
        allowed: false,
        reason: 'Cette opération a été rejetée et doit être corrigée avant d’être renvoyée.',
      );
    }
    if (event.failureKind == OutboxFailureKind.serverProcessing) {
      return const OutboxRetryDecision(
        allowed: false,
        reason: 'Le serveur a déjà enregistré cet échec. Une intervention serveur est nécessaire.',
      );
    }
    if (event.failureKind == OutboxFailureKind.localPayloadInvalid) {
      return const OutboxRetryDecision(
        allowed: false,
        reason: 'Les données locales de cette opération sont invalides.',
      );
    }
    if (event.failureKind == OutboxFailureKind.protocolInvalid) {
      return const OutboxRetryDecision(
        allowed: false,
        reason: 'La réponse de synchronisation n’est pas compatible avec cette version de l’application.',
      );
    }
    final code = event.httpStatusCode;
    final allowed =
        event.status == OutboxStatus.failed &&
        event.failureKind == OutboxFailureKind.http &&
        code != null &&
        (code == 408 || code == 429 || (code >= 500 && code <= 599));
    return OutboxRetryDecision(
      allowed: allowed,
      reason: allowed
          ? 'Cet échec technique peut être réessayé.'
          : 'La cause de cet échec ne permet pas un réessai sécurisé.',
    );
  }
}
