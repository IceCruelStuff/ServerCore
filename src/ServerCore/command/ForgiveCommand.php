<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class ForgiveCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "forgive",
            "Forgives a player",
            "/forgive <player>"
        );
        $this->setPermission("command.forgive");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (isset($args[0])) {
            if ($this->plugin->warnedPlayers->exists($args[0])) {
                $this->plugin->warnedPlayers->remove($args[0]);
                $this->plugin->warnedPlayers->save();
                $player = $this->plugin->getServer()->getPlayer($args[0]);
                $action = strtolower($this->plugin->warnedPlayers->get("Action"));
                switch ($action) {
                    case "ban":
                        $player->setBanned(false);
                        break;
                    case "deop":
                        $player->setOp(true);
                        break;
                }
                $sender->sendMessage(TextFormat::BLUE . $args[0] . " has been forgiven.");
            } else {
                $sender->sendMessage(TextFormat::RED . "Player has not been warned before");
            }
        } else {
            $sender->sendMessage(TextFormat::RED . "Please enter a player name");
        }
    }

}
