<?php
namespace App\Domains\Evidence\Models;
use App\Domains\System\Models\User;
use Illuminate\Database\Eloquent\Model;
class Survey extends Model { protected $fillable=['code','name','description','version','status','created_by','published_by','published_at']; protected $casts=['published_at'=>'datetime']; public function questions(){return $this->hasMany(SurveyQuestion::class)->orderBy('display_order');} public function scenarios(){return $this->hasMany(SurveyScenario::class);} public function responses(){return $this->hasMany(SurveyResponse::class);} public function creator(){return $this->belongsTo(User::class,'created_by');} public function publisher(){return $this->belongsTo(User::class,'published_by');} }
