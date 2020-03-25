<?php


namespace BuildFFA\Tasks;

use BuildFFA\BuildFFA;
use pocketmine\item\ItemFactory;
use pocketmine\level\Position;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class SandstoneTask extends Task {

    public $plugin;
    public $block;
    public $player;

    public function __construct(BuildFFA $plugin, Block $block, Player $player){
        $this->plugin = $plugin;
        $this->block = $block;
        $this->player = $player;
    }

    public function onRun(int $currentTick){
        $this->block->getLevel()->setBlockIdAt($this->block->x, $this->block->y, $this->block->z, Block::AIR);
    }
}