<?php

namespace Os\Framework\Template\Render\Structure;

use Os\Framework\Template\Collection\TemplateBlockCollection;
use Os\Framework\Template\Struct\TemplateBlockStruct;

class Template
{
    protected ?string $extends;
    protected ?Template $parent;
    protected ?string $originContent;
    protected ?string $parsedContent;
    public TemplateBlockCollection $blocks;

    public function __construct()
    {
        $this->extends = null;
        $this->originContent = null;
        $this->parsedContent = null;
        $this->parent = null;
        $this->blocks = new TemplateBlockCollection();
    }


    public function parse(){
        if($this->parent === null){
            $this->parsedContent = $this->cleanUpTemplate($this->originContent);
            return;
        }
        $this->parseRaw();
    }

    public function parseRaw(){
        if($this->parent === null){
            return;
        }
        $this->parent->parseRaw();
        $this->originContent = $this->mergeWithParentBlocks();
        $this->parsedContent = $this->cleanUpTemplate($this->originContent);
    }


    protected function cleanUpTemplate(string $template): string
    {
        foreach(TemplateStructureResolver::PATTERNS as $pattern){
            /** @var string $template */
            $template = preg_replace($pattern, "", $template);
        }
        return $template;
    }

    protected function mergeWithParentBlocks(): string
    {
        $content = $this->parent->getOriginContent();
        $addedContentLengths = [];
        /** @var TemplateBlockStruct $parentBlock */
        foreach($this->parent->blocks as $parentBlock){
            $id = trim($parentBlock->getName(), "{%} ");
            /** @var TemplateBlockStruct $block */
            foreach($this->blocks as $i => $block){
                $blockId = trim($block->getName(), "{%} ");
                if($id === $blockId){
                    $blockContent = substr($this->originContent, $block->getPositionContentStart(), $block->getPositionEnd() - $block->getPositionContentStart() - $block->getEndblockLength());
                    $this->blocks->get($i)->setContent($blockContent);

                    $start = $parentBlock->getPositionContentStart();
                    $oldLength = strlen($content);
                    foreach($addedContentLengths as $s => $l){
                        if($s > $start) continue;
                        $start = $start + $l;
                    }
                    /** @var string $content */
                    $content = substr_replace($content, $blockContent, $start, $parentBlock->getPositionEnd() - $parentBlock->getPositionContentStart() - $parentBlock->getEndblockLength());
                    $length = strlen($content) - $oldLength;
                    $addedContentLengths[$start] = $length;
                }
            }
        }
        return $content;
    }

    /**
     * @return ?string
     */
    public function getExtends(): ?string
    {
        return $this->extends;
    }

    /**
     * @param ?string $extends
     */
    public function setExtends(?string $extends): static
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * @return ?Template
     */
    public function getParent(): ?Template
    {
        return $this->parent;
    }

    /**
     * @param ?Template $parent
     */
    public function setParent(?Template $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return string|null
     */
    public function getOriginContent(): ?string
    {
        return $this->originContent;
    }

    /**
     * @param string|null $originContent
     */
    public function setOriginContent(?string $originContent): void
    {
        $this->originContent = $originContent;
    }

    /**
     * @return string|null
     */
    public function getParsedContent(): ?string
    {
        return $this->parsedContent;
    }

    /**
     * @param string|null $parsedContent
     */
    public function setParsedContent(?string $parsedContent): void
    {
        $this->parsedContent = $parsedContent;
    }

    /**
     * @return TemplateBlockCollection
     */
    public function getBlocks(): TemplateBlockCollection
    {
        return $this->blocks;
    }

    /**
     * @param TemplateBlockCollection $blocks
     */
    public function setBlocks(TemplateBlockCollection $blocks): void
    {
        $this->blocks = $blocks;
    }

}