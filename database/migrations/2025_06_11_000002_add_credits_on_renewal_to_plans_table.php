<?php

use App\Libraries\Database\Migration;

class AddCreditsOnRenewalToPlansTable extends Migration
{
    public function up()
    {
        if (!$this->schema->hasColumn('plans', 'credits_on_renewal')) {
            $this->schema->table('plans', function ($table) {
                $table->integer('credits_on_renewal')->nullable()->default(null)->after('credits');
            });
        }
    }

    public function down()
    {
        if ($this->schema->hasColumn('plans', 'credits_on_renewal')) {
            $this->schema->table('plans', function ($table) {
                $table->dropColumn('credits_on_renewal');
            });
        }
    }
}