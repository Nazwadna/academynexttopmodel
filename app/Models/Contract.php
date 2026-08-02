<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_number',
        'model_id',
        'agency_id',
        'type', // 'employment', 'project', 'exclusive'
        'title',
        'description',
        'start_date',
        'end_date',
        'duration_months',
        'payment_terms',
        'payment_amount',
        'payment_currency',
        'payment_schedule', // 'monthly', 'quarterly', 'upfront'
        'commission_rate',
        'exclusivity_terms',
        'usage_rights',
        'deliverables',
        'obligations',
        'termination_clause',
        'status', // 'draft', 'pending_signature', 'active', 'completed', 'terminated', 'expired'
        'model_signature',
        'agency_signature',
        'academy_signature',
        'model_signed_at',
        'agency_signed_at',
        'academy_signed_at',
        'created_by',
        'notes',
        'document_url',
        'auto_renewal',
        'renewal_notice_days',
        'confidentiality_clause',
        'dispute_resolution',
        'governing_law',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'model_signed_at' => 'datetime',
        'agency_signed_at' => 'datetime',
        'academy_signed_at' => 'datetime',
        'auto_renewal' => 'boolean',
        'usage_rights' => 'array',
        'deliverables' => 'array',
        'obligations' => 'array',
        'termination_clause' => 'array',
    ];

    // Relationships
    public function model()
    {
        return $this->belongsTo(Talent::class, 'model_id');
    }

    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(ContractPayment::class);
    }

    public function amendments()
    {
        return $this->hasMany(ContractAmendment::class);
    }

    public function documents()
    {
        return $this->hasMany(ContractDocument::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '>=', Carbon::today());
    }

    public function scopePendingSignature($query)
    {
        return $query->where('status', 'pending_signature');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '<=', Carbon::today()->addDays($days))
                    ->where('end_date', '>', Carbon::today());
    }

    public function scopeByModel($query, $modelId)
    {
        return $query->where('model_id', $modelId);
    }

    // Methods
    public function generateContractNumber()
    {
        $prefix = 'CON';
        $year = Carbon::now()->format('Y');
        $sequence = Contract::whereYear('created_at', Carbon::now()->year)->count() + 1;
        
        $this->contract_number = "{$prefix}-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        return $this->contract_number;
    }

    public function signContract($partyType, $signatureData)
    {
        switch ($partyType) {
            case 'model':
                $this->model_signature = $signatureData;
                $this->model_signed_at = Carbon::now();
                break;
            case 'agency':
                $this->agency_signature = $signatureData;
                $this->agency_signed_at = Carbon::now();
                break;
            case 'academy':
                $this->academy_signature = $signatureData;
                $this->academy_signed_at = Carbon::now();
                break;
        }

        // Check if all parties have signed
        if ($this->model_signature && $this->agency_signature && $this->academy_signature) {
            $this->status = 'active';
            $this->start_date = Carbon::today();
        }

        $this->save();
    }

    public function isFullySigned()
    {
        return $this->model_signature && $this->agency_signature && $this->academy_signature;
    }

    public function getDaysUntilExpiry()
    {
        if (!$this->end_date) return null;
        return Carbon::today()->diffInDays($this->end_date, false);
    }

    public function isExpiring()
    {
        $daysUntilExpiry = $this->getDaysUntilExpiry();
        return $daysUntilExpiry !== null && $daysUntilExpiry <= 30 && $daysUntilExpiry > 0;
    }

    public function isExpired()
    {
        return $this->end_date && Carbon::today()->gt($this->end_date);
    }

    public function canBeTerminated()
    {
        return in_array($this->status, ['active', 'pending_signature']);
    }

    public function terminate($reason)
    {
        $this->status = 'terminated';
        $this->notes .= "\n\nTerminated: " . Carbon::now()->format('Y-m-d H:i:s') . "\nReason: {$reason}";
        $this->save();
    }

    public function calculateTotalValue()
    {
        if ($this->payment_schedule === 'upfront') {
            return $this->payment_amount;
        }
        
        $months = $this->duration_months;
        if ($this->payment_schedule === 'monthly') {
            return $this->payment_amount * $months;
        } elseif ($this->payment_schedule === 'quarterly') {
            return $this->payment_amount * ($months / 3);
        }
        
        return $this->payment_amount;
    }

    public function generatePDF()
    {
        // Implementation for PDF generation
        // This would use a library like DomPDF or TCPDF
        return "contract_{$this->contract_number}.pdf";
    }

    public function sendForSignature($partyEmail, $partyType)
    {
        // Implementation for sending contract for signature
        // This would integrate with email service and digital signature service
        \Log::info("Contract sent for signature to {$partyEmail} ({$partyType})");
    }

    // Events
    protected static function booted()
    {
        static::created(function ($contract) {
            \Log::info("Contract created: {$contract->contract_number}");
        });

        static::updated(function ($contract) {
            if ($contract->wasChanged('status')) {
                \Log::info("Contract status changed: {$contract->contract_number} -> {$contract->status}");
            }
        });
    }
}
