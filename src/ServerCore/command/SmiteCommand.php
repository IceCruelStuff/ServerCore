<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use ServerCore\ServerCore as Main;

class SmiteCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "smite",
            "Smites a player",
            "/smite <player>"
        );
        $this->setPermission("command.smite");
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
                $pk = new AddActorPacket();
                $pk->type = "minecraft:lightning_bolt";
                $pk->entityRuntimeId = Entity::$entityCount++;
                $pk->metadata = [];
                $pk->motion = null;
                $pk->yaw = $player->getYaw();
                $pk->pitch = $player->getPitch();
                $pk->position = new Vector3($player->getX(), $player->getY(), $player->getZ());
                Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
                $sound = new PlaySoundPacket();
                $sound->soundName = "ambient.weather.thunder";
                $sound->x = $player->getX();
                $sound->y = $player->getY();
                $sound->z = $player->getZ();
                $sound->volume = 1;
                $sound->pitch = 1;
                Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $sound);
            } else {
                $sender->sendMessage(TextFormat::RED . "Player not found");
            }
        } else {
            $pk = new AddActorPacket();
            $pk->type = "minecraft:lightning_bolt";
            $pk->entityRuntimeId = Entity::$entityCount++;
            $pk->metadata = [];
            $pk->motion = null;
            $pk->yaw = $sender->getYaw();
            $pk->pitch = $sender->getPitch();
            $pk->position = new Vector3($sender->getX(), $sender->getY(), $sender->getZ());
            Server::getInstance()->broadcastPacket($sender->getLevel()->getPlayers(), $pk);
            $sound = new PlaySoundPacket();
            $sound->soundName = "ambient.weather.thunder";
            $sound->x = $sender->getX();
            $sound->y = $sender->getY();
            $sound->z = $sender->getZ();
            $sound->volume = 1;
            $sound->pitch = 1;
            Server::getInstance()->broadcastPacket($sender->getLevel()->getPlayers(), $sound);
        }
    }

}
