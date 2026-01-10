<div>
    <main class="d-flex flex-column container-fluid bg-light ">
        <div class="m-3 p-3 shadow min-vh-100">
            <h2 class="mb-5 fs-1 text-center">Medicine Inventory</h2>

            @if (session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="medicine-inventory d-flex gap-3 align-items-none align-items-sm-end flex-wrap flex-column flex-sm-row">
                <div class="flex-fill">
                    <label for="" class="form-label">Show</label>
                    <select type="text" class="form-select w-50" name="show" wire:model.live="perPage">
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
                    <input type="search" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search ....">
                </div>
                <div class="flex-fill">
                    <label for="" class="form-label">Category Filter</label>
                    <select name="" class="form-select" id="" wire:model.live="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->category_id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-fill">
                    <label for="" class="form-label">Age Range Filter</label>
                    <select name="" class="form-select" id="" wire:model.live="ageFilter">
                        <option value="">All Ages</option>
                        <option value="0-9months">0-9 months</option>
                        <option value="10-24months">10-24 months (1-2 years)</option>
                        <option value="2-5years">2-5 years</option>
                        <option value="6-12years">6-12 years</option>
                        <option value="13-17years">13-17 years</option>
                        <option value="adult">Adult (18+ years)</option>
                    </select>
                </div>
                <button class="btn btn-secondary" wire:click="toggleArchived">
                    <i class="fa-solid fa-{{ $showArchived ? 'list' : 'archive' }} pe-1"></i>
                    {{ $showArchived ? 'Show Active' : 'Show Archived' }}
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMedicineModal"><i class="fa-solid fa-plus pe-1"></i>Add Medicine</button>
            </div>
            @php
            function sortIcon($sortField, $currentField, $direction)
            {
                if ($sortField !== $currentField) {
                    return '
                               <i class="bi bi-chevron-expand"></i>
                            ';
                }

                if ($direction === 'asc') {
                    return '<i class="fa-solid fa-chevron-up"></i>';
                }

                if ($direction === 'desc') {
                    return '<i class="fa-solid fa-chevron-down"></i>';
                }

                return '';
            }

            function formatAgeRange($minMonths, $maxMonths)
            {
                if (is_null($minMonths) && is_null($maxMonths)) {
                    return 'All ages';
                }

                $minStr = '';
                $maxStr = '';

                if (!is_null($minMonths)) {
                    if ($minMonths < 12) {
                        $minStr = $minMonths . ' months';
                    } else {
                        $years = floor($minMonths / 12);
                        $months = $minMonths % 12;
                        $minStr = $years . ' ' . ($years == 1 ? 'year' : 'years');
                        if ($months > 0) {
                            $minStr .= ' ' . $months . ' months';
                        }
                    }
                }

                if (!is_null($maxMonths)) {
                    if ($maxMonths < 12) {
                        $maxStr = $maxMonths . ' months';
                    } else {
                        $years = floor($maxMonths / 12);
                        $months = $maxMonths % 12;
                        $maxStr = $years . ' ' . ($years == 1 ? 'year' : 'years');
                        if ($months > 0) {
                            $maxStr .= ' ' . $months . ' months';
                        }
                    }
                }

                if (is_null($minMonths)) {
                    return 'Up to ' . $maxStr;
                } elseif (is_null($maxMonths)) {
                    return $minStr . '+';
                } else {
                    return $minStr . ' - ' . $maxStr;
                }
            }
            @endphp

            <div class="table-responsive mt-5">
                <table class="table table-hover" id="medicineTable">
                    <thead class="table-header">
                        <tr>
                            <!-- <th class="text-center" scope="col"><button wire:click="sortBy('medicine_id')" class="sort-btn">No. {!! sortIcon($sortField, 'medicine_id', $sortDirection) !!}</button></th> -->
                            <th class="text-center" scope="col"><button wire:click="sortBy('medicine_name')">Medicine Name {!! sortIcon($sortField, 'medicine_name', $sortDirection) !!}</button></th>
                            <th class="text-center" scope="col"><button wire:click="sortBy('category_name')">Category {!! sortIcon($sortField, 'category_name', $sortDirection) !!}</button></th>
                            <th class="text-center" scope="col"><button wire:click="sortBy('dosage')">Dosage {!! sortIcon($sortField, 'dosage', $sortDirection) !!}</button></th>
                            <th class="text-center" scope="col">Age Range</th>
                            <th class="text-center" scope="col"><button wire:click="sortBy('stock')"> Stock  {!! sortIcon($sortField, 'stock', $sortDirection) !!}</button></th>
                            <th class="text-center" scope="col"><button wire:click="sortBy('stock_status')"> Stock Status {!! sortIcon($sortField, 'stock_status', $sortDirection) !!}</button></th>
                            <th class="text-center" scope="col"><button wire:click="sortBy('expiry_status')">Expiry Status {!! sortIcon($sortField, 'expiry_status', $sortDirection) !!}</button></th>
                            <th class="text-center" scope="col"><button wire:click="sortBy('expiry_date')">Expiry Date {!! sortIcon($sortField, 'expiry_date', $sortDirection) !!}</button></th>
                            <!-- <th class="text-center" scope="col"><button wire:click="sortBy('created_at')">Date {!! sortIcon($sortField, 'created_at', $sortDirection) !!}</button></th> -->
                            <th class="text-center" scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($medicines as $medicine)
                        <tr>
                            <!-- <td>{{ $medicine->medicine_id }}</td> -->
                            <td>{{ $medicine->medicine_name }}</td>
                            <td>{{ $medicine->category->category_name }}</td>
                            <td>{{ $medicine->dosage }}</td>
                            <td class="text-center">
                                <span class=" px-3 py-1 rounded-full text-xs font-semibold bg-primary bg-opacity-25 text-blue-800">
                                    {{ formatAgeRange($medicine->min_age_months, $medicine->max_age_months) }}
                                </span>
                            </td>
                            <td>{{ $medicine->stock }}</td>
                            <td>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if ($medicine->stock_status === 'In Stock') bg-success bg-opacity-25 text-success
                                    @elseif ($medicine->stock_status === 'Low Stock') bg-warning bg-opacity-25 text-yellow-800
                                    @elseif ($medicine->stock_status === 'Out of Stock') bg-danger bg-opacity-25 text-danger
                                    @endif">
                                    {{ $medicine->stock_status }}
                                </span>
                            </td>
                            <td>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if ($medicine->expiry_status === 'Valid') bg-success bg-opacity-25 text-success
                                    @elseif ($medicine->expiry_status === 'Expiring Soon') bg-warning bg-opacity-25 text-yellow-800
                                    @elseif ($medicine->expiry_status === 'Expired') bg-danger bg-opacity-25 text-danger
                                    @endif">
                                    {{ $medicine->expiry_status }}
                                </span>
                            </td>
                            <td>{{ $medicine->expiry_date}}</td>
                            <!-- <td>{{ $medicine->created_at->format('M d, Y') }}</td> -->
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    @if($showArchived)
                                        <button class="btn btn-info text-white" wire:click="restoreMedicine({{ $medicine->medicine_id }})">
                                            <i class="fa-solid fa-rotate-left me-1"></i>Restore
                                        </button>
                                    @else
                                        <button class="btn bg-primary text-white" wire:click="editMedicineData({{ $medicine->medicine_id }})">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                        </button>
                                        <button class="btn p-0" wire:click="confirmMedicineArchive({{ $medicine->medicine_id }})">
                                             <i class="fa-solid fa-trash text-danger fs-3"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="fa-solid fa-inbox fs-1 text-muted mb-3 d-block"></i>
                                <p class="text-muted">No medicine found</p>
                            </td>
                        </tr>
                        @endforelse
                        <!-- Dynamic td -->
                    </tbody>
                </table>
                {{ $medicines->links() }}
            </div>
        </div>
    </main>

    <!-- Add Medicine Modal -->
    <div wire:ignore.self class="modal fade" id="addMedicineModal" tabindex="-1" aria-labelledby="addMedicineModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addMedicineModalLabel">
                        <i class="fa-solid fa-capsules me-2"></i> Add New Medicine
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="" id="addMedicineForm" method="POST" wire:submit.prevent="storeMedicineData">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Medicine Name</label>
                                <input type="text" class="form-control" name="medicine_name" wire:model="medicine_name">
                                @error('medicine_name')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="category_id" wire:model="category_id">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dosage</label>
                                <input type="text" class="form-control" name="dosage" wire:model="dosage" placeholder="e.g., 500mg, 10ml">
                                @error('dosage')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" name="stock" wire:model="stock">
                                @error('stock')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" wire:model="expiry_date">
                                @error('expiry_date')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-2">
                            <h6 class="mb-3"><i class="fa-solid fa-user-group me-2"></i>Age Range (Optional)</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Minimum Age</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="min_age_value" wire:model.live="min_age_value" min="0" placeholder="0">
                                        <select class="form-select" style="max-width: 120px;" wire:model.live="min_age_unit">
                                            <option value="years">Years</option>
                                            <option value="months">Months</option>
                                        </select>
                                    </div>
                                    <small class="text-muted">Leave empty if no minimum age</small>
                                    @error('min_age_value')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Maximum Age</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="max_age_value" wire:model.live="max_age_value" min="0" placeholder="18">
                                        <select class="form-select" style="max-width: 120px;" wire:model.live="max_age_unit">
                                            <option value="years">Years</option>
                                            <option value="months">Months</option>
                                        </select>
                                    </div>
                                    <small class="text-muted">Leave empty for no upper limit</small>
                                    @error('max_age_value')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="alert alert-info py-2">
                                <small><strong>Quick Guide:</strong> Use months for infants/toddlers (0-24 months), years for older children and adults (2+ years)</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Medicine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Medicine Modal -->
    <div wire:ignore.self class="modal fade" id="editMedicineModal" tabindex="-1" aria-labelledby="EditMedicineModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="EditMedicineModalLabel">
                        <i class="fa-solid fa-capsules me-2"></i> Edit Medicine
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="" id="editMedicineForm" method="POST" wire:submit.prevent="updateMedicineData">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Medicine Name</label>
                                <input type="text" class="form-control" name="medicine_name" wire:model="medicine_name">
                                @error('medicine_name')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" id="category_id" wire:model="category_id">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}">{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dosage</label>
                                <input type="text" class="form-control" name="dosage" wire:model="dosage">
                                @error('dosage')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" name="stock" wire:model="stock">
                                @error('stock')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" name="expiry_date" wire:model="expiry_date">
                                @error('expiry_date')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-2">
                            <h6 class="mb-3"><i class="fa-solid fa-user-group me-2"></i>Age Range (Optional)</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Minimum Age</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="min_age_value" wire:model.live="min_age_value" min="0" placeholder="0">
                                        <select class="form-select" style="max-width: 120px;" wire:model.live="min_age_unit">
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                    <small class="text-muted">Leave empty if no minimum age</small>
                                    @error('min_age_value')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Maximum Age</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="max_age_value" wire:model.live="max_age_value" min="0" placeholder="18">
                                        <select class="form-select" style="max-width: 120px;" wire:model.live="max_age_unit">
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                    <small class="text-muted">Leave empty for no upper limit</small>
                                    @error('max_age_value')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="alert alert-info py-2">
                                <small><strong>Quick Guide:</strong> Use months for infants/toddlers (0-24 months), years for older children and adults (2+ years)</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Medicine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>