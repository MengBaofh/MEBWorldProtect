<?php

declare(strict_types=1);

namespace MengBao\MEBWorldProtect\Form;

use MengBao\MEBWorldProtect\Main;
use pocketmine\player\Player;
use pocketmine\Server;

class FormFactory
{
    private Main $plugin;
    private bool $available = false;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->available = Server::getInstance()->getPluginManager()->getPlugin("MEBForms") !== null;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function sendMainForm(Player $player): void
    {
        if (!$this->available) {
            $player->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("forms_not_available"));
            return;
        }

        $lang = $this->plugin->getLang();
        $form = new \MengBao\MEBForms\SimpleForm(function (Player $player, mixed $data) {
            if ($data === null) return;
            switch ($data) {
                case "info": $this->sendInfoForm($player); break;
                case "list": $this->sendListForm($player); break;
                case "language": $this->sendLanguageForm($player); break;
                case "manage":
                    if ($this->plugin->isAdmin($player->getName())) {
                        $this->sendManageWorldsForm($player);
                    }
                    break;
            }
        });

        $form->setTitle("§l§6" . $lang->get("gui_main_title_full"));
        $form->setContent("§e" . $lang->get("gui_main_content") . "\n§7" . $lang->get("gui_main_subtitle"));
        $form->addButton("§a" . $lang->get("gui_button_current_world") . "\n§3" . $player->getWorld()->getFolderName(), 0, "textures/ui/magnifyingGlass", "info");
        $form->addButton("§e" . $lang->get("gui_button_protected_list") . "\n§3" . $lang->get("gui_list_worlds"), 0, "textures/ui/icon_list", "list");
        if ($this->plugin->isAdmin($player->getName())) {
            $form->addButton("§6" . $lang->get("gui_button_manage") . "\n§3" . $lang->get("gui_button_manage_sub"), 0, "textures/ui/gear", "manage");
        }
        $form->addButton("§b" . $lang->get("gui_button_language") . "\n§3" . $lang->getLanguageName($lang->getCurrentLang()), 0, "textures/ui/language_glyph_color", "language");
        $player->sendForm($form);
    }

    private function sendInfoForm(Player $player): void
    {
        $lang = $this->plugin->getLang();
        $worldName = $player->getWorld()->getFolderName();
        $config = $this->plugin->getProtectionConfig();
        $worlds = $config->get("worlds", []);
        $settings = $worlds[$worldName] ?? $config->get("default", []);
        $enabled = $settings["enabled"] ?? false;

        $form = new \MengBao\MEBForms\SimpleForm(fn($p, $d) => $d === "back" && $this->sendMainForm($p));
        $form->setTitle("§l§6" . $lang->get("gui_info_title", ["world" => $worldName]));

        $content = $lang->get("info_status", ["status" => $enabled ? $lang->get("info_status_enabled") : $lang->get("info_status_disabled")]) . "\n\n";
        $content .= $lang->get("info_rules") . "\n";

        foreach (["break", "place", "interact", "container", "pvp", "pve", "explosion", "fire", "water", "lava", "drop", "pickup", "mob"] as $rule) {
            $key = $this->getRuleKey($rule);
            $status = ($settings[$key] ?? false) ? $lang->get("info_enabled") : $lang->get("info_disabled");
            $content .= $lang->get("info_rule_line", ["rule" => $lang->get("rule_" . $rule), "status" => $status]) . "\n";
        }

        $form->setContent($content);
        $form->addButton("§a" . $lang->get("gui_button_back"), 0, "textures/ui/back_button_default_light", "back");
        $player->sendForm($form);
    }

    private function sendListForm(Player $player): void
    {
        $lang = $this->plugin->getLang();
        $worlds = $this->plugin->getProtectionConfig()->get("worlds", []);

        $form = new \MengBao\MEBForms\SimpleForm(function ($p, $d) use ($worlds) {
            if ($d === "back") $this->sendMainForm($p);
            elseif (is_string($d) && isset($worlds[$d])) $this->sendWorldDetailForm($p, $d);
        });

        $form->setTitle("§l§6" . $lang->get("gui_list_title_full"));
        $hasProtected = false;

        foreach ($worlds as $wn => $s) {
            if ($s["enabled"] ?? false) {
                $hasProtected = true;
                // 统计启用的保护规则数量（排除 enabled 字段本身）
                $count = 0;
                foreach ($s as $key => $value) {
                    if ($key !== "enabled" && $value === true) {
                        $count++;
                    }
                }
                $form->addButton("§e{$wn}\n§b" . $lang->get("gui_list_rules_count", ["count" => $count]), 0, "textures/ui/world_glyph_color", $wn);
            }
        }

        if (!$hasProtected) {
            $form->setContent($lang->get("list_empty"));
        } else {
            $form->setContent("§e" . $lang->get("gui_list_click_view"));
        }

        $form->addButton("§a" . $lang->get("gui_button_back"), 0, "textures/ui/back_button_default_light", "back");
        $player->sendForm($form);
    }


    private function sendWorldDetailForm(Player $player, string $worldName): void
    {
        $lang = $this->plugin->getLang();
        $worlds = $this->plugin->getProtectionConfig()->get("worlds", []);
        $settings = $worlds[$worldName] ?? $this->plugin->getProtectionConfig()->get("default", []);

        $form = new \MengBao\MEBForms\SimpleForm(function ($p, $d) use ($worldName) {
            if ($d === "back") $this->sendListForm($p);
            elseif ($d === "manage" && $this->plugin->isAdmin($p->getName())) $this->sendManageRulesForm($p, $worldName);
        });

        $form->setTitle("§l§6" . $lang->get("gui_world_detail_title", ["world" => $worldName]));
        $content = $lang->get("info_status", ["status" => ($settings["enabled"] ?? false) ? $lang->get("info_status_enabled") : $lang->get("info_status_disabled")]) . "\n\n" . $lang->get("info_rules") . "\n";

        foreach (["break", "place", "interact", "container", "pvp", "pve", "explosion", "fire", "water", "lava", "drop", "pickup", "mob"] as $rule) {
            $key = $this->getRuleKey($rule);
            $content .= $lang->get("info_rule_line", ["rule" => $lang->get("rule_" . $rule), "status" => ($settings[$key] ?? false) ? $lang->get("info_enabled") : $lang->get("info_disabled")]) . "\n";
        }

        $form->setContent($content);
        if ($this->plugin->isAdmin($player->getName())) {
            $form->addButton("§6" . $lang->get("gui_button_config_rules"), 0, "textures/ui/gear", "manage");
        }
        $form->addButton("§a" . $lang->get("gui_button_back"), 0, "textures/ui/back_button_default_light", "back");
        $player->sendForm($form);
    }

    private function sendManageWorldsForm(Player $player): void
    {
        $lang = $this->plugin->getLang();
        $form = new \MengBao\MEBForms\SimpleForm(function ($p, $d) {
            if ($d === "back") $this->sendMainForm($p);
            elseif (is_string($d)) $this->sendWorldToggleForm($p, $d);
        });

        $form->setTitle("§l§6" . $lang->get("gui_manage_title_full"));
        $form->setContent("§e" . $lang->get("gui_manage_content"));

        foreach ($this->plugin->getServer()->getWorldManager()->getWorlds() as $world) {
            $wn = $world->getFolderName();
            $enabled = $this->plugin->getProtectionConfig()->get("worlds", [])[$wn]["enabled"] ?? false;
            $statusIcon = $enabled ? "§a✔" : "§c✖";
            $statusText = $enabled ? $lang->get("gui_manage_enabled") : $lang->get("gui_manage_disabled");
            $form->addButton("{$statusIcon} §e{$wn}\n§b{$statusText}", 0, "textures/ui/world_glyph_color", $wn);
        }

        $form->addButton("§a" . $lang->get("gui_button_back"), 0, "textures/ui/back_button_default_light", "back");
        $player->sendForm($form);
    }

    private function sendWorldToggleForm(Player $player, string $worldName): void
    {
        $lang = $this->plugin->getLang();
        $worlds = $this->plugin->getProtectionConfig()->get("worlds", []);
        if (!isset($worlds[$worldName])) $worlds[$worldName] = $this->plugin->getProtectionConfig()->get("default", []);
        $enabled = $worlds[$worldName]["enabled"] ?? false;

        $form = new \MengBao\MEBForms\ModalForm(function ($p, $d) use ($worldName, $enabled) {
            if ($d === null) {
                $this->sendManageWorldsForm($p);
                return;
            }
            if ($d === true) {
                if ($enabled) {
                    $this->sendManageRulesForm($p, $worldName);
                } else {
                    $cfg = $this->plugin->getProtectionConfig();
                    $ws = $cfg->get("worlds", []);
                    if (!isset($ws[$worldName])) $ws[$worldName] = $cfg->get("default", []);
                    $ws[$worldName]["enabled"] = true;
                    $cfg->set("worlds", $ws);
                    $this->plugin->saveProtectionConfig();
                    $p->sendMessage($this->plugin->getLang()->get("prefix") . "§a" . $this->plugin->getLang()->get("protection_enabled", ["world" => $worldName]));
                    $this->sendManageRulesForm($p, $worldName);
                }
            } else {
                if ($enabled) {
                    $cfg = $this->plugin->getProtectionConfig();
                    $ws = $cfg->get("worlds", []);
                    $ws[$worldName]["enabled"] = false;
                    $cfg->set("worlds", $ws);
                    $this->plugin->saveProtectionConfig();
                    $p->sendMessage($this->plugin->getLang()->get("prefix") . "§a" . $this->plugin->getLang()->get("protection_disabled", ["world" => $worldName]));
                }
                $this->sendManageWorldsForm($p);
            }
        });

        $form->setTitle("§l§6" . $lang->get("gui_world_toggle_title", ["world" => $worldName]));
        if ($enabled) {
            $form->setContent($lang->get("protection_enabled", ["world" => $worldName]) . "\n\n§e" . $lang->get("gui_world_enabled_hint"));
            $form->setButton1("§a" . $lang->get("gui_button_configure"));
            $form->setButton2("§c" . $lang->get("gui_button_disable"));
        } else {
            $form->setContent($lang->get("protection_not_enabled", ["world" => $worldName]) . "\n\n§e" . $lang->get("gui_world_disabled_hint"));
            $form->setButton1("§a" . $lang->get("gui_button_enable"));
            $form->setButton2("§e" . $lang->get("gui_button_back"));
        }
        $player->sendForm($form);
    }

    private function sendManageRulesForm(Player $player, string $worldName): void
    {
        $lang = $this->plugin->getLang();
        $worlds = $this->plugin->getProtectionConfig()->get("worlds", []);
        if (!isset($worlds[$worldName])) $worlds[$worldName] = $this->plugin->getProtectionConfig()->get("default", []);
        $settings = $worlds[$worldName];

        $form = new \MengBao\MEBForms\CustomForm(function ($p, $d) use ($worldName) {
            if ($d === null) {
                $this->sendManageWorldsForm($p);
                return;
            }
            $cfg = $this->plugin->getProtectionConfig();
            $ws = $cfg->get("worlds", []);
            if (!isset($ws[$worldName])) $ws[$worldName] = $cfg->get("default", []);
            foreach (["break", "place", "interact", "container", "pvp", "pve", "explosion", "fire", "water", "lava", "drop", "pickup", "mob"] as $i => $rule) {
                $ws[$worldName][$this->getRuleKey($rule)] = (bool) $d[$i];
            }
            $cfg->set("worlds", $ws);
            $this->plugin->saveProtectionConfig();
            $p->sendMessage($this->plugin->getLang()->get("prefix") . "§a" . $this->plugin->getLang()->get("config_reloaded"));
            $this->sendWorldDetailForm($p, $worldName);
        });

        $form->setTitle("§l§6" . $lang->get("gui_rules_title_full", ["world" => $worldName]));
        foreach (["break", "place", "interact", "container", "pvp", "pve", "explosion", "fire", "water", "lava", "drop", "pickup", "mob"] as $rule) {
            $key = $this->getRuleKey($rule);
            $form->addToggle($lang->get("rule_" . $rule) . "\n§r§7" . $lang->get("rule_" . $rule . "_desc"), $settings[$key] ?? false);
        }
        $player->sendForm($form);
    }

    private function getRuleKey(string $rule): string
    {
        $map = ["break" => "disable_break", "place" => "disable_place", "interact" => "disable_interact", "container" => "disable_container", "pvp" => "disable_pvp", "pve" => "disable_pve", "explosion" => "disable_explosion", "fire" => "disable_fire_spread", "water" => "disable_water_flow", "lava" => "disable_lava_flow", "drop" => "disable_item_drop", "pickup" => "disable_item_pickup", "mob" => "restore_mob_behavior"];
        return $map[$rule] ?? $rule;
    }

    private function sendLanguageForm(Player $player): void
    {
        $lang = $this->plugin->getLang();
        $available = $lang->getAvailable();
        $current = $lang->getCurrentLang();

        $form = new \MengBao\MEBForms\SimpleForm(function (Player $player, mixed $data) use ($available) {
            if ($data === null || $data === "back") {
                $this->sendMainForm($player);
                return;
            }
            if (is_string($data) && in_array($data, $available, true)) {
                if ($this->plugin->getLang()->switchLang($data)) {
                    $player->sendMessage($this->plugin->getLang()->get("prefix") . "§a" . $this->plugin->getLang()->get("language_switched", ["lang" => $this->plugin->getLang()->getLanguageName($data)]));
                    $this->sendMainForm($player);
                } else {
                    $player->sendMessage($this->plugin->getLang()->get("prefix") . "§c" . $this->plugin->getLang()->get("language_switch_failed"));
                    $this->sendMainForm($player);
                }
            }
        });

        $form->setTitle("§l§6" . $lang->get("gui_language_title_full"));
        $form->setContent("§e" . $lang->get("gui_language_content", ["current" => $lang->getLanguageName($current)]));

        foreach ($available as $code) {
            $icon = $code === $current ? "§a✔ " : "§7";
            $form->addButton("{$icon}§e" . $lang->getLanguageName($code) . "\n§3{$code}", 0, "textures/ui/language_glyph_color", $code);
        }

        $form->addButton("§a" . $lang->get("gui_button_back"), 0, "textures/ui/back_button_default_light", "back");
        $player->sendForm($form);
    }
}
