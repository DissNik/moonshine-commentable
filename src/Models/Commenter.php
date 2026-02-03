<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Models;

use DissNik\MoonShineCommentable\Contracts\CommenterContract;
use Illuminate\Database\Eloquent\Model;

class Commenter extends Model implements CommenterContract
{
    public function getCommenterName(): string
    {
        return $this->name;
    }

    public function getCommenterAvatar(): ?string
    {
        return null;
    }

    public function can($abilities, $arguments = []): bool
    {
        return true;
    }
}
