<?php


namespace BuildFFA;

use BuildFFA\Tasks\giveblockTask;
use BuildFFA\Tasks\ScoreBoardTask;
use BuildFFA\Tasks\SandstoneTask;
use BuildFFA\Tasks\Sandstone1Task;
use BuildFFA\Tasks\SpinnenwebenTask;
use pocketmine\block\Sandstone;
use pocketmine\entity\Effect;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use jojoe77777\FormAPI;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as f;



class BuildFFA extends PluginBase implements Listener
{


	public $blocks = array(
		"24"
	);
	private static $plugin;
	public $kits = array(
		"§5Enderperlen",
		"§fSchneebälle",
		"§eGoldapfel",
		"§fSpinnenweben",
		"§dStandard"
	);
	public $cfg;
	public const PREFIX = "§8[§eBuild§cFFA§8] ";

	public function onEnable(): void
	{
		$this->getServer()->getLogger()->notice(BuildFFA::PREFIX . "§aPlugin wurde geladen!");
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		@mkdir($this->getDataFolder() . "players");
		@mkdir($this->getDataFolder() . "games");
		$this->saveResource("config.yml");
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->getScheduler()->scheduleRepeatingTask(new ScoreBoardTask($this), 20);

	//	$this->getServer()->getCommandMap()->register("stats", new StatsCommand($this));
	}

	public function onDisable(): void
	{
		$this->getServer()->getLogger()->error(BuildFFA::PREFIX . "§cPlugin wurde entladen!");
	}

