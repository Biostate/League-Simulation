<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TournamentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('teams') && is_string($this->teams)) {
            $this->merge([
                'teams' => json_decode($this->teams, true) ?? [],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'teams' => ['sometimes', 'array'],
            'teams.*.id' => ['required_with:teams', 'exists:teams,id'],
            'teams.*.strength' => ['required_with:teams', 'integer', 'min:0'],
        ];
    }
}
