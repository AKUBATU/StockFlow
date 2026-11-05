<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){ Schema::create('supplier', function(Blueprint $table){ $table->id(); $table->string('nama_supplier',120); $table->string('telepon',30)->nullable(); $table->text('alamat')->nullable(); $table->timestamps(); }); } public function down(){ Schema::dropIfExists('supplier'); } };
