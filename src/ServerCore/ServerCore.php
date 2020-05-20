<?php

namespace ServerCore;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\particle\FlameParticle;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as c;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;

class ServerCore extends PluginBase {

    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . "ServerCore" . TextFormat::GRAY . "] ";

    public function onEnable() : void {
        $this->getLogger()->notice(c::BOLD . c::DARK_AQUA . "(!)" . c::RESET.c::DARK_PURPLE . " ServerCore has been enabled");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new Scoreboard($this), 20);
    }

    public function onDisable() : void {
        $this->getLogger()->warning(c::BOLD.c::DARK_RED . "(!)" . c::RESET . c::RED . " ServerCore has been disabled");
    }

    public function mainItems(Player $player) {
        $player->getInventory()->clearAll();
        $player->getInventory()->setItem(0, Item::get(345)->setCustomName(C::BOLD . C::GOLD . "Teleporter"));
        $player->getInventory()->setItem(2, Item::get(339)->setCustomName(C::BOLD . C::GOLD . "Info"));
        $player->getInventory()->setItem(6, Item::get(280)->setCustomName("§eHide players"));
        $player->removeAllEffects();
        $player->getPlayer()->setHealth(20);
        $player->getPlayer()->setFood(20);
    }

    public function onDeath(PlayerDeathEvent $event) {
        $event->setDeathMessage("");
    }

    public function teleportItems(Player $player) {
        $player->getInventory()->clearAll();
        $player->getInventory()->setItem(3, Item::get(280)->setCustomName(C::BOLD . C::BLUE . "Light Wars"));
        $player->getInventory()->setItem(8, Item::get(341)->setCustomName(C::BOLD . C::RED . "Bakery"));
        $player->getInventory()->setItem(0, Item::get(267)->setCustomName(C::BOLD.C::RED."QSG"));
        $player->getInventory()->setItem(2, Item::get(19)->setCustomName(C::BOLD.C::AQUA."LSW"));
        $player->removeAllEffects();
        $player->getPlayer()->setHealth(20);
        $player->getPlayer()->setFood(20);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
        $player->setGamemode(0);
        $player->teleport($spawn);
        $event->setJoinMessage("");
        $this->mainItems($player);
        $player->setGamemode(0);
        if ($player->isOP()) {
            $event->setJoinMessage(C::RED . $name . C::AQUA . " has entered the game");
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $event->setQuitMessage("");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        $name = $sender->getName();
        switch ($command->getName()) {
            case "heal":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("healcommand")) {
                        $sender->sendMessage(c::DARK_PURPLE." You have been healed");
                        $sender->setHealth(20);
                    } elseif (!$sender->hasPermission("healcommand")) {
                        $sender->sendMessage(c::RESET.c::RED." You do not have permission to run this command");
                    }
                }
                break;
            case "ping":
                if ($sender instanceof Player) {
                    $sender->sendMessage("Ping time: " . $sender->getNetworkSession()->getPing() . "ms");
                }
                break;
            case "info":
                if (!empty($args[0])) {
                    if ($args[0] == "voter") {
                        $sender->sendMessage($this->prefix . " §aYou can vote for us");
                        return true;
                    }
                    if ($args[0] == "youtuber") {
                        $sender->sendMessage($this->prefix . "§aThe YouTuber rank is available from 200 subscribers!");
                        return true;
                    }
                    if ($args[0] == "vip") {
                        $sender->sendMessage($this->prefix . "§aYou can buy a rank from our server store");
                        return true;
                    }
                } else {
                    $sender->sendMessage($this->prefix. "§a/info voter|youtuber|vip");
                    return true;
                }
            case "hub":
                if ($sender instanceof Player) {
                    $spawnLocation = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                    $sender->teleport($spawnLocation);
                    $sender->sendMessage($this->prefix . "§aWelcome to spawn");
                    $this->mainItems($sender);
                    $sender->setHealth(20);
                    $sender->setFood(20);
                    return true;
                }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $player->getInventory()->getItemInHand();
        $itemId = $item->getID();
        $block = $event->getBlock();
        if ($item->getName() == C::BOLD . C::GOLD . "Teleporter") {
            $this->teleportItems($player);
        } elseif ($item->getName() == C::BOLD . C::GOLD . "Info") {
            $player->sendMessage($this->prefix . "§ause /info voter|youtuber|vip");
        } elseif ($item->getName() == C::BOLD . C::RED . "Bakery") {
            $this->mainItems($player);
        } elseif ($item->getName() == C::BOLD . C::BLUE . "Light Wars") {
            $this->mainItems($player);
            $x = 232;
            $y = 4;
            $z = 270;
            $player->teleport(new Vector3($x, $y, $z));
        } elseif ($item->getName() == C::BOLD.C::RED."QSG") {
            $this->mainItems($player);
            $x = 259;
            $y = 4;
            $z = 248;
            $player->teleport(new Vector3($x, $y, $z));
        } elseif ($item->getCustomName() == "§ePlayer Hiding") {
            $player->getInventory()->remove(Item::get(280)->setCustomName("§ePlayers Hiding"));
            $player->getInventory()->setItem(6, Item::get(369)->setCustomName("§ePlayers Show"));
            $player->sendMessage($this->prefix . "§aAll players are now invisible!");
            $this->hideall[] = $player;
            foreach ($this->getServer()->getOnlinePlayers() as $p2) {
                $player->hideplayer($p2);
            }
        } elseif ($item->getCustomName() == "§ePlayers Show") {
            $player->getInventory()->remove(Item::get(369)->setCustomName("§ePlayers Show"));
            $player->getInventory()->setItem(6, Item::get(280)->setCustomName("§ePlayer Hiding"));
            $player->sendMessage($this->prefix . "§aAll players are now visible again!");
            unset($this->hideall[array_search($player, $this->hideall)]);
            foreach ($this->getServer()->getOnlinePlayers() as $p2) {
                $player->showplayer($p2);
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if ($player->isOP()) {
            $event->setCancelled(false);
        } else {
            $event->setCancelled(true);
            $player->sendMessage($this->prefix . TextFormat::RED . " You cannot break anything here" . C::GRAY . "!");
       }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if ($player->isOP()) {
            $event->setCancelled(false);
        } else {
            $event->setCancelled(true);
            $player->sendMessage($this->prefix . TextFormat::RED . " You cannot place anything here" . C::GRAY . "!");
        }
    }

    public function onItemHeld(PlayerItemHeldEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $player->getInventory()->getItemInHand()->getID();
        switch ($item) {
            case 10:
                $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                $this->getLogger()->critical($name . " tried to use lava");
                return true;
            case 11:
                $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                $this->getLogger()->critical($name . " tried to use lava");
                return true;
            case 46:
                $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                $this->getLogger()->critical($name . " tried to use TNT");
                return true;
            case 325:
                $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                $this->getLogger()->critical($name . " tried to use bucket");
                return true;
        }
    }

}
