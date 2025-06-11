<?php

use App\Libraries\Database\Migration;

class CreateCreditTransactionsTable extends Migration
{
    public function up()
    {
        $this->schema->create('credit_transactions', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->integer('amount');
            $table->enum('type', ['credit', 'debit']);
            $table->string('description');
            $table->string('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Add foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            // Add indexes
            $table->index(['user_id', 'type']);
            $table->index(['reference_id', 'reference_type']);
        });
        
        // Add credits column to users table if it doesn't exist
        if (!$this->schema->hasColumn('users', 'credits')) {
            $this->schema->table('users', function ($table) {
                $table->integer('credits')->default(0)->after('remember_token');
            });
        }
    }
    
    public function down()
    {
        $this->schema->dropIfExists('credit_transactions');
        
        // Don't drop the credits column as it might contain user data
        // It can be removed in a separate migration if needed
    }
}
