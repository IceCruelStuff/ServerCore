<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class MuteCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "mute",
            "Mutes the player",
            "/mute <player> <reason>"
        );
        $this->setPermission("command.mute");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (isset($args[0])) {
            if ($this->plugin->getServer()-getPlayer($args[0])) {
                $player = $this->plugin->getServer()->getPlayer($args[0]);
                if ($this->plugin->mutedPlayers->exists($args[0])) {
                    $sender->sendMessage(TextFormat::RED . "That player is already muted");
                } else {
                    if (isset($args[1])) {
                        $this->plugin->mutedPlayers->set($args[0]);
                        $this->plugin->mutedPlayers->save();
                        $player->sendMessage(TextFormat::RED . "You have been muted. Reason: " . $args[1]);
                        $sender->sendMessage(TextFormat::DARK_GREEN . $this->plugin->prefix . $player->getName() . " was muted");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please enter the reason");
                    }
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Player not found");
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Please enter a player name");
        }
    }

}
