<?php

declare(strict_types=1);

namespace MengBao\MEBWorldProtect\Lang;

use MengBao\MEBWorldProtect\Main;
use pocketmine\utils\Config;

class LanguageManager
{
    private Main $plugin;

    private Config $lang;

    private string $currentLang = "zh_CN";

    /** 内置的语言列表 */
    public const BUILTIN = ["zh_CN", "en_US"];

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    public function load(string $lang): void
    {
        $this->currentLang = $lang;

        // 确保 lang 目录存在
        $langDir = $this->plugin->getDataFolder() . "lang/";
        if (!is_dir($langDir)) {
            @mkdir($langDir, 0777, true);
        }

        $langFile = $langDir . $lang . ".yml";

        // 如果文件不存在，尝试从资源复制
        if (!file_exists($langFile)) {
            // saveResource 的第二个参数 false 表示不替换已存在的文件
            $this->plugin->saveResource("lang/" . $lang . ".yml", false);
        }

        // 如果仍然不存在，回退到 zh_CN
        if (!file_exists($langFile)) {
            $this->plugin->getLogger()->warning("Language file {$lang}.yml not found, using zh_CN");
            $this->currentLang = "zh_CN";
            $langFile = $langDir . "zh_CN.yml";
            if (!file_exists($langFile)) {
                $this->plugin->saveResource("lang/zh_CN.yml", false);
            }
        }

        $this->lang = new Config($langFile, Config::YAML);
    }

    /**
     * 获取翻译
     */
    public function get(string $key, array $params = []): string
    {
        $message = $this->lang->get($key, $key);

        foreach ($params as $search => $replace) {
            $message = str_replace("{" . $search . "}", (string) $replace, $message);
        }

        return $message;
    }

    public function getCurrentLang(): string
    {
        return $this->currentLang;
    }

    /**
     * 切换语言
     */
    public function switchLang(string $lang): bool
    {
        if (!$this->isAvailable($lang)) {
            return false;
        }

        $this->load($lang);

        // 保存到配置文件
        $config = $this->plugin->getProtectionConfig();
        $config->set("language", $lang);
        $this->plugin->saveProtectionConfig();

        return true;
    }

    /**
     * 获取可用的语言列表
     */
    public function getAvailable(): array
    {
        $dir = $this->plugin->getDataFolder() . "lang/";
        $found = [];

        foreach (glob($dir . "*.yml") ?: [] as $path) {
            $code = basename($path, ".yml");
            if ($this->isValidCode($code)) {
                $found[] = $code;
            }
        }

        sort($found);
        return $found === [] ? ["zh_CN"] : $found;
    }

    /**
     * 检查语言是否可用
     */
    public function isAvailable(string $code): bool
    {
        $langFile = $this->plugin->getDataFolder() . "lang/" . $code . ".yml";
        return file_exists($langFile);
    }

    /**
     * 检查语言代码是否合法
     */
    private function isValidCode(string $code): bool
    {
        return preg_match('/^[A-Za-z0-9_\-]{1,32}$/', $code) === 1;
    }

    /**
     * 获取语言显示名称
     */
    public function getLanguageName(string $code): string
    {
        $names = [
            "zh_CN" => "简体中文",
            "en_US" => "English",
        ];
        return $names[$code] ?? $code;
    }
}
