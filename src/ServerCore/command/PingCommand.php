<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class PingCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "ping",
            "Returns player ping",
            "/ping",
            ["ms"]
        );
        $this->setPermission("command.ping");
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
                $sender->sendMessage("Ping: " . $player->getPing() . "ms");
            } else {
                $sender->sendMessage(TextFormat::RED . "Player not found");
            }
        } else {
            if ($sender instanceof Player) {
                $sender->sendMessage("Ping: " . $sender->getPing() . "ms");
            } else {
                $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
            }
        }
    }

}
