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
use App\Models\TeacherEval\Registration;
use App\Models\TeacherEval\RegistrationDetail;
use App\Models\Ignug\SchoolPeriod;
use App\Models\Ignug\Teacher;
use App\Models\Ignug\Subject;
use App\Models\Authentication\User;


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
            $evaluation->evaluationType()->associate($evaluationType);
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
                $evaluation= Evaluation::where('school_period_id', $schoolPeriod->id)
                ->where('teacher_id',$teacher->id)
                ->where('evaluation_type_id',$evaluationTypeDocencia->id)->first();
                if($evaluation){
                    if( $evaluation->result!=$resultadoDocencia/sizeof($subjectTeachers)){
                        $status = Catalogue::where('code','2')->where('type','STATUS_TYPE')->first();
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
                        $status = Catalogue::where('code','2')->where('type','STATUS_TYPE')->first();
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
    
    public function store(Request $request)
    {
        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

       $data = $request->json()->all();
       $dataSubjectTeacher = $data['subject_teacher'];
       $dataAnswerQuestions = $data['answer_questions'];
       $dataDetail = $data['detail'];
       $student = Student::firstWhere('user_id', $request->user_id);
       $state = State::firstWhere('code', $catalogues['state']['type']['active']);
       $subjectTeacher = SubjectTeacher::findOrFail($dataSubjectTeacher['id']);
       $detail = RegistrationDetail::findOrFail($dataDetail['id']);
       $detail->update(['status_evaluation'=> true]);


        foreach($dataAnswerQuestions as $answerQuestion)
        {
            $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);
            
            $studentResult= new StudentResult();
            $studentResult->state()->associate($state);
            $studentResult->subjectTeacher()->associate($subjectTeacher);
            $studentResult->student()->associate($student);
            $studentResult->answerQuestion()->associate(AnswerQuestion::findOrFail($answerQuestion['id']));
            $studentResult->save();

        }
        // if (!$studentResult) {
        //     return response()->json([
        //         'data' => null,
        //         'msg' => [
        //             'summary' => 'Evaluacion de Estudiante a Docentes no encontradas',
        //             'detail' => 'Intenta de nuevo',
        //             'code' => '404'
        //         ]], 404);
        // }
        return response()->json(['data' => null,
            'msg' => [
                'summary' => 'Evaluación Exitosa!',
                'detail' => 'Se completo correctamente evaluación Estudiante Docente',
                'code' => '200',
            ]], 200);
    }
  

    public function studentEvaluation(Request $request)
    {
        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

        // $teacher = Teacher::with('user')->get();
        $status = Catalogue::where('type',  $catalogues['status']['type']['type'])->Where('code',$catalogues['status']['type']['active'] )->first();
        $schoolPeriod = SchoolPeriod::firstWhere('status_id', $status->id);
        $state = State::where('code', $catalogues['state']['type']['active'])->first();
        $user = Teacher::with('state', 'user')->where('state_id', $state->id);
        $state = State::where('code', $catalogues['state']['type']['active'])->first();

        $evaluations = Evaluation::with('evaluationType', 'status', 'schoolPeriod')
        ->where('school_period_id', $schoolPeriod->id)
        ->where('status_id', $status->id)
        ->where('result','>','0')
        ->get();
    //     $registrationDetails= Evaluation::where('school_period_id', $schoolPeriod->id)
    //     ->where('status_id', $status->id)
    //     ->where('result','>','0')
    //     ->with(['teacher' => function ($query) use ($state){
    //             $query->where('state_id', $state->id);
    //     }])->get();
      
    //   $subjectRs= [];
    //     foreach($registrationDetails as $teacher_id=>$registrationDetail){
    //         $subjectRs= Evaluation::with('teacher')->get();
    //     }
        

    //     $d= $subjectRs[0]->teacher->user_id;
    //     for($i = 0; $i < count($subjectRs); $i++){
    //         $title_modal = $subjectRs[$i]->teacher->user_id;

    //     }
    //     return $title_modal;

    //     $variable=[];
    //     foreach ($subjectRs as $id=>$subjectR){

    //         $varible= User::where('id', $teacher->user_id);
    //     }
        //return $variable;

    

        
        $subjectProfesor= [];
        foreach($subjects as $subject_id=>$subject){
            $subjectProfesor= SubjectTeacher::get('subject_id');
        }
        if (sizeof($evaluations)=== 0) {
            return response()->json([
                'data' => null,
                'msg' => [
                    'summary' => 'No existen resultados de Evaluación Estudiante-Docente',
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
    public function registeredStudentEvaluation(Request $request)
    {
        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);

        $evaluationTypeTeaching = EvaluationType::firstWhere('code', '5');
        $evaluationTypeManagement = EvaluationType::firstWhere('code', '6');

        $status = Catalogue::where('type',  $catalogues['status']['type']['type'])->Where('code',$catalogues['status']['type']['active'] )->first();
        $schoolPeriod = SchoolPeriod::firstWhere('status_id', $status->id);

        $evaluations = Evaluation::where(function ($query) use ($evaluationTypeTeaching,$evaluationTypeManagement) {
            $query->where('evaluation_type_id', $evaluationTypeTeaching->id)
            ->orWhere('evaluation_type_id', $evaluationTypeManagement->id);
        })
        ->with('teacher')
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
    
    public function registration(Request $request){

        $catalogues = json_decode(file_get_contents(storage_path() . "/catalogues.json"), true);
        $status = Catalogue::where('type',  $catalogues['status']['type']['type'])->Where('code',$catalogues['status']['type']['active'] )->first();
        $student = Student::firstWhere('user_id', $request->user_id);
        $materias= Subject::where('status_id',$status->id);
        $schoolPeriod = SchoolPeriod::firstWhere('status_id', $status->id);
        $subjectTeacher= SubjectTeacher::where('school_period_id', $schoolPeriod->id);

       $studentRegistration= Registration::where('student_id',$student->id)->get()->first();
       $traer= $studentRegistration['id'];
       
       $registrationDetails= RegistrationDetail::where('registration_id',$traer)
        ->with(['subject' => function ($query) use ($status){
                $query->where('status_id', $status->id);
        }])->with('subjectTeacher')->get();
        return response()->json(['data' => $registrationDetails,
        'msg' => [
            'summary' => 'Registration',
            'detail' => 'Se consulto correctamente',
            'code' => '200',
        ]], 200);
        // $subjectRegistration= [];
        // foreach($registrationDetails as $subject_id=>$registrationDetail){
        //     $subjectRegistration= RegistrationDetail::where('registration_id',$traer)->get('subject_id');
            
        // }
        
        // $subjects = SubjectTeacher::where('subject_id', $registrationDetail->subject_id)
        // ->with(['subject' => function($query)use ($status){
        //     $query->where('status_id', $status->id);
        // }])->get();
        
        // $subjectProfesor= [];
        // foreach($subjects as $subject_id=>$subject){
        //     $subjectProfesor= SubjectTeacher::get('subject_id');
        // }
        
        // $subjectTeacherId= SubjectTeacher::whereIn('subject_id', $subjectRegistration)->with('teacher','subject')->get();
        // // return $subjectTeacherId;
        // if (sizeof($subjectTeacherId)=== 0) {
        //     return response()->json([
        //         'data' => null,
        //         'msg' => [
        //             'summary' => 'SubjectTeacher no encontrados',
        //             'detail' => 'Intenta de nuevo',
        //             'code' => '404'
        //         ]], 404);
        // }
        // return response()->json(['data' => $subjectTeacherId,
        //     'msg' => [
        //         'summary' => 'SubjectTeacher',
        //         'detail' => 'Se consulto correctamente',
        //         'code' => '200',
        //     ]], 200);

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
