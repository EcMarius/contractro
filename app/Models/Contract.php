<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'template_id',
        'lead_id',
        'folder_id',
        'contract_number',
        'title',
        'description',
        'content',
        'variables',
        'status',
        'approval_status',
        'current_approval_step',
        'is_favorite',
        'contract_value',
        'currency',
        'created_by',
        'signed_by',
        'signed_at',
        'effective_date',
        'expires_at',
        'metadata',
        'is_template',
    ];

    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'signed_at' => 'datetime',
        'effective_date' => 'datetime',
        'expires_at' => 'datetime',
        'is_template' => 'boolean',
        'is_favorite' => 'boolean',
        'contract_value' => 'decimal:2',
    ];

    protected $appends = ['formatted_contract_value', 'days_until_expiration'];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = static::generateContractNumber();
            }
            if (empty($contract->created_by)) {
                $contract->created_by = auth()->id();
            }
        });

        // Clear cache on contract changes
        static::saved(function ($contract) {
            app(\App\Services\ContractCacheService::class)->clearContractCache($contract->id);
            app(\App\Services\ContractCacheService::class)->clearUserContractsCache($contract->user_id);
            app(\App\Services\ContractCacheService::class)->clearStatsCache($contract->user_id);
        });

        static::deleted(function ($contract) {
            app(\App\Services\ContractCacheService::class)->clearContractCache($contract->id);
            app(\App\Services\ContractCacheService::class)->clearUserContractsCache($contract->user_id);
            app(\App\Services\ContractCacheService::class)->clearStatsCache($contract->user_id);
        });
    }

    /**
     * Generate unique contract number
     */
    public static function generateContractNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('CONT-%s-%04d', $year, $count);
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class)->orderBy('version_number', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContractComment::class)->whereNull('parent_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ContractFolder::class, 'folder_id');
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(ContractFolder::class, 'contract_folder')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ContractTag::class, 'contract_tag')
            ->withPivot('tagged_at')
            ->withTimestamps();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ContractApproval::class)->orderBy('step_number');
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingSignature($query)
    {
        return $query->where('status', 'pending_signature');
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->orWhere('created_by', $userId);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Accessors
     */
    public function getFormattedContractValueAttribute(): string
    {
        if (!$this->contract_value) {
            return 'N/A';
        }
        return $this->currency . ' ' . number_format($this->contract_value, 2);
    }

    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return now()->diffInDays($this->expires_at, false);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isFuture() && $this->expires_at->lte(now()->addDays(30));
    }

    public function getSignatureProgressAttribute(): array
    {
        $total = $this->signatures()->count();
        $signed = $this->signatures()->where('status', 'signed')->count();

        return [
            'total' => $total,
            'signed' => $signed,
            'pending' => $total - $signed,
            'percentage' => $total > 0 ? round(($signed / $total) * 100) : 0,
        ];
    }

    /**
     * Methods
     */
    public function createVersion($changeSummary = null): ContractVersion
    {
        $latestVersion = $this->versions()->first();
        $newVersionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

        return $this->versions()->create([
            'version_number' => $newVersionNumber,
            'content' => $this->content,
            'variables' => $this->variables,
            'changed_by' => auth()->id(),
            'change_summary' => $changeSummary,
        ]);
    }

    public function updateStatus($status)
    {
        $this->update(['status' => $status]);

        // Auto-update based on signature status
        if ($status === 'pending_signature') {
            $this->checkSignatureStatus();
        }
    }

    public function checkSignatureStatus()
    {
        $signatures = $this->signatures;
        $totalSignatures = $signatures->count();
        $signedCount = $signatures->where('status', 'signed')->count();

        if ($signedCount === 0) {
            $this->update(['status' => 'pending_signature']);
        } elseif ($signedCount < $totalSignatures) {
            $this->update(['status' => 'partially_signed']);
        } else {
            $this->update([
                'status' => 'signed',
                'signed_at' => now(),
            ]);
        }
    }

    public function processVariables(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    public function canBeEditedBy(User $user): bool
    {
        // Contract can be edited if it's a draft and user is the creator
        if ($this->status !== 'draft') {
            return false;
        }

        return $this->created_by === $user->id || $this->user_id === $user->id;
    }

    public function canBeViewedBy(User $user): bool
    {
        // User can view if they are the owner, creator, or part of the organization
        if ($this->user_id === $user->id || $this->created_by === $user->id) {
            return true;
        }

        if ($this->organization_id && $user->organizations()->where('organizations.id', $this->organization_id)->exists()) {
            return true;
        }

        // Check if user is a signer
        return $this->signatures()->where('user_id', $user->id)->exists();
    }

    /**
     * Check and update approval status based on all approvals
     */
    public function checkApprovalStatus(): void
    {
        $approvals = $this->approvals;

        if ($approvals->isEmpty()) {
            $this->update(['approval_status' => 'not_required']);
            return;
        }

        // Check if any approval was rejected
        if ($approvals->where('status', 'rejected')->count() > 0) {
            $this->update([
                'approval_status' => 'rejected',
                'current_approval_step' => null,
            ]);
            return;
        }

        // Check if all required approvals are complete
        $requiredApprovals = $approvals->where('is_required', true);
        $completedApprovals = $requiredApprovals->whereIn('status', ['approved', 'skipped']);

        if ($completedApprovals->count() === $requiredApprovals->count()) {
            $this->update([
                'approval_status' => 'approved',
                'current_approval_step' => null,
            ]);

            // Notify owner that all approvals are complete
            $this->user->notify(new \App\Notifications\ContractFullyApproved($this));
        } else {
            // Find current pending step
            $currentStep = $approvals->where('status', 'pending')->first();

            $this->update([
                'approval_status' => 'pending',
                'current_approval_step' => $currentStep?->step_number,
            ]);
        }
    }

    /**
     * Request approval from users
     */
    public function requestApproval(array $approvers, array $options = []): void
    {
        foreach ($approvers as $index => $approverId) {
            $stepNumber = $index + 1;
            $dueInDays = $options['due_in_days'] ?? 7;

            $this->approvals()->create([
                'approver_id' => $approverId,
                'step_number' => $stepNumber,
                'step_name' => $options['step_names'][$index] ?? "Step {$stepNumber}",
                'status' => 'pending',
                'due_at' => now()->addDays($dueInDays),
                'is_required' => $options['is_required'][$index] ?? true,
            ]);

            // Send notification to approver
            $approver = User::find($approverId);
            $approver->notify(new \App\Notifications\ContractApprovalRequested($this, $stepNumber));
        }

        $this->update([
            'approval_status' => 'pending',
            'current_approval_step' => 1,
        ]);
    }

    /**
     * Check if contract needs approval based on value
     */
    public function needsApproval(): bool
    {
        $threshold = (float) setting('contracts.high_value_threshold', 10000);

        return $this->contract_value && $this->contract_value >= $threshold;
    }
}
