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
        Schema::table('popular_models', function (Blueprint $table) {
            $table->string('nama_model')->after('id');
            $table->text('deskripsi')->nullable()->after('nama_model');
            $table->string('foto')->nullable()->after('deskripsi');
            $table->string('instagram')->nullable()->after('foto');
            $table->string('twitter')->nullable()->after('instagram');
            $table->string('status')->default('available')->after('twitter');
            
            // Drop the old id_model column if it exists
            if (Schema::hasColumn('popular_models', 'id_model')) {
                $table->dropColumn('id_model');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('popular_models', function (Blueprint $table) {
            $table->dropColumn(['nama_model', 'deskripsi', 'foto', 'instagram', 'twitter', 'status']);
            $table->unsignedBigInteger('id_model')->after('id');
        });
    }
};
