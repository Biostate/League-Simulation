<?php

namespace App\Http\Requests\Tournament;

use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;

class RollbackWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'week' => $this->route('week'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Tournament $tournament */
        $tournament = $this->route('tournament');

        return [
            'week' => [
                'required',
                'integer',
                'min:0',
                'max:'.$tournament->current_week,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'week.min' => 'Invalid rollback week.',
            'week.max' => 'Invalid rollback week.',
        ];
    }
}
