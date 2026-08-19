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
        Schema::table('usulan_kerjasamas', function (Blueprint $table) {
            $table->integer('mitra_id')->nullable()->change();
            
            $table->string('usulan_nama_mitra', 500)->nullable();
            $table->unsignedBigInteger('usulan_kategori_id')->nullable();
            $table->unsignedBigInteger('usulan_negara_id')->nullable();
            $table->unsignedBigInteger('usulan_provinsi_id')->nullable();
            $table->unsignedBigInteger('usulan_kota_id')->nullable();
            $table->string('usulan_telepon', 50)->nullable();
            $table->string('usulan_email', 150)->nullable();
            $table->string('usulan_qs_rank', 50)->nullable();
            $table->text('usulan_alamat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usulan_kerjasamas', function (Blueprint $table) {
            $table->integer('mitra_id')->nullable(false)->change();
            
            $table->dropColumn([
                'usulan_nama_mitra',
                'usulan_kategori_id',
                'usulan_negara_id',
                'usulan_provinsi_id',
                'usulan_kota_id',
                'usulan_telepon',
                'usulan_email',
                'usulan_qs_rank',
                'usulan_alamat',
            ]);
        });
    }
};
