<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateArchetypesTable
{
    public function up()
    {
        Capsule::schema()->create('archetypes', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('archetypes');
    }
}