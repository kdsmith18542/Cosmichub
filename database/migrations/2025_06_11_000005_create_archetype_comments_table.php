<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class CreateArchetypeCommentsTable
{
    public function up()
    {
        Capsule::schema()->create('archetype_comments', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('archetype_id');
            $table->unsignedInteger('user_id');
            $table->text('comment');
            $table->boolean('is_moderated')->default(false);
            $table->timestamps();

            $table->foreign('archetype_id')->references('id')->on('archetypes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('archetype_comments');
    }
}