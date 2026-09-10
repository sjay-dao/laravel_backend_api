<?php
namespace App\Domains\Evidence\Models; use Illuminate\Database\Eloquent\Model;
class SurveyQuestionOption extends Model { protected $fillable=['survey_question_id','option_code','label','raw_value','display_order']; public function question(){return $this->belongsTo(SurveyQuestion::class,'survey_question_id');} }
