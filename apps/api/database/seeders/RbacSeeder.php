<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $permissions = [
                // Organisation
                [
                    'module' => 'organization',
                    'name' => 'Voir les membres',
                    'slug' => 'organization.members.view',
                    'description' => 'Consulter les membres de l’organisation.',
                ],
                [
                    'module' => 'organization',
                    'name' => 'Gérer les membres',
                    'slug' => 'organization.members.manage',
                    'description' => 'Ajouter, modifier et retirer des membres.',
                ],
                [
                    'module' => 'organization',
                    'name' => 'Gérer les rôles',
                    'slug' => 'organization.roles.manage',
                    'description' => 'Gérer les groupes de permissions et les rôles.',
                ],

                // Ventes
                [
                    'module' => 'sales',
                    'name' => 'Voir les ventes',
                    'slug' => 'sales.view',
                    'description' => 'Consulter les ventes.',
                ],
                [
                    'module' => 'sales',
                    'name' => 'Créer une vente',
                    'slug' => 'sales.create',
                    'description' => 'Créer une nouvelle vente.',
                ],
                [
                    'module' => 'sales',
                    'name' => 'Modifier une vente',
                    'slug' => 'sales.edit',
                    'description' => 'Modifier une vente.',
                ],
                [
                    'module' => 'sales',
                    'name' => 'Finaliser une vente',
                    'slug' => 'sales.finalize',
                    'description' => 'Finaliser et valider une vente.',
                ],
                [
                    'module' => 'sales',
                    'name' => 'Annuler une vente',
                    'slug' => 'sales.cancel',
                    'description' => 'Annuler une vente.',
                ],

                // Produits
                [
                    'module' => 'products',
                    'name' => 'Voir les produits',
                    'slug' => 'products.view',
                    'description' => 'Consulter les produits.',
                ],
                [
                    'module' => 'products',
                    'name' => 'Gérer les produits',
                    'slug' => 'products.manage',
                    'description' => 'Créer, modifier et supprimer des produits.',
                ],

                // Stock
                [
                    'module' => 'stock',
                    'name' => 'Voir le stock',
                    'slug' => 'stock.view',
                    'description' => 'Consulter les niveaux de stock.',
                ],
                [
                    'module' => 'stock',
                    'name' => 'Gérer le stock',
                    'slug' => 'stock.manage',
                    'description' => 'Gérer les mouvements et ajustements de stock.',
                ],

                // Clients
                [
                    'module' => 'customers',
                    'name' => 'Voir les clients',
                    'slug' => 'customers.view',
                    'description' => 'Consulter les clients.',
                ],
                [
                    'module' => 'customers',
                    'name' => 'Gérer les clients',
                    'slug' => 'customers.manage',
                    'description' => 'Créer, modifier et supprimer des clients.',
                ],

                // Caisse
                [
                    'module' => 'cash',
                    'name' => 'Voir les sessions de caisse',
                    'slug' => 'cash.view',
                    'description' => 'Consulter les sessions de caisse.',
                ],
                [
                    'module' => 'cash',
                    'name' => 'Ouvrir une caisse',
                    'slug' => 'cash.open',
                    'description' => 'Ouvrir une session de caisse.',
                ],
                [
                    'module' => 'cash',
                    'name' => 'Fermer une caisse',
                    'slug' => 'cash.close',
                    'description' => 'Fermer une session de caisse.',
                ],
                [
                    'module' => 'cash',
                    'name' => 'Voir les mouvements de caisse',
                    'slug' => 'cash.movements.view',
                    'description' => 'Consulter les mouvements de caisse.',
                ],

                // Appareils
                [
                    'module' => 'devices',
                    'name' => 'Voir les appareils',
                    'slug' => 'devices.view',
                    'description' => 'Consulter les appareils de caisse.',
                ],
                [
                    'module' => 'devices',
                    'name' => 'Gérer les appareils',
                    'slug' => 'devices.manage',
                    'description' => 'Créer, modifier, activer et révoquer les appareils.',
                ],

                // Synchronisation
                [
                    'module' => 'sync',
                    'name' => 'Synchroniser les données',
                    'slug' => 'sync.push',
                    'description' => 'Envoyer les événements de synchronisation.',
                ],

                // Boutiques
                [
                    'module' => 'shops',
                    'name' => 'Voir les boutiques',
                    'slug' => 'shops.view',
                    'description' => 'Consulter les boutiques de l’organisation.',
                ],
                [
                    'module' => 'shops',
                    'name' => 'Gérer les boutiques',
                    'slug' => 'shops.manage',
                    'description' => 'Créer et modifier les boutiques.',
                ],

                // Terminaux
                [
                    'module' => 'terminals',
                    'name' => 'Voir les terminaux',
                    'slug' => 'terminals.view',
                    'description' => 'Consulter les terminaux de caisse.',
                ],
                [
                    'module' => 'terminals',
                    'name' => 'Gérer les terminaux',
                    'slug' => 'terminals.manage',
                    'description' => 'Créer et modifier les terminaux.',
                ],

                // Rapports
                [
                    'module' => 'reports',
                    'name' => 'Voir les rapports',
                    'slug' => 'reports.view',
                    'description' => 'Consulter les rapports de l’organisation.',
                ],
            ];

            foreach ($permissions as $permission) {
                Permission::updateOrCreate(
                    ['slug' => $permission['slug']],
                    [
                        'module' => $permission['module'],
                        'name' => $permission['name'],
                        'description' => $permission['description'],
                        'is_active' => true,
                    ]
                );
            }

            // Administrateur
            $this->createSystemRole(
                'Administrateur',
                'admin',
                'Administrateur de l’organisation.',
                array_column($permissions, 'slug')
            );

            // Manager
            $this->createSystemRole(
                'Manager',
                'manager',
                'Gestionnaire opérationnel avec gestion des ventes.',
                [
                    'sales.view',
                    'sales.create',
                    'sales.edit',
                    'sales.finalize',
                    'sales.cancel',
                    'products.view',
                    'stock.view',
                    'customers.view',
                    'customers.manage',
                    'cash.open',
                    'cash.close',
                    'cash.view',
                    'cash.movements.view',
                    'devices.view',
                    'devices.manage',
                    'sync.push',
                    'reports.view',
                    'shops.view',
                    'terminals.view',
                ]
            );

            // Caissier
            $this->createSystemRole(
                'Caissier',
                'cashier',
                'Utilisateur chargé des opérations de caisse et des ventes.',
                [
                    'sales.view',
                    'sales.create',
                    'products.view',
                    'stock.view',
                    'customers.view',
                    'cash.open',
                    'cash.close',
                    'cash.view',
                    'cash.movements.view',
                    'devices.view',
                    'sync.push',
                    'terminals.view',
                ]
            );
        });
    }

    private function createSystemRole(
        string $name,
        string $slug,
        string $description,
        array $permissionSlugs
    ): Role {
        $role = Role::updateOrCreate(
            [
                'organization_id' => null,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'description' => $description,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $permissions = Permission::whereIn(
            'slug',
            $permissionSlugs
        )->pluck('id');

        $role->permissions()->sync($permissions);

        return $role;
    }
}
