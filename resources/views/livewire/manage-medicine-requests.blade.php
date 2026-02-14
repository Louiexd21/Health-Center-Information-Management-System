<div>
    
    <main class="d-flex flex-column container-fluid bg-light">
        <h2 class="mb-5 fs-1 text-center">Manage Medicine Requests</h2>

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

        @if ($errors->has('stock'))
            <div class="alert alert-danger alert-dismissible fade show mx-3" role="alert">
                {{ $errors->first('stock') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="m-3 p-lg-5 p-md-3 p-2 shadow min-h-[70vh]">
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
                    <input wire:model.live.debounce.300ms="search" type="search" class="form-control" placeholder="Search patient or medicine...">
                </div>
                <div class="flex-fill">
                    <label for="" class="form-label">Filter Status</label>
                    <select wire:model.live="filterStatus" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                        <option value="">All Status</option>
                    </select>
                </div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#walkInModal" wire:click="resetWalkInForm">
                    <i class="fa-solid fa-user-plus me-1"></i>Add Walk-In
                </button>
            </div>

            {{-- Statistics Cards --}}
            <div class="row mt-4 mb-3">
                <div class="col-lg-3 col-md-4 col-sm-6  mb-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h3 class="text-warning">{{ $this->getPendingCount() }}</h3>
                            <p class="mb-0">Pending Requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6  mb-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h3 class="text-success">{{ $this->getCompletedCount() }}</h3>
                            <p class="mb-0">Completed</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6  mb-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h3 class="text-danger">{{ $this->getRejectedCount() }}</h3>
                            <p class="mb-0">Rejected</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6  mb-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h3 class="text-primary">{{ $this->getTotalCount() }}</h3>
                            <p class="mb-0">Total Requests</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Requests Table --}}
            <div class="table-responsive mt-4">
                <table class="table table-hover" id="requestsTable">
                    <thead class="table-header">
                        <tr>
                            <th class="text-center text-nowrap" scope="col">Patient Name</th>
                            <th class="text-center text-nowrap" scope="col">Medicine</th>
                            <th class="text-center text-nowrap" scope="col">Quantity</th>
                            <th class="text-center text-nowrap" scope="col">Reason</th>
                            <th class="text-center text-nowrap" scope="col">Status</th>
                            <th class="text-center text-nowrap" scope="col">Date Requested</th>
                            <th class="text-center text-nowrap" scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $index => $request)
                            <tr>
                                <td class="text-center">{{ $request->requester_name }}</td>
                                <td class="text-center">
                                    @if($request->medicine)
                                        <strong>{{ $request->medicine->medicine_name }}</strong>
                                        @if($request->medicine->trashed())
                                            <span class="badge bg-secondary text-white ms-1" title="This medicine has been archived">
                                                <i class="fa-solid fa-archive"></i> Archived
                                            </span>
                                        @endif
                                        <br>
                                        <small class="text-muted">{{ $request->medicine->dosage }}</small>
                                    @else
                                        <span class="text-muted fst-italic">Medicine not found</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $request->quantity_requested }}</td>
                                <td class="text-center">
                                    <small>{{ Str::limit($request->reason, 50) }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{
                                        $request->status === 'pending' ? 'warning' :
                                        ($request->status === 'completed' ? 'success' : 'danger') }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    {{ $request->created_at->format('F d Y') }}<br>
                                    <small class="text-muted">{{ $request->created_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        @if ($request->status === 'pending')
                                            @if($request->medicine && !$request->medicine->trashed())
                                                <button wire:click="approve({{ $request->id }})"
                                                        class="btn btn-sm btn-success text-nowrap">
                                                    <i class="fa-solid fa-check me-1"></i>Approve
                                                </button>
                                            @else
                                                <button disabled
                                                        class="btn btn-sm btn-secondary"
                                                        title="Cannot approve - medicine is archived text-nowrap">
                                                    <i class="fa-solid fa-ban me-1"></i>Archived
                                                </button>
                                            @endif

                                            <button wire:click="reject({{ $request->id }})"
                                                    class="btn btn-sm btn-danger text-nowrap">
                                                <i class="fa-solid fa-times me-1"></i>Reject
                                            </button>
                                        @endif

                                        <button wire:click="viewDetails({{ $request->id }})"
                                                class="btn btn-sm btn-primary text-nowrap"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewDetailsModal">
                                            <i class="fa-solid fa-eye fa-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fa-solid fa-inbox fs-1 text-muted mb-3 d-block"></i>
                                    <p class="text-muted">No medicine requests found</p>
                                </td>
                            </tr>
                            @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->links() }}
        </div>
    </main>

    {{-- Walk-In Modal --}}
    <div class="modal fade" id="walkInModal" tabindex="-1" aria-labelledby="walkInModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow">
                {{-- Modal Header --}}
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="walkInModalLabel">
                        <i class="fa-solid fa-user-plus me-2"></i>Walk-In Medicine Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" wire:click="resetWalkInForm"></button>
                </div>

                {{-- Modal Body --}}
                <div class="modal-body">
                    <form wire:submit.prevent="createWalkIn">
                        @csrf

                        {{-- User Search & Selection with Select2 --}}
                        <div class="mb-3">
                            <label class="form-label">Search User/Patient <span class="text-danger">*</span></label>
                            
                            <div wire:ignore>
                                <select id="userSelect" 
                                        class="form-control user-search @error('walkInUserId') is-invalid @enderror"
                                        style="width: 100%;">
                                    <option value="">Select user/patient</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->full_name }}
                                            @if($user->patient)
                                                ✓ Has Patient Record
                                            @endif
                                            @if($user->patient_type)
                                                - {{ $user->patient_type }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            @error('walkInUserId')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Medicine Selection --}}
                        <div class="mb-3">
                            <label class="form-label">Medicine <span class="text-danger">*</span></label>
                            <select wire:model="walkInMedicineId" class="form-select @error('walkInMedicineId') is-invalid @enderror">
                                <option value="">Select medicine</option>
                                @foreach($medicines as $medicine)
                                    <option value="{{ $medicine->medicine_id }}">
                                        {{ $medicine->medicine_name }} - {{ $medicine->dosage }}
                                        (Stock: {{ $medicine->stock }})
                                    </option>
                                @endforeach
                            </select>
                            @error('walkInMedicineId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Quantity --}}
                        <div class="mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input wire:model="walkInQuantity"
                                   type="number"
                                   class="form-control @error('walkInQuantity') is-invalid @enderror"
                                   min="1"
                                   placeholder="Enter quantity" min="0" max="99999" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2)" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                            @error('walkInQuantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Reason --}}
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea wire:model="walkInReason"
                                    class="form-control @error('walkInReason') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Enter reason for medicine request..."></textarea>
                            @error('walkInReason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-success">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <small>This is a walk-in request. The medicine will be dispensed immediately upon submission.</small>
                        </div>
                    </form>
                </div>

                {{-- Modal Footer --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="resetWalkInForm">
                        Cancel
                    </button>
                    <button type="button" wire:click="createWalkIn" class="btn btn-success">
                        <i class="fa-solid fa-check me-1"></i>Dispense Medicine
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- View Details Modal --}}
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
                                <label class="form-label text-muted small">Patient Name</label>
                                <p class="fw-bold">{{ $viewRequest->requester_name }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Status</label>
                                <p>
                                    @if($viewRequest->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
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
                                <p class="fw-bold">
                                    @if($viewRequest->medicine)
                                        {{ $viewRequest->medicine->medicine_name }}
                                        @if($viewRequest->medicine->trashed())
                                            <span class="badge bg-secondary text-white ms-1" title="This medicine has been archived">
                                                <i class="fa-solid fa-archive"></i> Archived
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted fst-italic">Medicine not found</span>
                                    @endif
                                </p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Dosage</label>
                                <p class="fw-bold">{{ $viewRequest->medicine->dosage ?? 'N/A' }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Type</label>
                                <p class="fw-bold">{{ $viewRequest->medicine->type ?? 'N/A' }}</p>
                            </div>

                            @if($viewRequest->medicine && !$viewRequest->medicine->trashed())
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Current Stock</label>
                                <p class="fw-bold">{{ $viewRequest->medicine->stock ?? 'N/A' }}</p>
                            </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Quantity Requested</label>
                                <p class="fw-bold text-primary">{{ $viewRequest->quantity_requested }}</p>
                            </div>

                            {{-- Request Reason --}}
                            <div class="col-12 mt-3">
                                <label class="form-label text-muted small">Reason for Request</label>
                                <div class="p-3 bg-light rounded">
                                    <p class="mb-0">{{ $viewRequest->reason }}</p>
                                </div>
                            </div>

                            {{-- Timestamps --}}
                            <div class="col-12 mt-4">
                                <h6 class="border-bottom pb-2 mb-3 text-success">
                                    <i class="fa-solid fa-clock me-2"></i>Timeline
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Date Requested</label>
                                <p>{{ $viewRequest->created_at->format('M d, Y h:i A') }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Last Updated</label>
                                <p>{{ $viewRequest->updated_at->format('M d, Y h:i A') }}</p>
                            </div>

                            @if($viewRequest->status === 'pending')
                                @if($viewRequest->medicine && $viewRequest->medicine->trashed())
                                    <div class="col-12 mt-3">
                                        <div class="alert alert-danger mb-0">
                                            <i class="fa-solid fa-archive me-2"></i>
                                            <small><strong>Warning:</strong> This medicine has been archived and cannot be approved.</small>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-12 mt-3">
                                        <div class="alert alert-warning mb-0">
                                            <i class="fa-solid fa-clock me-2"></i>
                                            <small>This request is awaiting approval.</small>
                                        </div>
                                    </div>
                                @endif
                            @elseif($viewRequest->status === 'completed')
                            <div class="col-12 mt-3">
                                <div class="alert alert-success mb-0">
                                    <i class="fa-solid fa-check-circle me-2"></i>
                                    <small>This request has been completed successfully.</small>
                                </div>
                            </div>
                            @elseif($viewRequest->status === 'rejected')
                            <div class="col-12 mt-3">
                                <div class="alert alert-danger mb-0">
                                    <i class="fa-solid fa-times-circle me-2"></i>
                                    <small>This request has been rejected.</small>
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
                    @if($viewRequest && $viewRequest->status === 'pending')
                        @if($viewRequest->medicine && !$viewRequest->medicine->trashed())
                            <button type="button"
                                    wire:click="approve({{ $viewRequest->id }})"
                                    class="btn btn-success"
                                    data-bs-dismiss="modal">
                                <i class="fa-solid fa-check me-1"></i>Approve
                            </button>
                        @endif
                        <button type="button"
                                wire:click="reject({{ $viewRequest->id }})"
                                class="btn btn-danger"
                                data-bs-dismiss="modal">
                            <i class="fa-solid fa-times me-1"></i>Reject
                        </button>
                    @endif
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>