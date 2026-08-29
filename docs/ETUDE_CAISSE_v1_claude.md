# Yessal Caisse — Étude comparative POS & conception du domaine métier

**Version : 1.0 — document d'étude**
**Date : 29 août 2026**
**Statut global : PROPOSÉE — aucune décision figée, aucune migration créée**

> Ce document ne modifie aucune décision validée dans `architecture.md`, `ROADMAP.md`, `CHANGELOG.md` ou `PACK_MODULE_MATRIX.md`.
> Il ne fixe **aucun tarif** ni **aucune limite commerciale**. Ces éléments restent réservés à l'étude commerciale et financière.
> Conformément à `CAISSE_RESEARCH_BRIEF.md` : **aucune migration métier Caisse n'est créée** avant validation de la présente étude.

---

## 0. Méthode, sources et conventions

### 0.1 Convention de marquage

Chaque affirmation du document porte une nature :

| Marque | Signification |
|---|---|
| **[FAIT]** | Vérifiable dans une source publique citée en annexe A, ou dans les documents du projet |
| **[WEB]** | Issu de la recherche web du 29/08/2026, source citée, non vérifié en environnement réel |
| **[RECO]** | Recommandation d'ingénierie de l'auteur de l'étude |
| **[HYP]** | Hypothèse à confirmer par terrain, essai ou fournisseur |

Chaque décision porte un statut :

| Statut | Signification |
|---|---|
| **VALIDÉE** | Déjà actée dans les documents du projet, reprise sans modification |
| **PROPOSÉE** | Proposition de l'étude, prête à être arbitrée |
| **À VALIDER** | Point ouvert nécessitant un arbitrage commercial, juridique ou terrain |

### 0.2 Limites de l'étude

- Les solutions propriétaires (Square, Lightspeed, Loyverse, Kyte) n'ont pas été testées en conditions réelles. Les constats reposent sur documentation éditeur et retours d'usage publiés. **[WEB]**
- Les solutions open source ou à code source livré (Odoo POS, Dolibarr/TakePOS, ERPNext, UltimatePOS) sont mieux documentées structurellement, le modèle de données étant public. **[FAIT]**
- Les tarifs des concurrents évoluent vite et ne sont pas repris ici ; ils relèvent de l'étude commerciale.
- Aucun benchmark de performance n'a été réalisé.

---

## 1. Tableau comparatif des POS

Huit solutions étudiées : **Odoo POS**, **Dolibarr / TakePOS**, **Loyverse**, **Square POS**, **Lightspeed Retail**, **Kyte**, **UltimatePOS** (thewebfosters), **ERPNext POS** (+ POS Awesome).

Le comparatif est découpé en cinq tableaux thématiques pour rester lisible.

### 1.1 Positionnement et modèle

| Solution | Nature | Cible | Déploiement | Modèle économique |
|---|---|---|---|---|
| **Odoo POS** | Module d'un ERP complet | TPE → ETI | SaaS Odoo Online, Odoo.sh, on-premise | Abonnement par app/utilisateur |
| **Dolibarr / TakePOS** | Module d'un ERP open source, livré en standard **[FAIT]** | TPE, artisans, associations | On-premise / hébergeur, PHP-MySQL | Gratuit (GPL) + modules Dolistore payants |
| **Loyverse** | POS mobile pur-cloud | Très petits commerces, cafés | SaaS uniquement, apps Android/iOS | Cœur gratuit + add-ons payants **[WEB]** |
| **Square POS** | POS + acquisition de paiement | TPE/PME de marchés desservis | SaaS + matériel propriétaire | Logiciel gratuit, revenus sur la commission d'acquisition |
| **Lightspeed Retail** | POS retail avancé | Retail multi-magasins, gros catalogues | SaaS | Abonnement par plan, par caisse, par site |
| **Kyte** | POS mobile ultra-simple + catalogue en ligne | Micro-commerce, vente sociale | SaaS, apps mobiles + web | Freemium, plans mensuels/annuels |
| **UltimatePOS** | Application PHP autonome, code source livré | TPE/PME, multi-business | On-premise, hébergement mutualisé PHP/MySQL **[WEB]** | Licence unique CodeCanyon + modules |
| **ERPNext POS** | Module d'un ERP open source | PME, retail structuré | On-premise / Frappe Cloud | Gratuit (GPL) + hébergement |

**Lecture pour Yessal.** Deux familles s'opposent nettement : les **POS-first mobiles** (Loyverse, Kyte, Square) qui gagnent sur l'adoption et l'ergonomie, et les **POS-in-ERP** (Odoo, Dolibarr, ERPNext, UltimatePOS) qui gagnent sur la profondeur comptable et l'évolutivité. Yessal Caisse est explicitement positionné comme **POS-first mobile adossé à une plateforme SaaS multi-tenant**, avec une passerelle ERP optionnelle. C'est le bon axe : c'est le segment où les ERP échouent sur l'adoption et où les POS mobiles échouent sur la comptabilité locale.

### 1.2 Catalogue, stock et achats

| Solution | Variantes | Codes-barres | Multi-emplacements | Mouvements & inventaires | Achats / fournisseurs |
|---|---|---|---|---|---|
| **Odoo POS** | Complet (attributs × valeurs) | Oui | Oui (entrepôts) | Complet, valorisation, traçabilité lot/série | Complet |
| **Dolibarr / TakePOS** | Via module Variantes | Oui | Oui (entrepôts) | Complet, inventaires, lots/séries | Complet |
| **Loyverse** | Oui, limitées ; les avis signalent la gestion des variantes comme un point faible **[WEB]** | Oui | Oui, multi-boutiques centralisées **[WEB]** | Basique gratuit ; bons de commande et valorisation via add-on payant **[WEB]** | Add-on payant |
| **Square POS** | Oui | Oui | Oui | Correct | Correct |
| **Lightspeed Retail** | Très complet : variantes matricielles, lots, numéros de série **[WEB]** | Oui | Oui, transferts inter-magasins **[WEB]** | Avancé, points de réapprovisionnement | Avancé, catalogues fournisseurs |
| **Kyte** | Limité (1 variation sur le plan intermédiaire) **[WEB]** | Oui | Limité | Basique | Non/limité |
| **UltimatePOS** | Modèles de variantes réutilisables ; calcul auto du prix de vente à partir du prix d'achat et de la marge **[WEB]** | Oui, étiquettes prédéfinies | Oui, transferts entre sites, stock d'ouverture **[WEB]** | Ajustements, alertes de stock bas, FIFO/LIFO **[WEB]** | Achats par site, partiels, échéances **[WEB]** |
| **ERPNext POS** | Complet | Oui | Oui (entrepôts) | Complet, Stock Ledger | Complet |

**Lecture pour Yessal.** Deux mécanismes d'UltimatePOS sont directement transposables et peu coûteux : le **calcul automatique du prix de vente à partir du prix d'achat et d'une marge cible**, et les **modèles de variantes réutilisables**. Ils réduisent massivement le temps de saisie du catalogue, qui est le premier point d'abandon dans le commerce informel.

### 1.3 Ventes, caisse et paiements

| Solution | Ticket / vente | Retours & avoirs | Sessions de caisse | Paiements mixtes | Vente à crédit |
|---|---|---|---|---|---|
| **Odoo POS** | Oui | Oui | Session avec ouverture/fermeture et écart | Oui | Via facture client |
| **Dolibarr / TakePOS** | Ticket → facture Dolibarr | Avoirs, y compris paiement par avoir via module tiers **[WEB]** | Oui, rapports de caisse | Oui, ventes en attente, remises **[WEB]** | Via facture impayée |
| **Loyverse** | Oui, notes fractionnées | Oui | Oui | Oui | Non natif |
| **Square POS** | Oui | Oui, mais un paiement offline en attente n'est ni annulable ni remboursable avant traitement **[WEB]** | Oui | Oui | Non natif |
| **Lightspeed Retail** | Oui | Oui | Oui | Oui | Comptes clients |
| **Kyte** | Oui, commande partageable | Basique | Basique | Basique | Basique |
| **UltimatePOS** | Écran POS, express checkout, brouillon/final **[WEB]** | Retours de vente et d'achat **[WEB]** | Caisse enregistreuse + rapport de caisse **[WEB]** | Paiements partiels **[WEB]** | Oui : dû client, échéances, alertes **[WEB]** |
| **ERPNext POS** | POS Invoice | Oui | POS Opening Entry / POS Closing Entry, comparaison caisse attendue vs comptée et journalisation de l'écart **[WEB]** | Oui | Via facture |

**Lecture pour Yessal.** La **vente à crédit** (« ardoise », dette client) est traitée sérieusement par UltimatePOS et par les ERP, mais mal ou pas par les POS mobiles grand public. Or c'est une pratique structurante du commerce de proximité ouest-africain. C'est un **différenciateur** naturel pour Yessal, et il doit être dans le MVP, pas repoussé.

Le couple **POS Opening Entry / POS Closing Entry** d'ERPNext est le modèle de session le plus propre rencontré : deux documents distincts, réconciliation par moyen de paiement, écart calculé et conservé. C'est le patron retenu pour Yessal.

### 1.4 Offline, mobile et synchronisation

| Solution | Nature du offline | Limites connues |
|---|---|---|
| **Odoo POS** | Session ouverte en ligne, données préchargées dans le navigateur, commandes conservées localement puis synchronisées au retour du réseau **[WEB]** | **Impossible d'ouvrir une session hors ligne** ; fonctionnement pensé comme repli temporaire, pas comme mode autonome **[WEB]** |
| **Dolibarr / TakePOS** | Application web serveur ; pas de mode déconnecté natif comparable | Dépendance forte au serveur |
| **Loyverse** | Vente hors ligne, stockage puis synchronisation au retour du réseau **[WEB]** | Applications mobiles uniquement, pas de client PC **[WEB]** |
| **Square POS** | Paiements hors ligne étendus à toute la gamme matérielle et à tous les pays **[WEB]** | Fenêtre de **24 h** pour se reconnecter et traiter les paiements ; plafond par transaction paramétrable **[WEB]** ; couverture pays limitée pour l'acquisition |
| **Lightspeed Retail** | Mode hors ligne automatique, prise de commandes, impression, enregistrement des paiements **[WEB]** | Reporting et historique indisponibles hors ligne **[WEB]** |
| **Kyte** | La majorité des fonctions marchent hors ligne et se synchronisent au retour **[WEB]** | Périmètre fonctionnel volontairement restreint |
| **UltimatePOS** | Application web PHP ; offline annoncé mais limité | Pas d'architecture offline-first réelle |
| **ERPNext POS** | Facturation hors ligne avec cache local et synchronisation **[WEB]** | Le offline natif est historiquement discuté par la communauté ; les gros volumes en réseau instable se rabattent sur POS Awesome ou équivalent **[WEB]** |

**Lecture pour Yessal — point le plus important de l'étude.** Aucune des huit solutions n'est réellement **offline-first**. Toutes sont **online-first avec repli offline**. La contrainte partagée est presque toujours la même : *il faut être en ligne pour démarrer*.

Sur un marché où la connectivité est intermittente par défaut et non par accident, cette différence n'est pas cosmétique. Un commerçant qui ne peut pas **ouvrir sa caisse** parce que le réseau est tombé pendant la nuit perd sa journée. **[RECO]** Yessal Caisse doit pouvoir **ouvrir une session, vendre, encaisser, imprimer et fermer une caisse sans aucun réseau**, la synchronisation n'intervenant qu'a posteriori. C'est le principal avantage concurrentiel défendable du produit et il doit conditionner le modèle de données, pas l'inverse.

