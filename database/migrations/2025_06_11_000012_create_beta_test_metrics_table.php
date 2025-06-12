<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBetaTestMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('beta_test_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name', 100)->comment('Name of the metric being tracked');
            $table->decimal('metric_value', 10, 4)->comment('Numeric value of the metric');
            $table->enum('metric_type', ['counter', 'gauge', 'histogram'])->default('gauge')
                  ->comment('Type of metric: counter (incremental), gauge (point-in-time), histogram (distribution)');
            $table->json('tags')->nullable()->comment('Additional metadata and dimensions for the metric');
            $table->timestamp('recorded_at')->useCurrent()->comment('When the metric was recorded');
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index('metric_name');
            $table->index('metric_type');
            $table->index('recorded_at');
            $table->index(['metric_name', 'recorded_at']);
            $table->index(['metric_name', 'metric_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('beta_test_metrics');
    }
}