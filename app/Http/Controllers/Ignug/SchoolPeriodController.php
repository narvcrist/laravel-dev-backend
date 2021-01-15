<?php

namespace App\Http\Controllers\Ignug;

use App\Http\Controllers\Controller;
use App\Models\Ignug\State;
use App\Models\Ignug\SchoolPeriod;
use Illuminate\Http\Request;

class SchoolPeriodController extends Controller
{
    public function index()
    {
        $catalogues = json_decode(file_get_contents(storage_path() . '/catalogues.json'), true);
        $state = State::where('code', $catalogues['state']['type']['active'])->first();
        $schoolPeriods = SchoolPeriod::where('state_id', $state->id)->get();
        
        if (sizeof($schoolPeriods)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Periodos académicos no encontrados',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $schoolPeriods,
            'msg' => [
                'summary' => 'Peridos académicos',
                'detail' => 'Se consulto correctamente',
                'code' => '200',
            ]], 200);
    }
}
