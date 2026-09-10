<?php

declare(strict_types=1);

namespace MengBao\MEBWorldProtect;

use MengBao\MEBWorldProtect\Command\WorldProtectCommand;
use MengBao\MEBWorldProtect\Form\FormFactory;
use MengBao\MEBWorldProtect\Lang\LanguageManager;
use MengBao\MEBWorldProtect\Listener\ProtectionListener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase
{
    private Config $config;

    private LanguageManager $lang;

    private ProtectionListener $listener;

    private FormFactory $forms;

    private WorldProtectCommand $commandHandler;

    public function onEnable(): void
    {
        @mkdir($this->getDataFolder(), 0777, true);
        @mkdir($this->getDataFolder() . "lang/", 0777, true);

        $this->saveDefaultConfig();
        $this->config = $this->getConfig();

        // 复制所有内置语言文件
        foreach (LanguageManager::BUILTIN as $langCode) {
            $this->saveResource("lang/" . $langCode . ".yml", false);
        }

        $this->lang = new LanguageManager($this);
        $this->lang->load((string) $this->config->get("language", "zh_CN"));

        $this->listener = new ProtectionListener($this);
        $this->forms = new FormFactory($this);
        $this->commandHandler = new WorldProtectCommand($this);

        $this->getServer()->getPluginManager()->registerEvents($this->listener, $this);

        if (!$this->forms->isAvailable()) {
            $this->getLogger()->warning($this->lang->get("forms_not_available"));
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() !== "mebwp") {
            return false;
        }
        return $this->commandHandler->execute($sender, $label, $args);
    }

    public function getProtectionConfig(): Config
    {
        return $this->config;
    }

    public function getLang(): LanguageManager
    {
        return $this->lang;
    }

    public function getForms(): FormFactory
    {
        return $this->forms;
    }

    public function saveProtectionConfig(): void
    {
        $this->config->save();
    }

    /**
     * 检查玩家是否有管理员权限
     */
    public function isAdmin(string $playerName): bool
    {
        // 检查是否是 MEBSociety 的最高权限
        if ($this->isMEBSocietyMaster($playerName)) {
            return true;
        }

        // 检查是否是任意世界的管理员
        $worlds = $this->config->get("worlds", []);
        foreach ($worlds as $worldName => $worldData) {
            $admins = $worldData["admins"] ?? [];
            if (in_array(strtolower($playerName), array_map('strtolower', $admins), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查玩家是否是 MEBSociety 的最高权限
     */
    public function isMEBSocietyMaster(string $playerName): bool
    {
        $mebsPlugin = $this->getServer()->getPluginManager()->getPlugin("MEBSociety");
        if ($mebsPlugin === null || !$mebsPlugin->isEnabled()) {
            return false;
        }

        try {
            // 获取 MEBSociety 的配置
            $basicConfig = $mebsPlugin->basicConfig ?? null;
            if ($basicConfig === null) {
                return false;
            }

            $master = $basicConfig->get("最高权限");
            // 检查 master 是否为有效的字符串
            if ($master === null || $master === false || !is_string($master) || $master === "") {
                return false;
            }

            return strtolower($master) === strtolower($playerName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 检查玩家是否是某个世界的管理员
     */
    public function isWorldAdmin(string $playerName, string $worldName): bool
    {
        // 检查是否是 MEBSociety 的最高权限
        if ($this->isMEBSocietyMaster($playerName)) {
            return true;
        }

        // 检查世界管理员列表
        $worlds = $this->config->get("worlds", []);
        if (!isset($worlds[$worldName])) {
            return false;
        }

        $admins = $worlds[$worldName]["admins"] ?? [];
        return in_array(strtolower($playerName), array_map('strtolower', $admins), true);
    }

    /**
     * 添加世界管理员
     */
    public function addWorldAdmin(string $worldName, string $playerName): bool
    {
        $worlds = $this->config->get("worlds", []);

        if (!isset($worlds[$worldName])) {
            $worlds[$worldName] = $this->config->get("default", []);
        }

        if (!isset($worlds[$worldName]["admins"])) {
            $worlds[$worldName]["admins"] = [];
        }

        $admins = $worlds[$worldName]["admins"];
        $lowerName = strtolower($playerName);

        // 检查是否已经是管理员
        foreach ($admins as $admin) {
            if (strtolower($admin) === $lowerName) {
                return false;
            }
        }

        $admins[] = $playerName;
        $worlds[$worldName]["admins"] = $admins;
        $this->config->set("worlds", $worlds);
        $this->config->save();

        return true;
    }

    /**
     * 移除世界管理员
     */
    public function removeWorldAdmin(string $worldName, string $playerName): bool
    {
        $worlds = $this->config->get("worlds", []);

        if (!isset($worlds[$worldName]) || !isset($worlds[$worldName]["admins"])) {
            return false;
        }

        $admins = $worlds[$worldName]["admins"];
        $lowerName = strtolower($playerName);
        $found = false;

        foreach ($admins as $key => $admin) {
            if (strtolower($admin) === $lowerName) {
                unset($admins[$key]);
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }

        $worlds[$worldName]["admins"] = array_values($admins);
        $this->config->set("worlds", $worlds);
        $this->config->save();

        return true;
    }

    /**
     * 获取世界管理员列表
     */
    public function getWorldAdmins(string $worldName): array
    {
        $worlds = $this->config->get("worlds", []);

        if (!isset($worlds[$worldName])) {
            return [];
        }

        return $worlds[$worldName]["admins"] ?? [];
    }
}
