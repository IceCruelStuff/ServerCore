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
        // $lines = is_array($config->get("line")) ? $config->get("line") : ["Please update your config"];
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
            if ($this->plugin->getServer()->getPluginManager()->getPlugin("FactionsPro") !== null) {
                $fac = $this->plugin->faction->getPlayerFaction($player);
            } else {
                $fac = null;
            }
            $x = round($player->getX(), 0);
            $y = round($player->getY(), 0);
            $z = round($player->getZ(), 0);
            $group = $this->plugin->group->getUserDataMgr()->getGroup($player)->getName();
            $money = $this->plugin->money->myMoney($player);
            $item = $player->getInventory()->getItemInHand()->getName();
            $id = $player->getInventory()->getItemInHand()->getId();
            $ids = $player->getInventory()->getItemInHand()->getDamage();
            $level = $player->getLevel()->getName();
            $date = date("H.i");
            if ($this->plugin->getServer()->getPluginManager()->getPlugin("KillChat") !== null) {
                $kills = $this->plugin->killChat->getKills($name);
                $deaths = $this->plugin->killChat->getDeaths($name);
            } else {
                $kills = null;
                $deaths = null;
            }
            $ping = $player->getPing($name);
            // $lines = $config->get("line");
            $api->new($p, $p->getName(), $titles[$this->titleIndex]);
            $api->setLine($player, 1, strval($name));
            $api->setLine($player, 2, strval($tps));
            $api->setLine($player, 3, strval($usage));
            $api->setLine($player, 4, strval($online));
            $api->setLine($player, 5, strval($x));
            $api->setLine($player, 6, strval($y));
            $api->setLine($player, 7, strval($z));
            $api->setLine($player, 8, strval($item));
            $api->setLine($player, 9, strval($level));
            $api->setLine($player, 10, strval($ping));
            $api->setLine($player, 11, strval($group));
            $api->setLine($player, 12, strval($money));
            if ($fac !== null) {
                $api->setLine($player, 13, $fac);
            } elseif ($kills !== null && $deaths !== null) {
                $api->setLine($player, 13, $kills);
                $api->setLine($player, 14, $deaths);
            }
            if ($kills !== null && $deaths !== null) {
                $api->setLine($player, 14, $kills);
                $api->setLine($player, 15, $deaths);
            }
        }
    }

}
