<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportChat extends Model
{
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeOrderByDesc($query, $data)
    {
        $query->orderBy($data, 'desc');
    }
    public function file ()
    {
        return $this->belongsTo(Upload::class, 'attached_file', 'id');
    }

    public function support(){
        return $this->belongsTo(Support::class, 'support_id', 'id');
    }

}
