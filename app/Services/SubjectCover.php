<?php
namespace App\Services;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class SubjectCover
{
    public static function rules(): array {
        return ['cover_photo'=>['nullable','image','mimes:jpg,jpeg,png,webp','extensions:jpg,jpeg,png,webp','max:5120','dimensions:max_width=6000,max_height=6000'], 'remove_cover'=>['nullable','boolean']];
    }
    public static function save(Subject $subject, Request $request, array $attributes = []): void {
        unset($attributes['cover_photo'], $attributes['remove_cover']);
        $oldPath=$subject->cover_photo_path; $newPath=null;
        if ($request->hasFile('cover_photo')) {
            $newPath=$request->file('cover_photo')->store('subject-covers','local');
            if (!$newPath) throw new \RuntimeException('Unable to store cover photo.');
            $attributes['cover_photo_path']=$newPath;
        } elseif ($request->boolean('remove_cover')) { $attributes['cover_photo_path']=null; }
        try { $subject->fill($attributes)->save(); }
        catch (\Throwable $e) { if($newPath) Storage::disk('local')->delete($newPath); throw $e; }
        if($oldPath && $oldPath !== $subject->cover_photo_path) Storage::disk('local')->delete($oldPath);
    }
}
