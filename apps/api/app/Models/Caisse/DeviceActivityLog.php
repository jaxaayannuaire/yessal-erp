<?php
namespace App\Models\Caisse;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeviceActivityLog extends Model
{
     use HasFactory;

	public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'device_id',
        'event_type',
        'ip_address',
        'user_agent',
        'app_version',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Organization::class
        );
    }
}

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('event_type', 50);
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['organization_id', 'device_id', 'created_at']);
            $table->index(['device_id', 'event_type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_activity_logs');
    }
};
