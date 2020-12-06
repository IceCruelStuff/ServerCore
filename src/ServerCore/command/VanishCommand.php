<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Entity;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class VanishCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "vanish",
            "Makes you invisible",
            "/vanish",
            ["v"]
        );
        $this->setPermission("command.vanish");
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
                    if (!isset($this->plugin->vanish[$player->getName()])) {
                        $this->plugin->vanish[$player->getName()] = true;
                        $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
                        $player->setNameTagVisible(false);
                        $player->sendMessage(TextFormat::GREEN . "You are now invisible");
                        $sender->sendMessage(TextFormat::GREEN . "You have vanished " . TextFormat::AQUA . $player->getName());
                    } else {
                        unset($this->plugin->vanish[$player->getName()]);
                        $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
                        $player->setNameTagVisible(true);
                        $player->sendMessage(TextFormat::GREEN . "You are no longer invisible");
                        $sender->sendMessage(TextFormat::GREEN . "You have made " . TextFormat::AQUA . $player->getName() . TextFormat::GREEN . " visible");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                if (!isset($this->plugin->vanish[$sender->getName()])) {
                    $this->plugin->vanish[$sender->getName()] = true;
                    $sender->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
                    $sender->setNameTagVisible(false);
                    $sender->sendMessage(TextFormat::GREEN . "You are now invisible");
                } else {
                    unset($this->plugin->vanish[$sender->getName()]);
                    $sender->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
                    $sender->setNameTagVisible(true);
                    $sender->sendMessage(TextFormat::GREEN . "You are no longer invisible");
                }
            }
        } else {
            if (isset($args[0])) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    if (!isset($this->plugin->vanish[$player->getName()])) {
                        $this->plugin->vanish[$player->getName()] = true;
                        $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
                        $player->setNameTagVisible(false);
                        $player->sendMessage(TextFormat::GREEN . "You are now invisible");
                        $sender->sendMessage(TextFormat::GREEN . "You have vanished " . TextFormat::AQUA . $player->getName());
                    } else {
                        unset($this->plugin->vanish[$player->getName()]);
                        $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
                        $player->setNameTagVisible(true);
                        $player->sendMessage(TextFormat::GREEN . "You are no longer invisible");
                        $sender->sendMessage(TextFormat::GREEN . "You have made " . TextFormat::AQUA . $player->getName() . TextFormat::GREEN . " visible");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "Please enter a player name")
            }
        }
    }

}