### 1.5 Utilisateurs, rapports, extensibilité

| Solution | Rôles & permissions | Fidélité / promotions | Rapports | API | Intégration comptable/ERP | Audit |
|---|---|---|---|---|---|---|
| **Odoo POS** | Très fin | Oui | Très complets | XML-RPC / JSON-RPC | Natif (même base) | Chatter, journaux |
| **Dolibarr / TakePOS** | Fin | Modules | Complets | REST `/api/index.php`, clé `DOLAPIKEY` par utilisateur **[WEB]** | Natif (même base), comptabilité en partie double **[WEB]** | Journaux |
| **Loyverse** | Add-on « Employee management » payant **[WEB]** | Programme de fidélité intégré **[WEB]** | Ventes par article, catégorie, employé **[WEB]** | Oui | Add-on intégrations payant **[WEB]** | Limité |
| **Square POS** | Correct | Oui | Bons | API publique riche, dont Point of Sale API **[WEB]** | Connecteurs | Bon |
| **Lightspeed Retail** | Permissions personnalisées **[WEB]** | Oui | Analytique avancée multi-sites **[WEB]** | API publique **[WEB]** | Connecteurs comptables | Bon |
| **Kyte** | Niveaux d'accès vendeurs **[WEB]** | Basique | Basiques, jugés perfectibles **[WEB]** | Limitée | Non | Faible |
| **UltimatePOS** | Rôles illimités, permission par site, permission déplacée du rôle vers l'utilisateur **[WEB]** | Modules | Rapport de caisse, rapport représentant, profits/pertes **[WEB]** | Oui | Modules comptables | Journal d'activité étendu sur ventes, achats, retours, contacts, utilisateurs, ajustements de stock **[WEB]** |
| **ERPNext POS** | Fin, POS Profile par magasin avec entrepôt, moyens de paiement, comptes et taxes par défaut **[WEB]** | Loyalty Points | Complets | REST Frappe | Natif | Version des documents |

**Lecture pour Yessal.** Le **POS Profile** d'ERPNext est le bon niveau d'abstraction : une configuration nommée qui fige, par point de vente, l'entrepôt source, les moyens de paiement acceptés, le client par défaut, les taxes et les utilisateurs autorisés. Il évite de disperser la configuration dans dix écrans. Yessal reprendra ce concept sous le nom de **profil de caisse**.

Le **journal d'activité** d'UltimatePOS montre le bon périmètre d'audit pour ce marché : ce ne sont pas les connexions qui comptent d'abord, ce sont les **remises, annulations, retours, ajustements de stock et changements de prix**, c'est-à-dire les vecteurs de fraude interne réels dans un commerce à plusieurs vendeurs.

---

## 2. Enseignements transposables et pièges à éviter

### 2.1 Ce qu'il faut reprendre

1. **Session de caisse en deux documents** (ouverture / fermeture), avec réconciliation par moyen de paiement et écart persisté — patron ERPNext. **[RECO]**
2. **Profil de caisse** figeant entrepôt, moyens de paiement, client par défaut, taxes, utilisateurs — patron ERPNext. **[RECO]**
3. **Calcul automatique du prix de vente** à partir du prix d'achat et d'une marge cible — patron UltimatePOS. **[RECO]**
4. **Modèles de variantes réutilisables** — patron UltimatePOS. **[RECO]**
5. **Vente à crédit avec échéances et alertes** — patron UltimatePOS, indispensable en contexte ouest-africain. **[RECO]**
6. **Audit centré sur les actions sensibles**, pas sur tout — patron UltimatePOS. **[RECO]**
7. **Plafond paramétrable sur les opérations à risque en mode dégradé** — patron Square (plafond par transaction hors ligne). Transposé à Yessal : plafonner le **montant cumulé encaissé hors ligne** avant blocage. **[RECO]**

### 2.2 Ce qu'il ne faut pas reproduire

1. **Exiger le réseau pour ouvrir une session** (Odoo, en pratique la plupart). Rédhibitoire ici.
2. **Faire du stock une donnée synchronisée par écrasement.** Le stock doit être une **projection de mouvements**, sinon deux terminaux hors ligne produisent des pertes silencieuses.
3. **Placer la fidélité, les promotions et le multi-devise dans le MVP.** Loyverse et Kyte montrent que le cœur — vendre vite, encaisser, savoir ce qu'il reste — suffit à emporter l'adoption.
4. **Faire dépendre l'encaissement de la disponibilité d'un prestataire de paiement.** Le paiement mobile doit être **déclaré puis rapproché**, jamais bloquant.
5. **Réserver la gestion des employés à un add-on payant** dans un produit dont l'argument est justement « plusieurs vendeurs » (arbitrage commercial, hors périmètre de cette étude, mais signalé).

### 2.3 Besoins spécifiques au contexte ouest-africain

| Besoin | Constat | Conséquence produit |
|---|---|---|
| Connectivité intermittente | Coupures quotidiennes, pas exceptionnelles **[HYP terrain]** | Offline-first strict, y compris ouverture de session |
| Terminal = smartphone Android d'entrée de gamme | Pas de matériel dédié, budget contraint | Flutter Android, base locale légère, pas d'exigence matérielle |
| FCFA / XOF | Devise **sans sous-unité** (0 décimale) | Montants en entiers ; arrondi caisse paramétrable |
| Wave, Orange Money, Free Money | Wave ~1 % de commission, adoption massive ; API Wave Business documentée en `https://api.wave.com/v1`, montants en FCFA entiers, webhooks avec retries **[WEB]** ; Orange Money Web Payment couvre le Sénégal **[WEB]** ; pas d'API marchande Wave « point de vente physique » publique — passage par agrégateur, QR marchand ou lien de paiement **[WEB]** | Paiement mobile modélisé comme **déclaré → rapproché**, jamais bloquant en caisse |
| Vente à crédit / ardoise | Pratique universelle du commerce de proximité **[HYP terrain]** | Dette client au cœur du MVP |
| Plusieurs vendeurs, faible littératie numérique | Rotation du personnel, illettrisme partiel | PIN 4 chiffres, changement rapide de caissier, écrans à icônes, français + wolof à terme |
| Plusieurs points de vente | Boutique + dépôt + étal | Multi-boutique dès la V1, transferts de stock |
| Passage futur à la comptabilité formelle | Formalisation progressive des entreprises | Connecteur Dolibarr optionnel, activé par entitlement |

---


## 3. Périmètre fonctionnel — MVP / V1 / V2 / avancé / hors périmètre

**Statut : PROPOSÉE.** Le découpage vise un MVP livrable et vendable, pas un MVP minimal théorique. Le critère de découpe est le suivant : *le MVP doit permettre à un commerçant de tenir sa journée entière sans réseau et de savoir le soir combien il a vendu, combien il a en caisse et ce qu'on lui doit.*

### 3.1 MVP — « tenir la journée »

| Domaine | Contenu |
|---|---|
| Référentiel | Produits simples, catégories, prix de vente, prix d'achat, code-barres, unité, image optionnelle |
| Stock | Stock par produit et par boutique, mouvements automatiques à la vente, ajustement manuel motivé, alerte stock bas |
| Clients | Client libre, client enregistré (nom + téléphone), client par défaut « Client comptant » |
| Vente | Panier, quantité, remise ligne et remise globale, ticket, annulation avant encaissement |
| Paiement | Espèces, Wave, Orange Money, Free Money, autre ; **paiement mixte** ; rendu monnaie ; arrondi caisse |
| Crédit | Vente partiellement ou totalement à crédit, dette client, encaissement ultérieur d'un règlement de dette |
| Caisse | Ouverture avec fonds initial, entrées / sorties / dépenses, fermeture avec montant compté, écart et justification |
| Utilisateurs | Rôles caissier / gérant / propriétaire, PIN 4 chiffres, changement rapide de caissier, vendeur attribué à la vente |
| Rapports | Rapport de session (Z), ventes du jour, ventes par produit, ventes par vendeur, état de caisse, dettes clients |
| Impression | Ticket 58 mm et 80 mm, Bluetooth et USB, impression système en repli |
| Offline | **Totalité du MVP fonctionne sans réseau**, y compris ouverture et fermeture de session |
| Multi-tenant | Contexte organisation strict, une seule boutique active par abonnement au niveau MVP |
| Audit | Journalisation des actions sensibles listées en §7.6 |

### 3.2 V1 — « structurer le commerce »

- Variantes de produits et modèles de variantes réutilisables.
- Multi-boutiques, transferts de stock entre boutiques, multi-caisses par boutique.
- Achats et fournisseurs : bons d'achat, réception, dettes fournisseurs.
- Inventaires physiques par campagne, écarts et validation.
- Retours de vente, remboursements et avoirs.
- Taxes paramétrables (préparation TVA), documents de vente numérotés.
- Appairage d'appareils par QR code, révocation d'appareil, gestion de flotte.
- Rapports comparatifs, exports CSV / Excel / PDF.
- Notifications Telegram et e-mail à la fermeture de caisse.
- Rapprochement des paiements mobiles (statut `declared` vers `confirmed`).

### 3.3 V2 — « fidéliser et piloter »

- Promotions, coupons, remises programmées, grilles de prix par client ou par canal.
- Programme de fidélité (points, paliers, privilèges).
- Objectifs et commissions vendeurs.
- Tableau de bord temps réel multi-boutiques.
- Connecteur Dolibarr (tiers, produits, stock, ventes, factures, paiements).
- Export comptable, connecteur Google Sheets.
- Gestion des lots, dates de péremption, numéros de série.

### 3.4 Avancé — au-delà de V2

- Restauration : plan de salle, commandes en cuisine, écran cuisine.
- Balances connectées, produits au poids.
- Multi-devise réelle.
- Marketplace et livraison (déjà identifiés comme phases ultérieures dans `architecture.md`).
- Prévision de réapprovisionnement assistée.
- Facturation électronique normée, si et quand la réglementation l'impose.

### 3.5 Hors périmètre — exclusions assumées

| Exclusion | Raison |
|---|---|
| Acquisition de paiement par carte par Yessal | Métier régulé, agrément requis, hors positionnement |
| Comptabilité en partie double dans Yessal Caisse | C'est le rôle de Dolibarr ; dupliquer créerait deux vérités |
| Paie et RH | Hors domaine Caisse |
| E-commerce complet | Marketplace déjà prévue comme brique distincte |
| Matériel propriétaire Yessal | Le produit doit tourner sur le téléphone existant du commerçant |
| Mode strictement en ligne | Contredit la proposition de valeur |

---

## 4. Architecture fonctionnelle de Yessal Caisse

### 4.1 Chaîne d'autorisation — VALIDÉE, reprise sans modification

```text
Utilisateur
  ↓
Organisation                      (ResolveOrganizationContext, X-Organization-Id)
  ↓
Abonnement                        (EnsureSubscriptionActive, grâce 3 jours)
  ↓
Plan
  ↓
Modules                           (module_plan)
  ↓
Entitlements                      (EntitlementService, EnsureEntitlement)
  ↓
Quotas                            (PlanLimitService, QuotaService)
  ↓
Yessal Caisse                     (domaine métier, objet de la présente étude)
```

**Règle d'ordre. [RECO]** L'évaluation reste dans cet ordre exact et s'arrête au premier refus, avec des codes distincts, afin que l'application Flutter puisse réagir correctement :