	public function Join(PlayerJoinEvent $event)
	{
		$player = $event->getPlayer();
		$data = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getInventory()->setItem(4, Item::get(130)->setCustomName("§3Perks"));
		$player->getInventory()->setItem(8, Item::get(341)->setCustomName("§aLobby"));
		$event->setJoinMessage(BuildFFA::PREFIX . "§8[§a+§8] §r§f" . $player->getName());
		$data->set("Killstreak", 0);
		$data->set("Perks", "Standard");
        $data->set("usePerks", true);
		$data->save();
		$player = $event->getPlayer();
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());
		$i = $this->cfg->getAll();
		if (!file_exists($this->getDataFolder() . "players/" . $player->getName() . ".yml")) {
			$data->set("Kills", 1);
			$data->set("Deaths", 1);
			$data->set("Killstreak", 0);
			$data->set("Coins", 0);
			$data->set("Perks", "Standard");
			$data->set("usePerks", true);
			$data->save();
			$this->getServer()->getLogger()->notice(BuildFFA::PREFIX . "§aDas Profil von §b" . $player->getName() . "§a wurde erstellt!");
		}
	}

	public function onQuit(PlayerQuitEvent $event)
	{
		$event->setQuitMessage(BuildFFA::PREFIX . "§8[§c-§8] §r§f" . $event->getPlayer()->getName());
		$data = new Config($this->getDataFolder() . "players/" . $event->getPlayer()->getName() . ".yml", Config::YAML);
		$data->set("Killstreak", 0);
		$data->set("usePerks", true);
		$data->save();
	}

	public function Hunger(PlayerExhaustEvent $ev)
	{
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($ev->getEntity()->getLevel()->getName() == $this->cfg->get("Level")) {
			$ev->setCancelled(true);
		}
	}

	public function onRespawn(PlayerRespawnEvent $event)
	{
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($event->getPlayer()->getLevel()->getName() == $this->cfg->get("Level")) {
			$player = $event->getPlayer();
			$player->getInventory()->clearAll();
			$player->getInventory()->setItem(4, Item::get(130)->setCustomName("§3Perks"));
			$player->getInventory()->setItem(8, Item::get(341)->setCustomName("§aLobby"));
		}
	}


	public function onInteract(PlayerInteractEvent $ev)
	{
		$player = $ev->getPlayer();
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($ev->getPlayer()->getLevel()->getName() == $this->cfg->get("Level")) {
			$item = $ev->getItem();
			if ($item->getCustomName() == "§3Perks") {
				$this->openPerksUI($player);
			} elseif ($item->getCustomName() == "§aLobby") {
				$this->openLobbyUI($player);
			}
		}
	}


	public function onFallDamage(EntityDamageEvent $ev)
	{
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($ev->getEntity()->getLevel()->getName() == $this->cfg->get("Level")) {
			if ($ev->getCause() == EntityDamageEvent::CAUSE_FALL) {
				$ev->setCancelled(true);
				$player = $ev->getEntity();
				$perk = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
				if ($perk->get("usePerks") == true) {
					$perk->get("Perks");
					if ($perk->get("Perks") == "Spinnenweben") {
						$cmd = new ConsoleCommandSender();
						$this->getServer()->getCommandMap()->dispatch($cmd, "buildffa kit s " . $player->getName());
						$perk->set("usePerks", false);
						$perk->save();
					}
					if ($perk->get("Perks") == "Schneebälle") {
						$cmd = new ConsoleCommandSender();
						$this->getServer()->getCommandMap()->dispatch($cmd, "buildffa kit t " . $player->getName());
						$perk->set("usePerks", false);
						$perk->save();
					}
					if ($perk->get("Perks") == "Goldapfel") {
						$cmd = new ConsoleCommandSender();
						$this->getServer()->getCommandMap()->dispatch($cmd, "buildffa kit g " . $player->getName());
						$perk->set("usePerks", false);
						$perk->save();
					}
					if ($perk->get("Perks") == "Enderperlen") {
						$cmd = new ConsoleCommandSender();
						$this->getServer()->getCommandMap()->dispatch($cmd, "buildffa kit e " . $player->getName());
						$perk->set("usePerks", false);
						$perk->save();
					}
					if ($perk->get("Perks") == "Standard") {
						$cmd = new ConsoleCommandSender();
						$this->getServer()->getCommandMap()->dispatch($cmd, "buildffa kit standard " . $player->getName());
						$perk->set("usePerks", false);
						$perk->save();
					}
				} else {
					$player = $ev->getEntity();
					return true;
				}
			} elseif ($ev->getEntity()->getLevel()->getName() == $this->cfg->get("Level")) {
				$player = $ev->getEntity();
				$v = new Vector3($player->getLevel()->getSpawnLocation()->getX(), $player->getPosition()->getY(), $player->getLevel()->getSpawnLocation()->getZ());
				$r = $this->getServer()->getSpawnRadius();
				if (($player instanceof Player) && ($player->getPosition()->distance($v) <= $r)) {
					$ev->setCancelled(TRUE);

				} else {
					$ev->setCancelled(FALSE);
					$ev->setAttackCooldown(0);
				}
			}
		}
	}


	public function onCommand(CommandSender $player, Command $command, string $label, array $args): bool
	{

		switch ($command->getName()) {
			case "buildffa":
				if (isset($args[0])) {
					switch ($args[0]) {
						case "help":

							$player->sendMessage("§7------ §eBuild§cFFA §7------");
							$player->sendMessage("§7» §c/buildffa §ehelp §8| §eBuild§cFFA §aBefehls Liste!");
							$player->sendMessage("§7» §c/buildffa §esetup §7< LevelName > §8| §eBuild§cFFA §aMap Setup.");
							$player->sendMessage("§7» §c/buildffa §ekits §8| §eBuild§cFFA §aKits");
							$player->sendMessage("§7------ §eBuild§cFFA §7------");
							break;
						case "setup":
							if (isset($args[1])) {
								if (!$this->getServer()->getLevelByName($args[1])) {
									$player->sendMessage(BuildFFA::PREFIX . "§cWelt §7" . $args[1] . " §cexistiert nicht!");
									$player->sendMessage(BuildFFA::PREFIX . "§c/buildffa §esetup §7< LevelName >" . "\n" . BuildFFA::PREFIX . "§c/buildffa §ehelp");
									return true;
								}
								$welt = $args[1];
								$player->sendMessage(BuildFFA::PREFIX . "§aDie Welt §e" . $welt . " §aist nun die §r§e§lBuild§cFFA §r§aWelt!");
								$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
								$this->cfg->set("Level", $welt);
								$this->cfg->save();
								return true;

							} else {
								$player->sendMessage(BuildFFA::PREFIX . "§cFalsche Benutzung: §e/buildffa setup §7< LevelName >");
							}
							break;

                        case "author":
                            $player->sendMessage(BuildFFA::PREFIX . "§4§lINFO \n §6Auhor: §eceepkev77 \n §6Helfer: §er3pt1s");
                            break;
						case "kit":
							if (isset($args[1])) {
								switch ($args[1]) {
									case "s":
										if (isset($args[2])) {
											$sender = $this->getServer()->getPlayer($args[2]);
											$sender->getInventory()->clearAll();
											$i5 = Item::get(301, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setBoots($i5);
											$i5 = Item::get(300, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setLeggings($i5);
											$i5 = Item::get(303, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setChestplate($i5);
											$i5 = Item::get(298, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setHelmet($i5);


											$i5 = Item::get(268);
											$ie5 = Enchantment::getEnchantment(0);
											$iee5 = Enchantment::getEnchantment(9);
											$i5->addEnchantment(new EnchantmentInstance($iee5, 2));
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(0, $i5);
											$i5 = Item::get(280);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(1, $i5);
											$i5 = Item::get(179, 0, 64);
											$ie5 = Enchantment::getEnchantment(0);
											$sender->getInventory()->setItem(2, $i5);
											$i5 = Item::get(261);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(3, $i5);
											$i5 = Item::get(30, 0, 16);
											$sender->getInventory()->setItem(8, $i5);
											$i5 = Item::get(262, 0, 64);
											$sender->getInventory()->setItem(7, $i5);
										}
										break;
									case "e":
										if (isset($args[2])) {
											$sender = $this->getServer()->getPlayer($args[2]);
											$sender->getInventory()->clearAll();
											$i5 = Item::get(301, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setBoots($i5);
											$i5 = Item::get(300, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setLeggings($i5);
											$i5 = Item::get(303, 0, 1);//"Schöner Code" :-D xd
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setChestplate($i5);
											$i5 = Item::get(298, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setHelmet($i5);


											$i5 = Item::get(268);
											$ie5 = Enchantment::getEnchantment(0);
											$iee5 = Enchantment::getEnchantment(9);
											$i5->addEnchantment(new EnchantmentInstance($iee5, 2));
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(0, $i5);
											$i5 = Item::get(280);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(1, $i5);
											$i5 = Item::get(179, 0, 64);
											$ie5 = Enchantment::getEnchantment(0);
											$sender->getInventory()->setItem(2, $i5);
											$i5 = Item::get(261);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(3, $i5);
											$i5 = Item::get(368, 0, 2);
											$sender->getInventory()->setItem(8, $i5);
											$i5 = Item::get(262, 0, 64);
											$sender->getInventory()->setItem(7, $i5);
										}
										break;
									case "g":
										if (isset($args[2])) {
											$sender = $this->getServer()->getPlayer($args[2]);
											$sender->getInventory()->clearAll();
											$i5 = Item::get(301, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setBoots($i5);
											$i5 = Item::get(300, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setLeggings($i5);
											$i5 = Item::get(303, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setChestplate($i5);
											$i5 = Item::get(298, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setHelmet($i5);


											$i5 = Item::get(268);
											$ie5 = Enchantment::getEnchantment(0);
											$iee5 = Enchantment::getEnchantment(9);
											$i5->addEnchantment(new EnchantmentInstance($iee5, 2));
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(0, $i5);
											$i5 = Item::get(280);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(1, $i5);
											$i5 = Item::get(179, 0, 64);
											$ie5 = Enchantment::getEnchantment(0);
											$sender->getInventory()->setItem(2, $i5);
											$i5 = Item::get(261);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(3, $i5);
											$i5 = Item::get(322, 0, 2);
											$sender->getInventory()->setItem(8, $i5);
											$i5 = Item::get(262, 0, 64);
											$sender->getInventory()->setItem(7, $i5);
										}
										break;
									case "t":
										if (isset($args[2])) {
											$sender = $this->getServer()->getPlayer($args[2]);
											$sender->getInventory()->clearAll();
											$i5 = Item::get(301, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setBoots($i5);
											$i5 = Item::get(300, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setLeggings($i5);
											$i5 = Item::get(303, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setChestplate($i5);
											$i5 = Item::get(298, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setHelmet($i5);


											$i5 = Item::get(268);
											$ie5 = Enchantment::getEnchantment(0);
											$iee5 = Enchantment::getEnchantment(9);
											$i5->addEnchantment(new EnchantmentInstance($iee5, 2));
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(0, $i5);
											$i5 = Item::get(280);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(1, $i5);
											$i5 = Item::get(179, 0, 64);
											$ie5 = Enchantment::getEnchantment(0);
											$sender->getInventory()->setItem(2, $i5);
											$i5 = Item::get(261);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(3, $i5);
											$i5 = Item::get(332, 0, 16);
											$sender->getInventory()->setItem(8, $i5);
											$i5 = Item::get(262, 0, 64);
											$sender->getInventory()->setItem(7, $i5);
										}
										break;

									case "standard":
										if (isset($args[2])) {
											$sender = $this->getServer()->getPlayer($args[2]);
											$sender->getInventory()->clearAll();
											$i5 = Item::get(301, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setBoots($i5);
											$i5 = Item::get(300, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setLeggings($i5);
											$i5 = Item::get(303, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setChestplate($i5);
											$i5 = Item::get(298, 0, 1);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getArmorInventory()->setHelmet($i5);


											$i5 = Item::get(268);
											$ie5 = Enchantment::getEnchantment(0);
											$iee5 = Enchantment::getEnchantment(9);
											$i5->addEnchantment(new EnchantmentInstance($iee5, 2));
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(0, $i5);
											$i5 = Item::get(280);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(1, $i5);
											$i5 = Item::get(179, 0, 64);
											$ie5 = Enchantment::getEnchantment(0);
											$sender->getInventory()->setItem(2, $i5);
											$i5 = Item::get(261);
											$ie5 = Enchantment::getEnchantment(0);
											$i5->addEnchantment(new EnchantmentInstance($ie5, 10));
											$sender->getInventory()->setItem(3, $i5);
											$i5 = Item::get(346, 0, 1);
											$sender->getInventory()->setItem(8, $i5);
											$i5 = Item::get(262, 0, 64);
											$sender->getInventory()->setItem(7, $i5);
										}
								}
							} else {
								$player->sendMessage(BuildFFA::PREFIX . "§cFalsche Benutzung: §6/buildffa help");
							}
							break;
						case "kits":
							$player->sendMessage("§7------ §eBuild§cFFA §7------");
							foreach ($this->kits as $kits) {
								$player->sendMessage($kits);
							}
							$player->sendMessage("§7------ §eBuild§cFFA §7------");

					}
				} else {
					$player->sendMessage(BuildFFA::PREFIX . "§cFalsche Benutzung: §6/buildffa kit §7< kitname >");
				}
				break;
            case "stats":
                if ($player instanceof Player) {
                    $this->openStatsUI($player);
                } else {
                    $player->sendMessage(BuildFFA::PREFIX . "§cBitte benutze den Command In-Game!");
                }
                break;
            case "coins":
                if ($player instanceof Player) {
                    $data = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
                    $player->sendMessage(BuildFFA::PREFIX . "§eCoins: §7" . $data->get("Coins"));
                } else {
                    $player->sendMessage(BuildFFA::PREFIX . "§cBitte benutze den Command In-Game!");
                }
		}
		return true;
	}

	public function openPerksUI($player)
	{//Hol dir halt FormAPI dazu. Ist ehct hilfeich mach ich dann
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, int $data = null) {
			$perk = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch ($result) {
				case 0;

					break;
				case 1:
					$perk->set("Perks", "Enderperlen");
					$perk->save();
					$player->sendMessage(BuildFFA::PREFIX . "§aDu hast das §5Enderperlen §eKit §aausgewählt!");
					break;
				case 2:
					$perk->set("Perks", "Schneebälle");
					$perk->save();
					$player->sendMessage(BuildFFA::PREFIX . "§aDu hast das §fSchneebälle §eKit §aausgewählt!");
					break;
				case 3:
					$perk->set("Perks", "Goldapfel");
					$perk->save();
					$player->sendMessage(BuildFFA::PREFIX . "§aDu hast das §6Goldäpfel §eKit §aausgewählt!");
					break;
				case 4:
					$perk->set("Perks", "Spinnenweben");
					$perk->save();
					$player->sendMessage(BuildFFA::PREFIX . "§aDu hast das §fSpinnenweben §eKit §aausgewählt!");
					break;
				case 5:
					$perk->set("Perks", "Standard");
					$perk->save();
					$player->sendMessage(BuildFFA::PREFIX . "§aDu hast das §dStandard §eKit §aausgewählt!");
			}
		});

		$form->setTitle("§3Perks");
		$form->setContent("");
		$form->addButton("§cAbbrechen", 0);
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$form->addButton("§5Enderperlen", 1, "https://gamepedia.cursecdn.com/minecraft_de_gamepedia/1/1a/Enderperle.png?version=cd96950debbf685c77025edd00d4e67c");
		$form->addButton("§7Schneebälle", 2, "https://gamepedia.cursecdn.com/minecraft_gamepedia/2/2a/Snowball_JE3_BE3.png?version=bfea069753ddc255ad148cb60cce22f5");
		$form->addButton("§6Goldäpfel", 3, "https://gamepedia.cursecdn.com/minecraft_de_gamepedia/3/31/Goldener_Apfel.png?version=6d91695672039b5b6be1d974dacc66d9");
		$form->addButton("§7Spinnenweben", 4, "https://gamepedia.cursecdn.com/minecraft_gamepedia/a/ae/Cobweb_JE3_BE2.png?version=102bb393d7fec64b1d5c922bf83b44ab");
		$form->addButton("§dStandard", 5, "https://barrettjacksoncdn.azureedge.net/staging/carlist/items/Fullsize/Automobilia/91520/91520_Auto_Front_3-4_Web.jpg");
		$form->sendToPlayer($player);
		return $form;
	}

	public function onDeath(PlayerDeathEvent $event)
	{
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($event->getEntity()->getLevel()->getName() == $this->cfg->get("Level")) {

			$player = $event->getPlayer();
			$name = $player->getName();
			$entity = $event->getEntity();
			if ($entity instanceof Player) {
				$event->setDrops([]);
			}


			$killer = $player->getLastDamageCause();
			if ($killer instanceof EntityDamageByEntityEvent) {
				$killer = $killer->getDamager();
				if ($killer instanceof Player) {
					$killer->sendPopup(BuildFFA::PREFIX . "§a+ 1 §eKill");
					$event->setDeathMessage(BuildFFA::PREFIX . "§b" . $player->getName() . " §7wurde von §b" . $killer->getName() . " §7getötet!");
					$data = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
					$kill = new Config($this->getDataFolder() . "players/" . $killer->getName() . ".yml", Config::YAML);
					$kill->set("Kills", $kill->get("Kills") + 1);
					$kill->set("Killstreak", $kill->get("Killstreak") + 1);
                    $kill->set("Coins", $kill->get("Coins") + 9);
                    $kill->save();
					$data->set("Deaths", $data->get("Deaths") + 1);
					$data->set("Killstreak", 0);
                    $data->set("Coins", $kill->get("Coins") - 6);
                    $data->set("usePerks", true);
					$data->save();
				}
				if ($data->get("Killstreak") == 5) {
					$this->getServer()->broadcastMessage(BuildFFA::PREFIX . "§aDer Spieler §c" . $player->getName() . " §ahat eine §e5§aer §eKillstreak §aerreicht!");
				}
				if ($kill->get("Killstreak") == 5) {
					$this->getServer()->broadcastMessage(BuildFFA::PREFIX . "§aDer Spieler §c" . $player->getName() . " §ahat eine §e5§aer §eKillstreak §aerreicht!");
				}
			}
		}
	}

	public function openLobbyUI($player)
	{
		$api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data) {
			$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
			$result = $data;
			if ($result === null) {
				return true;
			}
			switch ($result) {
				case 0:

					break;
				case 1:
					$this->getServer()->getCommandMap()->dispatch($player, "hub");
					break;
			}
		});
		$form->setTitle("§aLobby");
		$form->setContent("§7Wähle eine Option aus!");
		$form->addButton("§cAbbrechen", 0);
		$form->addButton("§aTeleportieren", 1);
		$form->sendToPlayer($player);
		return $form;
	}


	public function onPlayerMove(PlayerMoveEvent $event)
	{
		if ($event->getPlayer()->getLevel()->getName() == $this->cfg->get("Level")) {
			$player = $event->getPlayer();
			$v = new Vector3($player->getLevel()->getSpawnLocation()->getX(), $player->getPosition()->getY(), $player->getLevel()->getSpawnLocation()->getZ());
			$r = $this->getServer()->getSpawnRadius();
			if (($player instanceof Player) && ($player->getPosition()->distance($v) <= $r)) {
				$player->sendTip(BuildFFA::PREFIX . "§aSicherheit!");
				$player->setHealth(20);

			} else {
				$data = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
				$player->sendPopup("§eKillstreak: §6" . $data->get("Killstreak"));
			}
		}

	}

	/*  public function onPlayerDamage(EntityDamageEvent $event){
		  $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		  if ($event->getEntity()->getLevel()->getName() == $this->cfg->get("Level")) {
			  $player = $event->getEntity();
			  $v = new Vector3($player->getLevel()->getSpawnLocation()->getX(), $player->getPosition()->getY(), $player->getLevel()->getSpawnLocation()->getZ());
			  $r = $this->getServer()->getSpawnRadius();
			  if (($player instanceof Player) && ($player->getPosition()->distance($v) <= $r)) {
				  $event->setCancelled(TRUE);

			  } else {
				  $event->setCancelled(FALSE);
			  }
		  }
	  }*/

	public function onTransaction(InventoryTransactionEvent $event)
	{
		$event->setCancelled(true);
	}


	public function onDrop(PlayerDropItemEvent $event)
	{
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($event->getPlayer()->getLevel()->getName() == $this->cfg->get("Level")) {
			$player = $event->getPlayer();
			$name = $player->getName();
			$event->setCancelled(true);
		}
	}

	public static function getPlugin(): BuildFFA
	{
		return self::$plugin;
	}

	public function setScoreboardEntry(Player $player, int $score, string $msg, string $objName)
	{
		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objName;
		$entry->type = 3;
		$entry->customName = " $msg   ";
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$pk = new SetScorePacket();
		$pk->type = 0;
		$pk->entries[$score] = $entry;
		$player->sendDataPacket($pk);
	}

	public function rmScoreboardEntry(Player $player, int $score)
	{
		$pk = new SetScorePacket();
		if (isset($pk->entries[$score])) {
			unset($pk->entries[$score]);
			$player->sendDataPacket($pk);
		}
	}

	public function createScoreboard(Player $player, string $title, string $objName, string $slot = "sidebar", $order = 0)
	{
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = $slot;
		$pk->objectiveName = $objName;
		$pk->displayName = $title;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = $order;
		$player->sendDataPacket($pk);
	}

	public function rmScoreboard(Player $player, string $objName)
	{
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objName;
		$player->sendDataPacket($pk);
	}

	public function onScore()
	{
		$pl = $this->getServer()->getOnlinePlayers();
		foreach ($pl as $player) {
			$name = $player->getName();
			$this->rmScoreboard($player, "objektName");
			$data = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);


			$this->createScoreboard($player, "§l§eBuild§cFFA", "objektName");
			$this->setScoreboardEntry($player, 0, "   §e ", "objektName");
			$this->setScoreboardEntry($player, 1, f::GRAY . "» §8Name", "objektName");
			$this->setScoreboardEntry($player, 2, f::DARK_RED . "§7" . $player->getDisplayName(), "objektName");
			$this->setScoreboardEntry($player, 3, " §a", "objektName");
			$this->setScoreboardEntry($player, 4, f::GRAY . "» §eKills ", "objektName");#»
			$this->setScoreboardEntry($player, 5, f::GRAY . "§7" . $data->get("Kills"), "objektName");
			$this->setScoreboardEntry($player, 6, "   §c ", "objektName");
			$this->setScoreboardEntry($player, 7, f::GRAY . "» §cDeaths", "objektName");
			$this->setScoreboardEntry($player, 8, f::RED . "§7" . $data->get("Deaths"), "objektName");
            $this->setScoreboardEntry($player, 9, "  §d    ", "objektName");
		}
	}

	public function onBlockPlace(BlockPlaceEvent $event)
	{
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($event->getPlayer()->getLevel()->getName() == $this->cfg->get("Level")) {
			$block = $event->getBlock();
			$player = $event->getPlayer();
			$id = $event->getBlock()->getId();
			if ($id === 179) {
				$this->getScheduler()->scheduleDelayedTask(new SandstoneTask($this, $block, $player), 100);
				$this->getScheduler()->scheduleDelayedTask(new giveblockTask($this, $player), 100);
				$this->getScheduler()->scheduleDelayedTask(new Sandstone1Task($this, $block), 50);
				$event->setCancelled(false);
			} elseif ($id === 30) {
				$this->getScheduler()->scheduleDelayedTask(new SpinnenwebenTask($this, $block), 120);
				$event->setCancelled(false);
			} else {
				$event->setCancelled(true);
			}
		}
	}

	public function onBlockBreak(BlockBreakEvent $event)
	{
		
			$block = $event->getBlock()->getDamage();
			$id = $event->getBlock()->getId();
			if ($id === 179) {
				$event->setCancelled(false);
			} else {
				$event->setCancelled(true);
			}
		
	}


	public function checkVoid(PlayerMoveEvent $event)
	{
		$this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		if ($event->getPlayer()->getLevel()->getName() == $this->cfg->get("Level")) {
			$player = $event->getPlayer();
			$x = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getFloorX();
			$y = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getFloorY();
			$z = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getFloorZ();
			$level = $this->getServer()->getDefaultLevel();
			if ($event->getTo()->getFloorY() < 0) {//Geht auc ja das geht xd
				$data = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
				$player->teleport(new Position($x, $y, $z, $level));
				$player->setHealth(20);
				$player->sendMessage(BuildFFA::PREFIX . "§cDu bist gestorben");
				$data->set("Deaths", $data->get("Deaths") + 1);
				$data->set("Killstreak", 0);
				$data->set("usePerks", true);
				$data->set("Coins", $data->get("Coins") -6);
				$player->getArmorInventory()->clearAll();
				$player->getInventory()->clearAll();
				$player->getInventory()->setItem(4, Item::get(130)->setCustomName("§3Perks"));
				$player->getInventory()->setItem(8, Item::get(341)->setCustomName("§aLobby"));
				$data->save();
				if ($data->get("Killstreak") == 5) {
					$this->getServer()->broadcastMessage(BuildFFA::PREFIX . "§aDer Spieler §c" . $player->getName() . " §ahat eine §e5§aer §eKillstreak §aerreicht!");
				}
				if ($data->get("Killstreak") == 10) {
					$this->getServer()->broadcastMessage(BuildFFA::PREFIX . "§aDer Spieler §c" . $player->getName() . " §ahat eine §e10§aer §eKillstreak §aerreicht!");
				}


				$cause = $player->getLastDamageCause();
				if ($cause instanceof EntityDamageByEntityEvent) {
					$damager = $cause->getDamager();
					if ($damager instanceof Player) {
						$kill = new Config($this->getDataFolder() . "players/" . $damager->getName() . ".yml", Config::YAML);
						$kill->set("Kills", $kill->get("Kills") + 1);
						$kill->set("Killstreak", $kill->get("Killstreak") + 1);
						$kill->set("Coins", $kill->get("Coins") + 9);

						$kill->save();
						$this->getServer()->broadcastMessage(BuildFFA::PREFIX . "§7" . $player->getName() . " §cwurde von §7" . $damager->getName() . " §cgetötet!");
						$player->sendMessage(BuildFFA::PREFIX . "§cDu wurdest von §7" . $damager->getName() . " §cgetötet!");
						$damager->sendMessage(BuildFFA::PREFIX . "§cDu hast §7" . $player->getName() . " §cgetötet!");
						$damager->setHealth(20);

						$perk = new Config($this->getDataFolder() . "players/" . $damager->getName() . ".yml", Config::YAML);


					}


				}


			}
		}
	}

	public function openStatsUI($player) {
        $data = new Config($this->getDataFolder() . "players/" . $player->getName() . ".yml", Config::YAML);
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, $data) {
	       $result = $data;
	       if ($result === null) {
	           return true;
           }
	       switch ($result) {
               case 0:

                   break;
           }
        });
        $form->setTitle("StatsUI");
	    $form->setContent("§7Name: §7" . $player->getName() . "\n\n" . "§eKills: §7" . $data->get("Kills") . "\n\n" . "§cDeaths: §7" . $data->get("Deaths") . "\n\n" . "§eKillstreak: §7" . $data->get("Killstreak") . "\n\n" . "§eCoins: §7" . $data->get("Coins"));
	    $form->addButton("§cSchließen");
	    $form->sendToPlayer($player);
	    return $form;
	}
}