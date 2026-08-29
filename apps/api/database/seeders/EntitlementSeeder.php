<?php

namespace Database\Seeders;

use App\Models\Entitlement;
use App\Models\Module;
use Illuminate\Database\Seeder;

class EntitlementSeeder extends Seeder
{
    public function run(): void
    {
        $entitlements = [
            'caisse' => [
                ['name' => 'Effectuer une vente', 'slug' => 'pos.sell'],
                ['name' => 'Rembourser une vente', 'slug' => 'pos.refund'],
                ['name' => 'Appliquer une remise', 'slug' => 'pos.discount'],
                ['name' => 'Ouvrir une caisse', 'slug' => 'cash.open'],
                ['name' => 'Fermer une caisse', 'slug' => 'cash.close'],
                ['name' => 'Effectuer un retrait de caisse', 'slug' => 'cash.withdraw'],
            ],

            'ventes' => [
                ['name' => 'Créer une vente', 'slug' => 'sales.create'],
                ['name' => 'Créer un devis', 'slug' => 'sales.quote'],
                ['name' => 'Créer une commande', 'slug' => 'sales.order'],
                ['name' => 'Gérer les retours', 'slug' => 'sales.returns'],
            ],

            'stock' => [
                ['name' => 'Créer des produits', 'slug' => 'products.create'],
                ['name' => 'Importer des produits', 'slug' => 'products.import'],
                ['name' => 'Ajuster le stock', 'slug' => 'stock.adjust'],
                ['name' => 'Réaliser un inventaire', 'slug' => 'stock.inventory'],
            ],

            'clients' => [
                ['name' => 'Créer des clients', 'slug' => 'customers.create'],
                ['name' => 'Exporter les clients', 'slug' => 'customers.export'],
            ],

            'achats' => [
                ['name' => 'Gérer les fournisseurs', 'slug' => 'suppliers.manage'],
                ['name' => 'Créer des achats', 'slug' => 'purchases.create'],
                ['name' => 'Créer des commandes fournisseurs', 'slug' => 'purchases.order'],
            ],

            'facturation' => [
                ['name' => 'Créer des factures', 'slug' => 'invoices.create'],
                ['name' => 'Créer des avoirs', 'slug' => 'invoices.credit_note'],
            ],

            'rapports' => [
                ['name' => 'Consulter les rapports de base', 'slug' => 'reports.basic'],
                ['name' => 'Consulter les rapports avancés', 'slug' => 'reports.advanced'],
                ['name' => 'Exporter les rapports', 'slug' => 'reports.export'],
            ],

            'utilisateurs' => [
                ['name' => 'Gérer les utilisateurs', 'slug' => 'users.manage'],
                ['name' => 'Gérer les équipes', 'slug' => 'teams.manage'],
                ['name' => 'Gérer les rôles', 'slug' => 'users.roles'],
            ],

            'crm' => [
                ['name' => 'Gérer les prospects', 'slug' => 'crm.leads'],
                ['name' => 'Gérer les activités CRM', 'slug' => 'crm.activities'],
            ],

            'marketing' => [
                ['name' => 'Gérer les promotions', 'slug' => 'marketing.promotions'],
                ['name' => 'Gérer la fidélité', 'slug' => 'marketing.loyalty'],
            ],

            'performance' => [
                ['name' => 'Gérer les objectifs', 'slug' => 'performance.objectives'],
                ['name' => 'Gérer les commissions', 'slug' => 'performance.commissions'],
            ],

            'audit' => [
                ['name' => 'Consulter les journaux d’audit', 'slug' => 'audit.view'],
            ],

            'sync' => [
                ['name' => 'Utiliser le mode offline', 'slug' => 'sync.offline'],
                ['name' => 'Synchroniser plusieurs appareils', 'slug' => 'sync.multi_device'],
            ],

            'dolibarr' => [
                ['name' => 'Synchroniser avec Dolibarr', 'slug' => 'dolibarr.sync'],
            ],

            'api' => [
                ['name' => 'Accéder à l’API', 'slug' => 'api.access'],
            ],

            'notifications' => [
                ['name' => 'Notifications Telegram', 'slug' => 'notifications.telegram'],
                ['name' => 'Notifications email', 'slug' => 'notifications.email'],
            ],
        ];

        foreach ($entitlements as $moduleSlug => $moduleEntitlements) {
            $module = Module::where('slug', $moduleSlug)->first();

            if (! $module) {
                continue;
            }

            foreach ($moduleEntitlements as $entitlementData) {
                $entitlement = Entitlement::updateOrCreate(
                    ['slug' => $entitlementData['slug']],
                    $entitlementData + [
                        'description' => null,
                        'is_active' => true,
                    ]
                );

                $module->entitlements()->syncWithoutDetaching(
                    [$entitlement->id]
                );
            }
        }
    }
}