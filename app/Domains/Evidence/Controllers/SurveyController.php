<?php

namespace App\Domains\Evidence\Controllers;

use App\Domains\Evidence\Models\Survey;
use App\Domains\Evidence\Requests\StoreSurveyRequest;
use App\Domains\Evidence\Requests\SubmitSurveyResponseRequest;
use App\Domains\Evidence\Requests\UpdateSurveyRequest;
use App\Domains\Evidence\Resources\SurveyResource;
use App\Domains\Evidence\Resources\SurveyResponseResource;
use App\Domains\Evidence\Services\EvidenceService;
use App\Domains\Evidence\Services\SurveyService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class SurveyController extends BaseApiController
{
    public function __construct(
        protected SurveyService $surveys,
        protected EvidenceService $evidence
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'evidence.surveys.view');

        return $this->paginated(
            $this->surveys->paginate(
                min(max($request->integer('per_page', 15), 1), 100)
            ),
            SurveyResource::class,
            'Surveys retrieved successfully.'
        );
    }

    public function store(StoreSurveyRequest $request)
    {
        $survey = $this->surveys->create(
            $request->validated(),
            $request->user()->id
        );

        return $this->created(
            new SurveyResource($survey),
            'Survey created as a draft.'
        );
    }

    public function show(Request $request, Survey $survey)
    {
        $this->authorizeAbility($request, 'evidence.surveys.view');

        return $this->resource(
            new SurveyResource($this->surveys->detail($survey)),
            'Survey retrieved successfully.'
        );
    }

    public function update(UpdateSurveyRequest $request, Survey $survey)
    {
        return $this->resource(
            new SurveyResource($this->surveys->update($survey, $request->validated())),
            'Survey updated successfully.'
        );
    }

    public function publish(Request $request, Survey $survey)
    {
        $this->authorizeAbility($request, 'evidence.surveys.publish');

        return $this->resource(
            new SurveyResource($this->surveys->publish($survey, $request->user()->id)),
            'Survey published successfully.'
        );
    }

    public function responses(Request $request, Survey $survey)
    {
        $this->authorizeAbility($request, 'evidence.responses.view');

        return $this->paginated(
            $this->surveys->responsePaginate(
                $survey,
                min(max($request->integer('per_page', 15), 1), 100)
            ),
            SurveyResponseResource::class,
            'Survey responses retrieved successfully.'
        );
    }

    public function submitAssisted(
        SubmitSurveyResponseRequest $request,
        Survey $survey
    ) {
        $this->authorizeAbility($request, 'evidence.responses.create');

        $response = $this->evidence->submitResponse(
            $request->validated(),
            $request->user()->id
        );

        return $this->created(
            new SurveyResponseResource($response),
            'Survey response captured and simulated behavior recorded.'
        );
    }
}
