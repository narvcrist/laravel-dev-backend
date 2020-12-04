<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeacherEvalEvaluationsTable extends Migration
{
    public function up()
    {
        Schema::connection('pgsql-teacher-eval')->create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->comment('Informacion Profesor')->constrained('ignug.teachers');
            $table->foreignId('evaluation_type_id')->comment('pares, autoevaluacion,estudiante');
            $table->foreignId('school_period_id')->comment('periodo academico')->constrained('ignug.school_periods');;
            $table->foreignId('status_id')->constrained('ignug.catalogues'); 
            $table->foreignId('state_id')->comment('Activo o Inactivo')->constrained('ignug.states');
            $table->double('result',5,2)->nullable()->comment('Total Evaluacion');
            $table->double('percentage')->nullable()->comment('Porcentaje cada Tipo Evaluacion');;
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('pgsql-teacher-eval')->dropIfExists('evaluations');
    }
}
