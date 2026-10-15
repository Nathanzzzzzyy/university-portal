<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->role === 'teacher'; }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['code', 'title', 'schedule_day', 'schedule_time', 'room'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = preg_replace('/\s+/u', ' ', trim($this->input($field)));
            }
        }
        if (isset($normalized['code'])) { $normalized['code'] = strtoupper($normalized['code']); }
        $this->merge($normalized);
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['bail', 'required', 'string', 'min:3', 'max:255', 'regex:/^(?=.*\pL)[\pL\pM\pN\s.,:&\x{0027}()\/+\-\x{2013}\x{2019}]+$/u'],
            'units' => ['bail', 'required', 'integer', 'between:1,6'],
            'schedule_day' => ['bail', 'nullable', 'required_with:schedule_time', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (! preg_match('/^(?:MWF|TTh|MW|TF|MTh|TThS|Mon|Tue|Wed|Thu|Fri|Sat|Sun|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)$/i', $value)) {
                    $fail('Select a valid meeting day or day combination.');
                }
            }],
            'schedule_time' => ['bail', 'nullable', 'required_with:schedule_day', 'string', 'max:30', function ($attribute, $value, $fail) {
                $pattern = '/^(\d{1,2}):(\d{2})\s*(AM|PM)?\s*[-\x{2013}]\s*(\d{1,2}):(\d{2})\s*(AM|PM)?$/iu';
                if (! preg_match($pattern, $value, $m)) { $fail('Use a time range such as 8:00 AM - 9:00 AM or 13:00 - 14:30.'); return; }
                $first = strtoupper($m[3] ?? ''); $last = strtoupper($m[6] ?? '');
                if ($first || $last) { $first = $first ?: $last; $last = $last ?: $first; }
                $startHour = (int) $m[1]; $endHour = (int) $m[4];
                if ((int) $m[2] > 59 || (int) $m[5] > 59 || ($first ? ($startHour < 1 || $startHour > 12 || $endHour < 1 || $endHour > 12) : ($startHour > 23 || $endHour > 23))) {
                    $fail('Enter valid hours and minutes for the class schedule.'); return;
                }
                $start = ($first ? ($startHour % 12 + ($first === 'PM' ? 12 : 0)) : $startHour) * 60 + (int) $m[2];
                $end = ($last ? ($endHour % 12 + ($last === 'PM' ? 12 : 0)) : $endHour) * 60 + (int) $m[5];
                if ($end <= $start) { $fail('The end time must be later than the start time on the same day.'); }
            }],
            'room' => ['bail', 'nullable', 'string', 'max:30', 'regex:/^[\pL\pN][\pL\pM\pN\s.#()\/-]*$/u'],
        ];
        if (! $this->route('subject')) {
            $rules['code'] = ['bail', 'required', 'string', 'max:20', 'regex:/^[A-Z][A-Z0-9]*(?:[ -][A-Z0-9]+)*$/', function ($attribute, $value, $fail) { if (\App\Models\Subject::whereRaw('UPPER(code) = ?', [$value])->exists()) { $fail('This subject code already exists. Use My Classes to add an unassigned subject.'); } }];
        }
        return $rules + \App\Services\SubjectCover::rules();
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Enter a subject code.', 'code.regex' => 'Start with a letter; use letters, numbers, spaces or hyphens (e.g. IT101 or CS-101).',
            'code.unique' => 'This subject code already exists. Use My Classes to add an unassigned subject.',
            'title.required' => 'Enter the subject title.', 'title.regex' => 'Use a readable subject title with letters and standard punctuation.',
            'units.integer' => 'Units must be a whole number.', 'units.between' => 'Units must be between 1 and 6.',
            'schedule_day.required_with' => 'Select meeting days when a class time is provided.',
            'schedule_time.required_with' => 'Enter a time range when meeting days are selected.',
            'room.regex' => 'Use a room name or number, such as CL-201, Lab 1 or Gym.',
        ];
    }
}
