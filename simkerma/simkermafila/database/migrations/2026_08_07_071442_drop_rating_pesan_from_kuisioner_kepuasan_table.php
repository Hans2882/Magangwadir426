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
        Schema::table('kuisioner_kepuasan', function (Blueprint $table) {
            if (Schema::hasColumn('kuisioner_kepuasan', 'rating')) {
                $table->dropColumn('rating');
            }
            if (Schema::hasColumn('kuisioner_kepuasan', 'pesan')) {
                $table->dropColumn('pesan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kuisioner_kepuasan', function (Blueprint $table) {
            if (! Schema::hasColumn('kuisioner_kepuasan', 'rating')) {
                $table->tinyInteger('rating')->after('program_studi_alumni');
            }
            if (! Schema::hasColumn('kuisioner_kepuasan', 'pesan')) {
                $table->text('pesan')->after('rating');
            }
        });
    }
};
