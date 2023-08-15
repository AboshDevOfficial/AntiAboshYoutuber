<?php

namespace AntiCheat;

use AntiCheat\Task\CheckPlayerTask;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\Command;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\Plugin;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;

class Main extends PluginBase implements Listener
{
    protected $spamplayers = [];
	private $banapi;
	public $Config;
	public $PlayerObservers = array();
	public $Logger;
	
	// clientid
	public $clientId;
	public $clientData = [];
	
	/**@var array*/
	public $hackScore = array();	
	
	/** @var AntiHack */
	private static $instance;
	protected $inAirTicks = 6;
	public $banclient = "§cAniti ToolBox And Block Launcher";
	
	const PLAYER_MAX_SPEED = 19;
	
	/**@var AntiHack*/
	private $plugin;
	/**@var array*/
	private $flyPlayers = array();
	/**@var array*/
	private $movePlayers = array();
	
	public static function getInstance() {
		return self::$instance;
	}
	
	
    public function onEnable(): void
    {
		self::getInstance();
		@mkdir($this->getDataFolder());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->banapi = $this->getServer()->getPluginManager()->getPlugin("BanAPI");
        $this->getScheduler()->scheduleRepeatingTask(new CheckPlayerTask($this), 20);
		$this->saveResource("OP.yml");
		$this->saveResource("Config.yml");
		$this->saveResource("Ban.yml");
    }
	
	public function getAirTick(){
		return $this->inAirTicks;
	}
	
	 
  
  /** بلوك لانشر وتول بوكس*/
	public function onReceive(DataPacketReceiveEvent $e) {
		$bc = $e->getPacket();
		$sender = $e->getPlayer();
        if ($bc instanceof LoginPacket) {
			if ($sender instanceof Player){
            if ($bc->clientId === 0) {
                $e->setCancelled(true);
                $sender->getPlayer()->close($this->banclient);
            }
			if ($bc->clientId === 1) {
                $e->setCancelled(true);
                $e->getPlayer()->close("No BlockLauncher");
            }
		}
	}
}
	
	/** حماية فورس اوب وحماية من بلوك لانشر وحماية من تول بوكس بواسطة ليث*/
	
	public function onJoin(PlayerJoinEvent $ev){
        $player = $ev->getPlayer();
		//if (!$player instanceof Player or !$ev->isCancelled())return true;
		$server = Server::getInstance();
		$Logger = Server::getInstance()->getLogger();
		$LegitOPsYML = new Config($this->getDataFolder() . "OP.yml", Config::YAML);
		$cfg = new Config($this->getDataFolder() . "Config.yml", Config::YAML);
		$c = new Config($this->getDataFolder() . "Ban.yml", Config::YAML);
		
		/*if(!$c->get($player->getName())){
			$c->set("$player->getName()", "f");
			$c->save();
		}
		
		if($c->get($player->getName()) == "t"){
			$player->close("You are Banned");
		}*/
		
		if ($player->isOp()){
        if (!in_array($player->getName(), $LegitOPsYML->get("LegitOPs"))){
          if ($player instanceof Player)
          {
            $sname = $player->getName();
            $message  = "§b§lCrystalMC >> $sname used ForceOP!";
            $server->broadcastMessage(TF::AQUA . $message . "\n");
			$this->getServer()->dispatchCommand(new ConsoleCommandSender, "deop $sname");
            $player->getPlayer()->kick("§b§lCrystalMC > ForceOP detected!");
          }
        }
      }
		$server->broadcastMessage(TF::RED . $player->getName() . " Join, " . TF::RED . " ClientID: ". TF::AQUA .$player->getClientId());
	}
	
	public function onEntityMotion(EntityMotionEvent $event){
		$player = $event->getEntity();
		if($player instanceof Player){
			if(isset($this->movePlayers[$player->getId()])){
				$this->movePlayers[$player->getId()]["freeze"] = 2;
			}
		}
}
	/** حماية من زيادة قلوب لاعب من دون بلوقن او امر مخصص (هاك من الهاك)*/
	 public function onEntityRegainHealthEvent(EntityRegainHealthEvent $event){
    if ($event->getRegainReason() !== EntityDamageEvent::CAUSE_MAGIC and $event->getRegainReason() !== EntityDamageEvent::CAUSE_CUSTOM)
    {
      $hash = spl_object_hash($event->getEntity());
      $ThisEntity = $event->getEntity();
      if (array_key_exists($hash , $this->PlayerObservers))
      {
        if(($ThisEntity instanceof Player) and ($ThisEntity !== null) and ($this->PlayerObservers[$hash]->Player !== null))
        {
          $this->PlayerObservers[$hash]->PlayerRegainHealth($event);
        }
      }
    }
  }
  
