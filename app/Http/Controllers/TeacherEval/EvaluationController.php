<?php

namespace App\Http\Controllers\TeacherEval;

use App\Http\Controllers\Controller;
use App\Models\Ignug\Catalogue;
use App\Models\Ignug\SchoolPeriod;
use App\Models\Ignug\State;
use App\Models\Ignug\Teacher;
use App\Models\TeacherEval\DetailEvaluation;
use App\Models\TeacherEval\Evaluation;
use App\Models\TeacherEval\EvaluationType;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index()
    {
        $catalogues = json_decode(file_get_contents(storage_path() . '/catalogues.json'), true);
        
        $evaluationTypeDocencia = EvaluationType::where('code', '7')->first();
        $evaluationTypeGestion = EvaluationType::where('code', '8')->first();
        $status = Catalogue::where('type', $catalogues['status']['type']['type'])->Where('code', $catalogues['state']['type']['active'])->first();

        $evaluations = Evaluation::with(['teacher', 'evaluationType', 'status', 'detailEvaluations', 'schoolPeriod'  => function ($query) use ($status) {
            $query->where('status_id', $status->id);
        }])->where(function ($query) use ($evaluationTypeDocencia, $evaluationTypeGestion) {
            $query->where('evaluation_type_id', $evaluationTypeDocencia->id)
                ->orWhere('evaluation_type_id', $evaluationTypeGestion->id);
        })->get();

        if (sizeof($evaluations) === 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluaciones no encontradas',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }
        return response()->json(['data' => $evaluations,
            'msg' => [
                'summary' => 'Evaluaciones',
                'detail' => 'Se consulto correctamente evaluaciones',
                'code' => '200',
            ]], 200);
    }

    public function show($id)
    {
        $evaluation = Evaluation::findOrFail($id);
        if (!$evaluation) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluación no encontrada',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }
        return response()->json(['data' => $evaluation,
            'msg' => [
                'summary' => 'Evaluación',
                'detail' => 'Se consulto correctamente la evaluación',
                'code' => '200',
            ]], 200);
    }

    public function store(Request $request)
    {
        $catalogues = json_decode(file_get_contents(storage_path() . '/catalogues.json'), true);

        $data = $request->json()->all();

        $dataEvaluation = $data['evaluation'];
        $dataEvaluationType = $data['evaluation_type'];
        $dataTeacher = $data['teacher'];
        $dataStatus = $data['status'];

        $evaluation = new Evaluation();
        $evaluation->percentage = $dataEvaluation['percentage'];

        $teacher = Teacher::findOrFail($dataTeacher['id']);
        $evaluationType = EvaluationType::findOrFail($dataEvaluationType['id']);
        $status = Catalogue::findOrFail($dataStatus['id']);

        $evaluation->teacher()->associate($teacher);
        $evaluation->evaluationType()->associate($evaluationType);
        $evaluation->state()->associate(State::firstWhere('code', $catalogues['state']['type']['active'])->first());
        $evaluation->schoolPeriod()->associate(SchoolPeriod::firstWhere('code', '1')->first());
        $evaluation->status()->associate($status);
        $evaluation->save();

        if (!$evaluation) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluación no creada',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }
        return response()->json(['data' => $evaluation,
            'msg' => [
                'summary' => 'Evaluación',
                'detail' => 'Se creo correctamente la evaluación',
                'code' => '201',
            ]], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->json()->all();

        $dataEvaluation = $data['evaluation'];
        $dataEvaluationType = $data['evaluation_type'];
        $dataTeacher = $data['teacher'];
        $dataEvaluators = $data['evaluators'];
        $dataStatus = $data['status'];

        $evaluation = Evaluation::findOrFail($id);
        $evaluation->percentage = $dataEvaluation['percentage'];
        $teacher = Teacher::findOrFail($dataTeacher['id']);
        $evaluationType = EvaluationType::findOrFail($dataEvaluationType['id']);
        $status = Catalogue::findOrFail($dataStatus['id']);

        $evaluation->teacher()->associate($teacher);
        $evaluation->evaluationType()->associate($evaluationType);
        $evaluation->status()->associate($status);
        $evaluation->save();

        foreach ($dataEvaluators as $evaluator) {
            $detailEvaluation = DetailEvaluation::firstWhere('evaluation_id', $id)->first();
            $detailEvaluation->detailEvaluationable()->associate(Teacher::findOrFail($evaluator['id']));
            $detailEvaluation->save();
        }

        if (!$detailEvaluation) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluador no actualizada',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }
        return response()->json(['data' => $detailEvaluation,
            'msg' => [
                'summary' => 'Evaluador',
                'detail' => 'Se actualizo correctamente el evaluador',
                'code' => '201',
            ]], 201);
    }

    public function destroy($id)
    {
        $evaluation = Evaluation::findOrFail($id);

        $evaluation->state_id = '2';
        $evaluation->save();

        if (!$evaluation) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluación no eliminada',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }

        $detailEvaluations = DetailEvaluation::Where('evaluation_id', $id)->get();
        
        if($detailEvaluations){
            foreach($detailEvaluations as $detailEvaluation){
                $detailEvaluation->state_id = '2';
                $detailEvaluation->save();
            }
        }
       
        return response()->json(['data' => $evaluation,
            'msg' => [
                'summary' => 'Evaluación',
                'detail' => 'Se elimino correctamente la evaluación',
                'code' => '201',
            ]], 201);
    }

    public function updateEvaluationPair()
    {
        $evaluationTypeTeaching = EvaluationType::firstWhere('code', '7');
        $evaluationTypeManagement = EvaluationType::firstWhere('code', '8');

        $teachers = Teacher::get();
        foreach ($teachers as $teacher) {
            $evaluations = Evaluation::where('school_period_id', 1)->where('teacher_id', $teacher->id)
                ->where(function ($query) use ($evaluationTypeTeaching, $evaluationTypeManagement) {
                    $query->where('evaluation_type_id', $evaluationTypeTeaching->id)
                        ->OrWhere('evaluation_type_id', $evaluationTypeManagement->id);
                })
                ->get();
            foreach ($evaluations as $evaluation) {
                $result = 0;
                foreach ($evaluation->detailEvaluations as $detailEvaluation) {
                    $result += $detailEvaluation->result;
                }
                $evaluation->result = $result / sizeOf($evaluation->detailEvaluations);
                $evaluation->save();
            }
        }

        if (!$evaluation) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluación no creada',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }
        return response()->json(['data' => $evaluation,
            'msg' => [
                'summary' => 'Evaluación creada',
                'detail' => 'Se creó correctamente evaluación',
                'code' => '201',
            ]], 201);
    }

    public function updateEvaluationAuthorityEvaluator()
    {
        $evaluationTypeTeaching = EvaluationType::firstWhere('code', '9');
        $evaluationTypeManagement = EvaluationType::firstWhere('code', '10');

        $teachers = Teacher::get();
        foreach ($teachers as $teacher) {
            $evaluations = Evaluation::where('school_period_id', 1)->where('teacher_id', $teacher->id)
                ->where(function ($query) use ($evaluationTypeTeaching, $evaluationTypeManagement) {
                    $query->where('evaluation_type_id', $evaluationTypeTeaching->id)
                        ->OrWhere('evaluation_type_id', $evaluationTypeManagement->id);
                })
                ->get();
            foreach ($evaluations as $evaluation) {
                $result = 0;
                foreach ($evaluation->detailEvaluations as $detailEvaluation) {
                    $result += $detailEvaluation->result;
                }
                $evaluation->result = $result / sizeOf($evaluation->detailEvaluations);
                $evaluation->save();
            }
        }

        if (!$evaluation) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluación no creada',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }
        return response()->json(['data' => $evaluation,
            'msg' => [
                'summary' => 'Evaluación creada',
                'detail' => 'Se creó correctamente evaluación',
                'code' => '201',
            ]], 201);
    }

    public function registeredSelfEvaluation(Request $request)
    {
        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

        $evaluationTypeTeaching = EvaluationType::firstWhere('code', $catalogues["evaluation"]["type"]["self_evaluation_teaching"]);
        $evaluationTypeManagement = EvaluationType::firstWhere('code', $catalogues["evaluation"]["type"]["self_evaluation_management"]);

        $teacher = Teacher::firstWhere('user_id', $request->user_id);
        $status = Catalogue::where('type',  $catalogues['status']['type']['type'])->Where('code',$catalogues['status']['type']['active'] )->first();
        $schoolPeriod = SchoolPeriod::firstWhere('status_id', $status->id);

        $evaluations = Evaluation::where(function ($query) use ($evaluationTypeTeaching,$evaluationTypeManagement) {
            $query->where('evaluation_type_id', $evaluationTypeTeaching->id)
            ->orWhere('evaluation_type_id', $evaluationTypeManagement->id);
        })
        ->where('teacher_id', $teacher->id)
        ->where('school_period_id', $schoolPeriod->id)
        ->where('status_id', $status->id)
        ->get();
        if (sizeof($evaluations)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'No hay autoEvaluación registrada',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $evaluations,
            'msg' => [
                'summary' => 'AutoEvaluaciones',
                'detail' => 'AutoEvaluacion ya está registrada',
                'code' => '201',
            ]], 200);
    }
    public function teacherEvaluation(Request $request)
    {
        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

        $teacher = Teacher::firstWhere('user_id', $request->user_id);
        $status = Catalogue::where('type',  $catalogues['status']['type']['type'])->Where('code',$catalogues['status']['type']['active'] )->first();
        $schoolPeriod = SchoolPeriod::firstWhere('status_id', $status->id);

        $evaluations = Evaluation::with('teacher', 'evaluationType', 'status', 'detailEvaluations', 'schoolPeriod')
        ->where('teacher_id', $teacher->id)
        ->where('school_period_id', $schoolPeriod->id)
        ->where('status_id', $status->id)
        ->get();

        if (sizeof($evaluations)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'El docente no tiene evaluaciones',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $evaluations,
            'msg' => [
                'summary' => 'Evaluaciones del docente',
                'detail' => 'Se consultó correctamente las evaluaciones',
                'code' => '201',
            ]], 200);
    }
    // public function registeredStudentEvaluation(Request $request)
    // {
    //     $evaluationTypeTeaching = EvaluationType::firstWhere('code', '5');
    //     $evaluationTypeManagement = EvaluationType::firstWhere('code', '6');

    //     $teacher = Teacher::firstWhere('user_id', $request->user_id); //Es Temporal, viene por un interceptor
    //     $status = Catalogue::where('type', 'STATUS')->Where('code', '1')->first();
    //     $schoolPeriod = SchoolPeriod::firstWhere('status_id', $status->id);//El id del status es Temporal

    //     $evaluations = Evaluation::where(function ($query) use ($evaluationTypeTeaching,$evaluationTypeManagement) {
    //         $query->where('evaluation_type_id', $evaluationTypeTeaching->id)
    //         ->orWhere('evaluation_type_id', $evaluationTypeManagement->id);
    //     })
    //     ->where('teacher_id', $teacher->id)
    //     ->where('school_period_id', $schoolPeriod->id)
    //     ->where('status_id', $status->id)
    //     ->get();
    //     if (sizeof($evaluations)=== 0) {
    //         return response()->json([
    //             'data' => null,
    //             'msg' => [
    //                 'summary' => 'No hay autoEvaluación registrada',
    //                 'detail' => 'Intenta de nuevo',
    //                 'code' => '404'
    //             ]], 404);
    //     }
    //     return response()->json(['data' => $evaluations,
    //         'msg' => [
    //             'summary' => 'AutoEvaluaciones',
    //             'detail' => 'AutoEvaluacion ya está registrada',
    //             'code' => '201',
    //         ]], 200);
    // }
}
