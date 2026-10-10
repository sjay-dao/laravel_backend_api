<?php

namespace App\Domains\Evidence\Controllers;

use App\Domains\Evidence\Models\Survey;
use App\Domains\Evidence\Requests\SubmitSurveyResponseRequest;
use App\Domains\Evidence\Resources\PublicSurveyResource;
use App\Domains\Evidence\Resources\PublicSurveyResponseResource;
use App\Domains\Evidence\Services\EvidenceService;
use App\Domains\Evidence\Services\SurveyService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class PublicSurveyController extends BaseApiController
{
    public function __construct(
        protected SurveyService $surveys,
        protected EvidenceService $evidence
    ) {
    }

    public function show(Request $request, Survey $survey)
    {
        abort_unless($survey->status === 'published', 404);

        return $this->resource(
            new PublicSurveyResource($this->surveys->detail($survey)),
            'Survey retrieved successfully.'
        );
    }

    public function submit(SubmitSurveyResponseRequest $request, Survey $survey)
    {
        abort_unless($survey->status === 'published', 404);

        $response = $this->evidence->submitResponse($request->validated());

        return $this->created(
            new PublicSurveyResponseResource($response),
            'Thank you. Your response was recorded as simulated survey evidence, not a sale.'
        );
    }
}
