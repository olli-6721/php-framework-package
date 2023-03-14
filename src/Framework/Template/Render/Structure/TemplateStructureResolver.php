<?php

namespace Os\Framework\Template\Render\Structure;


use Os\Framework\Exception\FrameworkException;
use Os\Framework\Template\Collection\TemplateBlockCollection;
use Os\Framework\Template\Render\TemplateRenderer;
use Os\Framework\Template\Struct\TemplateBlockStruct;

class TemplateStructureResolver
{
    public const PATTERNS = [
        "extends" => "/{%(| )extends ('|\")(.*)('|\")(| )%}/",
        "block" => "/{%(| )block (\b(?!%}\b)\w+)(| )%}/",
        "endblock" => "/{%(| )endblock(| )%}/"
    ];

    /**
     * @throws FrameworkException
     */
    public function resolve(string &$templateContent, array $templateParameters, TemplateRenderer $renderer): Template
    {
        return $this->resolveToTemplate($templateContent, $templateParameters, $renderer);
    }
    /**
     * @throws FrameworkException
     */
    protected function resolveToTemplate(string $templateContent, array $parameters, TemplateRenderer $renderer): Template
    {
        $template = new Template();
        $template->setOriginContent($templateContent);
        $blockRegexMatches = [];
        $endblockRegexMatches = [];
        foreach(self::PATTERNS as $key => $pattern){
            $_matches = [];
            preg_match_all($pattern, $templateContent, $_matches, PREG_OFFSET_CAPTURE);
            switch($key){
                case "extends":
                    if(empty($_matches[3])) break;
                    $extends = reset($_matches[3])[0];
                    $template->setExtends($extends !== false ? $extends : null);
                    if($template->getExtends() !== null){
                        $extendsContent = $renderer->getTemplateContent($template->getExtends());
                        $template->setParent($this->resolveToTemplate($extendsContent, $parameters, $renderer));
                    }
                    break;
                case "block":
                    if(empty($_matches[0]) || empty($_matches[2])) break;
                    $blockRegexMatches = $_matches;
                    break;
                case "endblock":
                    if(empty($_matches[0])) break;
                    $endblockRegexMatches = $_matches[0];
                    break;
            }
        }
        $template->blocks = $this->parseBlocks($blockRegexMatches, $endblockRegexMatches);
        return $template;
    }

    /**
     * @throws FrameworkException
     */
    protected function parseBlocks(array $blockRegexMatches, array $endblockRegexMatches): TemplateBlockCollection
    {
        $rawBlocks = $this->associateBlocks($blockRegexMatches, $endblockRegexMatches);
        [$structuredBlocks, $parentRelations] = $this->structureBlocks($rawBlocks);

        $collection = new TemplateBlockCollection();
        foreach($structuredBlocks as $structuredBlock){
            $collection->add($this->cleanUpBlock($structuredBlock, $parentRelations));
        }

        return $collection;
    }

    protected function associateBlocks(array $blockRegexMatches, array $endblockRegexMatches): array
    {
        $rawBlocks = [];
        $regexBlocks = $blockRegexMatches[0];

        $merged = array_merge($regexBlocks, $endblockRegexMatches);
        $positions = array_column($merged, 1);
        array_multisort($positions, SORT_ASC, $merged);
        $nonAssociatedBlocks = array_column($regexBlocks, 0);

        foreach($merged as $i => $mergedItem){
            $isEndblock = $this->isEndblock($mergedItem[0]);
            if($isEndblock){
                $lastBlock = null;
                $c = $i;
                while($lastBlock === null){
                    $c--;
                    if(empty($merged[$c]))
                        break;
                    if(!$this->isEndblock($merged[$c][0]) && in_array($merged[$c][0], $nonAssociatedBlocks)){
                        $lastBlock = $merged[$c];
                        unset($nonAssociatedBlocks[array_search($lastBlock[0], $nonAssociatedBlocks)]);
                    }
                }

                if($lastBlock === null)
                    throw new FrameworkException(sprintf("No block found for endblock at position %d", $mergedItem[1]));
                $rawBlocks[] = [
                    "block" => $lastBlock,
                    "endblock" => $mergedItem
                ];
            }
        }

        return $rawBlocks;
    }

