<?php

namespace App\Actions\Fortify;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $registrable = array_map(fn (RoleName $role) => $role->value, RoleName::registrable());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'role' => ['required', Rule::in($registrable)],
            'agency_name' => ['required_if:role,agency', 'nullable', 'string', 'max:255'],
            'agency_license_no' => ['nullable', 'string', 'max:255'],
            'agency_address' => ['nullable', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? null,
            'agency_name' => $input['agency_name'] ?? null,
            'agency_license_no' => $input['agency_license_no'] ?? null,
            'agency_address' => $input['agency_address'] ?? null,
            'password' => Hash::make($input['password']),
        ]);

        $user->assignRole($input['role']);

        return $user;
    }
}
