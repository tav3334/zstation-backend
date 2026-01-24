<?php

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToOrganization
{
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    protected static function bootBelongsToOrganization()
    {
        // Filtre automatique sur les requêtes SELECT
        static::addGlobalScope('organization', function (Builder $builder) {
            $user = auth()->user();

            if ($user && !$user->isSuperAdmin()) {
                $builder->where((new static)->getTable() . '.organization_id', $user->organization_id);
            }
        });

        // Ajoute automatiquement organization_id lors de la création
        static::creating(function ($model) {
            $user = auth()->user();

            if ($user && !$user->isSuperAdmin() && empty($model->organization_id)) {
                $model->organization_id = $user->organization_id;
            }
        });
    }
}
