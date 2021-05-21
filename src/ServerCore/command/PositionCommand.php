<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class PositionCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "position",
            "Get your current coordinates",
            "/position",
            ["pos"]
        );
        $this->setPermission("command.pos");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (isset($args[0])) {
            if ($sender->isOp()) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    $sender->sendMessage(TextFormat::GREEN . $player->getName() . " is at X: " . round($player->getX()) . " Y: " . round($player->getY()) . " Z: " . round($player->getZ()));
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to view other players' coordinates");
            }
        } else {
            if ($sender instanceof Player) {
                $sender->sendMessage(TextFormat::GREEN . "Your current coordinates are X: " . round($sender->getX()) . " Y: " . round($sender->getY()) . " Z: " . round($sender->getZ()));
            } else {
                $sender->sendMessage(TextFormat::RED . "Please enter a player name");
            }
        }
    }

}
