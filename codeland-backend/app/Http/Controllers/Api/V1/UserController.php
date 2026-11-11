<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreStudentWithParentRequest;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\StudentAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\V1\AttachParentRequest;
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
                    ->map(fn(User $user) => $this->formatUser($user))
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
                'email' => $data['email'] ?? null,
                'username' => $data['username'] ?? null,
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

    public function storeStudentWithParent(
        StoreStudentWithParentRequest $request,
        StudentAccountService $service
    ): JsonResponse {
        $accounts = $service->createStudentWithParent(
            $request->validated()
        );

        return $this->success(
            'تم إنشاء حساب الطالب وربطه بولي الأمر بنجاح.',
            [
                'student' => $this->formatUser($accounts['student']),
                'parent' => $this->formatUser($accounts['parent']),
            ],
            201
        );
    }


    public function show(int $id): JsonResponse
    {
        $user = User::query()
            ->with([
                'roles:id,name,slug',
                'parents:id,name,phone',
                'children:id,name,username',
            ])
            ->findOrFail($id);

        $userData = $this->formatUser($user);

        if ($user->hasRole('student')) {
            $userData['parents'] = $user->parents->map(fn($parent) => [
                'id' => $parent->id,
                'name' => $parent->name,
                'phone' => $parent->phone,
                'relationship' => $parent->pivot->relationship,
                'is_primary_contact' => $parent->pivot->is_primary_contact,
            ])->values();
        }

        if ($user->hasRole('parent')) {
            $userData['children'] = $user->children->map(fn($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'username' => $child->username,
                'relationship' => $child->pivot->relationship,
                'is_primary_contact' => $child->pivot->is_primary_contact,
            ])->values();
        }

        return $this->success(
            'تم جلب بيانات المستخدم بنجاح.',
            ['user' => $userData]
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

    public function attachParent(
        AttachParentRequest $request,
        int $studentId
    ): JsonResponse {
        $data = $request->validated();

        $parent = User::query()
            ->with('roles')
            ->findOrFail($data['parent_id']);

        if (!$parent->hasRole('parent')) {
            return $this->error(
                'الحساب المحدد ليس حساب ولي أمر.',
                [],
                422
            );
        }

        DB::transaction(function () use ($studentId, $parent, $data) {
            $student = User::query()
                ->with('roles')
                ->lockForUpdate()
                ->findOrFail($studentId);

            if (!$student->hasRole('student')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'student_id' => 'الحساب المحدد ليس حساب طالب.',
                ]);
            }

            if ($data['is_primary_contact'] ?? false) {
                DB::table('parent_student')
                    ->where('student_id', $student->id)
                    ->update(['is_primary_contact' => false]);
            }

            $student->parents()->syncWithoutDetaching([
                $parent->id => [
                    'relationship' => $data['relationship']
                        ?? $student->parents()
                            ->where('users.id', $parent->id)
                            ->first()?->pivot->relationship,
                    'is_primary_contact' => $data['is_primary_contact'] ?? false,
                ],
            ]);
        });

        return $this->success(
            'تم ربط ولي الأمر بالطالب بنجاح.',
            [
                'student_id' => $studentId,
                'parent_id' => $parent->id,
            ]
        );
    }


    public function detachParent(
        int $studentId,
        int $parentId
    ): JsonResponse {
        $result = DB::transaction(function () use ($studentId, $parentId) {
            $student = User::query()
                ->with('roles')
                ->lockForUpdate()
                ->findOrFail($studentId);

            $parent = User::query()
                ->with('roles')
                ->findOrFail($parentId);

            if (!$student->hasRole('student')) {
                return 'invalid_student';
            }

            if (!$parent->hasRole('parent')) {
                return 'invalid_parent';
            }

            $relationship = $student->parents()
                ->where('users.id', $parent->id)
                ->first();

            if (!$relationship) {
                return 'not_attached';
            }

            $wasPrimary = (bool) $relationship->pivot->is_primary_contact;

            $student->parents()->detach($parent->id);

            if ($wasPrimary) {
                $nextParent = $student->parents()
                    ->orderByPivot('created_at')
                    ->first();

                if ($nextParent) {
                    DB::table('parent_student')
                        ->where('student_id', $student->id)
                        ->where('parent_id', $nextParent->id)
                        ->update(['is_primary_contact' => true]);
                }
            }

            return 'success';
        });

        if ($result === 'invalid_student') {
            return $this->error('الحساب المحدد ليس حساب طالب.', [], 422);
        }

        if ($result === 'invalid_parent') {
            return $this->error('الحساب المحدد ليس حساب ولي أمر.', [], 422);
        }

        if ($result === 'not_attached') {
            return $this->error('ولي الأمر غير مرتبط بهذا الطالب.', [], 404);
        }

        return $this->success('تم فصل ولي الأمر عن الطالب بنجاح.');
    }

}
