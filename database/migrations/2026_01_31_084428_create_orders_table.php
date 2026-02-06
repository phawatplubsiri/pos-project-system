<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->foreignId('session_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_id')->constrained();
        $table->integer('quantity');
        $table->decimal('unit_price', 10, 2); 
        $table->decimal('total_price', 10, 2); 
        $table->string('status')->default('PENDING'); 
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
        Schema::dropIfExists('orders');
    }
}
