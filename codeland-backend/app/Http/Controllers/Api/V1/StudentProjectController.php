<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StudentProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Project;
use App\Models\StudentProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user();

        abort_unless($student->hasRole('student'), 403);

        $enrollments = Enrollment::query()
            ->where('student_id', $student->id)
            ->with('group:id,course_id')
            ->get();

        $groupIds = $enrollments
            ->pluck('group.id')
            ->filter()
            ->unique()
            ->values();

        $courseIds = $enrollments
            ->pluck('group.course_id')
            ->filter()
            ->unique()
            ->values();

        $projects = Project::query()
            ->where('is_active', true)
            ->where(function ($query) use ($groupIds, $courseIds) {
                $query->whereIn('group_id', $groupIds)
                    ->orWhere(function ($query) use ($courseIds) {
                        $query->whereNull('group_id')
                            ->whereIn('course_id', $courseIds);
                    });
            })
            ->with([
                'course',
                'group',
                'studentProjects' => function ($query) use ($student) {
                    $query
                        ->where('student_id', $student->id)
                        ->select([
                            'id',
                            'project_id',
                            'student_id',
                            'project_url',
                            'status',
                            'score',
                            'instructor_feedback',
                            'reviewed_by',
                            'reviewed_at',
                        ])
                        ->with('reviewer:id,name');
                },
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $projects,
        ]);
    }

    public function submit(
        Request $request,
        Project $project
    ): JsonResponse {
        $student = $request->user();

        abort_unless($student->hasRole('student'), 403);

        $data = $request->validate([
            'project_url' => [
                'required',
                'url:http,https',
                'max:2048',
            ],
        ]);

        $submission = DB::transaction(function () use ($student, $project, $data) {
            $lockedProject = Project::query()
                ->lockForUpdate()
                ->findOrFail($project->id);

            abort_unless($lockedProject->is_active, 404);

            $enrollments = Enrollment::query()
                ->where('student_id', $student->id);

            if ($lockedProject->group_id !== null) {
                $isEnrolled = (clone $enrollments)
                    ->where('group_id', $lockedProject->group_id)
                    ->exists();
            } else {
                $isEnrolled = (clone $enrollments)
                    ->whereHas('group', function ($query) use ($lockedProject) {
                        $query->where(
                            'course_id',
                            $lockedProject->course_id
                        );
                    })
                    ->exists();
            }

            abort_unless($isEnrolled, 403);

            $existingSubmission = StudentProject::query()
                ->where('student_id', $student->id)
                ->where('project_id', $lockedProject->id)
                ->lockForUpdate()
                ->first();

            if ($existingSubmission) {
                abort_unless(
                    $existingSubmission->status->canBeResubmitted(),
                    409,
                    'You can only resubmit a project when revision is requested.'
                );

                $existingSubmission->update([
                    'project_url' => $data['project_url'],
                    'status' => StudentProjectStatus::SUBMITTED,
                    'score' => null,
                    'instructor_feedback' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);

                return $existingSubmission->fresh();
            }

            return StudentProject::create([
                'student_id' => $student->id,
                'project_id' => $lockedProject->id,
                'course_id' => $lockedProject->course_id,
                'group_id' => $lockedProject->group_id,
                'title' => $lockedProject->title,
                'description' => $lockedProject->description,
                'project_url' => $data['project_url'],
                'status' => StudentProjectStatus::SUBMITTED,
            ]);
        });

        return response()->json([
            'message' => 'Project submitted successfully.',
            'data' => $submission,
        ], 200);
    }

    public function mySubmissions(Request $request): JsonResponse
    {
        $student = $request->user();

        abort_unless($student->hasRole('student'), 403);

        $submissions = StudentProject::query()
            ->where('student_id', $student->id)
            ->with([
                'project:id,title,description,due_at',
                'course:id,title',
                'group:id,name',
                'reviewer:id,name',
            ])
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $submissions,
        ]);
    }
}
