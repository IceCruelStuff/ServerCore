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
use ServerCore\command\ClearCommand;
use ServerCore\command\FeedCommand;
use ServerCore\command\FlyCommand;
use ServerCore\command\ForgiveCommand;
use ServerCore\command\HealCommand;
use ServerCore\command\HubCommand;
use ServerCore\command\InfoCommand;
use ServerCore\command\PingCommand;
use ServerCore\command\RulesCommand;
use ServerCore\command\VanishCommand;
use ServerCore\command\WarnCommand;
use ServerCore\task\ScoreboardTask;
use onebone\economyapi\EconomyAPI;

class ServerCore extends PluginBase implements Listener {

    public $config;
    public $deaths;
    public $faction;
    public $group;
    public $hideAll;
    public $kills;
    public $money;
    public $music;
    public $killChat;
    public $warnedPlayers;
    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . "ServerCore" . TextFormat::GRAY . "] ";

    private static $instance;

    private $scoreboards = [];

    public $vanish = [];

    public function onEnable() : void {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->registerCommands();

        @mkdir($this->getDataFolder());
        $this->saveResource("warnedPlayers.txt");
        $this->warnedPlayers = new Config($this->getDataFolder() . "warnedPlayers.txt", Config::ENUM);

        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveResource("config.yml");
        }

        $this->config = new Config($this->getDataFolder() . 'config.yml', Config::YAML, [
            "disable-lava" => false,
            "disable-tnt" => false,
            "disable-bucket" => false,
            "enable-music" => false,
            "enable-kill-chat" => false
        ]);

