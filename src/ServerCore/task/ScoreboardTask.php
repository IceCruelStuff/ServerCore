<?php

declare(strict_types=1);

namespace ServerCore\task;

use pocketmine\scheduler\Task;
use ServerCore\ServerCore;

class ScoreboardTask extends Task {

    private $plugin;
    private $titleIndex;

    public function __construct(ServerCore $plugin, int $titleIndex) {
        $this->plugin = $plugin;
        $this->titleIndex = $titleIndex;
    }

    public function onRun(int $currentTick) : void {
        $this->titleIndex++;
        $config = $this->plugin->getConfig();
        $titles = is_array($config->get("title")) ? $config->get("title") : ["Your Server"];
        $lines = is_array($config->get("line")) ? $config->get("line") : ["Please update your config"];
        if (!isset($titles[$this->titleIndex])) {
            $this->titleIndex = 0;
        }
        $api = ServerCore::getInstance();
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
            $player = $p->getPlayer();
            $name = $player->getName();
            $tps = $this->plugin->getServer()->getTicksPerSecond();
            $usage = $this->plugin->getServer()->getTickUsage();
            $online = $online = count($this->plugin->getServer()->getOnlinePlayers());
            $max_online = $this->plugin->getServer()->getMaxPlayers();
            $fac = $this->plugin->faction->getPlayerFaction($player);
            $x = round($player->getX(), 0);
            $y = round($player->getY(), 0);
            $z = round($player->getZ(), 0);
            $group = $this->plugin->group->getUserDataMgr()->getGroup($player)->getName();
            $money = $this->plugin->money->getMoney($player);
            $item = $player->getInventory()->getItemInHand()->getName();
            $id = $player->getInventory()->getItemInHand()->getId();
            $ids = $player->getInventory()->getItemInHand()->getDamage();
            $level = $player->getLevel()->getName();
            $date = date("H.i");
            $kills = $this->plugin->killChat->getKills($name);
            $deaths = $this->plugin->killChat->getDeaths($name);
            $ping = $player->getPing($name);
            $lines = $config->get("line");
            $lines = str_replace("{name}", $name, $lines);
            $lines = str_replace("{tps}", $tps, $lines);
            $lines = str_replace("{usage}", $usage, $lines);
            $lines = str_replace("{online}", $online, $lines);
            $lines = str_replace("{max_online}", $ids, $lines);
            $lines = str_replace("{faction}", $fac, $lines);
            $lines = str_replace("{x}", $x, $lines);
            $lines = str_replace("{y}", $y, $lines);
            $lines = str_replace("{z}", $z, $lines);
            $lines = str_replace("{group}", $group, $lines);
            $lines = str_replace("{money}", $money, $lines);
            $lines = str_replace("{item}", $item, $lines);
            $lines = str_replace("{id}", $id, $lines);
            $lines = str_replace("{ids}", $ids, $lines);
            $lines = str_replace("{level}", $level, $lines);
            $lines = str_replace("{time}", $date, $lines);
            $lines = str_replace("{kills}", $kills, $lines);
            $lines = str_replace("{deaths}", $deaths, $lines);
            $lines = str_replace("{ping}", $ping, $lines);
            $api->new($p, $p->getName(), $titles[$this->titleIndex]);
            $i = 0;
            foreach ($lines as $line) {
                if ($i < 15) {
                    $i++;
                    $api->setLine($p, $i, $line);
                }
            }
        }
    }

}
