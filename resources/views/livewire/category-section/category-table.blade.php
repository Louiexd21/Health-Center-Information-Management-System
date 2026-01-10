<div>
    <main class="category d-flex flex-column container-fluid bg-light ">
        <div class="m-md-3 m-1 p-3 shadow min-vh-100">
            <h2 class="mb-5 fs-1 text-center">Medicine Inventory</h2>
            <div class="medicine-inventory d-flex gap-3 align-items-none align-items-sm-end flex-wrap flex-column flex-sm-row">
                <div class="flex-fill">
                    <label for="" class="form-label">Show</label>
                    <select type="text" class="form-select w-[50%] sm:w-[75%] md:w-[50%]" name="show" wire:model.live="perPage">
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
                    <input type="search" class="form-control" wire:model.live.debounce.300="search" placeholder="Search medicine or category...">
                </div>
                <button class="btn btn-secondary" wire:click="toggleArchived">
                    <i class="fa-solid fa-{{ $showArchived ? 'list' : 'archive' }} pe-1"></i>
                    {{ $showArchived ? 'Show Active' : 'Show Archived' }}
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="fa-solid fa-plus pe-1"></i>Add Category</button>
            </div>
            <div class="table-responsive mt-5">
                <table class="table table-hover" id="categoryTable">
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
                    @endphp
                    <thead class="table-header">
                        <tr>
                            <!-- <th scope="col" class="text-center" wire:click="sortBy('category_id')"><button class="text-nowrap">No. {!! sortIcon($sortField, 'category_id', $sortDirection) !!} </button></th> -->
                            <th scope="col" class="text-center" wire:click="sortBy('category_name')"><button class="text-nowrap">Category Name {!! sortIcon($sortField, 'category_name', $sortDirection) !!}</button></th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                    @foreach($categories as $category)
                        <tr>
                            <!-- <td>{{ $category->category_id  }}</td> -->
                            <td>{{ $category->category_name }}</td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    @if($showArchived)
                                        <button class="btn btn-danger text-white" wire:click="restoreCategory({{ $category->category_id }})">
                                            <i class="fa-solid fa-rotate-left me-1"></i>Restore
                                        </button>
                                    @else
                                        <button class="btn bg-primary text-white" wire:click="editCategoryData({{ $category->category_id }})">
                                            <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                        </button>
                                        <button class="btn p-0" wire:click="confirmArchive({{ $category->category_id }})">
                                            <i class="fa-solid fa-trash text-danger fs-3"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                        <!-- dynamic td -->
                    </tbody>
                </table>
                <div class="my-2">
                {{ $categories->links() }}
                </div>
            </div>
        </div>
    </main>
        <!-- Add Category Modal -->
    <div wire:ignore.self class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addMedicineModalLabel">
                        <i class="fa-solid fa-capsules me-2"></i> Add New Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="addCategoryForm" wire:submit.prevent="storeCategoryData">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" wire:model.defer="category_name">
                            @error('category_name')
                                <span class="text-danger" style="font-size: 11.5px">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div wire:ignore.self class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addMedicineModalLabel">
                        <i class="fa-solid fa-capsules me-2"></i> Edit Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editCategoryForm" wire:submit.prevent="updateCategoryData">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" wire:model.defer="category_name">
                            @error('category_name')
                                <span class="text-danger" style="font-size: 11.5px">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"  >Cancel</button>
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
