<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Medicine;
use App\Models\MedicineRequest;

class MedicineRequestComponent extends Component
{
    public $medicines;
    public $selectedMedicineId;
    public $quantity = 1;
    public $reason = '';

    public $edit_id;
    public $deleteRequestMedicineId;

    // Properties for view details
    public $viewRequest;

    protected $rules = [
        'selectedMedicineId' => 'required|exists:medicines,medicine_id',
        'quantity' => 'required|integer|min:1',
        'reason' => 'string|max:500',
    ];

    public function mount(){
        // Filter out expired medicines and out of stock medicines
        $this->medicines = Medicine::where('stock_status', '!=', 'Out of Stock')
            ->where('stock', '>', 0)
            ->where('expiry_status', '!=', 'Expired')
            ->where(function($query) {
                $query->where('expiry_date', '>', now())
                      ->orWhereNull('expiry_date');
            })
            ->get();
    }

    public function submitRequest()
    {
        $this->validate();

        $medicine = Medicine::findOrFail($this->selectedMedicineId);

        // Check if medicine is expired
        if ($medicine->expiry_status === 'Expired' || $medicine->expiry_date <= now()) {
            $this->addError('selectedMedicineId', 'This medicine has expired and cannot be requested.');
            return;
        }

        if ($this->quantity > $medicine->stock) {
            $this->addError('quantity', 'Requested quantity exceeds available stock.');
            return;
        }

        $user = auth()->user();
        $patient = $user->patient; // This might be null

        // Prepare request data
        $requestData = [
            'medicine_id' => $this->selectedMedicineId,
            'quantity_requested' => $this->quantity,
            'reason' => $this->reason,
            'status' => 'pending',
        ];

        // Set either patient_id or user_id
        if ($patient) {
            $requestData['patients_id'] = $patient->id;
        } else {
            $requestData['user_id'] = $user->id;
        }

        MedicineRequest::create($requestData);

        $this->reset(['selectedMedicineId', 'quantity', 'reason']);
        $this->resetErrorBag();

        $this->dispatch('medicineRequest-added');
    }

    public function editRequest($requestId)
    {
        $this->dispatch('show-editRequest-modal');
        $request = MedicineRequest::findOrFail($requestId);

        if ($request->status !== 'pending') {
            session()->flash('error', 'Only pending requests can be edited.');
            return;
        }

        // Verify this request belongs to the current user
        $user = auth()->user();
        $patient = $user->patient;

        $authorized = false;
        if ($patient && $request->patients_id === $patient->id) {
            $authorized = true;
        } elseif ($request->user_id === $user->id) {
            $authorized = true;
        }

        if (!$authorized) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->edit_id = $request->id;
        $this->selectedMedicineId = $request->medicine_id;
        $this->quantity = $request->quantity_requested;
        $this->reason = $request->reason;
    }

    public function updateRequest()
    {
        $this->validate();

        $request = MedicineRequest::findOrFail($this->edit_id);

        // Authorization check
        $user = auth()->user();
        $patient = $user->patient;

        $authorized = false;
        if ($patient && $request->patients_id === $patient->id) {
            $authorized = true;
        } elseif ($request->user_id === $user->id) {
            $authorized = true;
        }

        if (!$authorized) {
            $this->dispatch('swal:error', [
                'title' => 'Unauthorized',
                'text' => 'You are not authorized to perform this action.',
            ]);
            return;
        }

        if ($request->status !== 'pending') {
            $this->dispatch('swal:error', [
                'title' => 'Cannot Update',
                'text' => 'Only pending requests can be updated.',
            ]);
            return;
        }

        // Check if new quantity is available
        $medicine = Medicine::findOrFail($this->selectedMedicineId);

        // Check if medicine is expired
        if ($medicine->expiry_status === 'Expired' || $medicine->expiry_date <= now()) {
            $this->dispatch('swal:error', [
                'title' => 'Medicine Expired',
                'text' => 'This medicine has expired and cannot be requested.',
            ]);
            return;
        }

        if ($this->quantity > $medicine->stock) {
            $this->addError('quantity', 'Requested quantity exceeds available stock.');
            return;
        }

        // Update the request
        $request->update([
            'medicine_id' => $this->selectedMedicineId,
            'quantity_requested' => $this->quantity,
            'reason' => $this->reason,
        ]);

        $this->dispatch('swal:success', [
            'title' => 'Updated!',
            'text' => 'Medicine request updated successfully.',
        ]);

        $this->dispatch('close-medicineRequest-modal');
        $this->resetForm();
    }

    public function viewDetails($requestId)
    {
        $request = MedicineRequest::with(['medicine', 'patients', 'user'])->findOrFail($requestId);

        // Verify this request belongs to the current user
        $user = auth()->user();
        $patient = $user->patient;

        $authorized = false;
        if ($patient && $request->patients_id === $patient->id) {
            $authorized = true;
        } elseif ($request->user_id === $user->id) {
            $authorized = true;
        }

        if (!$authorized) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->viewRequest = $request;
    }

    public function confirmRequestMedicineDelete($id){
        $this->deleteRequestMedicineId = $id;
        $this->dispatch('show-deleleteRequestModal');
    }
    

    public function deleteRequest()
    {
        $request = MedicineRequest::findOrFail($this->deleteRequestMedicineId);

        // Authorization check before delete
        $user = auth()->user();
        $patient = $user->patient;

        $authorized = false;
        if ($patient && $request->patients_id === $patient->id) {
            $authorized = true;
        } elseif ($request->user_id === $user->id) {
            $authorized = true;
        }

        if (!$authorized) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $request->delete();
        $this->dispatch('success-deleteMedicineRequestModal');
    }

    public function resetForm()
    {
        $this->reset([
            'selectedMedicineId',
            'quantity',
            'reason',
            'edit_id',
        ]);

        $this->resetErrorBag();

        // Refresh the medicines list to exclude newly expired medicines
        $this->medicines = Medicine::where('stock_status', '!=', 'Out of Stock')
            ->where('stock', '>', 0)
            ->where('expiry_status', '!=', 'Expired')
            ->where(function($query) {
                $query->where('expiry_date', '>', now())
                      ->orWhereNull('expiry_date');
            })
            ->get();
    }


    public function render()
    {
        $user = auth()->user();
        $patient = $user->patient;

        // Get requests for this user (either through patient or user_id)
        $myRequests = MedicineRequest::with(['medicine', 'patients', 'user'])
            ->where(function($query) use ($patient, $user) {
                if ($patient) {
                    $query->where('patients_id', $patient->id);
                }
                $query->orWhere('user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.medicine-request', compact('myRequests'))->layout('livewire.layouts.requestMedicineLayout');
    }
}