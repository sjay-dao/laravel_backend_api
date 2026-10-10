<?php
namespace App\Domains\Evidence\Models; use Illuminate\Database\Eloquent\Model;
class SurveyRespondent extends Model { protected $fillable=['respondent_code','session_reference','profile_payload']; protected $casts=['profile_payload'=>'array']; public function responses(){return $this->hasMany(SurveyResponse::class);} }