| Étage | HTTP | Code applicatif | Réaction attendue côté Flutter |
|---|---|---|---|
| Organisation absente ou non membre | 403 | `ORG_CONTEXT_INVALID` | Retour au sélecteur d'organisation |
| Abonnement expiré | 403 | `SUBSCRIPTION_EXPIRED` | Écran de renouvellement, lecture seule autorisée |
| Module non inclus | 403 | `MODULE_NOT_INCLUDED` | Masquer la fonctionnalité |
| Entitlement absent | 403 | `ENTITLEMENT_MISSING` | Masquer ou griser l'action |
| Quota atteint | 409 | `QUOTA_EXCEEDED` | Message explicite avec la limite et l'usage |

Un `403 SUBSCRIPTION_EXPIRED` ne doit **jamais** empêcher la synchronisation **montante** des ventes déjà réalisées hors ligne. **[RECO]** Un commerçant dont l'abonnement a expiré pendant qu'il vendait hors ligne doit pouvoir remonter ses données ; sinon le produit détruit les données du client. La sanction porte sur la création de **nouvelles** ventes, pas sur la remontée du passé.

### 4.2 Découpage fonctionnel

```text
YESSAL CAISSE
├── Configuration
│   ├── Boutiques (shops)
│   ├── Caisses / terminaux (terminals)
│   ├── Profils de caisse (register_profiles)
│   ├── Moyens de paiement (payment_methods)
│   └── Appareils appairés (devices)
├── Référentiel
│   ├── Catégories, produits, variantes, codes-barres
│   ├── Unités, taxes
│   └── Clients, fournisseurs
├── Stock
│   ├── Niveaux (projection)
│   ├── Mouvements (source de vérité)
│   ├── Transferts
│   └── Inventaires
├── Vente
│   ├── Panier → Vente → Lignes
│   ├── Remises
│   ├── Paiements multi-moyens
│   ├── Retours / avoirs
│   └── Crédit client
├── Caisse
│   ├── Sessions
│   ├── Mouvements de caisse
│   └── Clôture et écarts
├── Restitution
│   ├── Rapports
│   ├── Impression
│   └── Exports
└── Plateforme
    ├── Synchronisation (événements)
    ├── Audit
    └── Connecteur Dolibarr
```

### 4.3 Principes structurants — PROPOSÉE

| # | Principe | Justification |
|---|---|---|
| P1 | **Le terminal est autoritaire sur ce qu'il a encaissé.** Le serveur ne rejette jamais une vente déjà encaissée ; il l'accepte ou la met en quarantaine. | Une vente refusée après encaissement est une perte comptable réelle |
| P2 | **Le stock est une projection de mouvements**, jamais une valeur synchronisée. | Convergence commutative entre terminaux hors ligne |
| P3 | **Les documents financiers sont immuables.** Une correction crée un nouveau document (avoir, annulation), jamais une modification. | Auditabilité, préparation comptable |
| P4 | **Les identifiants sont générés par le client** (ULID). | Aucune vente ne dépend d'un aller-retour serveur |
| P5 | **Toute écriture est idempotente**, portée par un `event_id` unique. | Reprise après coupure sans doublon |
| P6 | **Le serveur est autoritaire sur le référentiel** (produits, prix, droits). | Évite qu'un terminal compromis modifie les prix |
| P7 | **Le tenant est vérifié à l'écriture, pas seulement scopé à la lecture.** | Un scope global oublié dans une requête ne doit pas suffire à provoquer une fuite |
| P8 | **Le mode dégradé est borné**, pas illimité. | Reprise de la politique offline de `architecture.md` §24 |

### 4.4 Politique offline — VALIDÉE, reprise de `architecture.md` §24

```text
0–24 h   → normal
24–72 h  → alertes
72 h+    → restrictions sensibles
7 jours+ → blocage nouvelles ventes
faible connectivité → exception possible jusqu'à 14 jours
```

**[RECO] Complément proposé, À VALIDER :** ajouter un plafond **cumulé en montant**, indépendant du temps, inspiré du plafond hors ligne de Square. Un terminal qui a encaissé un montant anormalement élevé sans jamais synchroniser relève soit de la force majeure, soit de la fraude. Le plafond, paramétrable par plan, déclenche une alerte avant de déclencher un blocage.

---

## 5. Modèle métier et entités proposées

### 5.1 Vocabulaire — PROPOSÉE

Un vocabulaire stable évite les refontes. Les termes suivants sont proposés comme définitifs pour le domaine Caisse.

| Terme métier | Entité | Définition |
|---|---|---|
| Boutique | `Shop` | Lieu physique de vente ou de stockage. Porte le stock. |
| Caisse | `Terminal` | Poste d'encaissement logique rattaché à une boutique. Porte la numérotation. |
| Profil de caisse | `RegisterProfile` | Configuration nommée : entrepôt, moyens de paiement, client par défaut, taxes, utilisateurs autorisés. |
| Appareil | `Device` | Téléphone ou tablette appairé, porteur d'une base locale. |
| Session de caisse | `CashSession` | Période continue entre une ouverture et une fermeture, sur un terminal, par un ou plusieurs caissiers. |
| Vente | `Sale` | Document commercial immuable une fois finalisé. |
| Encaissement | `SalePayment` | Fraction du règlement d'une vente par un moyen donné. |
| Retour | `SaleReturn` | Document lié à une vente d'origine. |
| Avoir | `CreditNote` | Créance du client sur le commerce, utilisable en paiement. |
| Dette client | `CustomerCredit` | Solde dû par le client, alimenté par les ventes à crédit. |
| Mouvement de stock | `StockMovement` | Fait élémentaire et immuable modifiant une quantité. |
| Niveau de stock | `StockLevel` | Projection recalculable, jamais source de vérité. |
| Mouvement de caisse | `CashMovement` | Entrée, sortie, dépense ou correction d'espèces. |
| Événement de synchronisation | `SyncEvent` | Unité idempotente de réplication. |

**Distinction importante — PROPOSÉE.** `Terminal` (caisse logique) et `Device` (appareil physique) sont **séparés**. Un même appareil peut servir successivement deux caisses ; une caisse peut être reprise sur un autre appareil si le premier est cassé. Fusionner les deux est le piège classique qui rend impossible le remplacement d'un téléphone sans perdre l'historique.

### 5.2 Cycles de vie — PROPOSÉE

**Vente**

```text
draft ──► finalized ──► (partially_paid | paid)
  │            │
  │            └──► returned_partially ──► returned_fully
  └──► voided                    (avant finalisation uniquement)
```

Une vente `finalized` n'est jamais modifiée, jamais supprimée. L'annulation après finalisation passe par un retour ou un avoir.

**Session de caisse**

```text
open ──► closing ──► closed ──► (reconciled)
                        │
                        └──► closed_with_variance   (écart non nul, justification obligatoire)
```

**Encaissement**

```text
declared ──► confirmed
    │
    └──► failed ──► (compensation : nouvel encaissement ou passage en dette)
```

L'état `declared` est essentiel : il permet d'encaisser un paiement Wave ou Orange Money **hors ligne**, en enregistrant la référence donnée par le client, puis de le rapprocher plus tard. Le ticket est remis, la vente est close, le rapprochement est un travail de back-office.

**Événement de synchronisation**

```text
pending ──► sent ──► acked
   │          │
   │          └──► rejected ──► quarantined
   └──► failed ──► retry (backoff)
```

### 5.3 Règles métier structurantes — PROPOSÉE

| # | Règle |
|---|---|
| R1 | Une vente finalisée ne peut être ni modifiée ni supprimée, par personne, y compris administrateur. |
| R2 | La somme des `sale_payments` confirmés et déclarés, augmentée du montant porté en dette, égale toujours le total de la vente. |
| R3 | Toute vente finalisée génère exactement un mouvement de stock sortant par ligne de produit géré en stock. |
| R4 | Aucune session de caisse ne peut être ouverte sur un terminal ayant déjà une session `open`. |
| R5 | Une session ne peut être fermée qu'après finalisation ou annulation de toutes ses ventes `draft`. |
| R6 | Un écart de caisse non nul exige un motif ; l'absence de motif bloque la fermeture. |
| R7 | Le stock négatif est **autorisé** mais signalé. Une vente n'est jamais refusée pour cause de stock insuffisant. |
| R8 | Le prix appliqué est figé dans la ligne de vente au moment de la vente ; une modification ultérieure du catalogue ne réécrit pas l'historique. |
| R9 | Toute remise supérieure au seuil configuré exige la validation par un PIN autorisé. |
| R10 | La numérotation d'un ticket est déterministe et générée localement, sans appel serveur. |

**Sur R7.** C'est un arbitrage assumé et il mérite d'être explicité, car il va à l'encontre du réflexe ERP. Dans un commerce de proximité, le stock théorique est souvent faux (dons, casse, ventes non saisies, réception non enregistrée). Refuser une vente réelle parce que le compteur est à zéro fait perdre la vente **et** fait désinstaller l'application. Le bon comportement est de vendre, d'enregistrer, et de signaler l'anomalie au gérant dans un rapport d'écarts.

### 5.4 Numérotation — PROPOSÉE

```text
Format : {SHOP_CODE}-{TERMINAL_CODE}-{YYMMDD}-{SEQ}
Exemple : DKR1-C02-260829-0147
```

- `SEQ` est un compteur local au terminal, remis à zéro chaque jour.
- Unicité garantie sans coordination réseau, car `TERMINAL_CODE` est unique dans l'organisation.
- Le serveur **vérifie** l'unicité mais ne **produit** pas le numéro.
- Un numéro déjà présent avec un `id` différent déclenche une mise en quarantaine, jamais un écrasement.

### 5.5 Représentation monétaire — PROPOSÉE

**Décision : montants stockés en entiers signés, dans l'unité mineure de la devise, avec l'exposant explicite.**

| Champ | Type | Exemple XOF |
|---|---|---|
| `currency` | CHAR(3) | `XOF` |
| `currency_exponent` | TINYINT | `0` |
| `*_amount` | BIGINT | `15000` = 15 000 FCFA |

Le franc CFA n'a pas de sous-unité en pratique : l'exposant vaut 0 et un montant est un nombre entier de francs. Stocker en entier avec exposant explicite plutôt qu'en « centimes » systématiques évite deux erreurs : la multiplication parasite par 100 dans les exports comptables, et l'impossibilité future de gérer une devise à 2 décimales sans migration. **Aucun `FLOAT` ni `DOUBLE` nulle part.**

**Arrondi caisse.** Champ `cash_rounding_step` par boutique (valeurs typiques : 1, 5, 25 F). L'arrondi s'applique **au total à payer en espèces uniquement**, jamais aux lignes, et l'écart d'arrondi est stocké dans `rounding_amount` afin que la somme des lignes reste vérifiable.

---

## 6. Modèle de données

**Statut : PROPOSÉE. Aucune migration n'est écrite.** Les définitions ci-dessous sont un support d'arbitrage ; elles deviendront des migrations Laravel **après validation**.

### 6.1 Conventions transverses — PROPOSÉE

