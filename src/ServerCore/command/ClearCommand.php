<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class ClearCommand extends Command {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "clear",
            "Clears player inventory",
            "/clear",
            ["clearinv"]
        );
        $this->setPermission("command.clear");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (isset($args[0])) {
            if ($this->plugin->getServer()->getPlayer($args[0])) {
                $player = $this->plugin->getServer()->getPlayer($args[0]);
                $player->getArmorInventory()->clearAll();
                $player->getInventory()->clearAll();
                $player->sendMessage(TextFormat::RED . "Your inventory has been cleared");
                $sender->sendMessage(TextFormat::GREEN . "You have cleared " . $player->getName() . "'s inventory");
            } else {
                $sender->sendMessage(TextFormat::RED . "Player not found");
            }
        } else {
            if ($sender instanceof Player) {
                $sender->getArmorInventory()->clearAll();
                $sender->getInventory()->clearAll();
                $sender->sendMessage(TextFormat::RED . "Your inventory has been cleared");
            } else {
                $sender->sendMessage(TextFormat::RED . "Please enter a player name");
            }
        }
    }

}
