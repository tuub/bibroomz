<?php

namespace App\Http\Requests\Admin;

use App\Models\Happening;
use App\Models\Resource;

class UpdateHappeningRequest extends HappeningRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $happening = $this->happeningOrNull();
        $resource = $this->resource();

        if ($user === null || $happening === null || $resource === null) {
            return false;
        }

        if ($happening->resource->resource_group_id === $resource->resource_group_id) {
            return $user->can('adminUpdate', $happening);
        }

        return $user->can('adminCreate', [Happening::class, $resource->resource_group->institution]);
    }

    public function happening(): Happening
    {
        return $this->findModelOrFail(Happening::class);
    }

    public function happeningOrNull(): ?Happening
    {
        return $this->findModel(Happening::class);
    }
}
