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
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_pic')->nullable();
            $table->boolean('is_buisness')->default(false);
            $table->boolean('is_govt')->default(false);
            $table->boolean('designation_id')->nullable();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('about_profiles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->text('description');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('_users', function (Blueprint $table) {
          $table->dropColumn('profile_pic');
            $table->dropColumn('is_buisness');
            $table->dropColumn('is_govt');
            $table->dropColumn('designation_id');
        });

         Schema::dropIfExists('about_profiles');
         Schema::dropIfExists('designations');
    }
};
