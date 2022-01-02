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
use pocketmine\event\player\PlayerCommandPreprocessEvent;
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
use pocketmine\permission\Permission;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use ServerCore\command\ClearCommand;
use ServerCore\command\EnableCommand;
use ServerCore\command\DisableCommand;
use ServerCore\command\FeedCommand;
use ServerCore\command\FlyCommand;
use ServerCore\command\ForgiveCommand;
use ServerCore\command\HealCommand;
use ServerCore\command\HubCommand;
use ServerCore\command\InfoCommand;
use ServerCore\command\MuteCommand;
use ServerCore\command\PingCommand;
use ServerCore\command\PositionCommand;
use ServerCore\command\RulesCommand;
use ServerCore\command\SmiteCommand;
use ServerCore\command\UnmuteCommand;
use ServerCore\command\VanishCommand;
use ServerCore\command\VisionCommand;
use ServerCore\command\WarnCommand;
use ServerCore\scoreboard\Scoreboard;
use ServerCore\task\ScoreboardTask;
use onebone\economyapi\EconomyAPI;
use jojoe77777\FormAPI\SimpleForm;

class ServerCore extends PluginBase implements Listener {

    public $config;
    public $deaths = null;
    public $faction = null;
    public $group = null;
    public $hideAll;
    public $kills = null;
    public $money = null;
    public $music = null;
    public $killChat = null;
    public $warnedPlayers;
    public $mutedPlayers;
    public $prefix;

    private static $instance;

    private $scoreboards = [];

    public $vanish = [];
    public $vision = [];
    public $scoreboard;

    public function onEnable() : void {
        self::$instance = $this;
        $this->scoreboard = new Scoreboard();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->registerPermissions();
        $this->registerCommands();

        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . 'players/');
        $this->saveResource("warnedPlayers.txt");
        $this->warnedPlayers = new Config($this->getDataFolder() . "warnedPlayers.txt", Config::ENUM);

        $this->saveResource("mutedPlayers.txt");
        $this->mutedPlayers = new Config($this->getDataFolder() . "mutedPlayers.txt", Config::ENUM);

        if (!file_exists($this->getDataFolder() . "config.yml")) {
            $this->saveResource("config.yml");
        }

        $this->config = new Config($this->getDataFolder() . 'config.yml', Config::YAML, [
            "enable-ui" => true,
        ]);

