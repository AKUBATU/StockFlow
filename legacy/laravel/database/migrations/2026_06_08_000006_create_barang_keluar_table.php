<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){ Schema::create('barang_keluar', function(Blueprint $table){ $table->id(); $table->foreignId('barang_id')->constrained('barang')->cascadeOnDelete(); $table->date('tanggal'); $table->integer('jumlah'); $table->string('tujuan',120); $table->text('keterangan')->nullable(); $table->timestamps(); }); } public function down(){ Schema::dropIfExists('barang_keluar'); } };
