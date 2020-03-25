<?php


namespace BuildFFA\Tasks;

use BuildFFA\BuildFFA;
use pocketmine\block\BlockIds;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

class Sandstone1Task extends Task {

    public $plugin;
    public $block;

    public function __construct(BuildFFA $plugin, Block $block){
        $this->plugin = $plugin;
        $this->block = $block;
    }

    public function onRun(int $currentTick){
        $this->block->getLevel()->setBlockIdAt($this->block->x, $this->block->y, $this->block->z, Block::SANDSTONE);
    }
}