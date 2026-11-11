<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentAccountService
{
    public function createStudentWithParent(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $studentRole = Role::where('slug', 'student')->firstOrFail();
            $parentRole = Role::where('slug', 'parent')->firstOrFail();

            $student = User::create([
                'name' => $data['student']['name'],
                'username' => $data['student']['username'],
                'password' => $data['student']['password'],
            ]);

            $student->roles()->attach($studentRole->id);

            $parentPhone = $data['parent']['phone'];

            $parent = User::query()
                ->where('phone', $parentPhone)
                ->lockForUpdate()
                ->first();

            if ($parent) {
                if (!$parent->hasRole('parent')) {
                    throw ValidationException::withMessages([
                        'parent.phone' => 'رقم الهاتف مرتبط بحساب ليس ولي أمر.',
                    ]);
                }
            } else {
                $existingAccount = User::query()
                    ->where('email', $parentPhone)
                    ->orWhere('username', $parentPhone)
                    ->exists();

                if ($existingAccount) {
                    throw ValidationException::withMessages([
                        'parent.phone' => 'رقم الهاتف مستخدم بالفعل في حساب آخر.',
                    ]);
                }

                $parent = User::create([
                    'name' => $data['parent']['name'],
                    'phone' => $parentPhone,
                    'password' => $data['parent']['password'],
                ]);

                $parent->roles()->attach($parentRole->id);
            }

            $parent->children()->syncWithoutDetaching([
                $student->id => [
                    'relationship' => $data['parent']['relationship'] ?? null,
                    'is_primary_contact' => true,
                ],
            ]);

            return [
                'student' => $student->load('roles:id,name,slug'),
                'parent' => $parent->load('roles:id,name,slug'),
            ];
        });
    }
}
