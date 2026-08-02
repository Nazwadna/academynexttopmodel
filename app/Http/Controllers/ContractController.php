<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class ContractController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:contract-manage')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of contracts.
     */
    public function index(Request $request)
    {
        $contracts = Contract::with(['model', 'agency', 'creator'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->model_id, function ($query, $modelId) {
                return $query->where('model_id', $modelId);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%")
                            ->orWhere('contract_number', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $models = Talent::where('status', 'active')->get();
        $agencies = User::where('role', 'agency')->get();
        
        return view('contracts.index', compact('contracts', 'models', 'agencies'));
    }

    /**
     * Show the form for creating a new contract.
     */
    public function create()
    {
        $models = Talent::where('status', 'active')->get();
        $agencies = User::where('role', 'agency')->get();
        
        return view('contracts.create', compact('models', 'agencies'));
    }

    /**
     * Store a newly created contract in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'model_id' => 'required|exists:talents,id',
            'agency_id' => 'required|exists:users,id',
            'type' => 'required|in:employment,project,exclusive',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'payment_amount' => 'required|numeric|min:0',
            'payment_currency' => 'required|string|size:3',
            'payment_schedule' => 'required|in:monthly,quarterly,upfront',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'usage_rights' => 'nullable|array',
            'deliverables' => 'nullable|array',
            'obligations' => 'nullable|array',
        ]);

        $contract = new Contract($request->all());
        $contract->generateContractNumber();
        $contract->created_by = Auth::id();
        $contract->status = 'draft';
        
        // Calculate duration
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $contract->duration_months = $startDate->diffInMonths($endDate);
        
        $contract->save();

        // Generate PDF document
        $this->generateContractPDF($contract);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contract created successfully. Ready for signatures.');
    }

    /**
     * Display the specified contract.
     */
    public function show(Contract $contract)
    {
        $contract->load(['model', 'agency', 'creator', 'payments', 'amendments']);
        
        return view('contracts.show', compact('contract'));
    }

    /**
     * Show the form for editing the specified contract.
     */
    public function edit(Contract $contract)
    {
        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'Cannot edit contract that is not in draft status.');
        }

        $models = Talent::where('status', 'active')->get();
        $agencies = User::where('role', 'agency')->get();
        
        return view('contracts.edit', compact('contract', 'models', 'agencies'));
    }

    /**
     * Update the specified contract in storage.
     */
    public function update(Request $request, Contract $contract)
    {
        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'Cannot update contract that is not in draft status.');
        }

        $request->validate([
            'model_id' => 'required|exists:talents,id',
            'agency_id' => 'required|exists:users,id',
            'type' => 'required|in:employment,project,exclusive',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'payment_amount' => 'required|numeric|min:0',
            'payment_currency' => 'required|string|size:3',
            'payment_schedule' => 'required|in:monthly,quarterly,upfront',
        ]);

        $contract->update($request->all());
        
        // Recalculate duration
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $contract->duration_months = $startDate->diffInMonths($endDate);
        $contract->save();

        // Regenerate PDF
        $this->generateContractPDF($contract);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contract updated successfully.');
    }

    /**
     * Remove the specified contract from storage.
     */
    public function destroy(Contract $contract)
    {
        if ($contract->status !== 'draft') {
            return redirect()->route('contracts.show', $contract)
                ->with('error', 'Cannot delete contract that is not in draft status.');
        }

        $contract->delete();

        return redirect()->route('contracts.index')
            ->with('success', 'Contract deleted successfully.');
    }

    /**
     * Send contract for signature.
     */
    public function sendForSignature(Contract $contract, Request $request)
    {
        $request->validate([
            'party' => 'required|in:model,agency,academy',
            'email' => 'required|email',
        ]);

        $contract->status = 'pending_signature';
        $contract->save();

        // Send email with signature link
        $contract->sendForSignature($request->email, $request->party);

        return redirect()->route('contracts.show', $contract)
            ->with('success', "Contract sent to {$request->party} for signature.");
    }

    /**
     * Sign contract.
     */
    public function signContract(Contract $contract, Request $request)
    {
        $request->validate([
            'party' => 'required|in:model,agency,academy',
            'signature' => 'required|string',
            'signature_ip' => 'required|ip',
        ]);

        // Verify the party is authorized to sign
        if (!$this->canSignContract($contract, $request->party)) {
            return back()->with('error', 'You are not authorized to sign this contract.');
        }

        $signatureData = [
            'signature' => $request->signature,
            'ip_address' => $request->signature_ip,
            'user_agent' => $request->userAgent(),
            'timestamp' => Carbon::now()->toISOString(),
        ];

        $contract->signContract($request->party, $signatureData);

        // Send notifications
        $this->notifyContractSigned($contract, $request->party);

        return back()->with('success', 'Contract signed successfully.');
    }

    /**
     * Terminate contract.
     */
    public function terminate(Contract $contract, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if (!$contract->canBeTerminated()) {
            return back()->with('error', 'This contract cannot be terminated.');
        }

        $contract->terminate($request->reason);

        // Send notifications
        $this->notifyContractTerminated($contract, $request->reason);

        return back()->with('success', 'Contract terminated successfully.');
    }

    /**
     * Download contract PDF.
     */
    public function download(Contract $contract)
    {
        $filePath = storage_path("app/contracts/{$contract->document_url}");
        
        if (!file_exists($filePath)) {
            // Generate PDF if it doesn't exist
            $this->generateContractPDF($contract);
        }

        return response()->download($filePath, "contract_{$contract->contract_number}.pdf");
    }

    /**
     * Get contract analytics.
     */
    public function analytics()
    {
        $totalContracts = Contract::count();
        $activeContracts = Contract::active()->count();
        $pendingContracts = Contract::pendingSignature()->count();
        $expiringContracts = Contract::expiringSoon()->count();
        
        $monthlyContracts = Contract::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        $contractTypes = Contract::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        $totalValue = Contract::active()->sum(function ($contract) {
            return $contract->calculateTotalValue();
        });

        return view('contracts.analytics', compact(
            'totalContracts',
            'activeContracts', 
            'pendingContracts',
            'expiringContracts',
            'monthlyContracts',
            'contractTypes',
            'totalValue'
        ));
    }

    /**
     * Check if user can sign contract.
     */
    private function canSignContract(Contract $contract, $party)
    {
        $user = Auth::user();
        
        switch ($party) {
            case 'model':
                return $user->talent && $user->talent->id === $contract->model_id;
            case 'agency':
                return $user->id === $contract->agency_id;
            case 'academy':
                return $user->hasRole('admin') || $user->hasRole('academy');
            default:
                return false;
        }
    }

    /**
     * Generate contract PDF.
     */
    private function generateContractPDF(Contract $contract)
    {
        $pdf = PDF::loadView('contracts.pdf', compact('contract'));
        
        $filename = "contract_{$contract->contract_number}.pdf";
        $path = storage_path("app/contracts/{$filename}");
        
        // Ensure directory exists
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $pdf->save($path);
        
        $contract->document_url = $filename;
        $contract->save();
    }

    /**
     * Notify parties when contract is signed.
     */
    private function notifyContractSigned(Contract $contract, $party)
    {
        // Implementation for sending notifications
        \Log::info("Contract {$contract->contract_number} signed by {$party}");
    }

    /**
     * Notify parties when contract is terminated.
     */
    private function notifyContractTerminated(Contract $contract, $reason)
    {
        // Implementation for sending notifications
        \Log::info("Contract {$contract->contract_number} terminated: {$reason}");
    }
}
