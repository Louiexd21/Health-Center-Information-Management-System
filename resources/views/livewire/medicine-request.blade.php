
<div>
    <main class="d-flex flex-column container-fluid bg-light">
        <h2 class="mb-2 fs-1 text-center">Request Medicine</h2>

        {{-- Success/Error Messages --}}
        @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show mx-3" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="m-nd-3 m-1 p-lg-5 p-md-3 p-2 shadow min-h-[70vh]">
            {{-- Filters and Actions --}}
            <div class="medicine-inventory d-flex gap-3 align-items-none align-items-sm-end flex-wrap flex-column flex-sm-row">
                <div class="flex-fill">
                    <label for="" class="form-label">Show</label>
                    <select wire:model.live="perPage" class="form-select w-50" name="show">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="75">75</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="flex-fill">
                    <label for="search" class="form-label">Search</label>
                    <input wire:model.live.debounce.300ms="search" type="search" class="form-control" placeholder="Search medicine...">
                </div>
                <div class="flex-fill">
                    <label for="" class="form-label">Filter</label>
                    <select wire:model.live="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestMedicineModal" wire:click="resetForm">
                    <i class="fa-solid fa-plus pe-1"></i>Request Medicine
                </button>
            </div>

            {{-- Requests Table --}}
            <div class="table-responsive mt-5">
                <table class="table table-hover" id="medicineTable">
                    <thead class="table-header">
                        <tr class="text-nowrap">
                            <th class="text-center" scope="col"><button class="sort-btn">No.</button></th>
                            <th class="text-center" scope="col"><button>Medicine Name</button></th>
                            <th class="text-center" scope="col"><button>Quantity</button></th>
                            <th class="text-center" scope="col"><button>Status</button></th>
                            <th class="text-center" scope="col"><button>Date Requested</button></th>
                            <th class="text-center" scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($myRequests as $index => $request)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="text-center">{{ $request->medicine->medicine_name ?? 'N/A' }}</td>
                                <td class="text-center">{{ $request->quantity_requested }}</td>
                                <td class="text-center">
                                    @if($request->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($request->status === 'approved')
                                        <span class="badge bg-info">Approved</span>
                                    @elseif($request->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($request->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $request->requested_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        @if($request->status === 'pending')
                                            <button   wire:click="editRequest({{ $request->id }})"
                                                    class="btn bg-primary text-white">
                                                <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                            </button>
                                            <button wire:click="confirmRequestMedicineDelete({{ $request->id }})"
                                                    class="btn p-0">
                                                <i class="fa-solid fa-trash text-danger fs-3"></i>
                                            </button>
                                        @else
                                            <button wire:click="viewDetails({{ $request->id }})"
                                                    class="btn btn-sm btn-success text-white text-nowrap"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewDetailsModal">
                                                <i class="fa-solid fa-eye me-1"></i>View Details
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fa-solid fa-inbox fs-1 text-muted mb-3 d-block"></i>
                                    <p class="text-muted">No medicine requests found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $myRequests->links() }}

        </div>
    </main>

    {{-- Request Medicine Modal --}}
    <div class="modal fade" id="requestMedicineModal" tabindex="-1" aria-labelledby="requestMedicineLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow">
                {{-- Modal Header --}}
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="requestMedicineLabel">
                        Request Modal
                </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="resetForm"></button>
                </div>

                {{-- Modal Body --}}
                <div class="modal-body">
                    <form wire:submit.prevent="submitRequest">
                        @csrf
                        {{-- Medicine Selection --}}
                        <div class="mb-3">
                            <label class="form-label">Medicine <span class="text-danger">*</span></label>
                            <select wire:model="selectedMedicineId" class="form-select @error('selectedMedicineId') is-invalid @enderror">
                                <option value="">Select medicine</option>
                                @foreach($medicines as $medicine)
                                    <option value="{{ $medicine->medicine_id }}">
                                        {{ $medicine->medicine_name }} - {{ $medicine->dosage }}
                                        (Available: {{ $medicine->stock }})
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedMedicineId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Quantity --}}
                        <div class="mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input wire:model="quantity"
                                   type="number"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   min="1"
                                   placeholder="Enter quantity" min="0" max="99999" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2)" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Reason --}}
                        <div class="mb-3">
                            <label class="form-label">Reason for Request</label>
                            <textarea wire:model="reason"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Describe the reason..."></textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <small>Please visit the health center to collect your medicine after your request is approved.</small>
                        </div>
                    </form>
                </div>

                {{-- Modal Footer --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal" wire:click="resetForm">
                        Cancel
                    </button>
                    <button type="button" wire:click="submitRequest" class="btn btn-success" id="submitBtn">Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
        {{-- Edit Medicine Modal --}}
    <div class="modal fade" id="editMedicineModal" tabindex="-1" aria-labelledby="editMedicineLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="editMedicineLabel">Edit Request Medicine</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="resetForm"></button>
                </div>

                <div class="modal-body">
                    <form wire:submit.prevent="updateRequest">
                        @csrf
                        {{-- Medicine Selection --}}
                        <div class="mb-3">
                            <label class="form-label">Medicine <span class="text-danger">*</span></label>
                            <select wire:model="selectedMedicineId" class="form-select @error('selectedMedicineId') is-invalid @enderror">
                                <option value="">Select medicine</option>
                                @foreach($medicines as $medicine)
                                    <option value="{{ $medicine->medicine_id }}">
                                        {{ $medicine->medicine_name }} - {{ $medicine->dosage }}
                                        (Available: {{ $medicine->stock }})
                                    </option>
                                @endforeach
                            </select>
                            @error('selectedMedicineId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Quantity --}}
                        <div class="mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input wire:model="quantity"
                                   type="number"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   min="1"
                                   placeholder="Enter quantity">
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Reason --}}
                        <div class="mb-3">
                            <label class="form-label">Reason for Request <span class="text-danger">*</span></label>
                            <textarea wire:model="reason"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Describe the reason..."></textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <small>Please visit the health center to collect your medicine after your request is approved.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal" wire:click="resetForm">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-success" wire:click="updateRequest" id="submitBtn">Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- View Details Modal - Add this before @push('scripts') --}}
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow">
                {{-- Modal Header --}}
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="viewDetailsLabel">
                        <i class="fa-solid fa-info-circle me-2"></i>Request Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                {{-- Modal Body --}}
                <div class="modal-body">
                    @if($viewRequest)
                        <div class="row g-3">
                            {{-- Request Information --}}
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3 text-success">
                                    <i class="fa-solid fa-file-medical me-2"></i>Request Information
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Request ID</label>
                                <p class="fw-bold">#{{ $viewRequest->id }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Status</label>
                                <p>
                                    @if($viewRequest->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($viewRequest->status === 'approved')
                                        <span class="badge bg-info">Approved</span>
                                    @elseif($viewRequest->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($viewRequest->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </p>
                            </div>

                            {{-- Medicine Details --}}
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2 mb-3 text-success">
                                    <i class="fa-solid fa-pills me-2"></i>Medicine Details
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Medicine Name</label>
                                <p class="fw-bold">{{ $viewRequest->medicine->medicine_name ?? 'N/A' }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Dosage</label>
                                <p class="fw-bold">{{ $viewRequest->medicine->dosage ?? 'N/A' }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Quantity Requested</label>
                                <p class="fw-bold">{{ $viewRequest->quantity_requested }}</p>
                            </div>

                            @if($viewRequest->quantity_approved)
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Quantity Approved</label>
                                <p class="fw-bold text-success">{{ $viewRequest->quantity_approved }}</p>
                            </div>
                            @endif

                            {{-- Request Reason --}}
                            <div class="col-12 mt-3">
                                <label class="form-label text-muted small">Reason for Request</label>
                                <div class="p-3 bg-light rounded">
                                    <p class="mb-0">{{ $viewRequest->reason }}</p>
                                </div>
                            </div>

                            {{-- Admin Response --}}
                            @if($viewRequest->admin_notes)
                            <div class="col-12 mt-3">
                                <label class="form-label text-muted small">Admin Notes</label>
                                <div class="p-3 bg-light rounded border-start border-4 border-info">
                                    <p class="mb-0">{{ $viewRequest->admin_notes }}</p>
                                </div>
                            </div>
                            @endif

                            {{-- Timestamps --}}
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2 mb-3 text-success">
                                    <i class="fa-solid fa-clock me-2"></i>Timeline
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Date Requested</label>
                                <p>{{ $viewRequest->requested_at->format('M d, Y h:i A') }}</p>
                            </div>

                            @if($viewRequest->processed_at)
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Date Processed</label>
                                <p>{{ $viewRequest->processed_at->format('M d, Y h:i A') }}</p>
                            </div>
                            @endif

                            @if($viewRequest->completed_at)
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Date Completed</label>
                                <p>{{ $viewRequest->completed_at->format('M d, Y h:i A') }}</p>
                            </div>
                            @endif

                            @if($viewRequest->status === 'approved' && !$viewRequest->completed_at)
                            <div class="col-12 mt-3">
                                <div class="alert alert-info mb-0">
                                    <i class="fa-solid fa-info-circle me-2"></i>
                                    <small>Please visit the health center to collect your medicine.</small>
                                </div>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fa-solid fa-exclamation-triangle fs-1 text-warning mb-3"></i>
                            <p class="text-muted">No details available</p>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- Scripts --}}
    @push('scripts')
    <script>
        // Close modal after successful submission
        document.addEventListener('livewire:init', () => {
            Livewire.on('close-modal', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('requestMedicineModal'));
                if (modal) {
                    modal.hide();
                }
            });
        });
    </script>
    @endpush

</div>