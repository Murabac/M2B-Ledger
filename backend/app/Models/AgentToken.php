<?php

namespace App\Models;

use Database\Factories\AgentTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentToken extends Model
{
    /** @use HasFactory<AgentTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'token_hash',
        'last_used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function hashToken(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
