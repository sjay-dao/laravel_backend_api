<?php
namespace App\Domains\Evidence\Models;
use App\Domains\Inventory\Models\InventoryObjectUnit; use Illuminate\Database\Eloquent\Model;
class MarketObservation extends Model { protected $fillable=['evidence_record_id','inventory_object_unit_id','store_name','channel','selling_price','currency_code','package_size','availability_status','promotion']; protected $casts=['selling_price'=>'decimal:6']; public function evidence(){return $this->belongsTo(EvidenceRecord::class,'evidence_record_id');} public function inventoryObjectUnit(){return $this->belongsTo(InventoryObjectUnit::class);} }
