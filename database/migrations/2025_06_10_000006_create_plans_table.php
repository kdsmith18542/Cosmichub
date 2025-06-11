<?php

use App\Libraries\Database\Migration;

class CreatePlansTable extends Migration
{
    public function up()
    {
        $this->schema->create('plans', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['one_time', 'monthly', 'yearly'])->default('one_time');
            $table->integer('credits');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->timestamps();
            
            // Add indexes
            $table->index(['is_active', 'sort_order']);
            $table->unique('stripe_price_id');
            $table->unique('stripe_product_id');
        });
        
        // Insert default plans if the table is empty
        if ($this->db->table('plans')->count() === 0) {
            $this->seedDefaultPlans();
        }
    }
    
    public function down()
    {
        $this->schema->dropIfExists('plans');
    }
    
    /**
     * Seed default credit plans
     */
    protected function seedDefaultPlans()
    {
        $plans = [
            [
                'name' => 'Starter Pack',
                'description' => 'Perfect for trying out our service',
                'price' => 4.99,
                'billing_cycle' => 'one_time',
                'credits' => 10,
                'features' => json_encode([
                    '10 credits',
                    'Basic reports',
                    'Email support'
                ]),
                'sort_order' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Power User',
                'description' => 'For regular users who want more',
                'price' => 19.99,
                'billing_cycle' => 'one_time',
                'credits' => 50,
                'features' => json_encode([
                    '50 credits (20% more value!)',
                    'All reports',
                    'Priority support',
                    'Save unlimited reports'
                ]),
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Unlimited',
                'description' => 'For the ultimate experience',
                'price' => 29.99,
                'billing_cycle' => 'monthly',
                'credits' => 0, // 0 means unlimited
                'features' => json_encode([
                    'Unlimited reports',
                    'Priority support',
                    'Advanced analytics',
                    'Early access to new features',
                    'Save unlimited reports'
                ]),
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->db->table('plans')->insert($plans);
    }
}
