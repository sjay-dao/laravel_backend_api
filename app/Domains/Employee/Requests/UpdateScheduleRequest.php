<?php

namespace App\Domains\Employee\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domains\Employee\Enums\ScheduleType;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('schedules', 'code')->ignore(
                    $this->route('schedule')
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:100'
            ],

            'type' => [
                'required',
                Rule::in(ScheduleType::values())
            ],

            'minimum_daily_minutes' => [
                'nullable',
                'integer',
                'min:0'
            ],

            'maximum_daily_minutes' => [
                'nullable',
                'integer',
                'min:0'
            ],

            'earliest_start' => [
                'nullable',
                'date_format:H:i'
            ],

            'latest_start' => [
                'nullable',
                'date_format:H:i'
            ],

            'latest_end' => [
                'nullable',
                'date_format:H:i'
            ],

            'core_start' => [
                'nullable',
                'date_format:H:i'
            ],

            'core_end' => [
                'nullable',
                'date_format:H:i'
            ],

            'approval_required' => [
                'boolean'
            ],

            'is_active' => [
                'boolean'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'days' => [
                'required',
                'array',
                'size:7'
            ],

            'days.*.day_of_week' => [
                'required',
                'integer',
                'between:1,7',
                'distinct'
            ],

            'days.*.start_time' => [
                'nullable',
                'date_format:H:i'
            ],

            'days.*.end_time' => [
                'nullable',
                'date_format:H:i'
            ],

            'days.*.break_minutes' => [
                'nullable',
                'integer',
                'min:0'
            ],

            'days.*.is_rest_day' => [
                'required',
                'boolean'
            ],

            'days.*.remarks' => [
                'nullable',
                'string'
            ],

        ];
    }
}