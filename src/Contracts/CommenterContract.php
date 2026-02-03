<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Contracts;
use Illuminate\Contracts\Auth\Access\Authorizable;

interface CommenterContract extends Authorizable
{
    public function getCommenterName(): string;

    public function getCommenterAvatar(): ?string;
}