| Convention | Choix | Justification |
|---|---|---|
| Clé primaire | `id CHAR(26)` (ULID) | Générable hors ligne, trié chronologiquement, bon comportement d'index B-tree contrairement à UUIDv4 |
| Tenant | `organization_id CHAR(26) NOT NULL` sur **toutes** les tables métier | Isolation stricte |
| Unicité | Toujours composite avec `organization_id` | Deux organisations peuvent avoir le même SKU |
| Index | Tout index de requête préfixé par `organization_id` | Le scope tenant doit être discriminant en tête d'index |
| Horodatage | `created_at`, `updated_at`, plus `occurred_at` sur les faits | `occurred_at` = heure réelle de l'acte, `created_at` = heure d'insertion serveur |
| Traçabilité offline | `device_id`, `client_generated_at`, `synced_at` | Diagnostic des dérives d'horloge |
| Suppression | `deleted_at` (soft delete) sur le référentiel, **jamais** sur les documents financiers | R1, R3 |
| Version de sync | `sync_version BIGINT` par ligne, alimenté par un compteur monotone par organisation | Pagination fiable du `pull` |
| Devise | `currency`, `currency_exponent` sur les documents | §5.5 |

**Contrainte tenant — [RECO] triple barrière.**

1. **Lecture** : global scope Eloquent `BelongsToOrganization` sur tous les modèles du domaine.
2. **Écriture** : validation explicite que chaque `*_id` référencé appartient à l'organisation courante, via une règle de validation dédiée (`ExistsInOrganization`). Ne jamais se reposer sur le seul global scope, qu'un `withoutGlobalScopes()` ou une requête brute suffirait à contourner.
3. **Base** : clés étrangères composites `(organization_id, xxx_id)` sur les relations critiques (ventes, paiements, mouvements de stock, sessions), de sorte qu'une ligne d'une organisation ne puisse **physiquement** pas référencer une ligne d'une autre.

La barrière 3 a un coût : elle impose une clé unique `(organization_id, id)` sur les tables cibles. C'est un coût acceptable au regard du risque, et c'est le seul mécanisme qui protège contre une erreur applicative.

### 6.2 Configuration

**`shops`** — boutiques / points de vente

```text
id, organization_id, code, name, type(shop|warehouse|stall),
address, phone, currency, currency_exponent, cash_rounding_step,
timezone, is_active, created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, code)
INDEX (organization_id, is_active)
```

**`terminals`** — caisses logiques

```text
id, organization_id, shop_id, code, name, is_active,
last_sequence_date, last_sequence_number,
created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, code)
UNIQUE (organization_id, id)          -- support des FK composites
INDEX (organization_id, shop_id)
```

**`register_profiles`** — profils de caisse (patron ERPNext)

```text
id, organization_id, shop_id, name,
default_customer_id, allowed_payment_method_ids JSON,
default_tax_id, allow_negative_stock BOOL,
max_discount_percent, require_pin_above_amount,
print_format(58mm|80mm), is_default,
created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, shop_id, name)
```

**`devices`** — appareils appairés (reprend `architecture.md` §12)

```text
id, organization_id, device_uid, name, platform, app_version,
user_id, terminal_id, status(active|blocked|revoked),
paired_at, last_seen_at, last_synced_at,
push_token, created_at, updated_at, sync_version
UNIQUE (organization_id, device_uid)
INDEX (organization_id, status)
```

**`device_pairings`** — jetons QR temporaires (reprend `architecture.md` §13)

```text
id, organization_id, token_hash, issued_by_user_id, issued_by_device_id,
terminal_id, expires_at, consumed_at, consumed_by_device_id,
created_at
UNIQUE (organization_id, token_hash)
```

Le jeton est stocké **haché**, à usage unique, expirant, sans secret permanent, conformément à la décision déjà validée.

**`payment_methods`**

```text
id, organization_id, code, name,
type(cash|mobile_money|card|transfer|credit|voucher|other),
provider(wave|orange_money|free_money|null),
requires_reference BOOL, opens_cash_drawer BOOL,
is_reconcilable BOOL, is_active, sort_order,
created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, code)
```

### 6.3 Référentiel

**`product_categories`**

```text
id, organization_id, parent_id, name, color, icon, sort_order,
is_active, created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, id)
INDEX (organization_id, parent_id)
```

**`products`**

```text
id, organization_id, category_id, sku, name, description,
type(simple|variable|service),
unit_id, track_stock BOOL, is_active,
default_purchase_price BIGINT, default_sale_price BIGINT,
target_margin_percent DECIMAL(6,3),        -- calcul auto du prix (patron UltimatePOS)
tax_id, image_path, low_stock_threshold,
created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, sku)
UNIQUE (organization_id, id)
INDEX (organization_id, category_id, is_active)
FULLTEXT (name)
```

**`product_variants`** — une ligne même pour un produit simple

```text
id, organization_id, product_id, sku, name,
attributes JSON,                            -- {"couleur":"rouge","taille":"M"}
purchase_price BIGINT, sale_price BIGINT,
is_active, created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, sku)
UNIQUE (organization_id, id)
INDEX (organization_id, product_id)
```

**[RECO] Décision : toujours matérialiser une variante, même pour un produit simple.** Le référencement se fait exclusivement sur `variant_id` dans les ventes, le stock et les achats. Cela coûte une ligne par produit simple ; cela évite la refonte massive au moment d'introduire les variantes en V1, refonte qui toucherait sinon les ventes, le stock, les achats, les inventaires et le connecteur Dolibarr d'un seul coup.

**`product_barcodes`**

```text
id, organization_id, variant_id, barcode, is_primary,
created_at, updated_at, sync_version
UNIQUE (organization_id, barcode)
INDEX (organization_id, variant_id)
```

**`variant_templates`** — modèles réutilisables (patron UltimatePOS)

```text
id, organization_id, name, attributes JSON, created_at, updated_at, deleted_at
UNIQUE (organization_id, name)
```

**`units`**, **`taxes`** — tables de dictionnaire par organisation

```text
units:  id, organization_id, code, name, decimals, is_active
taxes:  id, organization_id, code, name, rate DECIMAL(6,3),
        mode(inclusive|exclusive), is_active
UNIQUE (organization_id, code)
```

**`partners`** — clients et fournisseurs unifiés

```text
id, organization_id, type SET('customer','supplier'),
code, name, phone, phone_normalized, email, address,
credit_limit BIGINT, credit_balance BIGINT,      -- projection, cf. §6.5
loyalty_points BIGINT,                            -- V2
is_default BOOL, is_active,
external_refs JSON,
created_at, updated_at, deleted_at, sync_version
UNIQUE (organization_id, code)
UNIQUE (organization_id, id)
INDEX (organization_id, phone_normalized)
INDEX (organization_id, type)
```

**[RECO] Table unique `partners` plutôt que `customers` + `suppliers`.** Dans le commerce de proximité, le même tiers est très souvent à la fois client et fournisseur. Dolibarr fait exactement ce choix avec ses *tiers* portant les drapeaux `client` et `fournisseur`, ce qui simplifiera aussi le mapping du connecteur. **[FAIT]**

`phone_normalized` (format E.164) sert au dédoublonnage automatique des clients créés hors ligne sur plusieurs terminaux — cas très fréquent.

### 6.4 Stock

**`stock_movements`** — source de vérité, append-only

```text
id, organization_id, shop_id, variant_id,
type(sale|return|purchase|purchase_return|adjustment|transfer_in|transfer_out|inventory|opening|loss),
quantity DECIMAL(16,4),                  -- signée : négative en sortie
unit_cost BIGINT,
reference_type, reference_id,            -- polymorphe : sale, purchase, inventory...
reason, note,
user_id, device_id, occurred_at, created_at, sync_version
INDEX (organization_id, shop_id, variant_id, occurred_at)
INDEX (organization_id, reference_type, reference_id)
UNIQUE (organization_id, id)
```

Aucune mise à jour, aucune suppression. Une correction est un nouveau mouvement de type `adjustment`.

**`stock_levels`** — projection recalculable

```text
organization_id, shop_id, variant_id,
quantity DECIMAL(16,4), reserved DECIMAL(16,4),
average_cost BIGINT, last_movement_at, recomputed_at
PRIMARY KEY (organization_id, shop_id, variant_id)
```

Recalculable intégralement par `SUM(quantity)` sur `stock_movements`. Une commande artisan de reconstruction doit exister dès le début : c'est le filet de sécurité qui rend le modèle défendable.

**`stock_transfers` / `stock_transfer_lines`** — V1

```text
stock_transfers: id, organization_id, reference, from_shop_id, to_shop_id,
  status(draft|sent|received|cancelled), sent_at, received_at,
  user_id, note, created_at, updated_at, sync_version
stock_transfer_lines: id, organization_id, transfer_id, variant_id,
  quantity_sent, quantity_received
UNIQUE (organization_id, reference)
```

**`inventories` / `inventory_lines`** — V1

```text
inventories: id, organization_id, shop_id, reference,
  status(draft|counting|validated|cancelled), started_at, validated_at,
  user_id, note, created_at, updated_at, sync_version
inventory_lines: id, organization_id, inventory_id, variant_id,
  theoretical_quantity, counted_quantity, variance, reason
```

La validation d'un inventaire génère des `stock_movements` de type `inventory`. Le stock n'est jamais « écrit » directement.

### 6.5 Vente

**`sales`**

```text
id, organization_id, shop_id, terminal_id, cash_session_id,
number,                                    -- §5.4
channel(pos|backoffice),
type(sale|return),
parent_sale_id,                            -- renseigné si type = return
customer_id, cashier_user_id, seller_user_id,
status(draft|finalized|voided),
payment_status(unpaid|partially_paid|paid|overpaid|refunded),
subtotal_amount BIGINT, discount_amount BIGINT, tax_amount BIGINT,
rounding_amount BIGINT, total_amount BIGINT,
paid_amount BIGINT, due_amount BIGINT, change_amount BIGINT,
currency, currency_exponent,
note, occurred_at, finalized_at,
device_id, client_generated_at, synced_at,
created_at, updated_at, sync_version
UNIQUE (organization_id, number)
UNIQUE (organization_id, id)
INDEX (organization_id, shop_id, occurred_at)
INDEX (organization_id, cash_session_id)
INDEX (organization_id, customer_id, payment_status)
INDEX (organization_id, seller_user_id, occurred_at)
FOREIGN KEY (organization_id, terminal_id) REFERENCES terminals (organization_id, id)
```

**[RECO]** Distinguer `cashier_user_id` (qui a encaissé) de `seller_user_id` (à qui la vente est attribuée). C'est la condition pour que les commissions vendeurs de la V2 ne réclament aucune migration douloureuse, et c'est la réalité d'une boutique où un caissier unique encaisse pour plusieurs vendeurs.

**`sale_lines`**

```text
id, organization_id, sale_id, line_number,
variant_id, product_name_snapshot, sku_snapshot,
quantity DECIMAL(16,4), unit_price BIGINT, unit_cost BIGINT,
discount_type(none|percent|amount), discount_value, discount_amount BIGINT,
tax_id, tax_rate DECIMAL(6,3), tax_amount BIGINT,
line_total BIGINT, note,
created_at, sync_version
INDEX (organization_id, sale_id)
INDEX (organization_id, variant_id)
```

Les champs `*_snapshot` figent le libellé et le SKU au moment de la vente (règle R8). Un ticket réimprimé six mois plus tard doit afficher ce qui a été vendu, pas le catalogue d'aujourd'hui.

**`sale_payments`**

```text
id, organization_id, sale_id, payment_method_id,
amount BIGINT, tendered_amount BIGINT, change_amount BIGINT,
status(declared|confirmed|failed|refunded),
provider, provider_reference, provider_payload JSON,
reconciled_at, reconciled_by_user_id,
occurred_at, device_id, created_at, updated_at, sync_version
INDEX (organization_id, sale_id)
INDEX (organization_id, status, occurred_at)
INDEX (organization_id, provider, provider_reference)
```

