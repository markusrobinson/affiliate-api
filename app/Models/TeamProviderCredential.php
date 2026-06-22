<?php

namespace App\Models;

use App\Enums\AffiliateProvider;
use Database\Factories\TeamProviderCredentialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property AffiliateProvider $provider
 * @property array<string, string> $credentials
 * @property bool $is_active
 * @property Carbon|null $last_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 */
class TeamProviderCredential extends Model
{
    /** @use HasFactory<TeamProviderCredentialFactory> */
    use HasFactory;

    protected $fillable = ['team_id', 'provider', 'credentials', 'is_active', 'last_verified_at'];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @param  Builder<TeamProviderCredential>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => AffiliateProvider::class,
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_verified_at' => 'datetime',
        ];
    }
}
