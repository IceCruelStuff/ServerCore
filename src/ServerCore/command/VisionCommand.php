<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use ServerCore\ServerCore as Main;

class VisionCommand extends Command implements PluginIdentifiableCommand {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(
            "vision",
            "Gives you night vision",
            "/vision <player>"
        );
        $this->setPermission("command.vision");
    }

    public function execute(CommandSender $sender, string $label, array $args) {
        if (!$this->testPermission($sender)) {
            return;
        }

        if ($sender instanceof Player) {
            if (isset($args[0])) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    if (isset($this->plugin->vision[$player])) {
                        $player->removeEffect(Effect::NIGHT_VISION);
                        unset($this->plugin->vision[$player]);
                        $player->sendMessage(TextFormat::RED . "Night Vision has been turned off");
                        $sender->sendMessage(TextFormat::GREEN . "You turned off Night Vision for " . $player->getName());
                    } else {
                        $player->addEffect(new EffectInstance(Effect::getEffectByName("NIGHT_VISION"), INT32_MAX, 1, false));
                        $this->plugin->vision[] = $player;
                        $player->sendMessage(TextFormat::GREEN . "Night Vision has been turned on");
                        $sender->sendMessage(TextFormat::GREEN . "You turned off Night Vision for " . $player->getName());
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Player not found");
                }
            } else {
                if (isset($this->plugin->vision[$sender])) {
                    $sender->removeEffect(Effect::NIGHT_VISION);
                    unset($this->plugin->vision[$sender]);
                    $sender->sendMessage(TextFormat::RED . "Night Vision has been turned off");
                } else {
                    $sender->addEffect(new EffectInstance(Effect::getEffectByName("NIGHT_VISION"), INT32_MAX, 1, false));
                    $this->plugin->vision[] = $sender;
                    $sender->sendMessage(TextFormat::GREEN . "Night Vision has been turned on");
                }
            }
        } else {
            if (isset($args[0])) {
                if ($this->plugin->getServer()->getPlayer($args[0])) {
                    $player = $this->plugin->getServer()->getPlayer($args[0]);
                    if (isset($this->plugin->vision[$player])) {
                        $player->removeEffect(Effect::NIGHT_VISION);
                        unset($this->plugin->vision[$player]);
                        $player->sendMessage(TextFormat::RED . "Night Vision has been turned off");
                        $sender->sendMessage(TextFormat::GREEN . "You turned off Night Vision for " . $player->getName());
                    } else {
                        $player->addEffect(new EffectInstance(Effect::getEffectByName("NIGHT_VISION"), INT32_MAX, 1, false));
                        $this->plugin->vision[] = $player;
                        $player->sendMessage(TextFormat::GREEN . "Night Vision has been turned on");
                        $sender->sendMessage(TextFormat::GREEN . "You turned on Night Vision for " . $player->getName());
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
