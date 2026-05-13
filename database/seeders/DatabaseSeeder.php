<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Roles
        $adminRole = \App\Models\Role::create(['name' => 'Admin', 'description' => 'System Administrator']);
        $managerRole = \App\Models\Role::create(['name' => 'Store Manager', 'description' => 'Manages store inventory and reports']);
        $customerRole = \App\Models\Role::create(['name' => 'Customer', 'description' => 'Regular store customer']);

        // 2. Create Categories
        $catFace = \App\Models\Category::create(['name' => 'Face', 'slug' => 'face', 'description' => 'Foundations, concealers, and powders']);
        $catEyes = \App\Models\Category::create(['name' => 'Eyes', 'slug' => 'eyes', 'description' => 'Eyeshadows, mascaras, and eyeliners']);
        $catLips = \App\Models\Category::create(['name' => 'Lips', 'slug' => 'lips', 'description' => 'Lipsticks, glosses, and liners']);
        $catSkin = \App\Models\Category::create(['name' => 'Skincare', 'slug' => 'skincare', 'description' => 'Cleansers, moisturizers, and serums']);
        $categories = [$catFace, $catEyes, $catLips, $catSkin];

        // 3. Create Users
        $admin = \App\Models\User::factory()->create([
            'name' => 'Alice Admin',
            'email' => 'admin@istore.com',
            'role_id' => $adminRole->id,
        ]);
        $manager = \App\Models\User::factory()->create([
            'name' => 'Mark Manager',
            'email' => 'manager@istore.com',
            'role_id' => $managerRole->id,
        ]);

        $customers = [];
        for ($i = 0; $i < 48; $i++) {
            $customers[] = \App\Models\User::factory()->create([
                'role_id' => $customerRole->id,
            ]);
        }

        // 4. Create Products
        $makeupProducts = [
            ['name' => 'Velvet Matte Lipstick', 'cat' => $catLips, 'price' => 24.00],
            ['name' => 'Hydrating Lip Gloss', 'cat' => $catLips, 'price' => 18.50],
            ['name' => 'Flawless Finish Foundation', 'cat' => $catFace, 'price' => 42.00],
            ['name' => 'Radiant Concealer', 'cat' => $catFace, 'price' => 28.00],
            ['name' => 'Volumizing Mascara', 'cat' => $catEyes, 'price' => 22.00],
            ['name' => 'Precision Liquid Eyeliner', 'cat' => $catEyes, 'price' => 20.00],
            ['name' => 'Sunset Eyeshadow Palette', 'cat' => $catEyes, 'price' => 55.00],
            ['name' => 'Gentle Foaming Cleanser', 'cat' => $catSkin, 'price' => 26.00],
            ['name' => 'Hyaluronic Acid Serum', 'cat' => $catSkin, 'price' => 65.00],
            ['name' => 'Dewy Setting Spray', 'cat' => $catFace, 'price' => 32.00],
        ];

        $products = [];
        foreach ($makeupProducts as $idx => $mp) {
            $products[] = \App\Models\Product::create([
                'name' => $mp['name'],
                'category_id' => $mp['cat']->id,
                'description' => 'Premium beauty product by iStore.',
                'price' => $mp['price'],
                'stock_quantity' => rand(50, 500),
                'sku' => 'SKU-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
            ]);
        }

        // 5. Create Orders, OrderItems, Payments
        foreach ($customers as $user) {
            $numOrders = rand(1, 8);
            for ($j = 0; $j < $numOrders; $j++) {
                $orderStatus = ['pending', 'completed', 'completed', 'shipped'][rand(0, 3)];
                $order = \App\Models\Order::create([
                    'user_id' => $user->id,
                    'total_amount' => 0, // Calculate below
                    'status' => $orderStatus,
                    'shipping_address' => rand(100, 999) . ' Beauty Ave, NY',
                ]);

                $orderTotal = 0;
                $numItems = rand(1, 4);
                $usedProducts = [];
                for ($k = 0; $k < $numItems; $k++) {
                    $product = $products[rand(0, 9)];
                    if (in_array($product->id, $usedProducts)) continue;
                    $usedProducts[] = $product->id;

                    $qty = rand(1, 3);
                    $lineTotal = $product->price * $qty;
                    $orderTotal += $lineTotal;

                    \App\Models\OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $product->price,
                    ]);
                }

                $order->update(['total_amount' => $orderTotal]);

                // Create Payment
                $paymentStatus = $orderStatus === 'pending' ? 'pending' : 'completed';
                \App\Models\Payment::create([
                    'order_id' => $order->id,
                    'amount' => $orderTotal,
                    'status' => $paymentStatus,
                    'method' => ['credit_card', 'paypal', 'apple_pay'][rand(0, 2)],
                ]);
            }
        }

        // 6. Define Default Virtual Attributes using Eloquent
        \Nisalatp\DynamicReportGenerator\Models\VirtualAttribute::create([
            'name' => 'lifetime_spend',
            'base_model' => \App\Models\User::class,
            'sql_fragment' => '(SELECT SUM(total_amount) FROM orders WHERE orders.user_id = users.id AND orders.status IN ("completed", "shipped"))',
            'dependencies' => [\App\Models\Order::class],
        ]);

        \Nisalatp\DynamicReportGenerator\Models\VirtualAttribute::create([
            'name' => 'total_units_sold',
            'base_model' => \App\Models\Product::class,
            'sql_fragment' => '(SELECT SUM(quantity) FROM order_items WHERE order_items.product_id = products.id)',
            'dependencies' => [\App\Models\OrderItem::class],
        ]);
        
        \Nisalatp\DynamicReportGenerator\Models\VirtualAttribute::create([
            'name' => 'category_revenue',
            'base_model' => \App\Models\Category::class,
            'sql_fragment' => '(SELECT SUM(order_items.quantity * order_items.unit_price) FROM order_items JOIN products ON products.id = order_items.product_id WHERE products.category_id = categories.id)',
            'dependencies' => [\App\Models\Product::class, \App\Models\OrderItem::class],
        ]);
    }
}
