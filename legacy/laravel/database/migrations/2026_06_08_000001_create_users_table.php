<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(){ Schema::create('users', function(Blueprint $table){ $table->id(); $table->string('nama',100); $table->string('username',50)->unique(); $table->string('password'); $table->enum('role',['admin','gudang','pimpinan'])->default('admin'); $table->timestamps(); }); } public function down(){ Schema::dropIfExists('users'); } };
