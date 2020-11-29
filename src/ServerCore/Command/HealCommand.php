<?php

namespace ServerCore\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class HealCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "heal",
            "Heals a player",
            "/heal <player>",
        );
        $this->setPermission("command.heal");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if ($sender instanceof Player) {
            if (isset($args[0])) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    $player->setHealth(20);
                    $player->sendMessage(TextFormat::DARK_PURPLE . "You have been healed");
                    $sender->sendMessage(TextFormat::GREEN . "You have healed " . $player->getName());
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                $sender->setHealth(20);
                $sender->sendMessage(TextFormat::DARK_PURPLE . "You have been healed");
            }
        } else {
            // for console
            if (isset($args[0])) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    $player->setHealth(20);
                    $player->sendMessage(TextFormat::DARK_PURPLE . "You have been healed");
                    $sender->sendMessage(TextFormat::GREEN . "You have healed " . $player->getName());
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Please enter a player name");
            }
        }
    }

}
