<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class WarnCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "warn",
            "Warns a player",
            "/warn <player> <string:message>"
        );
        $this->setPermission("command.warn");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage(TextFormat::RED . "Please enter the player name and the message correctly");
            return;
        } elseif ($this->plugin->getServer()->getPlayer($args[0]) instanceof Player) {
            if ($args[1] !== null) {
                $name = strtolower(array_shift($args));
                $player = $this->plugin->getServer()->getPlayer($name);
                $message = implode(" ", $args);
                if ($this->plugin->warnedPlayers->exists($name)) {
                    $action = strtolower($this->plugin->warnedPlayers->get("Action"));
                    if ($action === "kick") {
                        $player->kick($message, false);
                        $sender->sendMessage(TextFormat::DARK_GREEN . $this->plugin->prefix . " " . $player->getName() . " was kicked");
                    }
                    if ($action === "ban") {
                        $player->setBanned(true);
                        $sender->sendMessage(TextFormat::DARK_GREEN . $this->plugin->prefix . " " . $player->getName() . " was banned");
                    }
                    if ($action === "deop") {
                        $player->setOp(false);
                        $player->sendMessage(TextFormat::DARK_RED . $this->plugin->prefix . " Admin Warning: " . $message);
                        $sender->sendMessage(TextFormat::DARK_GREEN . $this->plugin->prefix . " " . $player->getName() . " was deoped");
                    }
                } elseif ($this->plugin->getServer()->getPlayer($name)->isOnline()) {
                    $player->sendMessage(TextFormat::DARK_RED . $this->plugin->prefix . " Admin Warning: " . $message);
                    $sender->sendMessage(TextFormat::DARK_GREEN . $this->plugin->prefix . " " . $player->getName() . " was warned.");
                    $this->plugin->warnedPlayers->set($name);
                    $this->plugin->warnedPlayers->save();
                }
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Player not found");
        }
    }

}
