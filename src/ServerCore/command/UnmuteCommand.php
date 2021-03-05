<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class UnmuteCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "unmute",
            "Unmutes the player",
            "/unmute <player>"
        );
        $this->setPermission("command.unmute");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (isset($args[0])) {
            if ($this->plugin->mutedPlayers->exists($args[0])) {
                $this->plugin->mutedPlayers->remove($args[0]);
                $this->plugin->mutedPlayers->save();
                $sender->sendMessage(TextFormat::GREEN . $args[0] . " has been unmuted");
                $player = $this->plugin->getServer()->getPlayer($args[0]);
                if ($player !== null) {
                    $player->sendMessage(TextFormat::GREEN . "You have been unmuted");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "That player is not muted");
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Please enter a player name");
        }
    }

}
