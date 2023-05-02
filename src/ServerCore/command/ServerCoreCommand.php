<?php

namespace ServerCore\command;

use pocketmine\command\Command;
use ServerCore\ServerCore as Main;

class ServerCoreCommand extends Command {
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    //
}