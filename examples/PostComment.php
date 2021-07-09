<?php

declare(strict_types=1);

use Zp\Supple\Annotation as Elastic;

class PostComment
{
    /**
     * @Elastic\Property(type="keyword", index=false)
     * @var string
     */
    public $authorName;

    /**
     * @Elastic\Property(type="keyword")
     * @var string
     */
    public $comment;
}
