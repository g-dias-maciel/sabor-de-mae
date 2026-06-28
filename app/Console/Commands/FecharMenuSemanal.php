<?php

namespace App\Console\Commands;

use App\Models\Menu;
use Illuminate\Console\Command;

class FecharMenuSemanal extends Command
{
    protected $signature = 'menu:fechar-semanal';
    protected $description = 'Encerra o menu aberto atual e cria o menu da próxima semana';

    public function handle(): int
    {
        $menuAtivo = Menu::ativo()->first();

        if (!$menuAtivo) {
            // Se não houver menu ativo, verifica se há algum menu aberto
            // e cria um próximo baseado nele
            $ultimoMenu = Menu::orderBy('end_date', 'desc')->first();

            if ($ultimoMenu) {
                $novoMenu = Menu::criarProximo();
                $this->info("Nenhum menu ativo encontrado. Criado novo menu #{$novoMenu->id}.");
                return self::SUCCESS;
            }

            $this->error('Nenhum menu encontrado no sistema.');
            return self::FAILURE;
        }

        // Encerra o menu ativo
        $menuAtivo->encerrar();
        $this->info("Menu #{$menuAtivo->id} encerrado.");

        // Copia produtos do menu encerrado para o próximo
        $produtosAnteriores = $menuAtivo->products()->get();

        $novoMenu = Menu::criarProximo();

        foreach ($produtosAnteriores as $produto) {
            $novoMenu->products()->attach($produto->id, [
                'day_of_week' => $produto->pivot->day_of_week,
            ]);
        }

        $this->info("Menu #{$novoMenu->id} criado com " . $produtosAnteriores->count() . " produtos copiados do menu anterior.");

        return self::SUCCESS;
    }
}
