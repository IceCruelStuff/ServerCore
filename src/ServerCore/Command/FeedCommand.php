<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class FeedCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "feed",
            "Feeds a player",
             "/heal <player>",
             ["eat"]
        );
        $this->setPermission("command.feed");
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
                    if ($player->getFood() == $player->getMaxFood()) {
                        $sender->sendMessage(TextFormat::RED . "That player is already at max food");
                    } else {
                        $player->setFood($player->getMaxFood()); // max value
                        $player->sendMessage(TextFormat::GREEN . "You have been fed");
                        $sender->sendMessage(TextFormat::GREEN . "You have fed " . $player->getName());
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                if ($sender->getFood() == $sender->getMaxFood()) {
                    $sender->sendMessage(TextFormat::RED . "You are already at max food");
                } else {
                    $sender->setFood($sender->getMaxFood()); // max value
                    $sender->sendMessage(TextFormat::GREEN . "You have been fed");
                }
            }
        } else {
            if (isset($args[0])) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    if ($player->getFood() == $player->getMaxFood()) {
                        $sender->sendMessage(TextFormat::RED . "That player is already at max food");
                    } else {
                        $player->setFood($player->getMaxFood()); // max value
                        $player->sendMessage(TextFormat::GREEN . "You have been fed");
                        $sender->sendMessage(TextFormat::GREEN . "You have fed " . $player->getName());
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Please enter a player name");
            }
        }
    }

}
