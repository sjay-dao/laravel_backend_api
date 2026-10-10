<?php
namespace App\Domains\Evidence\Models;
use Illuminate\Database\Eloquent\Model;
class SurveyQuestion extends Model { protected $fillable=['survey_id','question_key','prompt','question_type','is_required','display_order','configuration']; protected $casts=['is_required'=>'boolean','configuration'=>'array']; public function survey(){return $this->belongsTo(Survey::class);} public function options(){return $this->hasMany(SurveyQuestionOption::class)->orderBy('display_order');} }
