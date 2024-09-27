<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuickBooksJournalEntry extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'quickbooks_journal_entries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'quickbooks_journal_id',
        'for_date'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'organization_id' => 'integer',
        'quickbooks_journal_id' => 'integer',
        'for_date' => 'datetime'
    ];

    // RELATIONSHIPS

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
}
