<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Helper\Helper;
use App\Rules\CheckUnique;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $table = Helper::getTableFromURL($this);
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255',  new CheckUnique($table, auth($this->route)->id())],
            'mobile'    => ['required', 'digits:10', new CheckUnique($table, auth($this->route)->id()), 'regex:' . config('constant.phoneRegExp')],
            'image'     => ['image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'state_id'  => ['nullable', 'integer'],
            'city_id'   => ['nullable', 'integer'],
        ];
    }
}
