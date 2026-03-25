<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveTaskRequest extends FormRequest
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
        $task = $this->route('task');
        $taskId = $task instanceof Task ? $task->id : $task;

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:tasks,id',
                Rule::notIn([$taskId]),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'Cannot move a task to itself.',
        ];
    }
}
