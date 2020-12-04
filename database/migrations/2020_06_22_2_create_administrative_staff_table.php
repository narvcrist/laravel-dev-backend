<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdministrativeStaffTable extends Migration
{
    public function up()
    {
        Schema::connection('pgsql-ignug')->create('administrative_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('authentication.users');
            $table->foreignId('institution_id');
            $table->foreignId('position_id');
            $table->foreignId('administrative_staff_type_id')->nullable()->constrained('catalogues');
            $table->foreignId('employment_relationship_id')->nullable()->constrained('catalogues');
            $table->boolean('merit_contest')->default(false);
            $table->unsignedDouble('netsalary')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('catalogues');
            $table->foreignId('town_id')->nullable()->constrained('catalogues');
            $table->foreignId('suffrage_province_id')->nullable()->constrained('catalogues');
            $table->foreignId('country_nationality_id')->nullable()->constrained('catalogues');
            $table->boolean('disability')->default(false);
            $table->string('conadis_carnet')->nullable();
            $table->foreignId('disability_type_id')->nullable()->constrained('catalogues');
            $table->foreignId('catastrophic_illness_id')->nullable()->constrained('catalogues');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('state_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('pgsql-ignug')->dropIfExists('administrative_staff');
    }
}
