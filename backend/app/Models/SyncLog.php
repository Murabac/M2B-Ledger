<?php

namespace App\Models;

use App\Enums\SyncLogStatus;
use Database\Factories\SyncLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    /** @use HasFactory<SyncLogFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'status',
        'accounts_count',
        'customers_count',
        'duration_ms',
        'error',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SyncLogStatus::class,
            'accounts_count' => 'integer',
            'customers_count' => 'integer',
            'duration_ms' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
