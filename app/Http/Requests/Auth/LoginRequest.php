<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'     => ['required', 'string'],
            'login_as'  => ['required'],
            'password'  => ['required', 'string'],
        ];
    }

    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        $redirectTo = null;
        if (is_numeric($this->email)) {
            $userName = 'mobile';
            $this->merge(['mobile' => $this->email]);
        } elseif (filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $userName = 'email';
        } else {
            $userName = 'userId';
            $this->merge(['userId' => $this->email]);
        }

        if (auth($this->login_as)->attempt($this->only([$userName, 'password']), $this->get('remember'))) {
            switch ($this->login_as) {
                default:
                    $redirectTo = '/dashboard';
            }
        }

        if ($redirectTo != null) {
            if (auth($this->login_as)->user()->status == 0) {
                auth($this->login_as)->logout();
                throw ValidationException::withMessages([
                    $this->username() => "Your Account is blocked by Admin.",
                ]);
            }

            RateLimiter::clear($this->throttleKey());
            return $redirectTo;
        }

        RateLimiter::hit($this->throttleKey());
        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);

        return false;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')) . '|' . $this->ip());
    }
}