        $this->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($this, 0), (int) $this->getConfig()->get("update-interval"));

        if (!$this->config->get("enable-ui")) {
            $this->config->set("enable-ui", true);
        }

        $this->checkPlugins();
        $this->prefix = $this->config->get("prefix");
        $this->config->save();
        $this->warnedPlayers->save();
    }

    public function onDisable() : void {
        $this->warnedPlayers->save();
    }

    public function checkPlugins() {
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
        if ($this->getServer()->getPluginManager()->getPlugin("KillChat") !== null) {
            $this->killChat = $this->getServer()->getPluginManager()->getPlugin("KillChat");
        } else {
            $this->getLogger()->info(TextFormat::RED . "KillChat plugin is not installed. Some features may be disabled.");
        }
    }

    public function registerPermissions() {
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.warn", "Allows player to use /warn", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.vanish", "Allows player to use /vanish", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.vision", "Allows player to use /vision", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.forgive", "Allows player to use /forgive", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.heal", "Allows player to use /heal", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.clear", "Allows player to use /clear or /clearinv", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.fly", "Allows player to use /fly", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.feed", "Allows player to use /feed", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.smite", "Allows player to use /smite", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.info", "Allows player to use /info", Permission::DEFAULT_TRUE));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.rules", "Allows player to use /rules", Permission::DEFAULT_TRUE));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.ping", "Allows player to use /ping", Permission::DEFAULT_TRUE));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.hub", "Allows player to use /hub or /lobby", Permission::DEFAULT_TRUE));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.mute", "Allows player to use /mute", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.unmute", "Allows player to use /unmute", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.pos", "Allows player to use /position", Permission::DEFAULT_TRUE));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.enable", "Allows player to use /enable", Permission::DEFAULT_OP));
        $this->getServer()->getPluginManager()->addPermission(new Permission("command.disable", "Allows player to use /disable", Permission::DEFAULT_OP));
    }

    public function registerCommands() {
        $this->getServer()->getCommandMap()->register("clear", new ClearCommand($this));
        $this->getServer()->getCommandMap()->register("feed", new FeedCommand($this));
        $this->getServer()->getCommandMap()->register("fly", new FlyCommand($this));
        $this->getServer()->getCommandMap()->register("forgive", new ForgiveCommand($this));
        $this->getServer()->getCommandMap()->register("heal", new HealCommand($this));
        $this->getServer()->getCommandMap()->register("hub", new HubCommand($this));
        $this->getServer()->getCommandMap()->register("info", new InfoCommand($this));
        $this->getServer()->getCommandMap()->register("mute", new MuteCommand($this));
        $this->getServer()->getCommandMap()->register("ping", new PingCommand($this));
        $this->getServer()->getCommandMap()->register("position", new PositionCommand($this));
        $this->getServer()->getCommandMap()->register("rules", new RulesCommand($this));
        $this->getServer()->getCommandMap()->register("smite", new SmiteCommand($this));
        $this->getServer()->getCommandMap()->register("unmute", new UnmuteCommand($this));
        $this->getServer()->getCommandMap()->register("vanish", new VanishCommand($this));
        $this->getServer()->getCommandMap()->register("vision", new VisionCommand($this));
        $this->getServer()->getCommandMap()->register("warn", new WarnCommand($this));
        $this->getServer()->getCommandMap()->register("enable", new EnableCommand($this));
        $this->getServer()->getCommandMap()->register("disable", new DisableCommand($this));
    }

    public function getGroup() {
        return $this->group;
    }

    public static function getInstance() : self {
        return self::$instance;
    }

    public function new(Player $player, string $objectiveName, string $displayName) : void {
        $this->scoreboard->new($player, $objectiveName, $displayName);
    }

    public function remove(Player $player) : void {
        $this->scoreboard->remove($player);
    }

    public function setLine(Player $player, int $score, string $message) : void {
        $this->scoreboard->setLine($player, $score, $message);
    }

    public function getObjectiveName(Player $player) : ?string {
        return $this->scoreboard->getObjectiveName($player);
    }

    public function giveMainItems(Player $player) {
        $player->getInventory()->clearAll();
        $player->getInventory()->setItem(0, Item::get(Item::COMPASS)->setCustomName(TextFormat::BOLD . TextFormat::GOLD . "Teleporter"));
        $player->getInventory()->setItem(2, Item::get(Item::PAPER)->setCustomName(TextFormat::BOLD . TextFormat::GOLD . "Info"));
        $player->getInventory()->setItem(4, Item::get(Item::FEATHER)->setCustomName(TextFormat::BOLD . TextFormat::GRAY . "Enable Fly Mode"));
        $player->getInventory()->setItem(6, Item::get(Item::STICK)->setCustomName(TextFormat::BOLD . TextFormat::YELLOW . "Hide players"));
        $player->getInventory()->setItem(8, Item::get(Item::MELON)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . "Next Song"));
        $player->removeAllEffects();
        $player->setHealth($player->getMaxHealth());
        $player->setFood($player->getMaxFood());
    }

    public function giveUserInterfaceItems(Player $player) {
        $player->getInventory()->clearAll();
        $player->getInventory()->setItem(0, Item::get(Item::COMPASS)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . "Menu"));
    }

    public function giveTeleportItems(Player $player) {
        $player->getInventory()->clearAll();
        $game1 = $this->config->get("Game-1-name");
        $game2 = $this->config->get("Game-2-name");
        $game3 = $this->config->get("Game-3-name");
        $player->getInventory()->setItem(4, Item::get(Item::NETHER_STAR)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . $game1));
        $player->getInventory()->setItem(8, Item::get(Item::BED)->setCustomName(TextFormat::BOLD . TextFormat::RED . "Back"));
        $player->getInventory()->setItem(0, Item::get(Item::MAGMA_CREAM)->setCustomName(TextFormat::BOLD . TextFormat::GOLD . $game2));
        $player->getInventory()->setItem(2, Item::get(Item::ENDER_EYE)->setCustomName(TextFormat::BOLD . TextFormat::GREEN . $game3));
        $player->removeAllEffects();
        $player->setHealth($player->getMaxHealth());
        $player->setFood($player->getMaxFood());
    }

    public function sendGames(Player $sender) {
        $games = $this->config->get("games");
        $form = new SimpleForm(function(Player $player, $data = null) {
            if ($data === null) {
                return;
            }

            if ($data >= 0 && $data <= count($games) - 1) {
                $game = $games[$data];
                $player->teleport($game["x"], $game["y"], $game["z"]);
            }
            if ($data === count($games)) {
                $this->sendDefaultForm($player);
            }
        });
        $form->setTitle($this->config->get("game-form-title"));
        foreach ($games as $game) {
            $form->addButton($game["name"]);
        }
        $form->addButton("Back");
        $form->sendToPlayer($sender);
    }

    public function sendInfo(Player $sender) {
        
        $form = new SimpleForm(function(Player $player, $data = null) {
            if ($data === null) {
                return;
            }

            $infoPages = $this->config->get("info");
            if ($data >= 0 && $data <= count($infoPages) - 1) {
                $dataForm = new SimpleForm();
                $dataForm->setTitle($infoPages[$data]["name"]);
                $dataForm->setContent($infoPages[$data]["content"]);
                $dataForm->sendToPlayer($player);
            }
            if ($data === count($infoPages)) {
                $this->sendDefaultForm($player);
            }
        });
        $form->setTitle($this->config->get("info-form-title"));
        foreach ($this->config->get("info") as $page) {
            $form->addButton($page["name"]);
        }
        $form->addButton("Back");
        $form->sendToPlayer($sender);
    }

    public function sendMusic(Player $sender) {
        $form = new SimpleForm(function(Player $player, $data = null) {
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    if ($this->music !== null) {
                        $this->music->startTask();
                    } else {
                        $sender->sendMessage(TextFormat::RED . "Music is not enabled on this server");
                    }
                    break;
                case 1:
                    $this->sendDefaultForm($player);
                    break;
                default:
                    break;
            }
        });
        $form->setTitle($this->config->get("music-form-title"));
        $form->addButton("Play Music");
        $form->addButton("Back");
        $form->sendToPlayer($sender);
    }

    public function sendOptions(Player $sender) {
        $form = new SimpleForm(function(Player $player, $data = null) {
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $player->setAllowFlight(true);
                    $player->sendMessage(TextFormat::GREEN . "Fly mode has been enabled");
                    break;
                case 1:
                    $player->setAllowFlight(false);
                    $player->sendMessage(TextFormat::RED . "Fly mode has been disabled");
                    break;
                case 2:
                    foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
                        $player->hidePlayer($onlinePlayer);
                    }
                    $player->sendMessage(TextFormat::BLUE . "All players are now invisible");
                    break;
                case 3:
                    foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
                        $player->hidePlayer($onlinePlayer);
                    }
                    $player->sendMessage(TextFormat::BLUE . "All players are no longer invisible");
                    break;
                case 4:
                    $this->sendDefaultForm($player);
            }
        });
        $form->setTitle($this->config->get("options-form-title"));
        $form->addButton(TextFormat::GREEN . TextFormat::BOLD . "Enable Fly Mode");
        $form->addButton(TextFormat::RED . TextFormat::BOLD . "Disable Fly Mode");
        $form->addButton(TextFormat::BLUE . TextFormat::BOLD . "Hide Players");
        $form->addButton(TextFormat::BLUE . TextFormat::BOLD . "Show Players");
        $form->addButton("Back");
        $form->sendToPlayer($sender);
    }

    public function sendDefaultForm($player) {
        $form = new SimpleForm(function (Player $user, $data = null) {
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $this->sendGames($user);
                    break;
                case 1:
                    $this->sendInfo($user);
                    break;
                case 2:
                    $this->sendMusic($user);
                    break;
                case 3:
                    $this->sendOptions($user);
                    break;
                case 4:
                    break;
                default:
                    break;
            }
        });
        $form->setTitle(TextFormat::AQUA . "Menu");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Games");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Info");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Music");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Options");
        $form->addButton(TextFormat::LIGHT_PURPLE . "Close");
        $form->sendToPlayer($player);
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $player->getInventory()->getItemInHand();
        $itemId = $item->getId();
        $block = $event->getBlock();
        $game1 = $this->config->get("Game-1-name");
        $game2 = $this->config->get("Game-2-name");
        $game3 = $this->config->get("Game-3-name");

        if ($this->config->get("enable-ui")) {
            if ($item->getName() == TextFormat::BOLD . TextFormat::BLUE . "Menu") {
                $this->sendDefaultForm($player);
            }
        } else {
            switch ($item->getName()) {
                case TextFormat::BOLD . TextFormat::GOLD . "Teleporter":
                    $this->giveTeleportItems($player);
                    break;
                case TextFormat::BOLD . TextFormat::GOLD . "Info":
                    $player->sendMessage($this->prefix . TextFormat::GREEN . "Usage: /info <ranks|server>");
                    break;
                case TextFormat::BOLD . TextFormat::RED . "Enable Fly Mode":
                    $player->setAllowFlight(true);
                    $player->getInventory()->remove(Item::get(Item::FEATHER)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . "Enable Fly Mode"));
                    $player->getInventory()->setItem(4, Item::get(Item::FEATHER)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . "Disable Fly Mode"));
                    break;
                case TextFormat::BOLD . TextFormat::BLUE . "Disable Fly Mode":
                    $player->setAllowFlight(false);
                    $player->getInventory()->remove(Item::get(Item::FEATHER)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . "Disable Fly Mode"));
                    $player->getInventory()->setItem(4, Item::get(Item::FEATHER)->setCustomName(TextFormat::BOLD . TextFormat::BLUE . "Enable Fly Mode"));
                    break;
                case TextFormat::BOLD . TextFormat::RED . "Back":
                    $this->giveMainItems($player);
                    break;
                case TextFormat::BOLD . TextFormat::GREEN . $game1:
                    $this->giveMainItems($player);
                    $x = $this->config->get("Game-1-X");
                    $y = $this->config->get("Game-1-Y");
                    $z = $this->config->get("Game-1-Z");
                    $player->teleport(new Vector3($x, $y, $z));
                    break;
                case TextFormat::BOLD . TextFormat::GREEN . $game2:
                    $this->giveMainItems($player);
                    $x = $this->config->get("Game-2-X");
                    $y = $this->config->get("Game-2-Y");
                    $z = $this->config->get("Game-2-Z");
                    break;
                case TextFormat::BOLD . TextFormat::GREEN . $game3:
                    $this->giveMainItems($player);
                    $x = $this->config->get("Game-3-X");
                    $y = $this->config->get("Game-3-Y");
                    $z = $this->config->get("Game-3-Z");
                    break;
                case TextFormat::YELLOW . "Hide Players":
                    $player->getInventory()->remove(Item::get(Item::STICK)->setCustomName(TextFormat::YELLOW . "Hide Players"));
                    $player->getInventory()->setItem(6, Item::get(Item::BLAZE_ROD)->setCustomName(TextFormat::YELLOW . "Show Players"));
                    $this->hideAll[] = $player;
                    foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
                        $player->hidePlayer($onlinePlayer);
                    }
                    $player->sendMessage($this->prefix . TextFormat::GREEN . "All players are now invisible!");
                    break;
                case TextFormat::YELLOW . "Show Players":
                    $player->getInventory()->remove(Item::get(Item::BLAZE_ROD)->setCustomName(TextFormat::YELLOW . "Show Players"));
                    $player->getInventory()->setItem(6, Item::get(Item::STICK)->setCustomName(TextFormat::YELLOW . "Hide Players"));
                    unset($this->hideAll[array_search($player, $this->hideAll)]);
                    foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
                        $player->showPlayer($onlinePlayer);
                    }
                    $player->sendMessage($this->prefix . TextFormat::GREEN . "All players are now visible!");
                    break;
                case TextFormat::BOLD . TextFormat::GREEN . "Next Song":
                    $this->music->startTask();
                    break;
            }
        }
    }

    public function onDeath(PlayerDeathEvent $event) {
        $event->setDeathMessage("");
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
        if ($this->config->get("enable-ui")) {
            $this->giveUserInterfaceItems($player);
        } else {
            $this->giveMainItems($player);
        }
        if ($player->isOp()) {
            if ($this->config->get("broadcast-admin-joins")) {
                $message = TextFormat::RED . $name . TextFormat::AQUA . " has joined the game";
                if ($this->config->get("join-admin-tag")) {
                    $message = $this->config->get("admin-tag") . " " . TextFormat::RED . $name . TextFormat::AQUA . " has joined the game";
                }
                $event->setJoinMessage($message);
            } else {
                $event->setJoinMessage("");
            }
        } else {
            if ($this->config->get("broadcast-player-joins")) {
                $event->setJoinMessage(TextFormat::RED . $name . TextFormat::AQUA . " has joined the game");
            } else {
                $event->setJoinMessage("");
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        if (isset($this->scoreboards[($player = $event->getPlayer()->getName())])) {
            unset($this->scoreboards[$player]);
        }
        $player = $event->getPlayer();
        $name = $player->getName();
        if ($player->isOp()) {
            $event->setQuitMessage(TextFormat::YELLOW . $name . " has left the game");
        } else {
            $event->setQuitMessage("");
        }
    }

    public function onPlayerChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        if ($this->mutedPlayers->exists($player->getName())) {
            $player->sendMessage(TextFormat::RED . "You are muted");
            $event->setCancelled();
        }
    }

    public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) {
        $dbPath = $this->getServer()->getOnlineMode() ? $event->getPlayer()->getXuid() : strtolower($event->getPlayer()->getName());
        $config = new Config($this->getDataFolder() . 'players/' . $dbPath, Config::YAML, [
            "break" => true,
            "place" => true,
            "chat" => true
        ]);
        if (!$config->get("chat")) {
            $event->setCancelled();
        }
    }
    
    public function onBlockBreak(BlockBreakEvent $event) {
        $dbPath = $this->getServer()->getOnlineMode() ? $event->getPlayer()->getXuid() : strtolower($event->getPlayer()->getName());
        $config = new Config($this->getDataFolder() . 'players/' . $dbPath, Config::YAML, [
            "break" => true,
            "place" => true,
            "chat" => true
        ]);
        if (!$config->get("break")) {
            $event->setCancelled();
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $dbPath = $this->getServer()->getOnlineMode() ? $event->getPlayer()->getXuid() : strtolower($event->getPlayer()->getName());
        $config = new Config($this->getDataFolder() . 'players/' . $dbPath, Config::YAML, [
            "break" => true,
            "place" => true,
            "chat" => true
        ]);
        if (!$config->get("place")) {
            $event->setCancelled();
        }
    }

}
