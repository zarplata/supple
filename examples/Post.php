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
     * @Elastic\Mapping(type="keyword")
     * @var int
     */
    public $id;

    /**
     * @Elastic\Mapping(type="keyword")
     * @Serializer\Type("int")
     * @var int
     */
    public $authorID;

    /**
     * @Elastic\Mapping(type="text", analyzer="my_custom_analyzer")
     * @var string
     */
    public $authorName;

    /**
     * @Elastic\Mapping(type="text")
     * @var string
     */
    public $content;

    /**
     * @Elastic\Mapping(type="date")
     * @var DateTime
     */
    public $createdAt;

    /**
     * @Elastic\EmbeddedMapping(type="", targetClass="PostComment")
     * @var array<PostComment>
     */
    public $comments;
}