    protected function structureBlocks(array $rawBlocks): array
    {
        $structuredBlocks = [];
        $parentRelations = [];
        foreach($rawBlocks as $i => $rawBlock){
            $children = [];
            $parent = null;
            foreach($rawBlocks as $i2 => $rawBlockInner){
                if(
                    $rawBlockInner["block"][1] < $rawBlock["block"][1] &&
                    $rawBlockInner["endblock"][1] > $rawBlock["endblock"][1]){
                    $parent = $i2;
                }
            }
            foreach($rawBlocks as $rawBlockChild){
                if(
                    $rawBlockChild["block"][1] > $rawBlock["block"][1] &&
                    $rawBlockChild["endblock"][1] < $rawBlock["endblock"][1]){
                    if(!isset($parentRelations[$rawBlockChild["block"][0]])) $parentRelations[$rawBlockChild["block"][0]] = [];
                    if(!in_array($rawBlock["block"][0], $parentRelations[$rawBlockChild["block"][0]]))
                        $parentRelations[$rawBlockChild["block"][0]][] = $rawBlock["block"][0];
                    $children[] = $rawBlockChild;
                }
            }

            $rawBlock["children"] = $children;
            if($parent !== null){
                if(!isset($structuredBlocks[$parent]))
                    $structuredBlocks[$parent] = $rawBlocks[$parent];
                if(!isset($structuredBlocks[$parent]["children"]))
                    $structuredBlocks[$parent]["children"] = [];
                $structuredBlocks[$parent]["children"][] = $rawBlock;
                if(!isset($parentRelations[$rawBlock["block"][0]])) $parentRelations[$rawBlock["block"][0]] = [];
                if(!in_array($rawBlocks[$parent]["block"][0], $parentRelations[$rawBlock["block"][0]]))
                    $parentRelations[$rawBlock["block"][0]][] = $rawBlocks[$parent]["block"][0];
            }
            else {
                if(!isset($structuredBlocks[$i]))
                    $structuredBlocks[$i] = $rawBlock;
            }
        }

        return [$structuredBlocks, $parentRelations];
    }

    protected function cleanUpBlock(array $structuredBlock, array $parentRelations): TemplateBlockStruct
    {
        $block = $this->toTemplateBlock($structuredBlock);
        if(empty($structuredBlock["children"])) $structuredBlock["children"] = [];
        foreach($structuredBlock["children"] as $i => $child){
            $_child = $this->toTemplateBlock($child);

            if(!isset($parentRelations[$child["block"][0]])) {
                $block->addChild($_child);
                continue;
            }

            $lastItem = $parentRelations[$child["block"][0]][array_key_last($parentRelations[$child["block"][0]])];
            if($lastItem !== $structuredBlock["block"][0]){
                $children = $structuredBlock["children"];
                unset($children[$i]);
                $structuredBlock["children"] = $children;
                continue;
            }
            $block->addChild($this->cleanUpBlock($child, $parentRelations));
        }
        return $block;
    }

    protected function isEndblock(string $value): bool
    {
        return trim($value, "{%} ") === "endblock";
    }

    protected function toTemplateBlock(array $blockArray): TemplateBlockStruct
    {
        $block = new TemplateBlockStruct();
        $block->setName($blockArray["block"][0]);
        $block->setPositionStart($blockArray["block"][1]);
        $block->setEndblockLength(strlen($blockArray["endblock"][0]));
        $block->setPositionContentStart($blockArray["block"][1] + strlen($blockArray["block"][0]));
        $block->setPositionEnd($blockArray["endblock"][1] + $block->getEndblockLength());
        return $block;
    }
}