**`sale_returns`** — V1. Modélisé comme une `sales` de `type = return` avec `parent_sale_id`, plus `sale_return_lines` référençant les `sale_line_id` d'origine. Aucune table de tête supplémentaire : cela évite deux moteurs de calcul parallèles.

**`credit_notes`** — V1

```text
id, organization_id, customer_id, number, origin_sale_id,
amount BIGINT, used_amount BIGINT, remaining_amount BIGINT,
status(open|partially_used|used|expired|cancelled),
expires_at, created_at, updated_at, sync_version
UNIQUE (organization_id, number)
```

**`customer_credit_entries`** — dette client, append-only, MVP

```text
id, organization_id, customer_id,
type(sale_credit|repayment|adjustment|write_off),
amount BIGINT,                              -- positif = augmente la dette
reference_type, reference_id,
due_date, note, user_id, device_id,
occurred_at, created_at, sync_version
INDEX (organization_id, customer_id, occurred_at)
```

`partners.credit_balance` est la projection de `SUM(amount)`. Même principe que le stock : le solde n'est jamais écrit directement, il est dérivé. C'est ce qui rend la dette client réconciliable après une synchronisation désordonnée entre deux terminaux.

### 6.6 Caisse

**`cash_sessions`**

```text
id, organization_id, shop_id, terminal_id,
opened_by_user_id, closed_by_user_id,
status(open|closing|closed|closed_with_variance|reconciled),
opening_amount BIGINT,
expected_cash_amount BIGINT, counted_cash_amount BIGINT,
variance_amount BIGINT, variance_reason,
expected_by_method JSON, counted_by_method JSON,     -- réconciliation par moyen
sales_count, sales_total BIGINT, returns_total BIGINT,
opened_at, closed_at, device_id,
created_at, updated_at, sync_version
UNIQUE (organization_id, id)
INDEX (organization_id, terminal_id, status)
INDEX (organization_id, shop_id, opened_at)
```

Contrainte applicative R4 : au plus une session `open` par `terminal_id`. À garantir par un index unique partiel côté serveur (`UNIQUE (organization_id, terminal_id, status)` filtré sur `open` via colonne générée) **et** par un verrou local côté Flutter.

**`cash_movements`**

```text
id, organization_id, cash_session_id,
type(in|out|expense|correction|opening|closing),
amount BIGINT, reason, category, note,
user_id, approved_by_user_id, device_id,
occurred_at, created_at, sync_version
INDEX (organization_id, cash_session_id, occurred_at)
```

**`cash_session_users`** — traçabilité du changement de caissier

```text
id, organization_id, cash_session_id, user_id,
started_at, ended_at, device_id
INDEX (organization_id, cash_session_id)
```

### 6.7 Achats — V1

```text
purchases: id, organization_id, shop_id, supplier_id, reference,
  status(draft|ordered|received|cancelled),
  payment_status(unpaid|partially_paid|paid),
  subtotal_amount, discount_amount, tax_amount, total_amount,
  paid_amount, due_amount, due_date,
  ordered_at, received_at, user_id, created_at, updated_at, sync_version
purchase_lines: id, organization_id, purchase_id, variant_id,
  quantity_ordered, quantity_received, unit_cost, tax_id, tax_amount, line_total
purchase_payments: id, organization_id, purchase_id, payment_method_id,
  amount, reference, occurred_at, user_id
UNIQUE (organization_id, reference)
```

La réception génère des `stock_movements` de type `purchase`.

### 6.8 Plateforme

**`sync_events`** — journal d'idempotence côté serveur

```text
id, organization_id, event_id CHAR(26),
device_id, user_id,
entity_type, entity_id, action(create|update|delete|apply),
payload JSON, payload_hash CHAR(64),
sequence BIGINT,                            -- séquence locale au device
status(accepted|duplicate|rejected|quarantined),
rejection_code, rejection_detail,
occurred_at, received_at, processed_at
UNIQUE (organization_id, event_id)
INDEX (organization_id, device_id, sequence)
INDEX (organization_id, status, received_at)
```

`UNIQUE (organization_id, event_id)` est le mécanisme d'idempotence : un renvoi retourne `duplicate` avec le résultat d'origine, sans double écriture.

**`audit_logs`** — reprend `architecture.md` §19

```text
id, organization_id, shop_id,
user_id, device_id, ip, user_agent,
action, entity_type, entity_id,
before JSON, after JSON,
reason, severity(info|warning|critical),
occurred_at, created_at
INDEX (organization_id, occurred_at)
INDEX (organization_id, entity_type, entity_id)
INDEX (organization_id, action, occurred_at)
```

**`external_refs`** — mapping vers systèmes tiers, prépare Dolibarr

```text
id, organization_id, system(dolibarr|sheets|other), connection_id,
entity_type, entity_id,
external_id, external_ref, external_updated_at,
last_pushed_at, last_pulled_at, status(linked|pending|error), error_message
UNIQUE (organization_id, system, connection_id, entity_type, entity_id)
UNIQUE (organization_id, system, connection_id, entity_type, external_id)
INDEX (organization_id, status)
```

**[RECO]** Cette table doit exister **dès le MVP**, même vide et sans connecteur actif. La créer plus tard signifierait rétro-mapper des dizaines de milliers de lignes sans clé fiable.

**`sequences`** — compteurs serveur (documents back-office uniquement)

```text
id, organization_id, scope, period, current_value, updated_at
UNIQUE (organization_id, scope, period)
```

### 6.9 Récapitulatif des relations

```text
Organization 1─n Shop 1─n Terminal 1─n CashSession 1─n Sale 1─n SaleLine
                                              │            └─n SalePayment
                                              └─n CashMovement
Shop 1─n StockLevel n─1 ProductVariant n─1 Product n─1 ProductCategory
ProductVariant 1─n ProductBarcode
ProductVariant 1─n StockMovement n─1 Shop
Partner 1─n Sale
Partner 1─n CustomerCreditEntry
Partner 1─n Purchase 1─n PurchaseLine
Sale 1─n Sale (parent_sale_id, retours)
Device n─1 Terminal ;  Device 1─n SyncEvent
Toute entité 1─n ExternalRef
```

### 6.10 Index et volumétrie — [RECO]

| Table | Croissance | Risque | Mesure |
|---|---|---|---|
| `sales`, `sale_lines` | Forte, linéaire | Requêtes de rapport lentes | Partitionnement par mois envisageable en V2 ; index couvrant `(organization_id, shop_id, occurred_at)` |
| `stock_movements` | Très forte | Recalcul de projection coûteux | Snapshot périodique : ligne `opening` mensuelle figeant le cumul, recalcul borné à un mois |
| `sync_events` | Très forte | Table qui gonfle sans limite | Purge des `accepted` après N jours, conservation des `rejected` et `quarantined` |
| `audit_logs` | Forte | Idem | Rétention par plan ; archivage froid |

---

## 7. Proposition d'API

**Statut : PROPOSÉE.** Esquisse de contrat, non exhaustive, sans implémentation.

### 7.1 Conventions

| Élément | Choix |
|---|---|
| Préfixe | `/api/v1/caisse/...` — le domaine Caisse est isolé du reste de la plateforme |
| Authentification | Sanctum, en-tête `Authorization: Bearer` — **VALIDÉE** |
| Tenant | En-tête `X-Organization-Id` obligatoire — **VALIDÉE** |
| Appareil | En-tête `X-Device-Id`, obligatoire sur toute écriture métier — **PROPOSÉE** |
| Idempotence | En-tête `Idempotency-Key` (ULID) sur tout `POST` métier — **PROPOSÉE** |
| Erreurs | Enveloppe `{ code, message, details }`, `code` stable et documenté |
| Pagination | Curseur `?cursor=&limit=`, jamais d'`offset` sur les tables volumineuses |
| Dates | ISO 8601 avec fuseau, `occurred_at` fourni par le client |
| Montants | Entiers, jamais de décimales en JSON |

### 7.2 Référentiel — lecture descendante

```text
GET  /api/v1/caisse/bootstrap          # tout le référentiel d'un terminal, en un appel
GET  /api/v1/caisse/shops
GET  /api/v1/caisse/terminals
GET  /api/v1/caisse/register-profiles
GET  /api/v1/caisse/payment-methods
GET  /api/v1/caisse/categories
GET  /api/v1/caisse/products?updated_since=&cursor=
GET  /api/v1/caisse/variants?updated_since=&cursor=
GET  /api/v1/caisse/partners?type=customer&updated_since=&cursor=
GET  /api/v1/caisse/taxes
GET  /api/v1/caisse/units
```

`/bootstrap` est l'endpoint le plus important du produit sur le plan de l'expérience. Il doit renvoyer, en une seule réponse compressée, tout ce dont un terminal a besoin pour fonctionner de façon autonome : boutique, terminal, profil, moyens de paiement, catalogue, clients, taxes, droits effectifs, quotas et curseur de synchronisation initial. Sur une connexion 2G, un premier démarrage qui exige quinze appels échoue.

### 7.3 Écriture métier — usage en ligne

Ces endpoints existent pour le back-office web et pour un terminal connecté. Le terminal Flutter, lui, passe normalement par `/sync/push`.

```text
POST   /api/v1/caisse/products                     entitlement products.create
PATCH  /api/v1/caisse/products/{id}
POST   /api/v1/caisse/products/import              entitlement products.import
POST   /api/v1/caisse/partners                     entitlement customers.create

POST   /api/v1/caisse/cash-sessions                entitlement cash.open
POST   /api/v1/caisse/cash-sessions/{id}/movements entitlement cash.withdraw | cash.deposit
POST   /api/v1/caisse/cash-sessions/{id}/close     entitlement cash.close
GET    /api/v1/caisse/cash-sessions/{id}/z-report  entitlement reports.basic

POST   /api/v1/caisse/sales                        entitlement pos.sell
POST   /api/v1/caisse/sales/{id}/finalize          entitlement pos.sell
POST   /api/v1/caisse/sales/{id}/payments          entitlement pos.sell
POST   /api/v1/caisse/sales/{id}/void              entitlement pos.void
POST   /api/v1/caisse/sales/{id}/returns           entitlement pos.refund
POST   /api/v1/caisse/sales/{id}/reprint           entitlement pos.reprint

POST   /api/v1/caisse/partners/{id}/credit-entries entitlement credit.collect
GET    /api/v1/caisse/partners/{id}/credit-balance

POST   /api/v1/caisse/stock/adjustments            entitlement stock.adjust
POST   /api/v1/caisse/stock/transfers              entitlement stock.transfer
POST   /api/v1/caisse/inventories                  entitlement stock.inventory
```

### 7.4 Synchronisation — cœur du protocole

```text
POST /api/v1/caisse/sync/push
GET  /api/v1/caisse/sync/pull?cursor=&limit=
GET  /api/v1/caisse/sync/status
POST /api/v1/caisse/sync/quarantine/{event_id}/resolve
```

**Requête `push`**

```json
{
  "device_id": "01J...",
  "events": [
    {
      "event_id": "01J8X...",
      "sequence": 4217,
      "entity_type": "sale",
      "entity_id": "01J8W...",
      "action": "create",
      "occurred_at": "2026-08-29T14:03:11+00:00",
      "payload": { }
    }
  ]
}
```

**Réponse `push` — acquittement par événement, jamais global**

