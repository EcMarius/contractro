<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'contract_type_id',
        'contract_number',
        'title',
        'content',
        'parties',
        'status',
        'signing_method',
        'value',
        'currency',
        'start_date',
        'end_date',
        'signed_at',
        'metadata',
    ];

    protected $casts = [
        'parties' => 'array',
        'metadata' => 'array',
        'value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'status_color'];

    // Relationships
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function contractType(): BelongsTo { return $this->belongsTo(ContractType::class); }
    public function contractParties(): HasMany { return $this->hasMany(ContractParty::class); }
    public function signatures(): HasMany { return $this->hasMany(ContractSignature::class); }
    public function attachments(): HasMany { return $this->hasMany(ContractAttachment::class); }
    public function amendments(): HasMany { return $this->hasMany(ContractAmendment::class); }
    public function tasks(): HasMany { return $this->hasMany(ContractTask::class); }
    public function notes(): HasMany { return $this->hasMany(ContractNote::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }

    // Scopes
    public function scopeActive(Builder $query): Builder { return $query->where('status', 'active'); }
    public function scopeExpired(Builder $query): Builder { return $query->where('status', 'expired'); }
    public function scopePending(Builder $query): Builder { return $query->where('status', 'pending'); }
    public function scopeSigned(Builder $query): Builder { return $query->whereIn('status', ['signed', 'active']); }
    public function scopeByCompany(Builder $query, int $companyId): Builder { return $query->where('company_id', $companyId); }

    // Business Logic
    public function isFullySigned(): bool
    {
        return $this->contractParties()->count() > 0 &&
               $this->contractParties()->count() === $this->contractParties()->where('is_signed', true)->count();
    }

    public function isExpired(): bool { return $this->end_date && $this->end_date->isPast(); }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->end_date && $this->end_date->diffInDays(now()) <= $days && !$this->isExpired();
    }

    public static function generateContractNumber(int $companyId, int $contractTypeId): string
    {
        $numbering = ContractNumbering::firstOrCreate(
            ['company_id' => $companyId, 'contract_type_id' => $contractTypeId, 'year' => now()->year],
            ['last_number' => 0, 'prefix' => 'CNT', 'format' => '{prefix}-{number}/{year}']
        );
        $numbering->increment('last_number');
        $numbering->refresh();
        return str_replace(
            ['{prefix}', '{number}', '{year}'],
            [$numbering->prefix, str_pad($numbering->last_number, 4, '0', STR_PAD_LEFT), $numbering->year],
            $numbering->format
        );
    }

    public function sign(): bool
    {
        if ($this->isFullySigned()) {
            $this->update(['status' => 'signed', 'signed_at' => now()]);
            return true;
        }
        return false;
    }

    public function duplicate(): Contract
    {
        $new = $this->replicate(['contract_number', 'signed_at', 'status']);
        $new->status = 'draft';
        $new->contract_number = self::generateContractNumber($this->company_id, $this->contract_type_id);
        $new->save();
        foreach ($this->contractParties as $party) {
            $newParty = $party->replicate(['is_signed', 'signed_at', 'ip_address']);
            $newParty->contract_id = $new->id;
            $newParty->save();
        }
        return $new;
    }

    // Attributes
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray', 'pending' => 'yellow', 'signed' => 'green',
            'active' => 'blue', 'expired', 'terminated' => 'red', default => 'gray'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Ciornă', 'pending' => 'Așteptare semnătură', 'signed' => 'Semnat',
            'active' => 'Activ', 'expired' => 'Expirat', 'terminated' => 'Reziliat', default => 'Necunoscut'
        };
    }
}
