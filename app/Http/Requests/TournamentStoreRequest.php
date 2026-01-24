<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TournamentStoreRequest extends FormRequest
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
            'teams' => ['array'],
            'teams.*.id' => ['required', 'exists:teams,id'],
            'teams.*.strength' => ['required', 'integer', 'min:0'],
        ];
    }
}