        $this->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($this, 0), (int) $this->getConfig()->get("update-interval"));

        if (!$this->config->get("disable-lava")) {
            $this->config->set("disable-lava", false);
        }

        if (!$this->config->get("disable-tnt")) {
            $this->config->set("disable-tnt", false);
        }

        if (!$this->config->get("disable-bucket")) {
            $this->config->set("disable-bucket", false);
        }

        if (!$this->config->get("enable-music")) {
            $this->config->set("enable-music", false);
        }

        if (!$this->config->get("enable-kill-chat")) {
            $this->config->set("enable-kill-chat", false);
        }

        if ($this->getServer()->getPluginManager()->getPlugin("ZMusicBox") !== null) {
            $this->music = $this->getServer()->getPluginManager()->getPlugin("ZMusicBox");
        } else {
            $this->getLogger()->info(TextFormat::RED . "ZMusicBox plugin is not installed. Some features may be disabled.");
        }
        if ($this->getServer()->getPluginManager()->getPlugin("FactionsPro") !== null) {
            $this->faction = $this->getServer()->getPluginManager()->getPlugin("FactionsPro");
        } else {
            $this->getLogger()->info(TextFormat::RED . 'FactionsPro plugin is not installed. Some features may be disabled.');
        }
        $this->group = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        $this->money = EconomyAPI::getInstance();
        // $this->money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($this->getServer()->getPluginManager()->getPlugin("KillChat") !== null) {
            $this->killChat = $this->getServer()->getPluginManager()->getPlugin("KillChat");
        } else {
            $this->getLogger()->info(TextFormat::RED . "KillChat plugin is not installed. Some features may be disabled.");
        }
        // $this->kills = $this->killChat->getKills($name);
        // $this->deaths = $this->killChat->getDeaths($name);

        /*foreach ($this->getServer()->getOnlinePlayers() as $player) {
            // $player = $p->getPlayer();
            $name = $player->getName();

            if ($this->config->get("enable-music") == true) {
                $this->music = $this->getServer()->getPluginManager()->getPlugin("ZMusicBox");
            } else {
                $this->music = null;
            }

            if ($this->getServer()->getPluginManager()->getPlugin("FactionsPro")) {
                $this->faction = $this->getServer()->getPluginManager()->getPlugin("FactionsPro")->getPlayerFaction($player->getName());
            } else {
                $this->getLogger()->info(TextFormat::RED . 'FactionsPro plugin is not installed. Some features may be disabled.');
            }

            if ($this->getServer()->getPluginManager()->getPlugin("PurePerms")) {
                $this->group = $this->getServer()->getPluginManager()->getPlugin("PurePerms")->getUserDataMgr()->getGroup($player)->getName();
            } else {
                $this->getLogger()->info(TextFormat::RED . 'PurePerms plugin is not installed. Some features may be disabled.');
            }

            if ($this->getServer()->getPluginManager()->getPlugin("EconomyAPI")) {
                $this->money = EconomyAPI::getInstance()->myMoney($player);
                //$this->money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player);
            } else {
                $this->getLogger()->info(TextFormat::RED . 'EconomyAPI plugin is not installed. Some features may be disabled.');
            }

            if ($this->getServer()->getPluginManager()->getPlugin("KillChat") !== null) {
                $api = $this->getServer()->getPluginManager()->getPlugin("KillChat");
                $this->kills = $api->getKills($name);
                $this->deaths = $api->getDeaths($name);
            } else {
                $this->kills = null;
                $this->deaths = null;
            }
        }*/
        $this->config->save();
        $this->warnedPlayers->save();
    }

    public function onDisable() : void {
        $this->warnedPlayers->save();
    }

    public function onLoad() : void {
        self::$instance = $this;
    }

    public function registerCommands() {
        $this->getServer()->getCommandMap()->register("clear", new ClearCommand($this));
        $this->getServer()->getCommandMap()->register("feed", new FeedCommand($this));
        $this->getServer()->getCommandMap()->register("fly", new FlyCommand($this));
        $this->getServer()->getCommandMap()->register("forgive", new ForgiveCommand($this));
        $this->getServer()->getCommandMap()->register("heal", new HealCommand($this));
        $this->getServer()->getCommandMap()->register("hub", new HubCommand($this));
        $this->getServer()->getCommandMap()->register("info", new InfoCommand($this));
        $this->getServer()->getCommandMap()->register("ping", new PingCommand($this));
        $this->getServer()->getCommandMap()->register("rules", new RulesCommand($this));
        $this->getServer()->getCommandMap()->register("vanish", new VanishCommand($this));
        $this->getServer()->getCommandMap()->register("warn", new WarnCommand($this));
    }

    public function getMoney() : ?EconomyAPI {
        return $this->money;
    }

    public function getGroup() {
        return $this->group;
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
        $player->getInventory()->setItem(4, Item::get(288)->setCustomName(C::BOLD . C::GRAY . "Enable Fly Mode"));
        $player->getInventory()->setItem(6, Item::get(280)->setCustomName(C::BOLD . C::YELLOW . "Hide players"));
        $player->getInventory()->setItem(8, Item::get(360)->setCustomName(C::BOLD . C::BLUE . "Next Song"));
        $player->removeAllEffects();
        $player->getPlayer()->setHealth(20);
        $player->getPlayer()->setFood(20);
    }

    public function onDeath(PlayerDeathEvent $event) {
        $event->setDeathMessage("");
    }

    public function teleportItems(Player $player) {
        $player->getInventory()->clearAll();
        $game1 = $this->config->get("Game-1-name");
        $game2 = $this->config->get("Game-2-name");
        $game3 = $this->config->get("Game-3-name");
        $player->getInventory()->setItem(4, Item::get(399)->setCustomName(C::BOLD . C::BLUE . $game1));
        $player->getInventory()->setItem(8, Item::get(355)->setCustomName(C::BOLD . C::RED . "Back"));
        $player->getInventory()->setItem(0, Item::get(378)->setCustomName(C::BOLD . C::GOLD . $game2));
        $player->getInventory()->setItem(2, Item::get(381)->setCustomName(C::BOLD . C::GREEN . $game3));
        $player->removeAllEffects();
        $player->getPlayer()->setHealth(20);
        $player->getPlayer()->setFood(20);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
        $x = $spawn->getX() + 0.5;
        $y = $spawn->getY() + 0.5;
        $z = $spawn->getZ() + 0.5;
        $player->setGamemode(2);
        $player->teleport(new Vector3($x, $y, $z));
        $this->getMainItems($player);
        if ($player->isOP()) {
            $event->setJoinMessage(C::RED . $name . C::AQUA . " has joined the game");
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

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $player->getInventory()->getItemInHand();
        $itemId = $item->getID();
        $block = $event->getBlock();
        $game1 = $this->config->get("Game-1-name");
        $game2 = $this->config->get("Game-2-name");
        $game3 = $this->config->get("Game-3-name");

        if ($item->getName() == C::BOLD . C::GOLD . "Teleporter") {
            $this->teleportItems($player);
        } else if ($item->getName() == C::BOLD . C::GOLD . "Info") {
            $player->sendMessage($this->prefix . TextFormat::GREEN . "Usage: /info <ranks|server>");
        } else if ($item->getName() == C::BOLD . C::RED . "Enable Fly Mode") {
            $player->setAllowFlight(true);
            $player->getInventory()->remove(Item::get(288)->setCustomName(C::BOLD . C::BLUE . "Enable Fly Mode"));
            $player->getInventory()->setItem(4, Item::get(288)->setCustomName(C::BOLD . C::BLUE . "Disable Fly Mode"));
        } else if ($item->getName() == C::BOLD . C::BLUE . "Disable Fly Mode") {
            $player->setAllowFlight(false);
            $player->getInventory()->remove(Item::get(288)->setCustomName(C::BOLD . C::BLUE . "Disable Fly Mode"));
            $player->getInventory()->setItem(4, Item::get(288)->setCustomName(C::BOLD . C::BLUE . "Enable Fly Mode"));
        } else if ($item->getName() == C::BOLD . C::RED . "Back") {
            $this->getMainItems($player);
        } else if ($item->getCustomName() == C::BOLD . C::GREEN . $game1) {
            $this->getMainItems($player);
            $x = $this->config->get("Game-1-X");
            $y = $this->config->get("Game-1-Y");
            $z = $this->config->get("Game-1-Z");
            $player->teleport(new Vector3($x, $y, $z));
        } else if ($item->getCustomName() == C::BOLD . C::GREEN . $game2) {
            $this->getMainItems($player);
            $x = $this->config->get("Game-2-X");
            $y = $this->config->get("Game-2-Y");
            $z = $this->config->get("Game-2-Z");
        } else if ($item->getCustomName() == C::BOLD . C::GREEN . $game3) {
            $this->getMainItems($player);
            $x = $this->config->get("Game-3-X");
            $y = $this->config->get("Game-3-Y");
            $z = $this->config->get("Game-3-Z");
        } else if ($item->getCustomName() == TextFormat::YELLOW . "Player Hiding") {
            $player->getInventory()->remove(Item::get(280)->setCustomName(TextFormat::YELLOW . "Players Hiding"));
            $player->getInventory()->setItem(6, Item::get(369)->setCustomName(TextFormat::YELLOW . "Players Show"));
            $player->sendMessage($this->prefix . TextFormat::GREEN . "All players are now invisible!");
            $this->hideAll[] = $player;
            foreach ($this->getServer()->getOnlinePlayers() as $p2) {
                $player->hideplayer($p2);
            }
        } else if ($item->getCustomName() == TextFormat::YELLOW . "Players Show") {
            $player->getInventory()->remove(Item::get(369)->setCustomName(TextFormat::YELLOW . "Players Show"));
            $player->getInventory()->setItem(6, Item::get(280)->setCustomName(TextFormat::YELLOW . "Player Hiding"));
            $player->sendMessage($this->prefix . TextFormat::GREEN . "All players are now visible!");
            unset($this->hideAll[array_search($player, $this->hideAll)]);
            foreach ($this->getServer()->getOnlinePlayers() as $p2) {
                $player->showplayer($p2);
            }
        } else if ($item->getCustomName() == C::BOLD . C::GREEN . "Next Song") {
            $this->music->startNewTask();
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
