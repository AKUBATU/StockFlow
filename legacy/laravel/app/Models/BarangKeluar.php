<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangKeluar extends Model
{
    use HasFactory;
    protected $table = 'barang_keluar';
    protected $fillable = ['barang_id','tanggal','jumlah','tujuan','keterangan'];

public function barang(){ return $this->belongsTo(Barang::class, 'barang_id'); }
}
