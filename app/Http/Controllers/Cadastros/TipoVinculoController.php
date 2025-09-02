<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TipoVinculo; 

use Illuminate\Support\Facades\DB;  

class TipoVinculoController
{
    public function add(Request $request){

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

    public function update(Request $request){

    }

    public function delete(Request $request){

    }
}