<?php
/**
 * Created by LaithYT.
 * User: Laith98
 * Date: 2018/08/23
 * Time: 15:22
 */

namespace AntiCheat\Task;

use AntiCheat\Main;
use pocketmine\{Player, Server};
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\scheduler\Task;

class CheckPlayerTask extends Task
{
	/**@var string*/
	private $serverIp;
	/**@var string*/
	private $serverName;
	/**@var string*/
	private $path;
	/**@var config*/
	private $config;

    public function __construct(Main $plugin)
    {
		$this->plugin = $plugin;
		$this->config = new Config($this->plugin->getDataFolder() . "Config.yml", Config::YAML);
    }

    public function onRun(int $tick) {
		$server = Server::getInstance();
		foreach($server->getOnlinePlayers() as $sender){
			foreach ($this->plugin->hackScore as $playerId => $data){
				if($data["suspicion"] > 0){
					$this->plugin->hackScore[$playerId]["suspicion"]--;
				}
			}
			if(!$sender->isOnline() or !$sender->isAlive()){
				return;
			}
			$Logger = Server::getInstance()->getLogger();
			$LegitOPsYML = new Config($this->plugin->getDataFolder() . "OP.yml", Config::YAML);
			if ($sender->isOp()){
				if (!in_array($sender->getName(), $LegitOPsYML->get("LegitOPs"))){
					if ($sender instanceof Player){
						$sname = $sender->getName();
						$message  = "§b§lCrystalMC >> $sname used ForceOP!";
						$server->broadcastMessage(TF::AQUA . $message . "\n");
						$sender->getPlayer()->kick($this->config->get("forceop_ban"));
						$server->dispatchCommand(new \pocketmine\command\ConsoleCommandSender(), 'deop' .  '$sender->getName()');
						$c = new Config($this->plugin->getDataFolder() . "Ban.yml", Config::YAML);
						$c->set($sname, "t");
						$c->save();
					}
				}
			}
			
			/*if($sender->getGamemode() == 1){
				if(!$sender->isOp()){
					if(!$sender->hasPermission("pocketmine.command.gamemode")){
						if($sender instanceof Player){
							$sname = $sender->getName();
							$message  = "§b§lCrystalMC >> $sname used ForceGamemode!";
							$server->broadcastMessage(TF::AQUA . $message . "\n");
							$sender->getPlayer()->kick($this->config->get("gamemode_kick"));
						}
					}
				}
			}
			
			if($sender->getAllowFlight()){
				if(!$sender->hasPermission("fly.command")){
					if($sender instanceof Player){
						$sname = $sender->getName();
						$message  = "§b§lCrystalMC >> $sname used ForceGamemode!";
						$server->broadcastMessage(TF::AQUA . $message . "\n");
						$sender->getPlayer()->kick($this->config->get("fly_kick"));
					}
				}
			}*/
		}
	}
}