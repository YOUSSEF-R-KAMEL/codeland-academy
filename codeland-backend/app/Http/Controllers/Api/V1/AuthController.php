<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $identifier = $data['identifier'];

        $user = User::query()
            ->with('roles:id,name,slug')
            ->where(function ($query) use ($identifier) {
                $query->where(function ($q) use ($identifier) {
                    $q->where('email', $identifier)
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->whereIn('slug', [
                                'admin',
                                'instructor',
                            ]);
                        });
                })->orWhere(function ($q) use ($identifier) {
                    $q->where('phone', $identifier)
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('slug', 'parent');
                        });
                })->orWhere(function ($q) use ($identifier) {
                    $q->where('username', $identifier)
                        ->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('slug', 'student');
                        });
                });
            })
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->error(
                'بيانات تسجيل الدخول غير صحيحة.',
                [],
                401
            );
        }

        if (!$user->is_active) {
            return $this->error(
                'هذا الحساب موقوف حاليًا. يرجى التواصل مع إدارة الأكاديمية.',
                [],
                403
            );
        }

        $user->tokens()->delete();

        $token = $user->createToken(
            'abqareno-web',
            ['*']
        )->plainTextToken;
        return $this->success(
            'تم تسجيل الدخول بنجاح.',
            [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->userData($user),
            ]
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success('تم تسجيل الخروج بنجاح.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles:id,name,slug');

        return $this->success(
            'تم جلب بيانات المستخدم بنجاح.',
            [
                'user' => $this->userData($user),
            ]
        );
    }


    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'roles' => $user->roles->map(fn($role) => [
                'name' => $role->name,
                'slug' => $role->slug,
            ])->values(),
        ];
    }

}
