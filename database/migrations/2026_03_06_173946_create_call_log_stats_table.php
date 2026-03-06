<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('call_log_stats', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('normalized_mobile', 15);

            $table->integer('total_calls')->default(0);
            $table->integer('incoming_calls')->default(0);
            $table->integer('outgoing_calls')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'normalized_mobile']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_log_stats');
    }
};
