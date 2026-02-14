$('.dropdown-search').select2();

document.addEventListener('livewire:init', function(){
    function loadJquery(){  
        $('.user-search').select2({
            placeholder: 'Search for a user/patient',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#walkInModal')
        });
        
        // Update Livewire when user is selected
        $('.user-search').on('change', function(e) {
            let selectedValue = $(this).val();
            Livewire.find($(this).closest('[wire\\:id]').attr('wire:id')).set('walkInUserId', selectedValue);
        });
    }
    
    loadJquery();

    Livewire.hook("morph", () => {
        loadJquery();
    });

    // Stock validation on quantity input
    Livewire.on('quantity-validation', () => {
        validateQuantity();
    });
});

// Quantity validation function
function validateQuantity() {
    const quantityInput = document.querySelector('input[wire\\:model="walkInQuantity"]');
    const medicineSelect = document.querySelector('select[wire\\:model="walkInMedicineId"]');
    
    if (!quantityInput || !medicineSelect) return;

    quantityInput.addEventListener('blur', function() {
        const selectedMedicineId = medicineSelect.value;
        const quantity = parseInt(this.value) || 0;
        
        if (!selectedMedicineId || quantity <= 0) {
            this.classList.remove('is-invalid', 'border-danger');
            removeStockError();
            return;
        }

        // Get stock from the selected option text
        const selectedOption = medicineSelect.options[medicineSelect.selectedIndex];
        const stockMatch = selectedOption.text.match(/Stock:\s*(\d+)/);
        
        if (stockMatch) {
            const availableStock = parseInt(stockMatch[1]);
            
            if (quantity > availableStock) {
                this.classList.add('is-invalid', 'border-danger');
                showStockError(availableStock);
            } else {
                this.classList.remove('is-invalid', 'border-danger');
                removeStockError();
            }
        }
    });

    // Also validate on input change
    quantityInput.addEventListener('input', function() {
        const selectedMedicineId = medicineSelect.value;
        const quantity = parseInt(this.value) || 0;
        
        if (!selectedMedicineId || quantity <= 0) {
            this.classList.remove('is-invalid', 'border-danger');
            removeStockError();
            return;
        }

        const selectedOption = medicineSelect.options[medicineSelect.selectedIndex];
        const stockMatch = selectedOption.text.match(/Stock:\s*(\d+)/);
        
        if (stockMatch) {
            const availableStock = parseInt(stockMatch[1]);
            
            if (quantity > availableStock) {
                this.classList.add('is-invalid', 'border-danger');
                showStockError(availableStock);
            } else {
                this.classList.remove('is-invalid', 'border-danger');
                removeStockError();
            }
        }
    });

    // Clear validation when medicine changes
    medicineSelect.addEventListener('change', function() {
        quantityInput.classList.remove('is-invalid', 'border-danger');
        removeStockError();
        
        // Re-validate if quantity already entered
        if (quantityInput.value) {
            quantityInput.dispatchEvent(new Event('blur'));
        }
    });
}

function showStockError(availableStock) {
    // Remove existing error first
    removeStockError();
    
    const quantityInput = document.querySelector('input[wire\\:model="walkInQuantity"]');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.id = 'stock-error-message';
    errorDiv.innerHTML = `<i class="fa-solid fa-exclamation-circle me-1"></i>Quantity exceeds available stock (${availableStock} available)`;
    
    quantityInput.parentNode.appendChild(errorDiv);
}

function removeStockError() {
    const existingError = document.getElementById('stock-error-message');
    if (existingError) {
        existingError.remove();
    }
}

// Initialize validation when modal opens
document.getElementById('walkInModal')?.addEventListener('shown.bs.modal', function() {
    validateQuantity();
});

// Clear validation when modal closes
document.getElementById('walkInModal')?.addEventListener('hidden.bs.modal', function() {
    const quantityInput = document.querySelector('input[wire\\:model="walkInQuantity"]');
    if (quantityInput) {
        quantityInput.classList.remove('is-invalid', 'border-danger');
    }
    removeStockError();
});