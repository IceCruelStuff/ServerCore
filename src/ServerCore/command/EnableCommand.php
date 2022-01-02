<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class EnableCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "enable",
            "Enables certain features for a player",
            "/enable <feature> <player>"
        );
        $this->setPermission("command.enable");
    }

    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage($this->getUsage());
            return;
        }

        $player = $this->plugin->getServer()->getPlayer($args[1]);
        if ($player === null) {
            $sender->sendMessage(TextFormat::RED . "Player not found");
            return;
        }

        $dbPath = $this->plugin->getServer()->getOnlineMode() ? $player->getXuid() : strtolower($player->getName());
        $config = new Config($this->plugin->getDataFolder() . 'players/' . $dbPath, Config::YAML, [
            "break" => true,
            "place" => true,
            "chat" => true
        ]);

        switch (strtolower($args[0])) {
            case "block":
            case "blocks":
                $config->set('break', true);
                $config->set('place', true);
                $config->save();
                $sender->sendMessage(TextFormat::GREEN . "Block placing and breaking has been enabled for " . $player->getName());
                break;
            case "place":
                $config->set('place', true);
                $config->save();
                $sender->sendMessage(TextFormat::GREEN . "Block placement has been enabled for " . $player->getName());
                break;
            case "break":
                $config->set('break', true);
                $config->save();
                $sender->sendMessage(TextFormat::GREEN . "Block breaking has been enabled for " . $player->getName());
                break;
            case "chat":
                $config->set('chat', true);
                $config->save();
                $sender->sendMessage(TextFormat::GREEN . "Chat has been enabled for " . $player->getName());
                break;
        }
    }

}
