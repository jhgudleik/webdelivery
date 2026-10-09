<?php

namespace Database\Seeders;

use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'user@webdelivery.com'],
            [
                'name'     => 'Тестовый пользователь',
                'password' => bcrypt('user123'),
                'is_admin' => false,
            ]
        );

        $deliveries = [
            [
                'title'            => 'Доставка электроники',
                'description'      => 'Срочная доставка из магазина электроники',
                'delivery_address' => 'г. Москва, ул. Ленина, д. 10, кв. 5',
                'phone'            => '+7 (999) 111-22-33',
                'status'           => 'confirmed',
                'items'            => [
                    ['product_id' => 1, 'product_title' => 'Смартфон iPhone 15', 'product_sku' => 'APL-IP15', 'quantity' => 1, 'price' => 89990.00],
                    ['product_title' => 'Чехол силиконовый', 'product_sku' => 'ACC-001', 'quantity' => 2, 'price' => 1500.00],
                ],
            ],
            [
                'title'            => 'Доставка одежды',
                'description'      => null,
                'delivery_address' => 'г. Москва, пр. Мира, д. 25, оф. 3',
                'phone'            => '+7 (999) 222-33-44',
                'status'           => 'new',
                'items'            => [
                    ['product_title' => 'Кроссовки Nike Air Max', 'product_sku' => 'NK-AM90', 'quantity' => 1, 'price' => 12500.00],
                    ['product_title' => 'Футболка хлопковая', 'product_sku' => 'CL-T01', 'quantity' => 3, 'price' => 2500.00],
                    ['product_title' => 'Джинсы Slim Fit', 'product_sku' => 'CL-J01', 'quantity' => 1, 'price' => 5800.00],
                ],
            ],
            [
                'title'            => 'Книги и аксессуары',
                'description'      => 'Заказ из книжного магазина',
                'delivery_address' => 'г. Москва, ул. Пушкина, д. 15',
                'phone'            => '+7 (999) 333-44-55',
                'status'           => 'shipped',
                'items'            => [
                    ['product_title' => 'Чистый код (Р. Мартин)', 'product_sku' => 'BK-001', 'quantity' => 1, 'price' => 1800.00],
                    ['product_title' => 'Паттерны проектирования', 'product_sku' => 'BK-002', 'quantity' => 1, 'price' => 2200.00],
                ],
            ],
        ];

        foreach ($deliveries as $data) {
            $items = $data['items'];
            unset($data['items']);

            $delivery = $user->deliveries()->create($data);

            foreach ($items as $item) {
                $delivery->items()->create($item);
            }

            $delivery->recalculateTotal();
        }
    }
}
