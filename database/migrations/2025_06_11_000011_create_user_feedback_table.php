<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('feedback_type', ['bug', 'feature', 'improvement', 'general'])->default('general');
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->tinyInteger('rating')->nullable()->comment('1-5 star rating');
            $table->string('email', 255)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'closed'])->default('pending');
            $table->text('admin_response')->nullable();
            $table->json('metadata')->nullable()->comment('Additional data like browser info, page URL, etc.');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('feedback_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['feedback_type', 'status']);
            
            // Foreign key constraint (if users table exists)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_feedback');
    }
}