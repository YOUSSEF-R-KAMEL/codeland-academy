<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'exists:roles,slug'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()
            ->with('roles:id,name,slug')
            ->latest();

        if (($filters['role'] ?? null) === 'student') {
            $query->with([
                'enrollments' => fn($builder) => $builder
                    ->with('group:id,course_id,name,code')
                    ->orderByDesc('enrolled_at'),
            ]);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($builder) use ($filters) {
                $builder->where('slug', $filters['role']);
            });
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        $users = $query->paginate($filters['per_page'] ?? 15);

        return $this->success(
            'تم جلب المستخدمين بنجاح.',
            [
                'users' => $users->getCollection()
                    ->map(function (User $user) use ($filters) {
                        $data = $this->formatUser($user);

                        if (($filters['role'] ?? null) === 'student') {
                            $enrollment = $user->enrollments->first(
                                fn($item) => in_array(
                                    $item->status,
                                    ['active', 'pending'],
                                    true
                                )
                            );

                            $data['enrollment'] = $enrollment ? [
                                'id' => $enrollment->id,
                                'status' => $enrollment->status,
                                'enrolled_at' => $enrollment->enrolled_at?->toDateString(),
                                'group' => $enrollment->group ? [
                                    'id' => $enrollment->group->id,
                                    'course_id' => $enrollment->group->course_id,
                                    'name' => $enrollment->group->name,
                                    'code' => $enrollment->group->code,
                                ] : null,
                            ] : null;
                        }

                        return $data;
                    })
                    ->values(),

                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $role = Role::where('slug', $data['role'])->firstOrFail();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            $user->roles()->attach($role->id);

            return $user->load('roles:id,name,slug');
        });

        return $this->success(
            'تم إنشاء الحساب بنجاح.',
            ['user' => $this->formatUser($user)],
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $user = User::query()
            ->with('roles:id,name,slug')
            ->findOrFail($id);

        return $this->success(
            'تم جلب بيانات المستخدم بنجاح.',
            ['user' => $this->formatUser($user)]
        );
    }

    public function update(
        UpdateUserRequest $request,
        int $id
    ): JsonResponse {
        $user = User::query()
            ->with('roles')
            ->findOrFail($id);

        if ($user->hasRole('admin')) {
            return $this->error(
                'لا يمكن تعديل حساب Admin من خلال هذه العملية.',
                [],
                403
            );
        }

        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->update($data);
        });

        $user->refresh()->load('roles:id,name,slug');

        return $this->success(
            'تم تحديث بيانات المستخدم بنجاح.',
            ['user' => $this->formatUser($user)]
        );
    }

    public function updateStatus(
        Request $request,
        int $id
    ): JsonResponse {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = filter_var(
            $data['is_active'],
            FILTER_VALIDATE_BOOLEAN
        );

        $user = User::query()
            ->with('roles')
            ->findOrFail($id);

        if ($user->id === $request->user()->id) {
            return $this->error(
                'لا يمكنك تغيير حالة حسابك الحالي.',
                [],
                403
            );
        }

        if ($user->hasRole('admin')) {
            return $this->error(
                'لا يمكن تغيير حالة حساب Admin من خلال هذه العملية.',
                [],
                403
            );
        }

        $user->update([
            'is_active' => $isActive,
        ]);

        $user->refresh()->load('roles:id,name,slug');

        return $this->success(
            $isActive
            ? 'تم تفعيل الحساب بنجاح.'
            : 'تم إيقاف الحساب بنجاح.',
            [
                'user' => $this->formatUser($user),
            ]
        );
    }

    private function formatUser(User $user): array
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