```json
{
  "results": [
    { "event_id": "01J8X...", "status": "accepted",    "server_id": "01J8W..." },
    { "event_id": "01J8Y...", "status": "duplicate",   "server_id": "01J8W..." },
    { "event_id": "01J8Z...", "status": "quarantined",
      "code": "SALE_NUMBER_COLLISION",
      "detail": "Numéro DKR1-C02-260829-0147 déjà attribué à une autre vente" }
  ],
  "server_cursor": "1729384",
  "mappings": [
    { "entity_type": "partner", "local_id": "01J8A...", "server_id": "01J7B...",
      "reason": "merged_on_phone" }
  ]
}
```

Trois propriétés à retenir. **Premièrement**, un lot partiellement en échec n'est jamais rejeté en bloc : chaque événement a son verdict, sinon une seule vente malformée bloque la journée entière. **Deuxièmement**, `duplicate` renvoie le `server_id` d'origine, ce qui rend le renvoi sûr après une coupure au moment de la réponse. **Troisièmement**, `mappings` permet au serveur de signaler une fusion (client dédoublonné par téléphone) sans casser les références locales : le client réécrit ses pointeurs.

### 7.5 Restitution

```text
GET /api/v1/caisse/reports/daily?shop_id=&date=
GET /api/v1/caisse/reports/sales-by-product?from=&to=
GET /api/v1/caisse/reports/sales-by-seller?from=&to=
GET /api/v1/caisse/reports/stock?shop_id=&low_only=
GET /api/v1/caisse/reports/credits?status=overdue
GET /api/v1/caisse/reports/cash-variances?from=&to=
GET /api/v1/caisse/reports/offline-anomalies       # ventes en stock négatif, écarts, quarantaine
```

Le dernier endpoint est la contrepartie indispensable de la règle R7 : autoriser le stock négatif n'est acceptable que si le gérant dispose d'un écran qui lui montre exactement où le compteur a décroché.

### 7.6 Actions sensibles auditées — reprend `architecture.md` §19

| Action | Entitlement | Sévérité | PIN requis |
|---|---|---|---|
| Annulation de vente | `pos.void` | critical | Oui |
| Retour / remboursement | `pos.refund` | critical | Oui |
| Remise au-delà du seuil | `pos.discount` | warning | Oui |
| Modification de prix en caisse | `pos.price_override` | warning | Oui |
| Retrait de caisse | `cash.withdraw` | critical | Oui |
| Fermeture de caisse avec écart | `cash.close` | critical | Oui |
| Ajustement de stock | `stock.adjust` | warning | Oui |
| Réimpression de ticket | `pos.reprint` | info | Non |
| Changement de caissier | — | info | Oui |
| Effacement de dette client | `credit.write_off` | critical | Oui |
| Révocation d'appareil | `devices.revoke` | critical | Non |

### 7.7 Codes d'erreur métier — PROPOSÉE

| Code | HTTP | Sens |
|---|---|---|
| `ORG_CONTEXT_INVALID` | 403 | En-tête absent ou organisation non accessible |
| `SUBSCRIPTION_EXPIRED` | 403 | Abonnement expiré, écritures nouvelles bloquées |
| `MODULE_NOT_INCLUDED` | 403 | Module absent du plan |
| `ENTITLEMENT_MISSING` | 403 | Droit fonctionnel absent |
| `QUOTA_EXCEEDED` | 409 | Limite du plan atteinte, avec `limit` et `usage` |
| `DEVICE_NOT_REGISTERED` | 403 | Appareil inconnu ou révoqué |
| `SESSION_ALREADY_OPEN` | 409 | R4 |
| `SESSION_NOT_OPEN` | 409 | Vente sur session close |
| `SALE_IMMUTABLE` | 409 | R1 |
| `SALE_NUMBER_COLLISION` | 409 | Mise en quarantaine, arbitrage humain |
| `PAYMENT_TOTAL_MISMATCH` | 422 | R2 |
| `OFFLINE_LIMIT_REACHED` | 423 | Politique offline §4.4 |
| `IDEMPOTENCY_CONFLICT` | 409 | Même clé, charge utile différente |

---

## 8. Matrice des entitlements

### 8.1 Entitlements existants réutilisés — VALIDÉE

Repris tels quels de `PACK_MODULE_MATRIX.md`, sans modification :

`pos.sell`, `pos.refund`, `pos.discount`, `cash.open`, `cash.close`, `cash.withdraw`,
`sales.create`, `sales.returns`, `products.create`, `products.import`, `stock.adjust`,
`stock.inventory`, `customers.create`, `customers.export`, `suppliers.manage`,
`purchases.create`, `invoices.create`, `invoices.credit_note`, `reports.basic`,
`reports.advanced`, `reports.export`, `users.manage`, `users.roles`,
`marketing.promotions`, `marketing.loyalty`, `performance.objectives`,
`performance.commissions`, `audit.view`, `sync.offline`, `sync.multi_device`,
`dolibarr.sync`, `api.access`, `notifications.telegram`, `notifications.email`

### 8.2 Entitlements à ajouter — PROPOSÉE

Le domaine Caisse fait apparaître des besoins non couverts par la matrice actuelle.

| Entitlement | Module | Justification |
|---|---|---|
| `cash.deposit` | `caisse` | Symétrique de `cash.withdraw` ; une entrée d'espèces n'a pas le même profil de risque qu'un retrait |
| `pos.void` | `caisse` | L'annulation est aujourd'hui implicitement couverte par `pos.refund`, ce qui est inexact : annuler avant encaissement et rembourser après sont deux actes distincts |
| `pos.price_override` | `caisse` | Vecteur de fraude majeur, doit être séparable de `pos.discount` |
| `pos.reprint` | `caisse` | Réimpression = risque de double comptabilisation manuelle |
| `pos.negative_stock` | `caisse` | Autorise la vente en stock insuffisant ; par défaut réservé aux plans supérieurs ou aux gérants |
| `pos.session.transfer` | `caisse` | Reprise d'une session sur un autre appareil (téléphone cassé) |
| `credit.grant` | `caisse` | Autoriser la vente à crédit |
| `credit.collect` | `caisse` | Encaisser un règlement de dette |
| `credit.write_off` | `caisse` | Effacer une dette — action critique |
| `shops.manage` | `utilisateurs` ou nouveau module `configuration` | Création/modification de boutiques, adossée au quota `shops` |
| `terminals.manage` | idem | Adossée au quota `terminals` |
| `devices.pair` | `sync` | Appairage QR |
| `devices.revoke` | `sync` | Révocation d'appareil |
| `stock.transfer` | `stock` | Transferts inter-boutiques, absent de la matrice actuelle |
| `stock.view_cost` | `stock` | Voir le prix d'achat et la marge ; un caissier ne doit pas y accéder |
| `reports.z_report` | `rapports` | Rapport de clôture, distinct des rapports d'analyse |
| `payments.reconcile` | `caisse` | Passage `declared` → `confirmed` des paiements mobiles |

### 8.3 Rattachement aux modules techniques existants

Aucun module nouveau n'est indispensable au MVP. Les entitlements ci-dessus se rattachent aux modules déjà déclarés dans `PACK_MODULE_MATRIX.md` : `caisse`, `stock`, `clients`, `achats`, `rapports`, `utilisateurs`, `sync`, `dolibarr`, `audit`.

**Point ouvert — À VALIDER.** `shops.manage`, `terminals.manage` et `devices.*` relèvent conceptuellement d'une **configuration de la Caisse** plutôt que du module `utilisateurs`. Deux options :

- **Option A** : les rattacher au module `caisse` existant. Simple, aucun changement de matrice.
- **Option B** : créer un module `configuration`. Plus propre, mais modifie la matrice commerciale et donc le discours de vente.

Recommandation : **Option A** pour le MVP, révisable en V1. Le coût d'un déplacement d'entitlement entre modules est faible tant que le nombre de clients est faible ; le coût d'une matrice commerciale instable est élevé.

### 8.4 Quotas nécessaires — PROPOSÉE

Les plans disposent aujourd'hui de `max_users` et `max_products` **[VALIDÉE]**. Le domaine Caisse en réclame d'autres. **Aucune valeur n'est proposée ici** : elles relèvent de l'étude commerciale.

| Clé de quota | Compté sur | Type |
|---|---|---|
| `users` | `organization_user` actifs | existant |
| `products` | `products` non supprimés | existant |
| `shops` | `shops` actives | à ajouter |
| `terminals` | `terminals` actifs | à ajouter |
| `devices` | `devices` en statut `active` | à ajouter |
| `customers` | `partners` de type `customer` | à ajouter |
| `sales_per_month` | `sales` finalisées du mois glissant | à ajouter, **À VALIDER** |
| `storage_mb` | images produits + exports | à ajouter |
| `offline_days` | politique §4.4, par plan | à ajouter |
| `offline_amount_cap` | plafond cumulé hors ligne, §4.4 | à ajouter, **À VALIDER** |

**Attention de conception — [RECO].** Un quota `sales_per_month` est techniquement simple mais commercialement dangereux dans ce contexte : bloquer l'encaissement d'un commerçant en fin de mois est une expérience destructrice, et le blocage se produirait pendant une synchronisation, donc après que les ventes ont eu lieu. Si ce quota est retenu, il doit être **soft** : alerte, invitation à monter de plan, jamais refus d'écriture. Cette réserve est signalée à l'étude commerciale, pas tranchée ici.

Le `QuotaService` existant devra compter des ressources dont une partie arrive **par lot différé** via la synchronisation. Le comptage doit donc être fait au moment de l'application de l'événement côté serveur, et le dépassement doit produire un signal, pas une exception qui perdrait l'événement.

---

## 9. Architecture offline-first Flutter

Socle technique **VALIDÉE** dans `architecture.md` §10 : Flutter/Dart, Riverpod, Drift, SQLite, Dio, Flutter Secure Storage. La présente section précise le fonctionnement, sans le modifier.

### 9.1 Stockage local

```text
SQLite (Drift)
├── Miroir du référentiel        products, variants, barcodes, partners,
│                                payment_methods, taxes, register_profile
│                                → écrasable, reconstructible depuis /bootstrap
├── Données transactionnelles    sales, sale_lines, sale_payments,
│                                cash_sessions, cash_movements,
│                                stock_movements, credit_entries
│                                → SOURCE DE VÉRITÉ tant que non acquittées
├── File de sortie               outbox (sync_events locaux)
├── Curseur d'entrée             sync_state (server_cursor, last_pull_at)
└── Sécurité                     device_id, tokens (Secure Storage, hors SQLite)
```

**Distinction fondamentale.** Le miroir du référentiel peut être effacé et rechargé à tout moment sans perte. Les données transactionnelles non acquittées ne peuvent **jamais** être effacées, ni par une purge de cache, ni par une mise à jour, ni par une déconnexion. Toute action destructive de l'application doit d'abord vérifier que l'outbox est vide, et refuser sinon. C'est la règle qui protège le chiffre d'affaires du client.

**Chiffrement.** Base locale chiffrée (SQLCipher via Drift). Le téléphone d'un commerçant est volé, prêté, revendu. Les prix d'achat, les marges et les dettes clients ne doivent pas être lisibles avec un explorateur de fichiers.

### 9.2 Identifiants

| Objet | Génération | Motif |
|---|---|---|
| `id` de toute entité | ULID côté client | Aucune dépendance réseau |
| `event_id` | ULID côté client | Idempotence |
| Numéro de ticket | Local, format §5.4 | Imprimable immédiatement |
| `device_id` | Attribué par le serveur à l'appairage | Contrôle d'accès |
| `sequence` | Compteur local monotone par appareil | Détection de trous |

