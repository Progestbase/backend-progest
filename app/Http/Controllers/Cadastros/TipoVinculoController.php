<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Requests\TipoVinculoRequest;
use Illuminate\Http\Request;
use App\Models\TipoVinculo; 

class TipoVinculoController
{
    public function add(TipoVinculoRequest $request){

    }

    public function listAll(Request $request){  
        $data = $request->all();
        $filters = $data['filters'] ?? [];  

        $tipoVinculos = $filters;
        $tipoVinculosQuery = TipoVinculo::query();
        foreach ($filters as $condition) {
            foreach ($condition as $field => $value) {
                $tipoVinculosQuery->where($field, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $tipoVinculos = $tipoVinculosQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        } else {
            $tipoVinculos = $tipoVinculosQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => true, 'data' => $tipoVinculos];
    }

    public function listData(Request $request){

    }

    public function update(TipoVinculoRequest $request){

    }

    public function delete(Request $request){

    }
}