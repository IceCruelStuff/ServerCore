<?php

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class FlyCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct("fly", "Allows player to fly", "/fly");
        $this->setPermission("command.fly");
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
                    if ($player->getAllowFlight()) {
                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                        $player->sendMessage(TextFormat::RED . 'You no longer have flight mode');
                    } else {
                        $player->setAllowFlight(true);
                        $player->sendMessage(TextFormat::GREEN . 'You now have flight mode');
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . 'Player not found');
                }
            } else {
                if ($sender->getAllowFlight()) {
                    $sender->setFlying(false);
                    $sender->setAllowFlight(false);
                    $sender->sendMessage(TextFormat::RED . 'You have disabled flight mode');
                } else {
                    $sender->setAllowFlight(true);
                    $sender->sendMessage(TextFormat::GREEN . "You have enabled flight mode");
                }
            }
        } else {
            // for console
            if (isset($args[0])) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    if ($player->getAllowFlight()) {
                        $player->setFlying(false);
                        $player->setAllowFlight(false);
                        $player->sendMessage(TextFormat::RED . 'You no longer have flight mode');
                    } else {
                        $player->setAllowFlight(true);
                        $player->sendMessage(TextFormat::GREEN . 'You now have flight mode');
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . 'Player not found');
                }
            } else {
                $sender->sendMessage(TextFormat::RED . 'Please enter a player name');
            }
        }
    }

}
