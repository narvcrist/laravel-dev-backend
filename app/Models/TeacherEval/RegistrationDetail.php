<?php

namespace App\Models\TeacherEval;

use App\Models\Ignug\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Ignug\SubjectTeacher;
use App\Models\Ignug\Student;
use App\Models\Ignug\Subject;
use App\Models\TeacherEval\Registration;



class RegistrationDetail extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    protected $connection = 'pgsql-teacher-eval';
    protected $table= 'registration_details';
    protected $fillable = [
        'status_evaluation'
    ];
    public $timestamps = false;
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
        public function status()
    {
        return $this->belongsTo(Catalogue::class, "status_id");
    }
    public function subjectTeacher()
    {
        return $this->hasOne(SubjectTeacher::class,"subject_id");
    }

    
}
