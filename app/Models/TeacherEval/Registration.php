<?php

namespace App\Models\TeacherEval;

use App\Models\Ignug\State;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Ignug\SubjectTeacher;
use App\Models\Ignug\Student;



class Registration extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;
    protected $connection = 'pgsql-teacher-eval';
    protected $table= 'registrations';
 


    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function registrationDetail()
    {
        return $this->hasMany(RegistrationDetail::class);
    }
    public function status()
    {
        return $this->belongsTo(Catalogue::class, "status_id");
    }
}
