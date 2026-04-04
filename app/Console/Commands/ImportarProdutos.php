<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProdutosImport;

class ImportarProdutos extends Command
{
    protected $signature = 'produtos:import {arquivo}';
    protected $description = 'Importa produtos via planilha';

    public function handle()
    {
        $caminho = $this->argument('arquivo');

        if (!file_exists($caminho)) {
            $this->error('Arquivo não encontrado!');
            return;
        }

        Excel::import(new ProdutosImport, $caminho);

        $this->info('Produtos importados com sucesso!');
    }
}