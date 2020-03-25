<?php


namespace BuildFFA\Tasks;


use BuildFFA\BuildFFA;
use pocketmine\scheduler\Task;

class ScoreBoardTask extends Task {

    private $plugin;

    public function __construct(BuildFFA $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick)
    {
        $this->plugin->onScore();
    }
}