<?php

namespace Os\Framework\Template\Struct;

use JetBrains\PhpStorm\Pure;
use Os\Framework\Template\Collection\TemplateBlockCollection;

class TemplateBlockStruct extends \Os\Framework\Kernel\Data\Struct\Struct
{
    protected string $name;
    protected ?int $positionStart;
    protected ?int $positionContentStart;
    protected ?int $positionEnd;
    protected ?int $endblockLength;
    protected ?string $content;
    protected TemplateBlockCollection $children;


    #[Pure]
    public function __construct(){
        $this->positionStart = null;
        $this->positionEnd = null;
        $this->positionContentStart = null;
        $this->endblockLength = null;
        $this->content = null;
        $this->children = new TemplateBlockCollection();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TemplateBlockStruct
     */
    public function setName(string $name): TemplateBlockStruct
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPositionStart(): ?int
    {
        return $this->positionStart;
    }

    /**
     * @param int|null $positionStart
     * @return TemplateBlockStruct
     */
    public function setPositionStart(?int $positionStart): TemplateBlockStruct
    {
        $this->positionStart = $positionStart;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPositionEnd(): ?int
    {
        return $this->positionEnd;
    }

    /**
     * @param int|null $positionEnd
     * @return TemplateBlockStruct
     */
    public function setPositionEnd(?int $positionEnd): TemplateBlockStruct
    {
        $this->positionEnd = $positionEnd;
        return $this;
    }

    /**
     * @return TemplateBlockCollection
     */
    public function getChildren(): TemplateBlockCollection
    {
        return $this->children;
    }

    /**
     * @param TemplateBlockCollection $children
     */
    public function setChildren(TemplateBlockCollection $children): void
    {
        $this->children = $children;
    }

    public function addChild(TemplateBlockStruct $child): void
    {
        $this->children->add($child);
    }

    /**
     * @return int|null
     */
    public function getPositionContentStart(): ?int
    {
        return $this->positionContentStart;
    }

    /**
     * @param int|null $positionContentStart
     * @return TemplateBlockStruct
     */
    public function setPositionContentStart(?int $positionContentStart): TemplateBlockStruct
    {
        $this->positionContentStart = $positionContentStart;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEndblockLength(): ?int
    {
        return $this->endblockLength;
    }

    /**
     * @param int|null $endblockLength
     */
    public function setEndblockLength(?int $endblockLength): void
    {
        $this->endblockLength = $endblockLength;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     */
    public function setContent(?string $content): void
    {
        $this->content = $content;
    }
}