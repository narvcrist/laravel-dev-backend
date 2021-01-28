<?php

namespace App\Models\Ignug;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\TeacherEval\DetailEvaluation;


class Subject extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $connection = 'pgsql-ignug';
    protected $table= 'subjects';
    protected $fillable = [
                'name'
    ];

    public function registrationDetail()
    {
        return $this->belongsTo(RegistrationDetail::class);
    }
        public function status()
    {
        return $this->belongsTo(Catalogue::class, "status_id");
    }

}