Le `sequence` mérite un mot : il permet au serveur de détecter qu'il a reçu les événements 4215 et 4217 mais pas 4216, donc qu'un événement manque. Sans lui, une perte silencieuse est indétectable.

### 9.3 Cycle de synchronisation

```text
     ┌──────────────── déclencheurs ────────────────┐
     │ retour réseau · timer 60 s · fin de vente     │
     │ fermeture de caisse · action manuelle          │
     └───────────────────┬───────────────────────────┘
                         ▼
   1. PUSH   outbox (lots de 50, ordre de sequence strict)
                         ▼
   2. Traiter les acquittements
        accepted    → marquer acked, purger après N jours
        duplicate   → marquer acked (renvoi sûr)
        rejected    → marquer quarantined, notifier le gérant
        mappings    → réécrire les références locales
                         ▼
   3. PULL   /sync/pull?cursor=<server_cursor>
                         ▼
   4. Appliquer le référentiel descendant (miroir)
                         ▼
   5. Recalculer les projections locales (stock_levels, credit_balance)
                         ▼
   6. Mettre à jour sync_state, last_synced_at, indicateur d'interface
```

**Push avant pull, toujours.** Remonter d'abord ce qui a été vendu garantit que le travail du commerçant est en sécurité avant toute autre opération. Un pull qui échoue est sans conséquence ; un push repoussé est un risque de perte.

**Ordre strict par `sequence`.** Les événements d'un appareil sont appliqués dans l'ordre. Un lot ne progresse pas au-delà d'un événement en quarantaine sans décision explicite, faute de quoi un paiement pourrait être appliqué à une vente jamais créée.

### 9.4 Résolution des conflits — PROPOSÉE

| Type de donnée | Stratégie | Justification |
|---|---|---|
| Vente, ligne, paiement, mouvement de caisse | **Append-only, immuable.** Le serveur accepte ou met en quarantaine, jamais de fusion. | Un fait comptable ne se fusionne pas |
| Stock | **Rejeu de mouvements (CRDT de type compteur).** Les deltas commutent, l'ordre n'a pas d'importance. | Deux terminaux hors ligne convergent naturellement |
| Dette client | Idem stock : somme d'écritures signées | Même propriété |
| Produit, prix, catégorie, taxe | **Le serveur gagne.** Le terminal ne modifie pas ces données sauf entitlement explicite. | Empêche un terminal compromis de modifier les prix |
| Client créé hors ligne | **Fusion serveur sur `phone_normalized`**, retour d'un `mapping` | Cas très fréquent : deux vendeurs créent le même client |
| Session de caisse | **Premier arrivé gagne** ; une seconde ouverture concurrente part en quarantaine | R4 |
| Numéro de ticket | **Collision = quarantaine**, jamais renumérotation automatique | Une renumérotation silencieuse casserait le lien avec le ticket papier déjà remis au client |

Le stock est le point où l'architecture se joue. En traitant `stock_levels` comme une projection de `stock_movements`, la synchronisation devient commutative : peu importe que le terminal A remonte avant ou après le terminal B, la somme est la même. C'est ce qui permet de tenir la promesse « plusieurs vendeurs, plusieurs caisses, réseau intermittent » sans verrou distribué.

### 9.5 Idempotence et reprise

**Coupure au pire moment.** Le terminal envoie l'événement, le serveur l'écrit, la réponse se perd. Le terminal renvoie. La contrainte `UNIQUE (organization_id, event_id)` fait retourner `duplicate` avec le `server_id` d'origine. Aucune double vente. C'est le seul mécanisme nécessaire, à condition qu'il soit systématique.

**Contrôle d'intégrité.** `payload_hash` détecte le cas où une même clé revient avec une charge utile différente — bug client ou tentative de manipulation. Réponse : `409 IDEMPOTENCY_CONFLICT`, mise en quarantaine, alerte.

**Quarantaine.** Un événement rejeté n'est jamais perdu. Il reste visible dans un écran dédié, avec son motif et sa charge utile, et une action de résolution : réappliquer après correction, ou abandonner avec motif. Un événement abandonné reste dans `audit_logs`. Le principe : **le système peut refuser d'appliquer, il ne peut jamais effacer.**

### 9.6 Migrations locales et sauvegardes — VALIDÉE, reprise de `architecture.md` §25

Rappel, sans modification : sauvegarde SQLite avant migration, vérification, reprise, et interdiction pour une migration de supprimer des opérations non synchronisées.

**[RECO] Complément :** bloquer toute migration destructive tant que l'outbox n'est pas vide, et exposer dans les réglages un export local (JSON ou CSV chiffré) que le commerçant peut déclencher lui-même. C'est le dernier recours quand un téléphone est en fin de vie.

### 9.7 Horloge

Les téléphones d'entrée de gamme dérivent, et l'heure est parfois réglée à la main. **[RECO]**

- Conserver `occurred_at` (heure du terminal) **et** `received_at` (heure du serveur).
- Calculer une dérive à chaque synchronisation, la stocker sur le `device`.
- Au-delà d'un seuil (par exemple 10 minutes), afficher un avertissement et journaliser.
- Ne **jamais** corriger silencieusement `occurred_at` : c'est l'heure déclarée par le commerçant, elle a une valeur probante.
- Les rapports de session s'appuient sur les bornes de la session, pas sur l'horloge murale — ce qui neutralise l'essentiel du problème.

---

## 10. Stratégie d'intégration Dolibarr

**Statut : PROPOSÉE. Portée : V2**, conformément à `architecture.md` §20 qui pose déjà le connecteur comme optionnel selon le pack.

### 10.1 Principe directeur

```text
Yessal Caisse → Yessal API → Connecteur Dolibarr → Instance Dolibarr client
```

Chaîne **VALIDÉE** dans `architecture.md` : Flutter ne parle jamais directement à Dolibarr.

**[RECO] Décision structurante : Yessal est la source de vérité de l'exploitation, Dolibarr est le miroir comptable.** Le flux est principalement **sortant**. Une synchronisation bidirectionnelle complète sur les ventes créerait deux vérités concurrentes et une classe de bugs impossible à supporter. Les rares flux entrants sont limités et explicites.

### 10.2 Mécanisme technique

L'API REST de Dolibarr est activable par module, s'authentifie par clé `DOLAPIKEY` propre à un utilisateur, et n'expose que les ressources des modules activés **[WEB]**. Elle couvre notamment `thirdparties`, `products`, `invoices`, `orders`, `stockmovements`, `warehouses`, `payments` **[WEB]**.

Conséquences pratiques :

1. Le connecteur doit **découvrir** les endpoints disponibles à la connexion, car ils dépendent des modules activés chez le client. Un connecteur qui suppose `/invoices` disponible échouera chez un client sans module Facturation. **[RECO]**
2. Un **utilisateur Dolibarr dédié** doit être créé pour Yessal, avec des droits minimaux, jamais un compte administrateur partagé. **[RECO]**
3. La clé est stockée chiffrée côté serveur Yessal, jamais transmise à Flutter.

### 10.3 Mapping des entités — PROPOSÉE

| Yessal | Dolibarr | Direction | Clé de correspondance |
|---|---|---|---|
| `partners` (customer) | `thirdparties` avec `client=1` | Yessal → Dolibarr | `code` Yessal ↔ `code_client` |
| `partners` (supplier) | `thirdparties` avec `fournisseur=1` | Yessal → Dolibarr | `code` ↔ `code_fournisseur` |
| `products` / `product_variants` | `products` | Bidirectionnel encadré | **`sku` ↔ `ref`** |
| `shops` | `warehouses` | Yessal → Dolibarr | `code` ↔ `ref` |
| `stock_movements` | `stockmovements` | Yessal → Dolibarr | `external_refs` |
| `sales` finalisées | `invoices` (facture validée) | Yessal → Dolibarr | `number` ↔ `ref_client` |
| `sale_payments` confirmés | `payments` sur facture | Yessal → Dolibarr | `external_refs` |
| `sale_returns` | `invoices` de type avoir | Yessal → Dolibarr | `external_refs` |
| `purchases` | `supplier invoices` | Yessal → Dolibarr | `external_refs` |
| Catalogue initial | `products` | Dolibarr → Yessal, **import ponctuel uniquement** | `ref` ↔ `sku` |

**Le SKU est la clé de voûte.** La correspondance `sku ↔ ref` doit être imposée dès la création du produit dans Yessal, avec un format normalisé et un contrôle d'unicité. Les retours d'expérience d'intégration POS convergent sur ce point : les SKU dupliqués sont la première cause de conflits de synchronisation et d'affectation erronée des commandes **[WEB]**. C'est aussi la conclusion déjà tirée dans les travaux préparatoires du projet.

**Variantes.** Dolibarr ne gère les variantes que via un module dédié. **[À VALIDER]** Deux stratégies :

- **Stratégie A — aplatissement** : chaque `product_variant` devient un `product` Dolibarr distinct, avec un SKU composé (`REF-ROUGE-M`). Fonctionne sur toute instance Dolibarr, sans module. Perd la structure de variante.
- **Stratégie B — module Variantes** : conserve la structure, exige que le client ait installé et activé le module.

Recommandation : **Stratégie A par défaut**, Stratégie B en option détectée automatiquement. La priorité est que le connecteur fonctionne sur une instance Dolibarr standard.

### 10.4 Cadence de synchronisation — PROPOSÉE

**Décision : synchronisation au niveau de la clôture de session, pas à la vente.**

```text
Fermeture de session de caisse validée
        ↓
Fil d'attente (Laravel queue)
        ↓
1. Tiers manquants        → POST /thirdparties
2. Produits manquants     → POST /products
3. Ventes de la session   → POST /invoices  (une facture par vente)
4. Validation             → POST /invoices/{id}/validate
5. Paiements confirmés    → POST /invoices/{id}/payments
6. Mouvements de stock    → POST /stockmovements  (si module Stock actif)
        ↓
Écriture dans external_refs, statut linked | error
```

Trois raisons : la clôture est le moment où les données sont stables et complètes ; elle correspond au rythme comptable réel d'un commerce ; et elle évite d'inonder une instance Dolibarr mutualisée d'appels unitaires.

Les ventes à crédit posent une question spécifique : la facture Dolibarr est créée validée mais **non payée**, et le paiement remonte au moment du règlement de la dette. C'est exactement le comportement comptable attendu.

### 10.5 Robustesse

| Sujet | Traitement |
|---|---|
| Instance injoignable | File d'attente avec relances à backoff exponentiel, alerte après N échecs |
| Idempotence | `external_refs` consulté avant tout POST ; jamais de création à l'aveugle |
| Rejet Dolibarr | Statut `error` avec message, écran de reprise, **jamais de perte côté Yessal** |
| Rejeu | Action « rejouer la session » sur une plage de dates |
| Modules absents | Détection à la connexion, dégradation annoncée à l'utilisateur |
| Ordre | Dépendances respectées : tiers → produits → factures → paiements |
| Sécurité | Clé chiffrée, jamais côté client, entitlement `dolibarr.sync` requis |

### 10.6 Ce que le connecteur ne fera pas — exclusions assumées

- Pas de synchronisation descendante des ventes : Dolibarr ne crée pas de ventes dans Yessal.
- Pas d'écriture comptable en partie double par Yessal : c'est Dolibarr qui la produit.
- Pas de synchronisation temps réel : cadence de clôture, cf. §10.4.
- Pas de suppression dans Dolibarr : Yessal ne supprime jamais un document distant.

