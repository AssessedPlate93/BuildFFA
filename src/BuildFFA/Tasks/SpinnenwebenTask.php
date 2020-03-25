<?php


namespace BuildFFA\Tasks;


use pocketmine\block\Block;
use pocketmine\scheduler\Task;
use BuildFFA\BuildFFA;

class SpinnenwebenTask extends Task
{
	public $plugin;
	public $block;

	public function __construct(BuildFFA $plugin, Block $block){
		$this->plugin = $plugin;
		$this->block = $block;
	}

	public function onRun(int $currentTick){
		$this->block->getLevel()->setBlockIdAt($this->block->x, $this->block->y, $this->block->z, Block::AIR);
	}
}