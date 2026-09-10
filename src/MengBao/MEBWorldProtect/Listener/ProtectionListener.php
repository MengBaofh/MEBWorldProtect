<?php

declare(strict_types=1);

namespace MengBao\MEBWorldProtect\Listener;

use MengBao\MEBWorldProtect\Main;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\block\Lava;
use pocketmine\block\Liquid;
use pocketmine\block\Water;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;

class ProtectionListener implements Listener
{
    private Main $plugin;

    /** @var array<string, float> 玩家名 => 上次警告时间 */
    private array $lastWarn = [];

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * 获取世界保护设置
     */
    private function getWorldSettings(string $worldName): ?array
    {
        $config = $this->plugin->getProtectionConfig();
        $worlds = $config->get("worlds", []);

        if (!isset($worlds[$worldName])) {
            return null;
        }

        $settings = $worlds[$worldName];
        if (!($settings["enabled"] ?? false)) {
            return null;
        }

        return $settings;
    }

    /**
     * 检查是否可以执行操作
     */
    private function canPerform(Player $player, string $worldName): bool
    {
        // 检查全局管理员权限或世界管理员权限
        return $this->plugin->isWorldAdmin($player->getName(), $worldName);
    }

    /**
     * 发送警告消息
     */
    private function warn(Player $player, string $key): void
    {
        $name = strtolower($player->getName());
        $now = microtime(true);
        $cooldown = 2.0;

        if (($this->lastWarn[$name] ?? 0) + $cooldown > $now) {
            return;
        }

        $this->lastWarn[$name] = $now;
        $player->sendTip("§c" . $this->plugin->getLang()->get($key));
    }

    /**
     * 破坏方块
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $worldName = $player->getWorld()->getFolderName();

        if ($this->canPerform($player, $worldName)) {
            return;
        }

        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_break"] ?? false) {
            $event->cancel();
            $this->warn($player, "protect_break");
        }
    }

    /**
     * 放置方块
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $worldName = $player->getWorld()->getFolderName();

        if ($this->canPerform($player, $worldName)) {
            return;
        }

        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_place"] ?? false) {
            $event->cancel();
            $this->warn($player, "protect_place");
        }
    }

    /**
     * 方块交互
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void
    {
        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            return;
        }

        $player = $event->getPlayer();
        $worldName = $player->getWorld()->getFolderName();

        if ($this->canPerform($player, $worldName)) {
            return;
        }

        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_interact"] ?? false) {
            $event->cancel();
            $this->warn($player, "protect_interact");
        }
    }

    /**
     * 打开容器
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onInventoryOpen(InventoryOpenEvent $event): void
    {
        $player = $event->getPlayer();

        $inventory = $event->getInventory();
        if (!$inventory instanceof BlockInventory) {
            return;
        }

        $worldName = $inventory->getHolder()->getWorld()->getFolderName();

        if ($this->canPerform($player, $worldName)) {
            return;
        }

        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_container"] ?? false) {
            $event->cancel();
            $this->warn($player, "protect_container");
        }
    }

    /**
     * PVP 和 PVE 以及生物攻击
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($damager instanceof Player) {
            $worldName = $damager->getWorld()->getFolderName();
            $settings = $this->getWorldSettings($worldName);

            if ($settings === null) {
                return;
            }

            if ($entity instanceof Player) {
                // 玩家攻击玩家 - PVP
                if ($settings["disable_pvp"] ?? false) {
                    if (!$this->canPerform($damager, $worldName)) {
                        $event->cancel();
                        $this->warn($damager, "protect_pvp");
                    }
                }
            } elseif ($entity instanceof Living) {
                // 玩家攻击生物 - PVE
                if ($settings["disable_pve"] ?? false) {
                    if (!$this->canPerform($damager, $worldName)) {
                        $event->cancel();
                        $this->warn($damager, "protect_pve");
                    }
                }
            }
        } elseif ($damager instanceof Living && $entity instanceof Player) {
            // 生物攻击玩家
            $worldName = $entity->getWorld()->getFolderName();
            $settings = $this->getWorldSettings($worldName);

            if ($settings === null) {
                return;
            }

            // restore_mob_behavior为false时禁止生物攻击玩家
            if (!($settings["restore_mob_behavior"] ?? false)) {
                $event->cancel();
            }
        }
    }

    /**
     * 爆炸破坏
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onEntityExplode(EntityExplodeEvent $event): void
    {
        $worldName = $event->getPosition()->getWorld()->getFolderName();
        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_explosion"] ?? false) {
            $event->setBlockList([]);
        }
    }

    /**
     * 火焰蔓延
     * @ignoreCancelled true
     */
    public function onBlockBurn(BlockBurnEvent $event): void
    {
        $worldName = $event->getBlock()->getPosition()->getWorld()->getFolderName();
        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_fire_spread"] ?? false) {
            $event->cancel();
        }
    }

    /**
     * 液体蔓延
     * @ignoreCancelled true
     */
    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        $block = $event->getSource();
        if (!$block instanceof Liquid) {
            return;
        }

        $worldName = $block->getPosition()->getWorld()->getFolderName();
        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($block instanceof Water && ($settings["disable_water_flow"] ?? false)) {
            $event->cancel();
        } elseif ($block instanceof Lava && ($settings["disable_lava_flow"] ?? false)) {
            $event->cancel();
        }
    }

    /**
     * 液体流动更新
     * @ignoreCancelled true
     */
    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        $block = $event->getBlock();
        if (!$block instanceof Liquid) {
            return;
        }

        $worldName = $block->getPosition()->getWorld()->getFolderName();
        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($block instanceof Water && ($settings["disable_water_flow"] ?? false)) {
            $event->cancel();
        } elseif ($block instanceof Lava && ($settings["disable_lava_flow"] ?? false)) {
            $event->cancel();
        }
    }

    /**
     * 丢弃物品
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        $player = $event->getPlayer();
        $worldName = $player->getWorld()->getFolderName();

        if ($this->canPerform($player, $worldName)) {
            return;
        }

        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_item_drop"] ?? false) {
            $event->cancel();
            $this->warn($player, "protect_item_drop");
        }
    }

    /**
     * 拾取物品
     * @priority LOWEST
     */
    public function onEntityItemPickup(EntityItemPickupEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) {
            return;
        }

        $player = $entity;
        $worldName = $player->getWorld()->getFolderName();

        if ($this->canPerform($player, $worldName)) {
            return;
        }

        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        if ($settings["disable_item_pickup"] ?? false) {
            $event->cancel();
            $this->warn($player, "protect_item_pickup");
        }
    }

    /**
     * 生物移动（通过 setMotion 控制）
     * @priority MONITOR
     * @ignoreCancelled false
     */
    public function onEntityMotion(EntityMotionEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Living || $entity instanceof Player) {
            return;
        }

        $worldName = $entity->getWorld()->getFolderName();
        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        // restore_mob_behavior为false时禁止生物移动
        if (!($settings["restore_mob_behavior"] ?? false)) {
            $event->cancel();
        }
    }

    /**
     * 生物传送（阻止生物传送来限制移动）
     * @priority MONITOR
     * @ignoreCancelled false
     */
    public function onEntityTeleport(\pocketmine\event\entity\EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();
        if (!$entity instanceof Living || $entity instanceof Player) {
            return;
        }

        $worldName = $entity->getWorld()->getFolderName();
        $settings = $this->getWorldSettings($worldName);

        if ($settings === null) {
            return;
        }

        // restore_mob_behavior为false时也禁止生物传送
        if (!($settings["restore_mob_behavior"] ?? false)) {
            $event->cancel();
        }
    }
}
