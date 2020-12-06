<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class InfoCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "info",
            "Info command",
            "/info <ranks|server>"
        );
        $this->setPermission("command.info");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        $server = $this->plugin->config->get("info-server-command");
        $ranks = $this->plugin->config->get("info-ranks-command");
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                if ($args[0] === "ranks") {
                    $sender->sendMessage($this->plugin->prefix . $ranks);
                } elseif ($args[0] === "server") {
                    $sender->sendMessage($this->plugin->prefix . $server);
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Usage: /info <ranks|server>");
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
        }
    }

}
