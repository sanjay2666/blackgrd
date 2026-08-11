<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSetting extends Model
{
    protected $table = 'document_settings';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'show_logo' => 'boolean',
            'show_company_address' => 'boolean',
            'show_company_tax_identity' => 'boolean',
        ];
    }
}
