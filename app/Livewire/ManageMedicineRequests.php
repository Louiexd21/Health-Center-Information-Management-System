<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MedicineRequest;
use App\Models\User;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MedicineRequestLog;

class ManageMedicineRequests extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterStatus = 'pending';
    public $perPage = 10;

    // Property for view details
    public $viewRequest;

    // Walk-in properties
    public $walkInUserId;
    public $walkInMedicineId;
    public $walkInQuantity = 1;
    public $walkInReason = '';
    public $userSearch = '';
    public $users;
    public $medicines;

    protected $rules = [
        'walkInUserId' => 'required|exists:users,id',
        'walkInMedicineId' => 'required|exists:medicines,medicine_id',
        'walkInQuantity' => 'required|integer|min:1',
        'walkInReason' => 'nullable|string|max:500',
    ];

    protected $messages = [
        'walkInUserId.required' => 'Please select a user/patient.',
        'walkInMedicineId.required' => 'Please select a medicine.',
        'walkInQuantity.required' => 'Please enter a quantity.',
        'walkInQuantity.min' => 'Quantity must be at least 1.',
    ];

    public function mount()
    {
        $this->loadUsers();
        $this->loadMedicines();
    }

    public function loadUsers()
    {
        // Load all users (system will auto-detect if they have patient records)
        $this->users = User::when($this->userSearch, function ($query) {
                $query->where(function($q) {
                    $q->where('first_name', 'like', "%{$this->userSearch}%")
                      ->orWhere('last_name', 'like', "%{$this->userSearch}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', IFNULL(middle_initial, ''), ' ', last_name) LIKE ?", ["%{$this->userSearch}%"]);
                });
            })
            ->whereIn('role', ['user', 'patient']) // Only load user/patient roles, not staff
            ->orderBy('first_name')
            ->limit(50)
            ->get();
    }

    public function updatedUserSearch()
    {
        $this->reset('walkInUserId');   
        $this->loadUsers();
    }

    public function loadMedicines()
    {
        $this->medicines = Medicine::where('stock_status', '!=', 'Out of Stock')
            ->where('stock', '>', 0)
            ->where('expiry_status', '!=', 'Expired')
            ->where(function($query) {
                $query->where('expiry_date', '>', now())
                      ->orWhereNull('expiry_date');
            })
            ->get();
    }

    public function createWalkIn()
    {
        // First validate the basic rules
        $this->validate();

        // Then validate stock availability
        if (!$this->validateWalkInStock()) {
            return; // Validation failed, error already added
        }

        DB::transaction(function () {
            $medicine = Medicine::lockForUpdate()->findOrFail($this->walkInMedicineId);

            // Double-check stock inside transaction (race condition protection)
            if ($medicine->stock < $this->walkInQuantity) {
                throw new \Exception("Insufficient medicine stock. Only {$medicine->stock} available.");
            }

            // Get the user
            $user = User::findOrFail($this->walkInUserId);
            $patient = $user->patient; // This might be null

            // Deduct stock
            $medicine->decrement('stock', $this->walkInQuantity);

            // Recalculate and update stock status
            $newStock = $medicine->fresh()->stock;
            $newStockStatus = $this->determineStockStatus($newStock);

            $medicine->update([
                'stock_status' => $newStockStatus
            ]);

            // Prepare request data
            $requestData = [
                'medicine_id' => $this->walkInMedicineId,
                'quantity_requested' => $this->walkInQuantity,
                'reason' => $this->walkInReason,
                'status' => 'completed',
                'approved_by_id' => auth()->id(),
                'approved_by_type' => get_class(auth()->user()),
                'approved_at' => now(),
            ];

            // Automatically set patient_id if exists, otherwise use user_id
            if ($patient) {
                $requestData['patients_id'] = $patient->id;
            } else {
                $requestData['user_id'] = $user->id;
            }

            // Create the walk-in request with completed status
            $request = MedicineRequest::create($requestData);

            // Create log for walk-in
            MedicineRequestLog::create([
                'medicine_request_id' => $request->id,
                'patient_name'        => $user->full_name,
                'medicine_name'       => $medicine->medicine_name,
                'dosage'              => $medicine->dosage,
                'quantity'            => $this->walkInQuantity,
                'action'              => 'approved',
                'performed_by_id'     => auth()->id(),
                'performed_by_name'   => auth()->user()->username ?? auth()->user()->full_name,
                'performed_at'        => now(),
            ]);
        });

        $this->resetWalkInForm();
        $this->dispatch('close-walkin-modal');
        session()->flash('message', 'Walk-in medicine dispensed successfully.');
    }

    /**
     * Validate that the requested quantity doesn't exceed available stock
     */
    protected function validateWalkInStock()
    {
        if ($this->walkInMedicineId && $this->walkInQuantity) {
            $medicine = Medicine::find($this->walkInMedicineId);
            
            if (!$medicine) {
                $this->addError('walkInMedicineId', 'Selected medicine not found.');
                return false;
            }
            
            if ($this->walkInQuantity > $medicine->stock) {
                $this->addError('walkInQuantity', "Quantity exceeds available stock ({$medicine->stock} available).");
                return false;
            }
        }
        return true;
    }

    public function resetWalkInForm()
    {
        $this->reset([
            'walkInUserId',
            'walkInMedicineId',
            'walkInQuantity',
            'walkInReason',
            'userSearch'
        ]);
        $this->resetErrorBag();
        $this->loadUsers();
        $this->loadMedicines();
    }

    private function determineStockStatus($stock)
    {
        if ($stock <= 0) {
            return 'Out of Stock';
        }
        if ($stock <= 10) {
            return 'Low Stock';
        }
        return 'In Stock';
    }

    public function approve($requestId)
    {
        DB::transaction(function () use ($requestId) {
            $request = MedicineRequest::with(['medicine' => function($q) {
                    $q->withTrashed(); // Include archived medicines
                }, 'patients', 'user'])
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($request->status !== 'pending') {
                session()->flash('error', 'This request has already been processed.');
                return;
            }

            // Check if medicine is archived
            if ($request->medicine && $request->medicine->trashed()) {
                session()->flash('error', 'Cannot approve request - this medicine has been archived.');
                return;
            }

            if (!$request->medicine || $request->medicine->stock < $request->quantity_requested) {
                session()->flash('error', 'Insufficient medicine stock.');
                return;
            }

            // Deduct stock
            $request->medicine->decrement('stock', $request->quantity_requested);

            // Recalculate and update stock status
            $newStock = $request->medicine->fresh()->stock;
            $newStockStatus = $this->determineStockStatus($newStock);

            $request->medicine->update([
                'stock_status' => $newStockStatus
            ]);

            // Update status
            $request->update([
                'status' => 'completed',
            ]);

            // Get requester name using the accessor
            $requesterName = $request->requester_name;

            // Create log for APPROVAL
            MedicineRequestLog::create([
                'medicine_request_id' => $request->id,
                'patient_name'        => $requesterName,
                'medicine_name'       => $request->medicine->medicine_name,
                'dosage'              => $request->medicine->dosage,
                'quantity'            => $request->quantity_requested,
                'action'              => 'approved',
                'performed_by_id'     => auth()->id(),
                'performed_by_name'   => auth()->user()->username ?? auth()->user()->full_name,
                'performed_at'        => now(),
            ]);
        });

        $this->dispatch('approve-modal');
        session()->flash('message', 'Medicine request approved successfully.');
    }

    public function reject($requestId)
    {
        DB::transaction(function () use ($requestId) {
            $request = MedicineRequest::with(['medicine' => function($q) {
                    $q->withTrashed(); // Include archived medicines
                }, 'patients', 'user'])
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($request->status !== 'pending') {
                session()->flash('error', 'This request has already been processed.');
                return;
            }

            // Update status
            $request->update([
                'status' => 'rejected',
            ]);

            // Get requester name using the accessor
            $requesterName = $request->requester_name;

            // Create log for REJECTION
            MedicineRequestLog::create([
                'medicine_request_id' => $request->id,
                'patient_name'        => $requesterName,
                'medicine_name'       => $request->medicine->medicine_name ?? 'Unknown',
                'dosage'              => $request->medicine->dosage ?? 'N/A',
                'quantity'            => $request->quantity_requested,
                'action'              => 'rejected',
                'performed_by_id'     => auth()->id(),
                'performed_by_name'   => auth()->user()->username ?? auth()->user()->full_name,
                'performed_at'        => now(),
            ]);
        });

        session()->flash('message', 'Medicine request rejected.');
    }

    public function viewDetails($requestId)
    {
        $this->viewRequest = MedicineRequest::with([
                'medicine' => function($q) {
                    $q->withTrashed(); // Include archived medicines
                },
                'patients',
                'user'
            ])
            ->findOrFail($requestId);
    }

    public function getPendingCount()
    {
        return MedicineRequest::where('status', 'pending')->count();
    }

    public function getCompletedCount()
    {
        return MedicineRequest::where('status', 'completed')->count();
    }

    public function getRejectedCount()
    {
        return MedicineRequest::where('status', 'rejected')->count();
    }

    public function getTotalCount()
    {
        return MedicineRequest::count();
    }

    public function render()
    {
        $requests = MedicineRequest::query()
            ->with([
                'patients:id,first_name,middle_initial,last_name,suffix',
                'user:id,first_name,middle_initial,last_name',
                'medicine' => function($query) {
                    $query->withTrashed();
                }
            ])
            ->when($this->search, function ($q) {
                $q->where(function($query) {
                    $query->whereHas('patients', function($p) {
                            $p->where('first_name', 'like', "%{$this->search}%")
                              ->orWhere('last_name', 'like', "%{$this->search}%")
                              ->orWhereRaw("CONCAT(first_name, ' ', IFNULL(middle_initial, ''), ' ', last_name, ' ', IFNULL(suffix, '')) LIKE ?", ["%{$this->search}%"]);
                        })
                        ->orWhereHas('user', fn ($u) =>
                            $u->whereRaw("CONCAT(first_name, ' ', IFNULL(middle_initial, ''), ' ', last_name) LIKE ?", ["%{$this->search}%"])
                        )
                        ->orWhereHas('medicine', function($m) {
                            $m->withTrashed()
                              ->where('medicine_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterStatus, fn ($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->latest('created_at')
            ->paginate($this->perPage);

        return view('livewire.manage-medicine-requests', compact('requests'))
            ->layout('livewire.layouts.base');
    }
}