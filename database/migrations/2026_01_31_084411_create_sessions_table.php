<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained(); 
            $table->foreignId('user_id')->constrained(); 
            
            $table->integer('guest_amount'); 
            $table->string('guest_token')->unique(); 
        
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable(); 
        
            $table->string('status')->default('ONGOING'); 
            $table->decimal('total_amount', 10, 2)->default(0); 
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
