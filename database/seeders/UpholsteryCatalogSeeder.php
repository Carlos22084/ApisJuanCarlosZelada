<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class UpholsteryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            ['name' => 'Chenille Premium', 'category' => 'Tela', 'price' => 68, 'colors' => [
                'Arena' => '#C8AD8D', 'Beige' => '#D6C2A5', 'Gris plomo' => '#686B70',
                'Café' => '#704B38', 'Azul índigo' => '#344C73', 'Verde oliva' => '#727353',
            ]],
            ['name' => 'Terciopelo Soft', 'category' => 'Tela', 'price' => 82, 'colors' => [
                'Verde bosque' => '#315C4D', 'Azul petróleo' => '#235967', 'Vino' => '#743747',
                'Beige' => '#C9B596', 'Gris grafito' => '#4D5054', 'Rosa viejo' => '#B67C82',
            ]],
            ['name' => 'Lino antimanchas', 'category' => 'Tela', 'price' => 76, 'colors' => [
                'Gris piedra' => '#898783', 'Natural' => '#D8CDB9', 'Arena' => '#C9B79C',
                'Beige' => '#D1BEA1', 'Taupe' => '#928273', 'Azul' => '#567189',
            ]],
            ['name' => 'Cuerina Náutica', 'category' => 'Sintético', 'price' => 95, 'colors' => [
                'Café coñac' => '#9A5F37', 'Negro' => '#242424', 'Marfil' => '#E6DDC8',
                'Beige' => '#CAB493', 'Gris' => '#777B7E', 'Plomo' => '#555A60',
            ]],
            ['name' => 'Bouclé contemporáneo', 'category' => 'Tela', 'price' => 88, 'colors' => [
                'Marfil' => '#E8E0D0', 'Crema' => '#E5D4B8', 'Beige' => '#CDB99B',
                'Gris claro' => '#B9B8B3', 'Café' => '#755743',
            ]],
            ['name' => 'Microfibra Microsuede', 'category' => 'Tela', 'price' => 74, 'colors' => [
                'Camel' => '#B57B4A', 'Café' => '#654637', 'Beige' => '#CDB896',
                'Gris' => '#787A7B', 'Azul marino' => '#263D58',
            ]],
            ['name' => 'Jacquard decorativo', 'category' => 'Tela', 'price' => 90, 'colors' => [
                'Beige dorado' => '#BDA170', 'Café' => '#72503B', 'Azul' => '#45627D',
                'Verde' => '#61705B', 'Vino' => '#713C49',
            ]],
            ['name' => 'Poliéster impermeable Petra', 'category' => 'Tela', 'price' => 84, 'colors' => [
                'Chocolate' => '#654438', 'Marfil' => '#E7DDC7', 'Plomo' => '#66696B',
                'Almendra' => '#B99B78', 'Arena' => '#C7B08E',
            ]],
            ['name' => 'Cuerina PU Semicuero', 'category' => 'Sintético', 'price' => 108, 'colors' => [
                'Negro' => '#202020', 'Chocolate' => '#583B31', 'Camel' => '#AE7545',
                'Beige' => '#C9B18D', 'Gris' => '#727578',
            ]],
            ['name' => 'Cuero natural', 'category' => 'Cuero', 'price' => 185, 'colors' => [
                'Negro' => '#242220', 'Chocolate' => '#4E3328', 'Coñac' => '#9A5A2D',
                'Camel' => '#B67A47', 'Vino' => '#65343A',
            ]],
        ];

        foreach ($catalog as $material) {
            foreach ($material['colors'] as $color => $hex) {
                Material::firstOrCreate(
                    ['name' => $material['name'], 'color' => $color],
                    [
                        'category' => $material['category'],
                        'color_hex' => $hex,
                        'unit' => 'metro',
                        'stock' => 12,
                        'minimum_stock' => 5,
                        'unit_price' => $material['price'],
                        'active' => true,
                    ]
                );
            }
        }
    }
}
