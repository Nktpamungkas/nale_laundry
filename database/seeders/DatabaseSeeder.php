<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\ServicePackage;
use App\Models\ServicePackageMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'owner@nale-laundry.test'],
            [
                'name' => 'Owner Nale Laundry',
                'phone' => '081234567880',
                'role' => 'owner',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@nale-laundry.test'],
            [
                'name' => 'Admin Nale Laundry',
                'phone' => '081234567890',
                'role' => 'admin',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'kasir@nale-laundry.test'],
            [
                'name' => 'Kasir Nale Laundry',
                'phone' => '081234567891',
                'role' => 'kasir',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'operator@nale-laundry.test'],
            [
                'name' => 'Operator Nale Laundry',
                'phone' => '081234567892',
                'role' => 'operator',
                'is_active' => true,
                'password' => Hash::make('password'),
            ]
        );

        $customers = [
            [
                'code' => 'CUST-00001',
                'name' => 'Dhedhe Pratiwi',
                'phone' => '085156637499',
                'email' => 'dhedhepratiwi@gmail.com',
                'address' => null,
            ],
            [
                'code' => 'CUST-00002',
                'name' => 'Nilo',
                'phone' => '081293517242',
                'email' => 'nktpamungkas28@gmail.com',
                'address' => null,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::query()->updateOrCreate(
                ['code' => $customer['code']],
                $customer
            );
        }

        $detergent = InventoryItem::query()->updateOrCreate(
            ['sku' => 'INV-DETERGENT-01'],
            [
                'name' => 'Detergen Cair Premium',
                'category' => 'Bahan Cuci',
                'unit' => 'liter',
                'minimum_stock' => 10,
                'current_stock' => 45,
                'average_cost' => 18000,
                'last_purchase_cost' => 18000,
                'is_active' => true,
            ]
        );

        $softener = InventoryItem::query()->updateOrCreate(
            ['sku' => 'INV-SOFTENER-01'],
            [
                'name' => 'Softener',
                'category' => 'Bahan Cuci',
                'unit' => 'liter',
                'minimum_stock' => 8,
                'current_stock' => 32,
                'average_cost' => 15000,
                'last_purchase_cost' => 15000,
                'is_active' => true,
            ]
        );

        $plastic = InventoryItem::query()->updateOrCreate(
            ['sku' => 'INV-PLASTIC-01'],
            [
                'name' => 'Plastik Packing',
                'category' => 'Packing',
                'unit' => 'pcs',
                'minimum_stock' => 100,
                'current_stock' => 550,
                'average_cost' => 350,
                'last_purchase_cost' => 350,
                'is_active' => true,
            ]
        );

        $laundryKiloan = ServicePackage::query()->updateOrCreate(
            ['code' => 'PKG-KILOAN'],
            [
                'name' => 'Laundry Kiloan Regular',
                'pricing_unit' => 'kg',
                'sale_price' => 4000,
                'labor_cost' => 800,
                'overhead_cost' => 500,
                'estimated_hours' => 24,
                'description' => 'Cuci + kering + setrika + packing',
                'is_active' => true,
            ]
        );

        $expressPiece = ServicePackage::query()->updateOrCreate(
            ['code' => 'PKG-EXPRESS-PCS'],
            [
                'name' => 'Laundry Express Satuan',
                'pricing_unit' => 'piece',
                'sale_price' => 5000,
                'labor_cost' => 1100,
                'overhead_cost' => 650,
                'estimated_hours' => 12,
                'description' => 'Laundry satuan express',
                'is_active' => true,
            ]
        );

        ServicePackageMaterial::query()->updateOrCreate(
            ['service_package_id' => $laundryKiloan->id, 'inventory_item_id' => $detergent->id],
            ['quantity_per_unit' => 0.03, 'waste_percent' => 3]
        );
        ServicePackageMaterial::query()->updateOrCreate(
            ['service_package_id' => $laundryKiloan->id, 'inventory_item_id' => $softener->id],
            ['quantity_per_unit' => 0.02, 'waste_percent' => 2]
        );
        ServicePackageMaterial::query()->updateOrCreate(
            ['service_package_id' => $laundryKiloan->id, 'inventory_item_id' => $plastic->id],
            ['quantity_per_unit' => 1, 'waste_percent' => 0]
        );

        ServicePackageMaterial::query()->updateOrCreate(
            ['service_package_id' => $expressPiece->id, 'inventory_item_id' => $detergent->id],
            ['quantity_per_unit' => 0.035, 'waste_percent' => 3]
        );
        ServicePackageMaterial::query()->updateOrCreate(
            ['service_package_id' => $expressPiece->id, 'inventory_item_id' => $softener->id],
            ['quantity_per_unit' => 0.02, 'waste_percent' => 2]
        );
        ServicePackageMaterial::query()->updateOrCreate(
            ['service_package_id' => $expressPiece->id, 'inventory_item_id' => $plastic->id],
            ['quantity_per_unit' => 1, 'waste_percent' => 0]
        );
    }
}
