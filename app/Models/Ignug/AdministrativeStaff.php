<?php

namespace App\Models\Ignug;

use App\Models\Authentication\User;
use App\Traits\StatusActiveTrait;
use App\Traits\StatusDeletedTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdministrativeStaff extends Model
{
    use HasFactory;
    use StatusActiveTrait;
    use StatusDeletedTrait;
    protected $connection = 'pgsql-ignug';
    protected $table = 'ignug.administrative_staff';

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
