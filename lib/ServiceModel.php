<?php

namespace WHMCS\Module\Server\OpsShield;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use WHMCS\Database\Capsule;

class ServiceModel extends Model
{
    protected $table = 'module_opsshield_services';
    protected $primaryKey = 'id';

    protected function casts(): array
    {
        return [
            'details' => AsArrayObject::class,
        ];
    }

    public static function setupDatabase()
    {
        try {
            $schema = Capsule::schema();
            if (!$schema->hasTable('module_opsshield_services')) {
                $schema->create('module_opsshield_services', function (Blueprint $table) {
                    $table->charset = 'utf8';
                    $table->collation = 'utf8_unicode_ci';
                    $table->increments('id')->unique(); //whmcs serviceid                    
                    $table->unsignedBigInteger('serverid'); //whmcs serverid                    
                    $table->unsignedBigInteger('userid'); //whmcs userid
                    $table->unsignedBigInteger('service_id')->nullable();                    
                    $table->string('license_key')->nullable();
                    $table->string('status', 20)->nullable();
                    $table->json('details')->nullable();
                    $table->timestamp('sync_time')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Exception $e) {
            logActivity($e->getMessage());
        }
    }
}