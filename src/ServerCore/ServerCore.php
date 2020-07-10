<?php

declare(strict_types=1);

namespace ServerCore;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\entity\Entity;
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
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as C;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use ServerCore\task\ScoreboardTask;

class ServerCore extends PluginBase implements Listener {

    public $config;
    public $deaths;
    public $faction;
    public $group;
    public $hideAll;
    public $kills;
    public $money;
    public $warnedPlayers;
    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . "ServerCore" . TextFormat::GRAY . "] ";

    private static $instance;

    private $scoreboards = [];

    protected $vanish = [];

    public function onEnable() : void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        @mkdir($this->getDataFolder());
        $this->saveResource("warnedPlayers.txt");
        $this->warnedPlayers = new Config($this->getDataFolder() . "warnedPlayers.txt", Config::ENUM);

        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveResource("config.yml");
        }

        $this->config = new Config($this->getDataFolder() . 'config.yml', Config::YAML, array(
            "disable-lava" => false,
            "disable-tnt" => false,
            "disable-bucket" => false
        ));

        $this->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($this, 0), (int)$this->getConfig()->get("update-interval"));

        if (!$this->config->get("disable-lava")) {
            $this->config->set("disable-lava", false);
        }

        if (!$this->config->get("disable-tnt")) {
            $this->config->set("disable-tnt", false);
        }

        if (!$this->config->get("disable-bucket")) {
            $this->config->set("disable-bucket", false);
        }

        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            $player = $p->getPlayer();
            $name = $player->getName();

            $this->faction = $this->getServer()-getPluginManager()->getPlugin("FactionsPro")->getPlayerFaction($player->getName());
            $this->group = $this->getServer()-getPluginManager()->getPlugin("PurePerms")->getUserDataMgr()->getGroup($player)->getName();
            $this->money = $this->getServer()-getPluginManager()->getPlugin("EconomyAPI")->myMoney($player->getName());
            $this->kills = $this->getServer()-getPluginManager()->getPlugin("KillChat")->getKills($name);
            $this->deaths = $this->getServer()-getPluginManager()->getPlugin("KillChat")->getDeaths($name);
        }
        $this->config->save();
        $this->warnedPlayers->save();
    }

    public function onDisable() : void {
        $this->warnedPlayers->save();
    }

    public function onLoad() : void {
        self::$instance = $this;
    }

    public static function getInstance() : ServerCore {
        return self::$instance;
    }

    public function new(Player $player, string $objectiveName, string $displayName) : void {
        if (isset($this->scoreboards[$player->getName()])) {
            $this->remove($player);
        }
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = "sidebar";
        $pk->objectiveName = $objectiveName;
        $pk->displayName = $displayName;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $player->sendDataPacket($pk);
        $this->scoreboards[$player->getName()] = $objectiveName;
    }

    public function remove(Player $player) : void {
        $objectiveName = $this->getObjectiveName($player);
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->sendDataPacket($pk);
        unset($this->scoreboards[$player->getName()]);
    }

    public function setLine(Player $player, int $score, string $message) : void {
        if (!isset($this->scoreboards[$player->getName()])) {
            $this->getLogger()->error("Cannot set a score to a player with no scoreboard");
            return;
        }
        if ($score > 15 || $score < 1) {
            $this->getLogger()->error("Score must be between the value of 1-15. $score out of range");
            return;
        }
        $objectiveName = $this->getObjectiveName($player);
        $entry = new ScorePacketEntry();
        $entry->objectiveName = $objectiveName;
        $entry->type = $entry::TYPE_FAKE_PLAYER;
        $entry->customName = $message;
        $entry->score = $score;
        $entry->scoreboardId = $score;
        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;
        $player->sendDataPacket($pk);
    }

    public function getObjectiveName(Player $player) : ?string {
        return isset($this->scoreboards[$player->getName()]) ? $this->scoreboards[$player->getName()] : null;
    }

    public function getMainItems(Player $player) {
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
        $this->getMainItems($player);
        $player->setGamemode(0);
        if ($player->isOP()) {
            $event->setJoinMessage(C::RED . $name . C::AQUA . " has entered the game");
        } else {
            $event->setJoinMessage("");
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        if (isset($this->scoreboards[($player = $event->getPlayer()->getName())])) {
            unset($this->scoreboards[$player]);
        }
        $player = $event->getPlayer();
        $name = $player->getName();
        if ($player->isOP()) {
            $event->setQuitMessage(C::YELLOW . $name . " has left the game");
        } else {
            $event->setQuitMessage("");
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        $name = $sender->getName();
        switch ($command->getName()) {
            case "heal":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("command.heal")) {
                        $sender->sendMessage(TextFormat::DARK_PURPLE . " You have been healed");
                        $sender->setHealth(20);
                    } else {
                        $sender->sendMessage(TextFormat::RESET . TextFormat::RED . "You do not have permission to run this command");
                    }
                } else {
                    $sender->sendMessage("Please use this command in-game");
                }
                break;
            case "feed":
            case "eat":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("command.feed")) {
                        if ($sender->getFood() == 20) {
                            $sender->sendMessage(TextFormat::RED . "You already have maxed food");
                        } else {
                            $sender->setFood(20);
                            $sender->sendMessage(TextFormat::GREEN . "You have been fed");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
                }
                break;
            case "warn":
                if ($sender->hasPermission("command.warn")) {
                    if ((!isset($args[0])) || (!isset($args[1]))) {
                        $sender->sendMessage(TextFormat::GREEN . "Please enter the player name and the message correctly.");
                        return true;
                    } else if ($this->getServer()->getPlayer($args[0]) instanceof Player && $this->getServer()->getPlayer($args[0])->isOnline()) {
                        if ($args[1] !== null) {
                            $name = strtolower(array_shift($args));
                            $player = $this->getServer()->getPlayer($name);
                            $msg = implode(' ', $args);
                            if ($this->warnedPlayers->exists($name)) {
                                $action = strtolower($this->getConfig()->get("Action"));
                                if ($action === "kick") {
                                    $player->kick($msg, false);
                                    $sender->sendMessage(TextFormat::DARK_GREEN . $this->prefix . " " . $player->getName() . " was kicked");
                                }
                                if ($action === "ban") {
                                    $player->setBanned(true);
                                    $sender->sendMessage(TextFormat::DARK_GREEN . $this->prefix . " " . $player->getName() . " was banned");
                                }
                                if ($action === "deop") {
                                    $player->setOp(false);
                                    $player->sendMessage(TextFormat::DARK_RED . $this->prefix . " Admin Warning: " . $msg);
                                    $sender->sendMessage(TextFormat::DARK_GREEN . $this->prefix . " " . $player->getName() . " was deoped");
                                }
                                return true;
                            } elseif ($this->getServer()->getPlayer($name)->isOnline()) {
                                $player->sendMessage(TextFormat::DARK_RED . $this->prefix . " Admin Warning: " . $msg);
                                $sender->sendMessage(TextFormat::DARK_GREEN . $this->prefix . " " . $player->getName() . " was warned.");
                                $this->warnedPlayers->set($name);
                                return true;
                            }
                        }
                    } else {
                        $sender->sendMessage(TextFormat::YELLOW . "Player does not exist or is not online.");
                        return true;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                    return true;
                }
            case "forgive":
                if ($sender->hasPermission("command.forgive")) {
                    if (isset($args[0])) {
                        if ($this->warnedPlayers->exists($args[0])) {
                            $this->warnedPlayers->remove($args[0]);
                            $player = $this->getServer()->getPlayer($args[0]);
                            $action = strtolower($this->warnedPlayers->get("Action"));
                            if ($action === "ban") {
                                $player->setBanned(false);
                            }
                            if ($action === "deop") {
                                $player->setOp(true);
                            }
                            $sender->sendMessage(TextFormat::BLUE . $args[0] . " has been forgiven.");
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Player has not been warned before.");
                        }
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                }
                break;
            case "ping":
            case "ms":
                if ($sender->hasPermission("command.ping")) {
                    if ($sender instanceof Player) {
                        $sender->sendMessage("Ping: " . $sender->getPing() . "ms");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                }
                break;
            case "fly":
            case "flight":
                if ($sender->hasPermission("command.fly")) {
                    if ($sender instanceof Player) {
                        if ($sender->getAllowFlight()) {
                            $sender->setFlying(false);
                            $sender->setAllowFlight(false);
                            $sender->sendMessage(TextFormat::RED . "You have disabled flight mode");
                        } else {
                            $sender->setAllowFlight(true);
                            $sender->sendMessage(TextFormat::GREEN . "You have enabled flight mode");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                }
                break;
            case "vanish":
            case "v":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("command.vanish")) {
                        if (empty($args[0])) {
                            if (!isset($this->vanish[$sender->getName()])) {
                                $this->vanish[$sender->getName()] = true;
                                $sender->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
                                $sender->setNameTagVisible(false);
                                $sender->sendMessage(TextFormat::GREEN . "You are now invisible");
                            } else if (isset($this->vanish[$sender->getName()])) {
                                unset($this->vanish[$sender->getName()]);
                                $sender->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
                                $sender->setNameTagVisible(true);
                                $sender->sendMessage(TextFormat::GREEN . "You are no longer invisible");
                            }
                        }
                        if ($this->getServer()->getPlayer($args[0])) {
                            $player = $this->getServer()->getPlayer($args[0]);
                            if (!isset($this->vanish[$player->getName()])) {
                                $this->vanish[$player->getName()] = true;
                                $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
                                $player->setNameTagVisible(false);
                                $player->sendMessage(TextFormat::GREEN . "You are now invisible");
                                $sender->sendMessage(TextFormat::GREEN . "You have vanished " . TextFormat.AQUA . $player->getName());
                            } else if (isset($this->vanish[$player->getName()])) {
                                unset($this->vanish[$player->getName()]);
                                $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
                                $player->setNameTagVisible(true);
                                $player->sendMessage(TextFormat::GREEN . "You are no longer invisible");
                                $sender->sendMessage(TextFormat::GREEN . "You have made " . TextFormat::AQUA . $player->getName() . TextFormat::GREEN . " visible");
                            }
                        } else {
                            $sender->sendMessage(TextFormat::RED . "Player not found");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
                }
                break;
            case "info":
                if ($sender instanceof Player) {
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
                } else {
                    $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
                    return true;
                }
            case "clear":
            case "clearinv":
                if ($sender instanceof Player) {
                    if ($sender->hasPermission("command.clear")) {
                        $sender->getArmorInventory()->clearAll();
                        $sender->getInventory()->clearAll();
                        $sender->sendMessage(TextFormat::RED . "Your inventory has been cleared");
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
                }
                break;
            case "lobby":
            case "hub":
                if ($sender->hasPermission("command.hub")) {
                    if ($sender instanceof Player) {
                        $spawnLocation = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                        $sender->teleport($spawnLocation);
                        $sender->sendMessage($this->prefix . "§aWelcome to spawn");
                        $this->getMainItems($sender);
                        $sender->setHealth(20);
                        $sender->setFood(20);
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Please use this command in-game");
                        return true;
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command");
                    return true;
                }
        }
        return true;
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $player->getInventory()->getItemInHand();
        $itemId = $item->getID();
        $block = $event->getBlock();
        if ($item->getName() == C::BOLD . C::GOLD . "Teleporter") {
            $this->teleportItems($player);
        } else if ($item->getName() == C::BOLD . C::GOLD . "Info") {
            $player->sendMessage($this->prefix . "§ause /info voter|youtuber|vip");
        } else if ($item->getName() == C::BOLD . C::RED . "Bakery") {
            $this->getMainItems($player);
        } else if ($item->getName() == C::BOLD . C::BLUE . "Light Wars") {
            $this->getMainItems($player);
            $x = 232;
            $y = 4;
            $z = 270;
            $player->teleport(new Vector3($x, $y, $z));
        } else if ($item->getName() == C::BOLD.C::RED."QSG") {
            $this->getMainItems($player);
            $x = 259;
            $y = 4;
            $z = 248;
            $player->teleport(new Vector3($x, $y, $z));
        } else if ($item->getCustomName() == "§ePlayer Hiding") {
            $player->getInventory()->remove(Item::get(280)->setCustomName("§ePlayers Hiding"));
            $player->getInventory()->setItem(6, Item::get(369)->setCustomName("§ePlayers Show"));
            $player->sendMessage($this->prefix . "§aAll players are now invisible!");
            $this->hideAll[] = $player;
            foreach ($this->getServer()->getOnlinePlayers() as $p2) {
                $player->hideplayer($p2);
            }
        } else if ($item->getCustomName() == "§ePlayers Show") {
            $player->getInventory()->remove(Item::get(369)->setCustomName("§ePlayers Show"));
            $player->getInventory()->setItem(6, Item::get(280)->setCustomName("§ePlayer Hiding"));
            $player->sendMessage($this->prefix . "§aAll players are now visible again!");
            unset($this->hideAll[array_search($player, $this->hideAll)]);
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
                if ($this->config->get("disable-lava") == true) {
                    $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                    $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                    $this->getLogger()->critical($name . " tried to use lava");
                }
                return true;
            case 11:
                if ($this->config->get("disable-lava") == true) {
                    $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                    $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                    $this->getLogger()->critical($name . " tried to use lava");
                }
                return true;
            case 46:
                if ($this->config->get("disable-tnt") == true) {
                    $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                    $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                    $this->getLogger()->critical($name . " tried to use TNT");
                }
                return true;
            case 325:
                if ($this->config->get("disable-bucket") == true) {
                    $player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 0));
                    $player->sendMessage($this->prefix . TextFormat::RED . " You are not allowed to use this item");
                    $this->getLogger()->critical($name . " tried to use bucket");
                }
                return true;
        }
    }

}
