<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'contact_mobile', 'contact_name', 'normalized_mobile'];

    public function relations()
    {
        return $this->belongsToMany(
            Contact::class,
            'contact_relations',  
            'contact_id',
            'related_contact_id'
        );
    }
}
