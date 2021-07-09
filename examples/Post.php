<?php

declare(strict_types=1);

use Zp\Supple\Annotation as Elastic;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Elastic\IndexTemplate(name="posts", patterns="posts-*")
 * @Elastic\IndexSetting(name="index.refresh_interval", value="5s")
 */
class Post
{
    /**
     * @Elastic\ID()
     * @Elastic\Property(type="keyword")
     * @var int
     */
    public $id;

    /**
     * @Elastic\Property(type="keyword")
     * @Serializer\Type("int")
     * @var int
     */
    public $authorID;

    /**
     * @Elastic\Property(type="text", analyzer="my_custom_analyzer")
     * @var string
     */
    public $authorName;

    /**
     * @Elastic\Property(type="text")
     * @var string
     */
    public $content;

    /**
     * @Elastic\Property(type="date")
     * @var DateTime
     */
    public $createdAt;

    /**
     * @Elastic\EmbeddedProperty(type="", targetClass="PostComment")
     * @var array<PostComment>
     */
    public $comments;
}
