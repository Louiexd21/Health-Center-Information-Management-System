<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Medicine;
use App\Models\Category;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class Medicines extends Component
{
    use WithPagination;

    public $medicine_name, $category_id, $dosage, $stock, $stock_status, $expiry_status, $expiry_date, $edit_id;
    public $min_age_value, $min_age_unit = 'months';
    public $max_age_value, $max_age_unit = 'months';
    public $min_age_months, $max_age_months;
    public $sortField = null;
    public $sortDirection = null;
    public $search = '';
    public $perPage = 10;
    public $ageFilter = '';
    public $categoryFilter = '';
    public $archiveMedicineId;
    public $showArchived = false;

    protected $rules = [
        'medicine_name' => 'required|string|max:255',
        'category_id'   => 'required|integer|exists:categories,category_id',
        'dosage'        => 'required|string',
        'stock'         => 'required|numeric|min:0',
        'expiry_date'   => 'required|date',
        'min_age_value' => 'nullable|integer|min:0',
        'max_age_value' => 'nullable|integer|min:0'
    ];

    protected $messages = [
        'category_id.required' => 'Please select a category.',
    ];

    /**
     * Check if medicine name (singular or plural) already exists with the same dosage
     */
    private function medicineExists($name, $dosage, $excludeId = null)
    {
        $name = trim($name);
        $singular = Str::singular($name);
        $plural = Str::plural($name);

        $query = Medicine::where('dosage', $dosage)
            ->where(function($q) use ($name, $singular, $plural) {
                $q->whereRaw('LOWER(medicine_name) = ?', [strtolower($name)])
                  ->orWhereRaw('LOWER(medicine_name) = ?', [strtolower($singular)])
                  ->orWhereRaw('LOWER(medicine_name) = ?', [strtolower($plural)]);
            });

        if ($excludeId) {
            $query->where('medicine_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Custom validation for medicine name + dosage combination
     */
    private function validateMedicineUniqueness($excludeId = null)
    {
        if ($this->medicineExists($this->medicine_name, $this->dosage, $excludeId)) {
            $singular = Str::singular($this->medicine_name);
            $plural = Str::plural($this->medicine_name);

            // Check which form exists to give a helpful error message
            $existingForm = '';
            if (Medicine::whereRaw('LOWER(medicine_name) = ?', [strtolower($singular)])
                        ->where('dosage', $this->dosage)
                        ->when($excludeId, fn($q) => $q->where('medicine_id', '!=', $excludeId))
                        ->exists()) {
                $existingForm = $singular;
            } elseif (Medicine::whereRaw('LOWER(medicine_name) = ?', [strtolower($plural)])
                              ->where('dosage', $this->dosage)
                              ->when($excludeId, fn($q) => $q->where('medicine_id', '!=', $excludeId))
                              ->exists()) {
                $existingForm = $plural;
            }

            $message = $existingForm
                ? "This medicine already exists as '{$existingForm}' with dosage {$this->dosage}"
                : "This medicine with the same dosage already exists in singular or plural form";

            $this->addError('medicine_name', $message);
            $this->addError('dosage', 'Duplicate medicine + dosage detected');
            return false;
        }

        return true;
    }

    private function convertToMonths($value, $unit)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        return $unit === 'years' ? $value * 12 : $value;
    }

    private function convertFromMonths($months)
    {
        if (is_null($months)) {
            return ['value' => null, 'unit' => 'months'];
        }

        if ($months < 24) {
            return ['value' => $months, 'unit' => 'months'];
        }

        return ['value' => $months / 12, 'unit' => 'years'];
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['min_age_value', 'min_age_unit', 'max_age_value', 'max_age_unit'])) {
            $this->validateAgeRange();
        }
    }

    private function validateAgeRange()
    {
        $minMonths = $this->convertToMonths($this->min_age_value, $this->min_age_unit);
        $maxMonths = $this->convertToMonths($this->max_age_value, $this->max_age_unit);

        if (!is_null($minMonths) && !is_null($maxMonths) && $maxMonths < $minMonths) {
            $this->addError('max_age_value', 'Maximum age must be greater than or equal to minimum age.');
            return false;
        }

        $this->resetErrorBag(['max_age_value']);
        return true;
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

    private function determineExpiryStatus($expiry_date)
    {
        $daysUntilExpry = now()->diffInDays($expiry_date, false);
        if ($daysUntilExpry < 0) {
            return 'Expired';
        }
        if ($daysUntilExpry <= 30) {
            return 'Expiring Soon';
        }
        return 'Valid';
    }

    public function storeMedicineData()
    {
        $this->validate();

        if (!$this->validateAgeRange()) {
            return;
        }

        // Check for duplicate medicine name (singular/plural) + dosage
        if (!$this->validateMedicineUniqueness()) {
            return;
        }

        $min_age_months = $this->convertToMonths($this->min_age_value, $this->min_age_unit);
        $max_age_months = $this->convertToMonths($this->max_age_value, $this->max_age_unit);
        $stockStatus = $this->determineStockStatus($this->stock);
        $expiryStatus = $this->determineExpiryStatus($this->expiry_date);

        Medicine::create([
            'medicine_name' => trim($this->medicine_name),
            'category_id'   => $this->category_id,
            'dosage'        => $this->dosage,
            'stock'         => $this->stock,
            'expiry_date'   => $this->expiry_date,
            'stock_status'  => $stockStatus,
            'expiry_status' => $expiryStatus,
            'min_age_months' => $min_age_months,
            'max_age_months' => $max_age_months
        ]);

        $this->dispatch('medicine-addedModal');
        $this->reset();
    }

    public function editMedicineData($id)
    {
        $medicine = Medicine::findOrFail($id);
        $this->edit_id = $id;
        $this->medicine_name = $medicine->medicine_name;
        $this->category_id = $medicine->category_id;
        $this->dosage = $medicine->dosage;
        $this->stock = $medicine->stock;
        $this->expiry_date = $medicine->expiry_date;

        $minAge = $this->convertFromMonths($medicine->min_age_months);
        $maxAge = $this->convertFromMonths($medicine->max_age_months);

        $this->min_age_value = $minAge['value'];
        $this->min_age_unit = $minAge['unit'];
        $this->max_age_value = $maxAge['value'];
        $this->max_age_unit = $maxAge['unit'];

        $this->dispatch('show-editMedicine-modal');
        return $this->skipRender();
    }

    public function updateMedicineData()
    {
        $this->validate();

        if (!$this->validateAgeRange()) {
            return;
        }

        // Check for duplicate medicine name (singular/plural) + dosage, excluding current record
        if (!$this->validateMedicineUniqueness($this->edit_id)) {
            return;
        }

        $stockStatus = $this->determineStockStatus($this->stock);
        $expiryStatus = $this->determineExpiryStatus($this->expiry_date);

        $min_age_months = $this->convertToMonths($this->min_age_value, $this->min_age_unit);
        $max_age_months = $this->convertToMonths($this->max_age_value, $this->max_age_unit);

        Medicine::where('medicine_id', $this->edit_id)->update([
            'medicine_name'  => trim($this->medicine_name),
            'category_id'    => $this->category_id,
            'dosage'         => $this->dosage,
            'stock'          => $this->stock,
            'expiry_date'    => $this->expiry_date,
            'stock_status'   => $stockStatus,
            'expiry_status'  => $expiryStatus,
            'min_age_months' => $min_age_months,
            'max_age_months' => $max_age_months
        ]);

        $this->resetFields();
        $this->dispatch('close-editMedicine-modal');
    }

    public function resetFields()
    {
        $this->medicine_name = '';
        $this->category_id = '';
        $this->dosage = '';
        $this->stock = '';
        $this->expiry_date = '';
        $this->min_age_value = '';
        $this->min_age_unit = 'months';
        $this->max_age_value = '';
        $this->max_age_unit = 'months';
        $this->edit_id = '';
    }

    public function confirmMedicineArchive($id)
    {
        $this->archiveMedicineId = $id;
        $this->dispatch('show-medicine-archive-confirmation');
    }

    public function archiveMedicine()
    {
        $medicine = Medicine::findOrFail($this->archiveMedicineId);
        $medicine->delete();
        $this->dispatch('medicine-archive-success');
        $this->resetPage();
    }

    public function restoreMedicine($id)
    {
        Medicine::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('medicine-restore-success');
        $this->resetPage();
    }

    public function toggleArchived()
    {
        $this->showArchived = !$this->showArchived;
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    private function getAgeRangeFilter($range)
    {
        return match ($range) {
            '0-9months' => ['min' => 0, 'max' => 9],
            '10-24months' => ['min' => 10, 'max' => 24],
            '2-5years' => ['min' => 24, 'max' => 60],
            '6-12years' => ['min' => 72, 'max' => 144],
            '13-17years' => ['min' => 156, 'max' => 204],
            'adult' => ['min' => 216, 'max' => null],
            default => null,
        };
    }

    public function render()
    {
        $query = Medicine::with('category')->search($this->search);

        if ($this->showArchived) {
            $query = $query->onlyTrashed();
        }

        $medicines = $query
            ->when($this->categoryFilter, function ($q) {
                $q->where('category_id', $this->categoryFilter);
            })
            ->when($this->ageFilter, function ($query) {
                $range = $this->getAgeRangeFilter($this->ageFilter);

                if ($range) {
                    $query->where(function ($q) use ($range) {
                        if (!is_null($range['max'])) {
                            $q->where('min_age_months', '<=', $range['max']);
                        }

                        $q->where(function ($sub) use ($range) {
                            $sub->whereNull('max_age_months')
                                ->orWhere('max_age_months', '>=', $range['min']);
                        });
                    });
                }
            })
            ->when($this->sortField === 'category_name', function ($query) {
                $query->orderBy(
                    Category::select('category_name')->whereColumn('categories.category_id', 'medicines.category_id'),
                    $this->sortDirection
                );
            })
            ->when($this->sortField && $this->sortField !== 'category_name', function ($query) {
                $query->orderBy($this->sortField, $this->sortDirection);
            })
            ->paginate(perPage: $this->perPage);

        return view('livewire.medicines', [
            'categories' => Category::orderBy('category_name')->get(),
            'medicines' => $medicines
        ])->layout('livewire.layouts.base');
    }
}