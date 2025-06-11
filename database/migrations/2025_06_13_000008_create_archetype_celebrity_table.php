<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateArchetypeCelebrityTable
{
    public function up()
    {
        Capsule::schema()->create('archetype_celebrity', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('archetype_id');
            $table->unsignedInteger('celebrity_report_id');
            $table->timestamps();

            $table->foreign('archetype_id')->references('id')->on('archetypes')->onDelete('cascade');
            $table->foreign('celebrity_report_id')->references('id')->on('celebrity_reports')->onDelete('cascade');
            $table->unique(['archetype_id', 'celebrity_report_id']);
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('archetype_celebrity');
    }
}