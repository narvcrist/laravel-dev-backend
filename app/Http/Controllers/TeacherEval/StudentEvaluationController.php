<?php

namespace App\Http\Controllers\TeacherEval;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\TeacherEval\EvaluationType;
use App\Models\TeacherEval\StudentResult;
use App\Models\Ignug\State;
use App\Models\Ignug\Student;
use App\Models\Ignug\Catalogue;
use App\Models\Ignug\SubjectTeacher;
use App\Models\TeacherEval\AnswerQuestion;
use App\Models\TeacherEval\Evaluation;
use App\Models\Ignug\SchoolPeriod;
use App\Models\Ignug\Teacher;


class StudentEvaluationController extends Controller
{
    
    public function index(){
        $studentResult= StudentResult::all();
        if (sizeof($studentResult)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluacion de Estudiante a Docentes no encontradas',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $studentResult,
            'msg' => [
                'summary' => 'Evaluacion de Estudiante a Docentes',
                'detail' => 'Se consultó correctamente Evaluaciones de Estudiante a Docentes',
                'code' => '200',
            ]], 200);
    } 

    public function store(Request $request)
    {
       $data = $request->json()->all();

    //    $dataStudentResult= $data['student_result'];
       $dataSubjectTeacher = $data['subject_teacher'];
       $dataAnswerQuestions = $data['answer_questions'];
       //$dataUser= $data['user'];  
       $dataStudent= $data['student'];

        foreach($dataAnswerQuestions as $answerQuestion)
        {
            $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);
            
            $studentResult= new StudentResult();
            $state = State::firstWhere('code', $catalogues['state']['type']['active']);
            $subjectTeacher = SubjectTeacher::findOrFail($dataSubjectTeacher['id']);
            $student = Student::findOrFail($dataStudent['id']);
            
            
            $studentResult->state()->associate($state);
            $studentResult->subjectTeacher()->associate($subjectTeacher);
            $studentResult->student()->associate($student);
            $studentResult->answerQuestion()->associate(AnswerQuestion::findOrFail($answerQuestion['id']));
            $studentResult->save();

        }
        
        if (!$studentResult) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluacion de Estudiante a Docentes no encontradas',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $studentResult,
            'msg' => [
                'summary' => 'Evaluación Exitosa!',
                'detail' => 'Se completo correctamente evaluación Estudiante Docente',
                'code' => '200',
            ]], 200);
    }


     //Calcula el result en la tabla evaluation y crea sus campos
    
    public function createEvaluation($teacher,$schoolPeriod,$result,$evaluationType){
        
            $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);
        
            $evaluation = new Evaluation();

            $evaluation->teacher()->associate($teacher);   
            $evaluation->schoolPeriod()->associate($schoolPeriod);            
            $evaluation->result = $result;
            $state = State::firstWhere('code', $catalogues['state']['type']['active']);
            $evaluation->state()->associate($state); 
            //pendiente
            //$catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);
            $status = Catalogue::where('type', $catalogues['status']['type']['type'])->Where('code', $catalogues['status']['type']['active'])->first()->id;   
            $evaluation->status()->associate($status);         
            $evaluation->evaluationType ()->associate($evaluationType);
            $evaluation->save();
             
            return $evaluation;
            
        
    }
    

    //Metodo para calcular.
    public function calculateResults( Request $request){
        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);     
        $status = Catalogue::where('type', $catalogues['status']['type']['type'])->Where('code', $catalogues['status']['type']['active'])->first()->id;

        $schoolPeriod= SchoolPeriod::firstWhere('status_id',$status);   

        $teachers= Teacher::whereHas('careers')->get();
        $evaluationTypeDocencia = EvaluationType::where('code', $catalogues['evaluation']['type']['student_evaluation_teaching'])->first();
        $evaluationTypeGestion = EvaluationType::where('code', $catalogues['evaluation']['type']['student_evaluation_management'])->first();

        foreach($teachers as $teacher){
            $subjectTeachers = SubjectTeacher::where('school_period_id',$schoolPeriod->id)
            ->where('teacher_id',$teacher->id)
            ->get();            

            $resultadoDocencia=0;
            $resultadoGestion=0;
            foreach($subjectTeachers as $subjectTeacher){
                $studentDocenciaResults= StudentResult::where('subject_teacher_id',$subjectTeacher->id)
                ->with(['answerQuestion'=>function($answerQuestion){
                    $answerQuestion->with('answer');
                }])->whereHas('answerQuestion',function($answerQuestion)use($evaluationTypeDocencia){
                    $answerQuestion->whereHas('question',function($question)use($evaluationTypeDocencia){
                        $question->where('evaluation_type_id',$evaluationTypeDocencia->id);
                    });
                })
                ->get();
                $totalDocencia=0;

                foreach($studentDocenciaResults as $studentDocenciaResult){
                    $result = json_decode(json_encode($studentDocenciaResult));
                    
                    $totalDocencia += (int)$result->answer_question->answer->value;
                  
                }

                if(sizeof($studentDocenciaResults)>0){
                    $resultadoDocencia  += $totalDocencia/sizeof($studentDocenciaResults);                    
                }

                $studentGestionResults= StudentResult::where('subject_teacher_id',$subjectTeacher->id)
                ->with(['answerQuestion'=>function($answerQuestion){
                    $answerQuestion->with('answer');
                }])->whereHas('answerQuestion',function($answerQuestion)use($evaluationTypeGestion){
                    $answerQuestion->whereHas('question',function($question)use($evaluationTypeGestion){
                        $question->where('evaluation_type_id',$evaluationTypeGestion->id);
                    });
                })
                ->get();
                
                $totalGestion=0;
                foreach($studentGestionResults as $studentGestionResult){
                    $result  = json_decode(json_encode($studentGestionResult));

                    $totalGestion += (int)$result->answer_question->answer->value;
                }
                if(sizeof($studentGestionResults)>0){
                    $resultadoGestion += $totalGestion/sizeof($studentGestionResults);                    
                }

            }
            if(sizeof($subjectTeachers)>0){
                $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);     

                $evaluation= Evaluation::where('school_period_id', $schoolPeriod->id)
                ->where('teacher_id',$teacher->id)
                ->where('evaluation_type_id',$evaluationTypeDocencia->id)->first();
                if($evaluation){
                    if( $evaluation->result!=$resultadoDocencia/sizeof($subjectTeachers)){
                       // $status = Catalogue::where('code','2')->where('type','STATUS_TYPE')->first();
                        $status = Catalogue::where('type', $catalogues['status']['type']['type'])->Where('code', $catalogues['status']['type']['inactive'])->first();
                        $evaluation->status()->associate($status);
                        $evaluation->save();
                        $result=$resultadoDocencia/sizeof($subjectTeachers);
                        $evaluation= $this->createEvaluation($teacher,$schoolPeriod,$result,$evaluationTypeDocencia);
                    }
                }else{            
                    $result=$resultadoDocencia/sizeof($subjectTeachers);
                    $evaluation= $this->createEvaluation($teacher,$schoolPeriod,$result,$evaluationTypeDocencia);
                    
                   
                }
                $evaluation= Evaluation::where('school_period_id', $schoolPeriod->id)
                ->where('teacher_id',$teacher->id)
                ->where('evaluation_type_id',$evaluationTypeGestion->id)->first();
                if($evaluation){
                    if( $evaluation->result!=$resultadoGestion/sizeof($subjectTeachers)){
                        //$status = Catalogue::where('code','2')->where('type','STATUS_TYPE')->first();
                        $status = Catalogue::where('type', $catalogues['status']['type']['type'])->Where('code', $catalogues['status']['type']['inactive'])->first();
                        $evaluation->status()->associate($status);
                        $evaluation->save();
                        $result=$resultadoGestion/sizeof($subjectTeachers);
                        $evaluation= $this->createEvaluation($teacher,$schoolPeriod,$result,$evaluationTypeGestion);
                    }

                }else{   
                    $result=$resultadoGestion/sizeof($subjectTeachers);
                    $evaluation=  $this->createEvaluation($teacher,$schoolPeriod,$result,$evaluationTypeGestion);
                    
                }
            }

        }
        if (!$evaluation) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'Evaluacion de Estudiante a Docentes no encontradas',
                    'detail' => 'Intenta de nuevo',
                    'code' => '404'
                ]], 404);
        }
        return response()->json(['data' => $evaluation,
            'msg' => [
                'summary' => 'Evaluacion de Estudiante a Docentes',
                'detail' => 'Se completo correctamente evaluacion',
                'code' => '200',
            ]], 200);

       
    }
    public function update(Request $request){
        return $request;
    }

    public function destroy($id){
        return $id;
    }
    public function getEvaluation(Request $request){
        

        $teachers= Teacher::whereHas('careers')->get();
        return $teachers;
    }

}
