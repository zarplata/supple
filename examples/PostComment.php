<?php

declare(strict_types=1);

use Zp\Supple\Annotation as Elastic;

class PostComment
{
    /**
     * @var string
     * @Elastic\Mapping(type="keyword", index=false)
     */
    public $authorName;

    /**
     * @var string
     * @Elastic\Mapping(type="keyword")
     */
    public $comment;
}
