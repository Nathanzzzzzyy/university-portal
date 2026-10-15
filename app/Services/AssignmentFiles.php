<?php
namespace App\Services;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Facades\Storage;
class AssignmentFiles
{
    public static function rules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['required', 'file', 'mimes:pdf,docx,jpg,jpeg,png,webp', 'extensions:pdf,docx,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    // The caller tracks new paths to clean them up if its database transaction fails.
    public static function store(array $files, Assignment $assignment, ?Submission $submission, array &$paths): void
    {
        foreach ($files as $file) {
            $path = $file->store('assignment-files', 'local');
            if (! $path) { throw new \RuntimeException('Unable to store the attachment.'); }
            $paths[] = $path;
            $name = mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 240);
            \App\Models\Attachment::create([
                'assignment_id' => $assignment->id, 'submission_id' => $submission?->id,
                'path' => $path, 'name' => $name, 'mime' => $file->getMimeType() ?: 'application/octet-stream', 'size' => $file->getSize(),
            ]);
        }
    }

    public static function deletePaths(array $paths): void
    {
        if ($paths) { Storage::disk('local')->delete($paths); }
    }
}
