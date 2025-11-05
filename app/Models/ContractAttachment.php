<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContractAttachment extends Model
{
    protected $fillable = [
        'contract_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Get file size in human-readable format
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    // Get download URL
    public function getDownloadUrlAttribute(): string
    {
        return route('contracts.attachments.download', ['attachment' => $this->id]);
    }

    // Delete file from storage
    public function deleteFile(): bool
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->delete($this->file_path);
        }
        return true;
    }
}
