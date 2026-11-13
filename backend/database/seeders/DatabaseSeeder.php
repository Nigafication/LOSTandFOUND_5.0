<?php

namespace Database\Seeders;

use App\Models\LostFoundItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@campus.edu'],
            ['name' => 'Demo Student', 'password' => 'password']
        );

        $items = [
            ['title' => 'Silver MacBook charger', 'category' => 'Electronics', 'location' => 'Powell Library · 2nd floor', 'description' => 'USB-C power adapter with a small blue sticker on the side.', 'tone' => 'blue', 'icon' => '⌁', 'occurred_at' => now()->subMinutes(12)],
            ['title' => 'Black canvas tote bag', 'category' => 'Bags', 'location' => 'Kerckhoff Coffee House', 'description' => 'Has a yellow paperback, a water bottle, and a set of keys inside.', 'tone' => 'gold', 'icon' => '▱', 'occurred_at' => now()->subHours(2)],
            ['title' => 'Gold hoop earring', 'category' => 'Jewelry', 'location' => 'Royce Hall · North steps', 'description' => 'Single small hoop with a tiny textured detail around the edge.', 'tone' => 'rose', 'icon' => '◌', 'occurred_at' => now()->subHours(18)],
            ['title' => 'Blue Hydro Flask', 'category' => 'Drinkware', 'location' => 'John Wooden Center', 'description' => '24 oz bottle with a white mountain decal and a few scuffs.', 'tone' => 'mint', 'icon' => '◒', 'occurred_at' => now()->subDay()],
            ['title' => 'Student ID card', 'category' => 'Cards & IDs', 'location' => 'Sunset Village', 'description' => 'Name is visible. The card is being held securely at the front desk.', 'tone' => 'ink', 'icon' => '▣', 'occurred_at' => now()->subDays(2)],
            ['title' => 'Green knit scarf', 'category' => 'Clothing', 'location' => 'Bunche Hall · Room 315', 'description' => 'Forest green scarf with a soft ribbed texture and fringe ends.', 'tone' => 'green', 'icon' => '≋', 'occurred_at' => now()->subDays(3)],
        ];

        foreach ($items as $item) {
            LostFoundItem::updateOrCreate(['title' => $item['title']], [...$item, 'reported_by' => $user->id, 'kind' => 'found', 'status' => 'open']);
        }
    }
}
