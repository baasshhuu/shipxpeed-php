<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CheckUnique implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

    protected $table = 'users';
    protected $ignoreId =  null;
    protected $ignoreColumn = 'id';

    public function __construct($table = 'users',  $ignoreId =  null, $ignoreColumn =  'id')
    {
        $this->table            = $table;
        $this->ignoreId         = $ignoreId;
        $this->ignoreColumn     = $ignoreColumn;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Check Only In Respective table
        $user = DB::table($this->table)->where($attribute, $value);
        if ($this->ignoreId) $user = $user->whereNot($this->ignoreColumn, $this->ignoreId);
        $count = $user->count();
        return $count == 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute already in use.';
    }
}
