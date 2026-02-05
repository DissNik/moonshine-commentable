<?php

return [
    'comment' => [
        'model' => DissNik\MoonShineCommentable\Models\Comment::class,
        'policy' => DissNik\MoonShineCommentable\Policies\CommentPolicy::class,
    ],
    'height' => '600px',
    'interval' => null
];