---

## 11. Registre des décisions

### 11.1 Décisions reprises sans modification — VALIDÉE

| # | Décision | Source |
|---|---|---|
| V1 | Chaîne Utilisateur → Organisation → Abonnement → Plan → Modules → Entitlements → Quotas | `ROADMAP.md` |
| V2 | Grâce de 3 jours, cycle `active → past_due → expired` | `CHANGELOG.md` |
| V3 | Sanctum, login accessible même abonnement expiré | `architecture.md` §7 |
| V4 | Flutter, Riverpod, Drift, SQLite, Dio, Secure Storage | `architecture.md` §10 |
| V5 | Politique offline 0-24 h / 24-72 h / 72 h+ / 7 j+ / 14 j | `architecture.md` §24 |
| V6 | PIN 4 chiffres, verrouillage 10 min par défaut, liste des actions sensibles | `architecture.md` §12 |
| V7 | Appairage par QR temporaire, à usage unique, journalisé, sans secret permanent | `architecture.md` §13 |
| V8 | Connecteur Dolibarr optionnel selon le pack | `architecture.md` §20 |
| V9 | Tickets 58 mm et 80 mm prioritaires | `architecture.md` §23 |
| V10 | Tarifs et limites non définitifs | `CHANGELOG.md`, `architecture.md` §27 |

### 11.2 Décisions proposées par l'étude — PROPOSÉE

| # | Décision | Impact si rejetée |
|---|---|---|
| P1 | Offline-first strict, ouverture de session incluse | Perte du principal avantage concurrentiel |
| P2 | ULID côté client comme clé primaire | Retour à une dépendance réseau à la vente |
| P3 | Stock = projection de mouvements | Pertes de stock silencieuses en multi-terminal |
| P4 | Documents financiers immuables | Auditabilité compromise |
| P5 | Variante systématique, même pour produit simple | Refonte lourde à l'introduction des variantes |
| P6 | Table `partners` unifiée client/fournisseur | Duplication de tiers, mapping Dolibarr compliqué |
| P7 | Montants entiers avec exposant explicite | Erreurs d'arrondi, migration future |
| P8 | Paiements mobiles `declared` → `confirmed` | Caisse bloquée par la disponibilité d'un tiers |
| P9 | Vente à crédit dans le MVP | Produit inadapté au marché visé |
| P10 | Séparation `cashier_user_id` / `seller_user_id` | Commissions V2 impossibles sans migration |
| P11 | `external_refs` créée dès le MVP | Rétro-mapping impossible en V2 |
| P12 | Stock négatif autorisé mais signalé | Ventes refusées à tort, abandon de l'application |
| P13 | Synchronisation Dolibarr à la clôture de session | Charge excessive sur les instances clients |
| P14 | Triple barrière tenant (scope, validation, FK composite) | Risque de fuite inter-organisations |
| P15 | Base locale chiffrée | Exposition des marges et des dettes en cas de vol |

### 11.3 Points à trancher — À VALIDER

| # | Question | Instance |
|---|---|---|
| A1 | Valeurs des quotas `shops`, `terminals`, `devices`, `customers` par plan | Étude commerciale |
| A2 | Quota `sales_per_month` : retenu ou écarté ? Si retenu, soft uniquement | Étude commerciale |
| A3 | Plafond cumulé hors ligne `offline_amount_cap` | Commercial + technique |
| A4 | Module `configuration` séparé (Option B §8.3) ou rattachement à `caisse` (Option A) | Produit |
| A5 | Stratégie variantes Dolibarr : aplatissement ou module Variantes | Technique |
| A6 | Choix d'intégration paiement : agrégateur (PayDunya, CinetPay), API Wave Business directe, ou QR marchand manuel | Commercial + juridique + technique |
| A7 | Périmètre TVA : préparation seulement en V1, ou conformité fiscale complète | Juridique |
| A8 | Rétention des `audit_logs` et `sync_events` par plan | Commercial |
| A9 | Langues de l'interface : français seul au MVP, wolof en V1 ? | Produit |
| A10 | Multi-boutique dans le MVP ou en V1 | Produit + commercial |

Le point **A6** est le plus urgent car il a un délai externe : l'ouverture d'un compte marchand Wave passe par l'application Wave Business ou le portail marchand, avec un délai d'activation de l'ordre de cinq à dix jours ouvrés **[WEB]**. Si l'intégration directe est retenue, la démarche doit être lancée avant le développement, pas après.

---

## 12. Mises à jour proposées de la documentation

**Statut : PROPOSÉE. Aucun fichier du projet n'a été modifié par cette étude.**

### 12.1 `ROADMAP.md`

Remplacer la section « Prochaines étapes » par une version intégrant le domaine Caisse, sans toucher aux sections « Réalisé » et « Tests validés » :

```markdown
## Prochaines étapes

### Plateforme (en cours)
1. Finaliser le moteur de quotas.
2. Ajouter le comptage réel des ressources.
3. Protéger les endpoints métier.

### Caisse — après validation de l'étude
4. Valider l'étude comparative et le modèle métier Caisse (ETUDE_CAISSE_v1.md).
5. Arbitrer les points A1 à A10 du registre de décisions.
6. Étendre la matrice des entitlements (17 entitlements proposés).
7. Ajouter les quotas shops, terminals, devices, customers, storage, offline.
8. Écrire les migrations du domaine Caisse — MVP uniquement.
9. Implémenter le protocole de synchronisation (push, pull, quarantaine).
10. Implémenter l'API /api/v1/caisse.

### Commercial (bloquant pour 4 et 7)
11. Valider commercialement Free, Caisse, Business, Association et Pro.
12. Associer packs, modules et entitlements définitifs.
13. Fixer les valeurs de quotas par plan.

### Flutter
14. Préparer l'API Flutter / Yessal Caisse.
15. Implémenter le socle offline-first (Drift, outbox, ULID).

### Qualité
16. Ajouter les tests automatisés, dont les tests d'isolation multi-tenant.
```

### 12.2 `CHANGELOG.md`

Ajouter en tête :

```markdown
## 2026-08-29 — Étude Caisse

### Documentation
- Ajout de `ETUDE_CAISSE_v1.md` : étude comparative de 8 POS et
  conception du domaine métier Yessal Caisse.
- Périmètre MVP / V1 / V2 / avancé / hors périmètre proposé.
- Modèle de données proposé : 30 tables, contraintes tenant, index, audit.
- Protocole de synchronisation offline-first proposé.
- Stratégie d'intégration Dolibarr proposée.

### Gouvernance
- 15 décisions PROPOSÉES, 10 points À VALIDER (registre §11).
- Aucune migration métier Caisse créée, conformément au brief de recherche.
- Aucun tarif ni limite commerciale fixé.
```

### 12.3 `PACK_MODULE_MATRIX.md`

Trois ajouts, sans modifier l'existant :

1. **Section « Entitlements proposés — non validés »** reprenant les 17 entitlements du §8.2, clairement séparée de la liste actuelle.
2. **Section « Quotas »** listant les dix clés du §8.4, avec la mention explicite « valeurs à valider commercialement ».
3. **Note sur `stock.transfer`**, absent de la matrice actuelle alors que le module `stock` est déclaré et que les transferts inter-boutiques sont prévus.

Rappel : la ligne « Association de test — Pack Tambali → Caisse → pos.sell » reste une association de test et n'est pas modifiée.

### 12.4 `CAISSE_RESEARCH_BRIEF.md`

La décision actuelle — *« Aucune migration métier Caisse avant validation de l'étude comparative et du modèle métier »* — reste **VALIDÉE et en vigueur**. La présente étude constitue le livrable attendu ; la levée du gel relève d'une décision explicite après arbitrage du registre §11.

---

## Annexe A — Sources

**Documents du projet** : `ROADMAP.md`, `CHANGELOG.md`, `CAISSE_RESEARCH_BRIEF.md`, `PACK_MODULE_MATRIX.md`, `architecture.md`, `Tableau-tarifs-des-packs-yessalerp.txt`.

**Sources web consultées le 29 août 2026**

Odoo POS
- https://www.odoo.com/forum/help-1/can-odoo-pos-work-without-internet-offline-mode-without-custom-module-303143
- https://www.odoo.com/forum/help-1/what-s-the-mechanism-of-pos-offline-283217
- https://www.odoo.com/forum/help-1/how-is-the-point-of-sale-offline-mode-working-218314

Dolibarr / TakePOS
- https://wiki.dolibarr.org/index.php/Module_Point_de_vente_(TakePOS)
- https://wiki.dolibarr.org/index.php/Module_Web_Services_API_REST_(developer)
- https://deepwiki.com/Dolibarr/dolibarr/11.1-rest-api
- https://www.dolistore.com/fr/45-pos-point-de-vente

Loyverse
- https://loyverse.com/
- https://www.capterra.com/p/150632/Loyverse-POS/
- https://www.softwareadvice.com/retail/loyverse-profile/
- https://www.flozic.ai/blog/reviews/loyverse-review

Square
- https://squareup.com/us/en/press/square-brings-offline-payments
- https://squareup.com/help/us/en/article/7777-process-card-payments-with-offline-mode
- https://www.merchantmaverick.com/pos-101-offline-mode/

Lightspeed Retail
- https://www.lightspeedhq.com/pos/retail/inventory-management-software/
- https://www.lightspeedhq.com/pos/retail/midsize-business-pos/
- https://www.techrepublic.com/article/lightspeed-retail-review/
- https://www.numinix.com/blog/lightspeed-retail-pos-integration-connecting-inventory-online-orders-and-multi-location-operations/

Kyte
- https://www.kyteapp.com/
- https://www.kyteapp.com/pricing
- https://www.selecthub.com/p/pos-software/kyte-app/

UltimatePOS
- https://codecanyon.net/item/ultimate-pos-stock-management-point-of-sale-application/21216332
- https://ultimatefosters.com/docs/ultimatepos/feature-list-for-ultimatepos/
- https://ultimatefosters.com/docs/ultimatepos/release-notes-version-log-for-ultimatepos/ultimatepos-3-x-release-notes/

ERPNext POS
- https://github.com/wahni-green/POS-Awesome-V15
- https://www.ksolves.com/blog/erpnext/erpnext-pos-vs-traditional-pos-systems
- https://www.ksolves.com/blog/erpnext/how-erpnext-pos-transforms-retail-operations

Paiements Afrique de l'Ouest
- https://kolonell.com/fr/blog/wave-business-api-integration-guide-2026
- https://absitech.dev/blog/integrer-wave-orange-money-stripe-e-commerce-senegalais-2026
- https://developer.orange.com/apis/om-webpay

---

## Annexe B — Vérification du respect des règles du brief

| Règle | Respect |
|---|---|
| Ne pas modifier arbitrairement les décisions validées | Aucune décision validée modifiée ; §11.1 les recense telles quelles |
| Ne pas fixer les tarifs ni les limites commerciales | Aucun montant, aucune valeur de quota proposée |
| Distinguer faits, recherche web, recommandations et hypothèses | Marquage [FAIT] / [WEB] / [RECO] / [HYP] appliqué |
| Marquer PROPOSÉE / VALIDÉE / À VALIDER | Registre §11 |
| Aucune migration Laravel avant validation | Aucun fichier de migration produit |
| Préserver l'évolutivité, éviter une refonte | Décisions P2, P3, P5, P7, P10, P11 visent explicitement ce point |
