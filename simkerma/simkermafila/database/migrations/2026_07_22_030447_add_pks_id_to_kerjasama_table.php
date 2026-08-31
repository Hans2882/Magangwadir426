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
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->integer('pks_id')->nullable()->after('parent_id');
            $table->foreign('pks_id')->references('id')->on('kerjasama')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kerjasama', function (Blueprint $table) {
            $table->dropForeign(['pks_id']);
            $table->dropColumn('pks_id');
        });
    }
};
