<?php

namespace App\Http\Controllers\TeacherEval;

use App\Http\Controllers\Controller;
use App\Models\Ignug\Catalogue;
use App\Models\Ignug\State;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $catalogues = Catalogue::where('type',$request->type)->where('state_id', State::where('code',State::ACTIVE)->first()->id)->get();
        if (sizeof($catalogues)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Catalogos no encontrando',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $catalogues,
            'msg' => [
                'summary' => 'success',
                'detail' => 'Se consulto correctamente',
                'code' => '200',
            ]], 200);
        
    }

    public function show(Catalogue $catalogue)
    {
        return response()->json([
            'data' => [
                'catalogue' => $catalogue
            ]]);
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();
        $dataCatalogue = $data['catalogue'];
        $dataParentCode = $data['parent_code'];

        $catalogue = new Catalogue();
        $catalogue->code = $dataCatalogue['code'];
        $catalogue->name = $dataCatalogue['name'];
        $catalogue->icon = $dataCatalogue['icon'];
        $catalogue->type = $dataCatalogue['type'];

        $state = State::where('code', '1')->first();
        $parentCode = Catalogue::findOrFail($dataParentCode['id']);

        $catalogue->state()->associate($state);
        $catalogue->parentCode()->associate($parentCode);

        $catalogue->save();

        return response()->json([
            'data' => [
                'catalogues' => $catalogue
            ]
        ], 201);
    }

    public function update(Request $request, Catalogue $catalogue)
    {

        $data = $request->json()->all();
        $dataCatalogue = $data['catalogue'];
//        $dataParentCode = $data['parent_code'];

//        $catalogue->code = $dataCatalogue['code'];
        $catalogue->name = $dataCatalogue['name'];
        $catalogue->icon = $dataCatalogue['icon'];
        $catalogue->type = $dataCatalogue['type'];

//        $parentCode = Catalogue::findOrFail($dataParentCode['id']);

//        $catalogue->parentCode()->associate($parentCode);
        $catalogue->save();
        return response()->json([
            'data' => [
                'catalogue' => $catalogue
            ]
        ], 201);
    }

    public function destroy(Catalogue $catalogue)
    {
//        $catalogue->delete();
        $state = State::where('code', '3')->first();
        $catalogue->state()->associate($state);
        $catalogue->save();
        return response()->json([
            'data' => [
                'catalogue' => $catalogue
            ]
        ], 201);
    }

}
