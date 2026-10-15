<div class="field upload-panel subject-cover-uploader">
<label for="subject-cover-photo">Subject cover photo</label><p class="muted">JPG, PNG or WebP &middot; up to 5 MB. A wide landscape image works best.</p>
<div class="cover-upload-preview" data-cover-preview @if(empty(($subject ?? null)?->cover_photo_path)) hidden @endif>@if(!empty(($subject ?? null)?->cover_photo_path))<img src="{{ route('subjects.cover', $subject) }}" alt="Current subject cover">@endif</div>
<input id="subject-cover-photo" type="file" name="cover_photo" accept="image/jpeg,image/png,image/webp" data-cover-input aria-describedby="cover-photo-help">
<small id="cover-photo-help" class="field-help">Students enrolled in this subject will see the same cover. Select files again after a validation error.</small>
@if(!empty(($subject ?? null)?->cover_photo_path))<label class="check" style="margin-top:12px"><input type="checkbox" name="remove_cover" value="1" data-remove-cover> Remove current cover</label>@endif
<p class="muted" data-cover-status role="status"></p>@error('cover_photo')<p class="err">{{ $message }}</p>@enderror
</div>
