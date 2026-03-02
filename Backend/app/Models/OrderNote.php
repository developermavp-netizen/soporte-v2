<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderNote extends Model
{
    use HasFactory;

    protected $table = 'order_notes';

    protected $fillable = [
        'order_id', 'note', 'is_internal', 'created_by'
    ];

    protected $casts = [
        'is_internal' => 'boolean'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}