<?php

namespace App\Livewire;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class CategoriesTable extends Component
{
    use WithPagination;

    public $category_name, $edit_id;
    public $sortField = null;
    public $sortDirection = null;
    public $search;
    public $perPage = 10;
    public $archiveCategoryId;
    public $showArchived = false;

    /**
     * Normalize the category name to its base form for comparison
     */
    private function normalizeForComparison($name)
    {
        $name = strtolower(trim($name));

        // Remove common plural suffixes
        $singularized = $name;

        // Handle 'ies' -> 'y' (e.g., categories -> category)
        if (preg_match('/ies$/i', $name)) {
            $singularized = preg_replace('/ies$/i', 'y', $name);
        }
        // Handle 'es' -> '' (e.g., boxes -> box)
        elseif (preg_match('/([sx]|ch|sh)es$/i', $name)) {
            $singularized = preg_replace('/es$/i', '', $name);
        }
        // Handle 's' -> '' (e.g., tablets -> tablet)
        elseif (preg_match('/s$/i', $name) && !preg_match('/ss$/i', $name)) {
            $singularized = preg_replace('/s$/i', '', $name);
        }

        return $singularized;
    }

    /**
     * Check if category name (singular or plural) already exists
     */
    private function categoryExists($name, $excludeId = null)
    {
        $normalized = $this->normalizeForComparison($name);

        $query = Category::whereRaw('LOWER(category_name) = ?', [strtolower($name)])
            ->orWhereRaw('LOWER(category_name) = ?', [$normalized])
            ->orWhereRaw('LOWER(category_name) = ?', [$normalized . 's'])
            ->orWhereRaw('LOWER(category_name) = ?', [preg_replace('/y$/i', 'ies', $normalized)]);

        if ($excludeId) {
            $query->where('category_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Custom validation rule for category name
     */
    protected function validateCategoryName($excludeId = null)
    {
        $this->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($excludeId) {
                    if ($this->categoryExists($value, $excludeId)) {
                        $normalized = $this->normalizeForComparison($value);
                        $fail('The category name already exists in singular or plural form.');
                    }
                },
            ]
        ]);
    }

    // Create Category
    public function storeCategoryData()
    {
        $this->validateCategoryName();

        Category::create([
            'category_name' => trim($this->category_name)
        ]);

        $this->reset(['category_name']);
        $this->dispatch('category-added');
    }

    // EDIT (Load data into modal)
    public function editCategoryData($id)
    {
        $this->dispatch('show-edit-category-modal');
        $category = Category::where('category_id', $id)->select('category_name')->first();

        $this->edit_id = $id;
        $this->category_name = $category->category_name;
    }

    // UPDATE
    public function updateCategoryData()
    {
        $this->validateCategoryName($this->edit_id);

        Category::where('category_id', $this->edit_id)->update([
            'category_name' => trim($this->category_name)
        ]);

        $this->reset(['category_name', 'edit_id']);
        $this->dispatch('hide-edit-category-modal');
        $this->dispatch('category-updated');
    }

    public function sortBy($field)
    {
        if($this->sortField === $field){
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    // Archive confirmation
    public function confirmArchive($id)
    {
        $this->archiveCategoryId = $id;
        $this->dispatch('show-archive-confirmation');
    }

    // Archive the category (soft delete)
    public function archiveCategory()
    {
        $category = Category::findOrFail($this->archiveCategoryId);
        $category->delete();
        $this->dispatch('archive-success');
        $this->resetPage();
    }

    // Restore archived category
    public function restoreCategory($id)
    {
        Category::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('restore-success');
        $this->resetPage();
    }

    // Toggle archived view
    public function toggleArchived()
    {
        $this->showArchived = !$this->showArchived;
        $this->resetPage();
    }

    public function render()
    {
        $query = Category::search($this->search);

        if ($this->showArchived) {
            $query = $query->onlyTrashed();
        }

        $categories = $query->when($this->sortField, function($query){
            $query->orderBy($this->sortField, $this->sortDirection);
        })->paginate($this->perPage);

        return view('livewire.category-section.category-table',[
            'categories' => $categories
        ])->layout('livewire.layouts.base');
    }
}