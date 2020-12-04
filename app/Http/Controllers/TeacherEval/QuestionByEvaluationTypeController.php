<?php

namespace App\Http\Controllers\TeacherEval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ignug\State;
use App\Models\TeacherEval\Question;
use App\Models\TeacherEval\EvaluationType;
use App\Models\Ignug\Catalogue;
use App\Models\TeacherEval\Evaluation;
use App\Models\Ignug\Teacher;
use App\Models\Ignug\SchoolPeriod;

class QuestionByEvaluationTypeController extends Controller
{
    public function selfEvaluation()
    {
        $evaluationTypeDocencia = EvaluationType::where('code', '3')->first();
        $evaluationTypeGestion = EvaluationType::where('code', '4')->first();

        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

        $status = Catalogue::where('type', 'STATUS')->Where('code', '1')->first();
        $state = State::firstWhere('code', $catalogues['state']['type']['active']);

        $questions = Question::with(['evaluationType','answers' => function ($query) use ($status,$state) {
            $query->where('status_id', $status->id)->where('state_id', $state->id);
        }])
        ->where('status_id', $status->id)
        ->where('state_id', $state->id)
        ->where(function ($query) use ($evaluationTypeDocencia,$evaluationTypeGestion) {
            $query->where('evaluation_type_id', $evaluationTypeDocencia->id)
                  ->orWhere('evaluation_type_id', $evaluationTypeGestion->id);
        })
        ->get();

        if (sizeof($questions)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'No se han creado preguntas y respuestas para el formulario',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $questions,
            'msg' => [
                'summary' => 'Formulario',
                'detail' => 'Se creó correctamente el formulario',
                'code' => '200',
            ]], 200);
    }

    public function registeredSelfEvaluation(Request $request)
    {
        $evaluationTypeTeaching = EvaluationType::firstWhere('code', '3');
        $evaluationTypeManagement = EvaluationType::firstWhere('code', '4');

        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

        $teacher = Teacher::firstWhere('user_id', 1 /* $request->user_id */); //Es Temporal, viene por un interceptor
        $schoolPeriod = SchoolPeriod::firstWhere('status_id', 1);//El 1 es Temporal
        $status = Catalogue::where('type', 'STATUS')->Where('code', '1')->first();
        $state = State::firstWhere('code', $catalogues['state']['type']['active']);

        $evaluations = Evaluation::where(function ($query) use ($evaluationTypeTeaching,$evaluationTypeManagement) {
            $query->where('evaluation_type_id', $evaluationTypeTeaching->id)
            ->orWhere('evaluation_type_id', $evaluationTypeManagement->id);
        })
        ->where('teacher_id', $teacher->id)
        ->where('school_period_id', $schoolPeriod->id)
        ->where('state_id', $state->id)
        ->where('status_id', $state->id)
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
            ]], 201);
    }
    public function teacherEvaluation(Request $request)
    {
        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

        $teacher = Teacher::firstWhere('user_id', 1 /* $request->user_id */); //Es Temporal, viene por un interceptor
        $schoolPeriod = SchoolPeriod::firstWhere('status_id', 1);//El 1 es Temporal
        $status = Catalogue::where('type', 'STATUS')->Where('code', '1')->first();
        $state = State::firstWhere('code', $catalogues['state']['type']['active']);

        $evaluations = Evaluation::with('teacher', 'evaluationType', 'status', 'detailEvaluations', 'schoolPeriod')
        ->where('teacher_id', $teacher->id)
        ->where('school_period_id', $schoolPeriod->id)
        ->where('state_id', $state->id)
        ->where('status_id', $state->id)
        ->get();
        
        if (!$evaluations) {
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
            ]], 201);
    }

    public function studentEvaluation(){
        $evaluationTypeDocencia = EvaluationType::where('code','5')->first();
        $evaluationTypeGestion = EvaluationType::where('code','6')->first();

        $question = Question::with('answers')
        ->where('evaluation_type_id',$evaluationTypeDocencia->id)
        ->orWhere('evaluation_type_id',$evaluationTypeGestion->id)
        ->get();

        if (sizeof($question)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Preguntas no encontradas',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $question,
            'msg' => [
                'summary' => 'Preguntas',
                'detail' => 'Se consultó correctamente Preguntas',
                'code' => '200',
            ]], 200);
    }
    
    public function pairEvaluation()
    {
        $catalogues = json_decode(file_get_contents(storage_path(). '/catalogues.json'), true);

        $evaluationTypeDocencia = EvaluationType::where('code', '7')->first();
        $evaluationTypeGestion = EvaluationType::where('code', '8')->first();
        $state = State::where('code', $catalogues['state']['type']['active'])->first();

        $question = Question::with(['answers' => function ($query) {
            $query->where('state_id', 1);
        }])
            ->where('evaluation_type_id', $evaluationTypeDocencia->id)
            ->orWhere('evaluation_type_id', $evaluationTypeGestion->id)
            ->where('state_id', $state->id)
            ->get();

        if (sizeof($question) === 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Preguntas no encontradas',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404',
                ]], 404);
        }
        return response()->json(['data' => $question,
            'msg' => [
                'summary' => 'Preguntas',
                'detail' => 'Se consultó correctamente Preguntas',
                'code' => '200',
            ]], 200);
    }
}
