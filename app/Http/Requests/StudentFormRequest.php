<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $studentId = $this->route('student')?->id;
        
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('students')->ignore($studentId)
            ],
            'enrollment_number' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('students')->ignore($studentId)
            ],
            'gender' => 'required|in:Male,Female,Other',
            'father_name' => 'nullable|string|max:255',
            'student_mobile' => [
                'nullable', 
                'string', 
                'max:20',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('students')->ignore($studentId)
            ],
            'father_mobile' => [
                'nullable', 
                'string', 
                'max:20',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('students')->ignore($studentId)
            ],
            'village' => 'nullable|string|max:255',
            'admission_date' => 'required|date_format:Y-m-d',
            'batch_id' => 'nullable|exists:batches,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'student_mobile.unique' => 'This student mobile number is already registered with another student.',
            'father_mobile.unique' => 'This father mobile number is already registered with another student.',
            'student_mobile.regex' => 'Student mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
            'father_mobile.regex' => 'Father mobile number must be a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.',
            'enrollment_number.unique' => 'This enrollment number is already taken.',
            'email.unique' => 'This email address is already registered.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'student_mobile' => 'student mobile number',
            'father_mobile' => 'father mobile number',
            'enrollment_number' => 'enrollment number',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: Check if student_mobile and father_mobile are the same
            if ($this->student_mobile && $this->father_mobile && 
                $this->student_mobile === $this->father_mobile) {
                $validator->errors()->add('father_mobile', 'Father mobile number cannot be the same as student mobile number.');
            }

            // Custom validation: Check if mobile numbers are cross-duplicated
            if ($this->student_mobile) {
                $existsAsFatherMobile = \App\Models\Student::where('father_mobile', $this->student_mobile)
                    ->when($this->route('student'), function ($query) {
                        return $query->where('id', '!=', $this->route('student')->id);
                    })
                    ->exists();

                if ($existsAsFatherMobile) {
                    $validator->errors()->add('student_mobile', 'This mobile number is already registered as a father mobile number for another student.');
                }
            }

            if ($this->father_mobile) {
                $existsAsStudentMobile = \App\Models\Student::where('student_mobile', $this->father_mobile)
                    ->when($this->route('student'), function ($query) {
                        return $query->where('id', '!=', $this->route('student')->id);
                    })
                    ->exists();

                if ($existsAsStudentMobile) {
                    $validator->errors()->add('father_mobile', 'This mobile number is already registered as a student mobile number for another student.');
                }
            }
        });
    }
}