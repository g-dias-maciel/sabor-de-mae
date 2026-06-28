<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\Menu;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin (a Mãe)
        User::factory()->admin()->create([
            'name' => 'Mãe',
            'email' => 'mae@sabordemae.com',
            'password' => '12345678',
        ]);

        // Cliente de teste + endereço
        $cliente = User::factory()->cliente()->create([
            'name' => 'João Cliente',
            'email' => 'cliente@teste.com',
            'password' => '12345678',
            'phone' => '(11) 98765-4321',
        ]);

        // ===== Produtos — Cardápio Real =====

        // Refeições do dia: [nome, descricao, day_of_week]
        $refeicoes = [
            [
                'name' => 'Carne de Panela',
                'description' => "Carne de panela macia e suculenta\n🍝 Acompanha: Massa ao alho e óleo",
                'day_of_week' => 1,
            ],
            [
                'name' => 'Panquecas de Carne',
                'description' => "Panquecas recheadas com carne moída temperada\n🍚 Arroz\n🫘 Feijão\n🥬 Couve refogada",
                'day_of_week' => 2,
            ],
            [
                'name' => 'Almôndegas Recheadas',
                'description' => "Almôndegas recheadas com queijo e requeijão cremoso ao molho\n🥔 Purê de batata\n🍚 Arroz\n🫘 Feijão",
                'day_of_week' => 4,
            ],
            [
                'name' => 'Frango Agridoce',
                'description' => "Frango ao molho agridoce caseiro\n🍚 Arroz\n🥔 Batata-doce cozida",
                'day_of_week' => 5,
            ],
        ];

        $refeicaoProducts = [];
        foreach ($refeicoes as $data) {
            $day = $data['day_of_week'];
            unset($data['day_of_week']);

            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => 'refeicao',
            ]);

            // Preços P e G para refeições (R$19 / R$25)
            ProductPrice::create(['product_id' => $product->id, 'size' => 'P', 'price' => 19.00]);
            ProductPrice::create(['product_id' => $product->id, 'size' => 'G', 'price' => 25.00]);

            $refeicaoProducts[] = ['product' => $product, 'day_of_week' => $day];
        }

        // Pacote Semanal
        $pacote = Product::create([
            'name' => '⭐ Pacote Semanal (4 almoços)',
            'description' => "Receba os 4 almoços da semana com desconto!\n\n"
                . "🥩 Seg: Carne de Panela + Massa alho e óleo\n"
                . "🥞 Ter: Panquecas de Carne + Arroz, Feijão, Couve\n"
                . "🥩 Qui: Almôndegas Recheadas + Purê, Arroz, Feijão\n"
                . "🍗 Sex: Frango Agridoce + Arroz, Batata-doce\n\n"
                . "💰 Economia de ~5% em relação ao preço avulso!",
            'type' => 'pacote_semanal',
        ]);
        ProductPrice::create(['product_id' => $pacote->id, 'size' => 'P', 'price' => 72.00]);
        ProductPrice::create(['product_id' => $pacote->id, 'size' => 'G', 'price' => 95.00]);

        // Saladas — uma por dia (Seg-Sex)
        $saladas = [
            [
                'name' => '🥗 Salada Caesar',
                'description' => 'Mix de alface americana, croutons crocantes e lascas de parmesão.',
                'day_of_week' => 1,
            ],
            [
                'name' => '🥗 Salada de Folhas Verdes',
                'description' => 'Rúcula fresca, agrião e tomate cereja com molho cítrico.',
                'day_of_week' => 2,
            ],
            [
                'name' => '🥗 Salada Tropical',
                'description' => 'Alface, manga em cubos e cenoura ralada com molho de maracujá.',
                'day_of_week' => 4,
            ],
            [
                'name' => '🥗 Salada Caprese',
                'description' => 'Tomate fatiado, mussarela de búfala e manjericão fresco.',
                'day_of_week' => 5,
            ],
        ];

        $saladaProducts = [];
        foreach ($saladas as $data) {
            $day = $data['day_of_week'];
            unset($data['day_of_week']);

            $salada = Product::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => 'salada',
            ]);

            ProductPrice::create(['product_id' => $salada->id, 'size' => 'M', 'price' => 10.00]);

            $saladaProducts[] = ['product' => $salada, 'day_of_week' => $day];
        }

        // ===== Zonas de entrega =====
        $zonas = [
            ['neighborhood' => 'Centro', 'fee' => 0],
            ['neighborhood' => 'Jardim América', 'fee' => 5.00],
            ['neighborhood' => 'Vila Nova', 'fee' => 8.00],
            ['neighborhood' => 'Boa Vista', 'fee' => 10.00],
        ];

        foreach ($zonas as $z) {
            DeliveryZone::create($z);
        }

        // ===== Endereço do cliente =====
        Address::create([
            'user_id' => $cliente->id,
            'street' => 'Rua das Flores',
            'number' => '123',
            'complement' => 'Apto 45',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'zip_code' => '01001-000',
            'is_default' => true,
        ]);

        // ===== Menu da semana atual =====
        $menu = Menu::factory()->create([
            'start_date' => now()->startOfWeek(),
            'end_date' => now()->endOfWeek(),
            'status' => 'aberto',
        ]);

        // Vincula as refeições com seus dias da semana
        foreach ($refeicaoProducts as $entry) {
            $menu->products()->attach($entry['product']->id, ['day_of_week' => $entry['day_of_week']]);
        }

        // Vincula as saladas com seus dias da semana
        foreach ($saladaProducts as $entry) {
            $menu->products()->attach($entry['product']->id, ['day_of_week' => $entry['day_of_week']]);
        }

        // Pacote vinculado sem dia específico (disponível todos os dias)
        $menu->products()->attach($pacote->id);
    }
}
