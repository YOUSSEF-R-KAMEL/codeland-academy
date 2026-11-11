<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'username',
    'email',
    'phone',
    'password',
    'is_active',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('slug', $role)
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('slug', $permission);
            })
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissions) {
                $query->whereIn('slug', $permissions);
            })
            ->exists();
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'parent_student',
            'parent_id',
            'student_id'
        )
            ->withPivot([
                'relationship',
                'is_primary_contact',
            ])
            ->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'parent_student',
            'student_id',
            'parent_id'
        )
            ->withPivot([
                'relationship',
                'is_primary_contact',
            ])
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function lessonCompletions(): HasMany
    {
        return $this->hasMany(LessonCompletion::class, 'student_id');
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(
            Badge::class,
            'student_badges',
            'student_id',
            'badge_id'
        )
            ->withPivot([
                'awarded_by',
                'awarded_at',
                'notes',
            ])
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function studentQuestions(): HasMany
    {
        return $this->hasMany(StudentQuestion::class, 'student_id');
    }

    public function questionReplies(): HasMany
    {
        return $this->hasMany(StudentQuestionReply::class);
    }

    public function createdAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'created_by');
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function reviewedSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'reviewed_by');
    }

    public function studentProjects(): HasMany
    {
        return $this->hasMany(StudentProject::class, 'student_id');
    }

    public function reviewedProjects(): HasMany
    {
        return $this->hasMany(StudentProject::class, 'reviewed_by');
    }

    public function studentConsents(): HasMany
    {
        return $this->hasMany(StudentConsent::class, 'student_id');
    }

    public function parentConsents(): HasMany
    {
        return $this->hasMany(StudentConsent::class, 'parent_id');
    }

    public function createdAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function targetedAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'student_id');
    }

    public function readAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(
            Announcement::class,
            'announcement_reads',
            'user_id',
            'announcement_id'
        )
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function handledContactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'handled_by');
    }

    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentProgress::class, 'student_id');
    }

    public function studentEvaluations(): HasMany
    {
        return $this->hasMany(StudentEvaluation::class, 'student_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'student_id');
    }

    public function givenStudentEvaluations(): HasMany
    {
        return $this->hasMany(StudentEvaluation::class, 'instructor_id');
    }

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'issued_by');
    }

    public function markedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'marked_by');
    }

    public function instructorGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            Group::class,
            'group_instructors',
            'instructor_id',
            'group_id'
        )
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }
}
