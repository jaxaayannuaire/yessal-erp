<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Caisse',
                'slug' => 'caisse',
                'description' => 'Gestion des caisses, ventes et paiements.',
                'sort_order' => 10,
            ],
            [
                'name' => 'Ventes',
                'slug' => 'ventes',
                'description' => 'Gestion des ventes, commandes, devis et retours.',
                'sort_order' => 20,
            ],
            [
                'name' => 'Produits & Stock',
                'slug' => 'stock',
                'description' => 'Gestion des produits, stocks et inventaires.',
                'sort_order' => 30,
            ],
            [
                'name' => 'Clients',
                'slug' => 'clients',
                'description' => 'Gestion des clients et de leur historique.',
                'sort_order' => 40,
            ],
            [
                'name' => 'Fournisseurs & Achats',
                'slug' => 'achats',
                'description' => 'Gestion des fournisseurs et achats.',
                'sort_order' => 50,
            ],
            [
                'name' => 'Facturation',
                'slug' => 'facturation',
                'description' => 'Gestion des factures, reçus et avoirs.',
                'sort_order' => 60,
            ],
            [
                'name' => 'Rapports',
                'slug' => 'rapports',
                'description' => 'Tableaux de bord, rapports et exports.',
                'sort_order' => 70,
            ],
            [
                'name' => 'Utilisateurs & Équipes',
                'slug' => 'utilisateurs',
                'description' => 'Gestion des utilisateurs, rôles et équipes.',
                'sort_order' => 80,
            ],
            [
                'name' => 'CRM',
                'slug' => 'crm',
                'description' => 'Gestion des prospects et du suivi commercial.',
                'sort_order' => 90,
            ],
            [
                'name' => 'Promotions & Fidélité',
                'slug' => 'marketing',
                'description' => 'Promotions, coupons et fidélité.',
                'sort_order' => 100,
            ],
            [
                'name' => 'Objectifs & Commissions',
                'slug' => 'performance',
                'description' => 'Objectifs, commissions et performances.',
                'sort_order' => 110,
            ],
            [
                'name' => 'Audit & Sécurité',
                'slug' => 'audit',
                'description' => 'Journalisation et suivi des actions sensibles.',
                'sort_order' => 120,
            ],
            [
                'name' => 'Synchronisation',
                'slug' => 'sync',
                'description' => 'Synchronisation offline et multi-appareils.',
                'sort_order' => 130,
            ],
            [
                'name' => 'Intégration Dolibarr',
                'slug' => 'dolibarr',
                'description' => 'Connexion et synchronisation avec Dolibarr.',
                'sort_order' => 140,
            ],
            [
                'name' => 'API & Intégrations',
                'slug' => 'api',
                'description' => 'API externes, webhooks et connecteurs.',
                'sort_order' => 150,
            ],
            [
                'name' => 'Notifications',
                'slug' => 'notifications',
                'description' => 'Notifications Telegram, email et temps réel.',
                'sort_order' => 160,
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(
                ['slug' => $module['slug']],
                $module + ['is_active' => true]
            );
        }
    }
}