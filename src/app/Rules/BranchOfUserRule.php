<?php

namespace App\Rules;

use App\Models\Branch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BranchOfUserRule implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Branch::where('id', $value)
            ->where('facility_id', auth()->user()->facility_id)
            ->exists();

        if (!$exists) {
            $fail(__('validation.exists'));
        }
    }
}
