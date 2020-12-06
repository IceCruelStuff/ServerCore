<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class HubCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "hub",
            "Teleports player to hub",
            "/hub",
            ["lobby"]
        );
        $this->setPermission("command.hub");
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
                $spawnLocation = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                $player->teleport($spawnLocation);
                $player->sendMessage(TextFormat::GREEN . "Welcome to spawn");
                $this->plugin->getMainItems($player);
                $player->setHealth($player->getMaxHealth());
                $player->setFood($player->getMaxFood());
                $sender->sendMessage(TextFormat::GREEN . "You have sent " . $player->getName() . " to spawn");
            } else {
                $sender->sendMessage(TextFormat::RED . "Player not found");
            }
        } else {
            if ($sender instanceof Player) {
                $spawnLocation = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                $sender->teleport($spawnLocation);
                $sender->sendMessage(TextFormat::GREEN . "Welcome to spawn");
                $this->plugin->getMainItems($sender);
                $sender->setHealth($sender->getMaxHealth());
                $sender->setFood($sender->getMaxFood());
            } else {
                $sender->sendMessage(TextFormat::RED . "Please enter a player name");
            }
        }
    }

}
