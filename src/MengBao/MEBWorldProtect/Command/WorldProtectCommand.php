<?php

declare(strict_types=1);

namespace MengBao\MEBWorldProtect\Command;

use MengBao\MEBWorldProtect\Main;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class WorldProtectCommand
{
    private Main $plugin;

    private const RULES = [
        "break" => "disable_break",
        "place" => "disable_place",
        "interact" => "disable_interact",
        "container" => "disable_container",
        "pvp" => "disable_pvp",
        "pve" => "disable_pve",
        "explosion" => "disable_explosion",
        "fire" => "disable_fire_spread",
        "water" => "disable_water_flow",
        "lava" => "disable_lava_flow",
        "drop" => "disable_item_drop",
        "pickup" => "disable_item_pickup",
        "mob" => "restore_mob_behavior",
    ];

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        // 控制台永远有权限
        if (!($sender instanceof \pocketmine\command\ConsoleCommandSender) && !$sender->hasPermission("MEBWorldProtect.ge")) {
            $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("no_permission"));
            return true;
        }

        if (empty($args)) {
            // 空参数时：玩家且GUI可用则显示GUI，否则显示帮助
            if ($sender instanceof Player && $this->plugin->getForms()->isAvailable()) {
                $this->plugin->getForms()->sendMainForm($sender);
                return true;
            }
            $args[0] = "help";
        }

        switch (strtolower($args[0])) {
            case "help":
                $this->sendHelp($sender);
                break;

            case "gui":
                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("player_only"));
                    return true;
                }
                $this->plugin->getForms()->sendMainForm($sender);
                break;

            case "info":
                $this->handleInfo($sender, $args);
                break;

            case "list":
                $this->handleList($sender);
                break;

            case "enable":
                if (!$sender instanceof Player && !($sender instanceof \pocketmine\command\ConsoleCommandSender)) {
                    $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("player_only"));
                    return true;
                }
                if ($sender instanceof Player && !$this->plugin->isAdmin($sender->getName())) {
                    $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("no_permission"));
                    return true;
                }
                $this->handleEnable($sender, $args);
                break;

            case "disable":
                if (!$sender instanceof Player && !($sender instanceof \pocketmine\command\ConsoleCommandSender)) {
                    $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("player_only"));
                    return true;
                }
                if ($sender instanceof Player && !$this->plugin->isAdmin($sender->getName())) {
                    $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("no_permission"));
                    return true;
                }
                $this->handleDisable($sender, $args);
                break;

            case "set":
                if (!$this->checkWorldAdminPermission($sender, $args[1] ?? null)) {
                    return true;
                }
                $this->handleSet($sender, $args);
                break;

            case "addadmin":
                if (!$this->checkAdminPermission($sender, $args[1] ?? null)) {
                    return true;
                }
                $this->handleAddAdmin($sender, $args);
                break;

            case "removeadmin":
                if (!$this->checkAdminPermission($sender, $args[1] ?? null)) {
                    return true;
                }
                $this->handleRemoveAdmin($sender, $args);
                break;

            case "listadmin":
                $this->handleListAdmin($sender, $args);
                break;

            case "reload":
                if (!$sender instanceof Player && !($sender instanceof \pocketmine\command\ConsoleCommandSender)) {
                    $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("player_only"));
                    return true;
                }
                if ($sender instanceof Player && !$this->plugin->isMEBSocietyMaster($sender->getName())) {
                    $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("no_permission"));
                    return true;
                }
                $this->handleReload($sender);
                break;

            default:
                $this->sendHelp($sender);
                break;
        }

        return true;
    }

    private function sendHelp(CommandSender $sender): void
    {
        $lang = $this->plugin->getLang();
        $help = [
            $lang->get("help_title"),
            $lang->get("help_gui"),
            $lang->get("help_info"),
            $lang->get("help_list"),
            $lang->get("help_listadmin"),
        ];

        // 世界管理员可以看到 enable/disable/set 命令
        if ($sender instanceof Player && $this->plugin->isAdmin($sender->getName())) {
            $help[] = $lang->get("help_enable");
            $help[] = $lang->get("help_disable");
            $help[] = $lang->get("help_set");
        } elseif ($sender instanceof \pocketmine\command\ConsoleCommandSender) {
            $help[] = $lang->get("help_enable");
            $help[] = $lang->get("help_disable");
            $help[] = $lang->get("help_set");
        }

        // 只有 MEBSociety 最高权限或控制台可以看到管理员管理和重载命令
        if (($sender instanceof Player && $this->plugin->isMEBSocietyMaster($sender->getName())) ||
            $sender instanceof \pocketmine\command\ConsoleCommandSender) {
            $help[] = $lang->get("help_addadmin");
            $help[] = $lang->get("help_removeadmin");
            $help[] = $lang->get("help_reload");
        }

        $sender->sendMessage(implode("\n", $help));
    }

    /**
     * 检查世界管理员权限
     */
    private function checkWorldAdminPermission(CommandSender $sender, ?string $worldName): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("player_only"));
            return false;
        }

        if ($worldName === null) {
            $worldName = $sender->getWorld()->getFolderName();
        }

        if (!$this->plugin->isWorldAdmin($sender->getName(), $worldName)) {
            $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("no_permission"));
            return false;
        }

        return true;
    }

    /**
     * 检查管理员权限（用于添加/删除管理员）
     * 仅控制台和 MEBSociety 最高权限可以执行
     */
    private function checkAdminPermission(CommandSender $sender, ?string $worldName): bool
    {
        // 控制台永远有权限
        if (!$sender instanceof Player) {
            return true;
        }

        // 检查是否是 MEBSociety 最高权限
        if ($this->plugin->isMEBSocietyMaster($sender->getName())) {
            return true;
        }

        $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("no_permission"));
        return false;
    }

    private function handleInfo(CommandSender $sender, array $args): void
    {
        $lang = $this->plugin->getLang();
        $worldName = $args[1] ?? null;

        if ($worldName === null) {
            if ($sender instanceof Player) {
                $worldName = $sender->getWorld()->getFolderName();
            } else {
                $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("usage_enable"));
                return;
            }
        }

        if ($this->plugin->getServer()->getWorldManager()->getWorldByName($worldName) === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("world_not_found", ["world" => $worldName]));
            return;
        }

        $config = $this->plugin->getProtectionConfig();
        $worlds = $config->get("worlds", []);

        if (!isset($worlds[$worldName])) {
            $worlds[$worldName] = $config->get("default", []);
        }

        $settings = $worlds[$worldName];
        $enabled = $settings["enabled"] ?? false;
        $status = $enabled ? $lang->get("info_status_enabled") : $lang->get("info_status_disabled");

        $info = [
            $lang->get("info_title", ["world" => $worldName]),
            $lang->get("info_status", ["status" => $status]),
            $lang->get("info_rules"),
        ];

        foreach (self::RULES as $shortName => $configKey) {
            if ($configKey === "restore_mob_behavior") {
                $ruleStatus = ($settings[$configKey] ?? false) ? $lang->get("info_enabled") : $lang->get("info_disabled");
            } else {
                $ruleStatus = ($settings[$configKey] ?? false) ? $lang->get("info_enabled") : $lang->get("info_disabled");
            }
            $info[] = $lang->get("info_rule_line", [
                "rule" => $lang->get("rule_" . $shortName),
                "status" => $ruleStatus
            ]);
        }

        $sender->sendMessage(implode("\n", $info));
    }

    private function handleList(CommandSender $sender): void
    {
        $lang = $this->plugin->getLang();
        $config = $this->plugin->getProtectionConfig();
        $worlds = $config->get("worlds", []);

        $protected = [];
        foreach ($worlds as $worldName => $settings) {
            if ($settings["enabled"] ?? false) {
                $count = 0;
                foreach ($settings as $key => $value) {
                    if ($key !== "enabled" && $value === true) {
                        $count++;
                    }
                }
                $protected[] = $lang->get("list_item", ["world" => $worldName, "count" => $count]);
            }
        }

        if (empty($protected)) {
            $sender->sendMessage($lang->get("list_title") . "\n" . $lang->get("list_empty"));
            return;
        }

        $sender->sendMessage($lang->get("list_title") . "\n" . implode("\n", $protected));
    }

    private function handleEnable(CommandSender $sender, array $args): void
    {
        $lang = $this->plugin->getLang();

        if (!isset($args[1])) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("usage_enable"));
            return;
        }

        $worldName = $args[1];

        if ($this->plugin->getServer()->getWorldManager()->getWorldByName($worldName) === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("world_not_found", ["world" => $worldName]));
            return;
        }

        $config = $this->plugin->getProtectionConfig();
        $worlds = $config->get("worlds", []);

        if (!isset($worlds[$worldName])) {
            $worlds[$worldName] = $config->get("default", []);
        }

        $worlds[$worldName]["enabled"] = true;
        $config->set("worlds", $worlds);
        $this->plugin->saveProtectionConfig();

        $sender->sendMessage($lang->get("prefix") . "§a" . $lang->get("protection_enabled", ["world" => $worldName]));
    }

    private function handleDisable(CommandSender $sender, array $args): void
    {
        $lang = $this->plugin->getLang();

        if (!isset($args[1])) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("usage_disable"));
            return;
        }

        $worldName = $args[1];

        if ($this->plugin->getServer()->getWorldManager()->getWorldByName($worldName) === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("world_not_found", ["world" => $worldName]));
            return;
        }

        $config = $this->plugin->getProtectionConfig();
        $worlds = $config->get("worlds", []);

        if (!isset($worlds[$worldName])) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("protection_not_enabled", ["world" => $worldName]));
            return;
        }

        $worlds[$worldName]["enabled"] = false;
        $config->set("worlds", $worlds);
        $this->plugin->saveProtectionConfig();

        $sender->sendMessage($lang->get("prefix") . "§a" . $lang->get("protection_disabled", ["world" => $worldName]));
    }

    private function handleSet(CommandSender $sender, array $args): void
    {
        $lang = $this->plugin->getLang();

        if (count($args) < 4) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("usage_set"));
            $sender->sendMessage($lang->get("prefix") . "§e" . $lang->get("usage_set_rules"));
            return;
        }

        $worldName = $args[1];
        $rule = strtolower($args[2]);
        $value = filter_var($args[3], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($value === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("invalid_value"));
            return;
        }

        if (!isset(self::RULES[$rule])) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("invalid_rule"));
            $sender->sendMessage($lang->get("prefix") . "§e" . $lang->get("usage_set_rules"));
            return;
        }

        if ($this->plugin->getServer()->getWorldManager()->getWorldByName($worldName) === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("world_not_found", ["world" => $worldName]));
            return;
        }

        $config = $this->plugin->getProtectionConfig();
        $worlds = $config->get("worlds", []);

        if (!isset($worlds[$worldName])) {
            $worlds[$worldName] = $config->get("default", []);
        }

        $configKey = self::RULES[$rule];
        $worlds[$worldName][$configKey] = $value;
        $config->set("worlds", $worlds);
        $this->plugin->saveProtectionConfig();

        $status = $value ? $lang->get("rule_enabled") : $lang->get("rule_disabled");
        $sender->sendMessage($lang->get("prefix") . "§a" . $lang->get("rule_set", [
            "status" => $status,
            "rule" => $lang->get("rule_" . $rule),
            "world" => $worldName
        ]));
    }

    private function handleReload(CommandSender $sender): void
    {
        $this->plugin->reloadConfig();
        $this->plugin->getLang()->load((string) $this->plugin->getProtectionConfig()->get("language", "zh_CN"));
        $sender->sendMessage($this->plugin->getLang()->get("prefix") . "§a" . $this->plugin->getLang()->get("config_reloaded"));
    }

    private function handleAddAdmin(CommandSender $sender, array $args): void
    {
        $lang = $this->plugin->getLang();

        if (count($args) < 3) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("usage_addadmin"));
            return;
        }

        $worldName = $args[1];
        $playerName = $args[2];

        if ($this->plugin->getServer()->getWorldManager()->getWorldByName($worldName) === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("world_not_found", ["world" => $worldName]));
            return;
        }

        if ($this->plugin->addWorldAdmin($worldName, $playerName)) {
            $sender->sendMessage($lang->get("prefix") . "§a" . $lang->get("admin_added", ["player" => $playerName, "world" => $worldName]));
        } else {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("admin_already_exists", ["player" => $playerName, "world" => $worldName]));
        }
    }

    private function handleRemoveAdmin(CommandSender $sender, array $args): void
    {
        $lang = $this->plugin->getLang();

        if (count($args) < 3) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("usage_removeadmin"));
            return;
        }

        $worldName = $args[1];
        $playerName = $args[2];

        if ($this->plugin->getServer()->getWorldManager()->getWorldByName($worldName) === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("world_not_found", ["world" => $worldName]));
            return;
        }

        if ($this->plugin->removeWorldAdmin($worldName, $playerName)) {
            $sender->sendMessage($lang->get("prefix") . "§a" . $lang->get("admin_removed", ["player" => $playerName, "world" => $worldName]));
        } else {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("admin_not_found", ["player" => $playerName, "world" => $worldName]));
        }
    }

    private function handleListAdmin(CommandSender $sender, array $args): void
    {
        $lang = $this->plugin->getLang();
        $worldName = $args[1] ?? null;

        if ($worldName === null) {
            if ($sender instanceof Player) {
                $worldName = $sender->getWorld()->getFolderName();
            } else {
                $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("usage_listadmin"));
                return;
            }
        }

        if ($this->plugin->getServer()->getWorldManager()->getWorldByName($worldName) === null) {
            $sender->sendMessage($lang->get("prefix") . "§c" . $lang->get("world_not_found", ["world" => $worldName]));
            return;
        }

        $admins = $this->plugin->getWorldAdmins($worldName);

        if (empty($admins)) {
            $sender->sendMessage($lang->get("prefix") . $lang->get("admin_list_empty", ["world" => $worldName]));
            return;
        }

        $list = $lang->get("admin_list_title", ["world" => $worldName]) . "\n§e" . implode("§r, §e", $admins);
        $sender->sendMessage($lang->get("prefix") . $list);
    }
}
