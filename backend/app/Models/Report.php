<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    use HasFactory;

    protected $appends = ["file_path", "file_download_name"];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id',
        'store_id',
        'file_name',
        'report_type',
        'from_date',
        'to_date'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'organization_id' => 'integer',
        'store_id' => 'integer',
        'from_date' => 'datetime',
        'to_date' => 'datetime'
    ];

    // RELATIONSHIPS

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    // ATTRIBUTES

    public function getFilePathAttribute()
    {
        return 'reports/' . $this->organization_id . '/' . $this->report_type . '/' . ($this->store_id ? $this->store_id . '/' : '') . $this->file_name;
    }

    public function getFileDownloadNameAttribute()
    {
        $timezone = $this->timezone;

        return ucwords(str_replace("-", '_', $this->report_type)) 
        . '_' 
        . Carbon::createFromTimeString($this->from_date)->setTimezone($timezone)->format('m-d-y') 
        . (!in_array($this->report_type, ['daily_sales', 'inventory'])
            ? '_to_' . Carbon::createFromTimeString($this->to_date)->setTimezone($timezone)->format('m-d-y')
            : '');
    }

    public function getTimezoneAttribute()
    {
        return $this->store->state->timezone;
    }

    // OBSERVERS

    public static function boot()
    {
        parent::boot();

        static::deleting(function (Report $report) {
            Storage::disk('s3')->delete($report->file_path);
        });
    }
}
