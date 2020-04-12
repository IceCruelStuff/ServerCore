<?php

namespace ServerCore;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as c;

class ServerCore extends PluginBase{

    public function onEnable() : void {
        $this->getLogger()->notice(c::BOLD.c::DARK_AQUA."(!)".c::RESET.c::DARK_PURPLE." ServerCore has been enabled");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch($command->getName()) {
            case "heal":
                if($sender->hasPermission("healcommand")){
                    $sender->sendMessage(c::BOLD.c::DARK_AQUA."(!)",c::DARK_PURPLE." You have been healed");
                    $sender->setHealth(20.0);
                } elseif(!$sender->hasPermission("healcommand")){
                    $sender->sendMessage(c::BOLD.c::DARK_RED."(!)".c::RESET.c::RED." You do not have permission to run this command");
                }
        }
        return false;
    }

    public function onDisable() : void {
        $this->getLogger()->warning(c::BOLD.c::DARK_RED."(!)".c::RESET.c::RED." ServerCore has been disabled");
    }
}