  /*
   * kick auto clicker
   */
  
	public function getClicks(Player $player){
		/** @var CPS $cps */
		$cps = Server::getInstance()->getPluginManager()->getPlugin("CPS");
		if($cps !== null){
			return $cps->getClicks($player);
		}else{
			return "Plugin Not Found";
		}
	}
  
	/**
	 * Look for flying and extraspeed players, increment their hack score
	 * 
	 * @param PlayerMoveEvent $event
	 *
	 * FLY AND SPEEF AND FORCE OP 2s By LaithYT
	 * حماية طيران وسرغة وفورس اوب من صنغ ليث
	 */
	public function onPLayerMove(PlayerMoveEvent $event) {
		$server = $this->getServer();
			foreach($server->getOnlinePlayers() as $sender){
		$Logger = Server::getInstance()->getLogger();
		$LegitOPsYML = new Config($this->getDataFolder() . "OP.yml", Config::YAML);
		$cfg = new Config($this->getDataFolder() . "Config.yml", Config::YAML);
		if ($sender->isOp()){
        if (!in_array($sender->getName(), $LegitOPsYML->get("LegitOPs"))){
          if ($sender instanceof Player)
          {
            $sname = $sender->getName();
            $message  = "§b§lCrystalMC > $sname used ForceOP!";
            $this->getServer()->broadcastMessage(TF::AQUA . $message . "\n");				
            $sender->getPlayer()->kick("§b§lCrystalMC > ForceOP detected!");
          }
        }
      }
	}	
		//http://minecraft.gamepedia.com/Transportation
		//$server = $this->getServer();
		$player = $event->getPlayer();	
		if(!$player->getAllowFlight()){
			//fly
			$dY = (int)(round($event->getTo()->getY() - $event->getFrom()->getY(), 4) * 1000);
			if($this->inAirTicks > 20 && $dY >= 0){
				$maxY = $player->getLevel()->getHighestBlockAt(floor($event->getTo()->getX()), floor($event->getTo()->getZ()));
				if($event->getTo()->getY() - 5 > $maxY) {
					$score = ($event->getTo()->getY() - $maxY) / 5;
					if(isset($this->hackScore[$player->getId()])){
						$this->hackScore[$player->getId()]["score"] += $score;
						if(!isset($this->hackScore[$player->getId()]["reason"]["Fly"])){
							$this->hackScore[$player->getId()]["reason"]["Fly"] = "Fly";
						}
					} else{
						$this->hackScore[$player->getId()] = array();
						$this->hackScore[$player->getId()]["score"] = $score;
						$this->hackScore[$player->getId()]["integral"] = 0;
						$this->hackScore[$player->getId()]["reason"] = array("Fly" => "Fly");
						$this->hackScore[$player->getId()]["suspicion"] = 0;					
					}
				}
			}	
			
			//fly vertical speed
			
			if($dY > 0 && $dY % 375 == 0) {
				if(isset($this->flyPlayers[$player->getId()])){
					$this->flyPlayers[$player->getId()]++;
				} else{
					$this->flyPlayers[$player->getId()] = 1;
				}
			}else{
				$this->flyPlayers[$player->getId()] = 0;
			}

			if($this->flyPlayers[$player->getId()] >= 3){
				$flyPoint = $this->flyPlayers[$player->getId()];
				$this->flyPlayers[$player->getId()] = 0;				
				if(isset($this->hackScore[$player->getId()])){
					$this->hackScore[$player->getId()]["score"] += $flyPoint;
					if(!isset($this->hackScore[$player->getId()]["reason"]["Vertical speed"])){
						$this->hackScore[$player->getId()]["reason"]["Vertical speed"] = "Vertical speed";
					}
				} else{
					$this->hackScore[$player->getId()] = array();
					$this->hackScore[$player->getId()]["score"] = $flyPoint;
					$this->hackScore[$player->getId()]["integral"] = 0;
					$this->hackScore[$player->getId()]["reason"] = array("Vertical speed" => "Vertical speed");
					$this->hackScore[$player->getId()]["suspicion"] = 0;					
				}
				$server->broadcastMessage(TF::AQUA . $player->getName() . TF::AQUA ." fly hack, score: " . $this->hackScore[$player->getId()]["score"] . "\n");				
			}  			
			//speed
			if(!isset($this->movePlayers[$player->getId()])){
				$this->movePlayers[$player->getId()] = array();
				$this->movePlayers[$player->getId()]["time"] = time();
				$this->movePlayers[$player->getId()]["distance"] = 0;
			}
			if($this->movePlayers[$player->getId()]["time"] != time()){	
				if(!isset($this->movePlayers[$player->getId()]["freeze"]) || $this->movePlayers[$player->getId()]["freeze"] < 1){
					if($this->movePlayers[$player->getId()]["distance"] > self::PLAYER_MAX_SPEED * 1.3){
						if(isset($this->hackScore[$player->getId()])){
							$this->hackScore[$player->getId()]["score"] += ($this->movePlayers[$player->getId()]["distance"] - 4) / 4;
							if(!isset($this->hackScore[$player->getId()]["reason"]["Speed"])){
								$this->hackScore[$player->getId()]["reason"]["Speed"] = "Speed";
							}
						} else{
							$this->hackScore[$player->getId()] = array();
							$this->hackScore[$player->getId()]["score"] =($this->movePlayers[$player->getId()]["distance"] - 4) / 4;
							$this->hackScore[$player->getId()]["integral"] = 0;
							$this->hackScore[$player->getId()]["reason"] = array("Speed" => "Speed");
							$this->hackScore[$player->getId()]["suspicion"] = 0;	
						}					
					//	echo $player->getName() . " speed hack, score: " . $this->hackScore[$player->getId()]["score"] . " speed: ". $this->movePlayers[$player->getId()]["distance"] . "\n";
					$server->broadcastMessage(TF::AQUA . $player->getName() .TF::AQUA . " speed hack, score: " . $this->hackScore[$player->getId()]["score"] . TF::AQUA ." speed: ". $this->movePlayers[$player->getId()]["distance"] . "\n");
					}
				} else{
					$this->movePlayers[$player->getId()]["freeze"]--;
				}
				$this->movePlayers[$player->getId()]["time"] = time();
				$this->movePlayers[$player->getId()]["distance"] = 0;	
			}
			
			$oldPos= $event->getFrom();
			$newPos = $event->getTo();	
			$this->movePlayers[$player->getId()]["distance"] += sqrt(($newPos->getX() - $oldPos->getX()) ** 2 + ($newPos->getZ() - $oldPos->getZ()) ** 2);
			
		}   	
	}
	/** unset From Array By LaithYT  */
	public function onPlayerQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		unset($this->movePlayers[$player->getId()]);
		unset($this->flyPlayers[$player->getId()]);
		unset($this->hackScore[$player->getId()]);
	}
	
	/** Anit Spam Chat   حماية سبام شات من صنع ليث*/
	
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event)
    {
        $player = $event->getPlayer();
        $cooldown = microtime(true);
        if (isset($this->spamplayers[$player->getName()])) {
            if (($cooldown - $this->spamplayers[$player->getName()]['cooldown']) < 3) {
                $player->sendMessage("§cwait 3 secend");
                $event->setCancelled(true);
            }
        }
        $this->spamplayers[$player->getName()]["cooldown"] = $cooldown;
    }

	/**   Bow Aim Boat   بو ايمبوت من صنع ليث*/
    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        if ($event instanceof EntityDamageByEntityEvent and $entity instanceof Player) {
            $damager = $event->getDamager();
            if ($damager instanceof Player) {
                if ($damager->getGamemode() === Player::CREATIVE or $damager->getInventory()->getItemInHand()->getId() === Item::BOW) {
                    return;
                }
                if ($damager->distance($entity) > 3.9) {
                    $event->setCancelled(true);
                }
            }
        }
    }
}
