<?php

namespace App\Http\Requests;

use App\Helper\Helper;
use App\Rules\CheckUnique;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user_id    = $this->route('slug');
        $table      = $this->get('table');
        return [
            'name'                  => ['required', 'string',  'max:50'],
            'status'                => ['required', 'integer'],
            'email'                 => ['required', new CheckUnique($table, $user_id, 'slug')],
            'mobile'                => ['required', 'digits:10', new CheckUnique($table, $user_id, 'slug'), 'regex:' . config('constant.phoneRegExp')],
            'password'              => ['nullable', Rule::requiredIf(!$user_id), 'string', 'min:8', 'confirmed'],
            'image'                 => ['image', 'mimes:jpg,png,jpeg', 'max:2048'],
            'role_id'               => ['integer', 'nullable', Rule::requiredIf($table == 'users'), Rule::exists('roles', 'id')],
        ];
    }

    public function filter($user = null): array
    {
        $data = $this->only(['name', 'status', 'email', 'mobile', 'role_id']);

        if ($this->filled('password')) {
            $data['password']  = Hash::make($this->password);
        }

        if ($this->hasFile('image')) {
            if ($user && $user->image)  Helper::deleteFile($user->image);
            $data['image']     = Helper::saveFile($this->file('image'));
        }

        return $data;
    }
}
