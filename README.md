# MEBWorldProtect

一个功能强大的PocketMine-MP世界保护插件，提供全面的保护规则、液体流动控制和生物行为管理。

## 功能特性

- 🎨 **完整GUI支持**：所有功能均有GUI（需安装MEBForms）
- 🛡️ **全面保护系统**：13种可独立配置的保护规则（方块交互、液体流动、生物行为等）
- 👥 **世界管理员系统**：为每个世界单独添加管理员
- 🌍 **按世界配置**：每个世界独立设置保护选项
- 🔒 **分级权限管理**：全局管理员和世界管理员分离
- 🌐 **多语言支持**：支持中文和英文等多语言，可在游戏内切换

## 保护规则

| 规则 | 说明 |
|------|------|
| **break** | 禁止破坏方块 |
| **place** | 禁止放置方块 |
| **interact** | 禁止与方块交互（开门、按按钮等） |
| **container** | 禁止打开容器（箱子、熔炉等） |
| **pvp** | 禁止玩家互相伤害 |
| **pve** | 禁止玩家伤害生物 |
| **explosion** | 禁止爆炸破坏方块 |
| **fire** | 禁止火焰蔓延 |
| **water** | 禁止水流动 |
| **lava** | 禁止岩浆流动 |
| **drop** | 禁止丢弃物品 |
| **pickup** | 禁止拾取物品 |
| **mob** | 恢复生物行为（移动和攻击，需要生物AI） |

## 指令

| 指令 | 描述 | 权限 |
|------|------|------|
| `/mebwp help` | 显示帮助信息 | MEBWorldProtect.ge |
| `/mebwp` 或 `/mebwp gui` | 打开GUI界面 | MEBWorldProtect.ge |
| `/mebwp info [世界名]` | 查看世界保护信息 | MEBWorldProtect.ge |
| `/mebwp list` | 列出所有已保护的世界 | MEBWorldProtect.ge |
| `/mebwp listadmin [世界名]` | 查看世界管理员列表 | MEBWorldProtect.ge |
| `/mebwp enable <世界名>` | 启用世界保护 | 控制台或世界管理员 |
| `/mebwp disable <世界名>` | 禁用世界保护 | 控制台或世界管理员 |
| `/mebwp set <世界名> <规则> <true\|false>` | 设置保护规则 | 控制台或世界管理员 |
| `/mebwp addadmin <世界名> <玩家>` | 添加世界管理员 | 控制台或MEBSociety最高权限 |
| `/mebwp removeadmin <世界名> <玩家>` | 移除世界管理员 | 控制台或MEBSociety最高权限 |
| `/mebwp reload` | 重载配置 | 控制台或MEBSociety最高权限 |

### 规则名称

```
break, place, interact, container, pvp, pve, explosion, fire, water, lava, drop, pickup, mob
```

## 权限系统

### 两级权限管理

1. **MEBSociety 最高权限 或 控制台**
   - MEBSociety 插件配置中的"最高权限"玩家
   - 可以无视所有世界的保护规则
   - 可以启用/禁用任何世界的保护
   - 可以配置任何世界的保护规则
   - 可以添加/移除任何世界的管理员
   - 可以重载配置

2. **世界管理员**（通过命令添加到特定世界）
   - 只能无视指定世界的保护规则
   - 可以启用/禁用被授权世界的保护
   - 可以配置被授权世界的保护规则
   - **不能**添加/移除管理员（仅 MEBSociety 最高权限或控制台）
   - **不能**重载配置（仅 MEBSociety 最高权限或控制台）

> **注意**：插件不使用OP权限或权限节点，所有管理权限完全基于配置文件中的管理员名单。添加/删除管理员和重载配置仅限控制台或 MEBSociety 最高权限执行。

## 使用示例

### 启用主世界保护
```
/mebwp enable world
```

### 添加世界管理员
```
/mebwp addadmin world PlayerName
```

### 禁止主世界破坏方块
```
/mebwp set world break true
```

### 禁止主世界的水流动
```
/mebwp set world water true
```

### 查看当前世界信息
```
/mebwp info
```

### 查看世界管理员列表
```
/mebwp listadmin world
```

## 配置说明

配置文件位于 `plugin_data/MEBWorldProtect/config.yml`

```yaml
worlds:
  world:
    enabled: true
    admins:
      - PlayerName1
      - PlayerName2
    disable_break: true
    disable_place: true
    # ... 其他保护规则
```

- `enabled`: 是否启用该世界的保护
- `admins`: 世界管理员列表（不区分大小写）
- 其他字段：各项保护规则的开关

# MEBWorldProtect API 文档

## 概述

MEBWorldProtect 提供了公共API供其他插件使用，以实现统一的世界保护机制。

## 公共API方法

### 1. getWorldConfig(string $worldName): array

获取指定世界的完整配置。

**参数：**
- `$worldName` - 世界文件夹名称

**返回值：**
- `array` - 世界配置数组，如果世界未配置则返回默认配置

**示例：**
```php
$mebWorldProtect = $this->getServer()->getPluginManager()->getPlugin("MEBWorldProtect");
if ($mebWorldProtect !== null) {
    $config = $mebWorldProtect->getWorldConfig("world");
    // $config = ["restore_mob_behavior" => true, "admins" => [...], ...]
}
```

---

### 2. canMobBehave(string $worldName): bool

检查指定世界是否允许生物行为（移动、攻击等）。

**参数：**
- `$worldName` - 世界文件夹名称

**返回值：**
- `bool` - `true` 允许生物行为，`false` 禁止生物行为

**示例：**
```php
$mebWorldProtect = $this->getServer()->getPluginManager()->getPlugin("MEBWorldProtect");
if ($mebWorldProtect !== null) {
    if ($mebWorldProtect->canMobBehave("world")) {
        // 允许生物AI运行
    } else {
        // 禁止生物AI运行
    }
}
```

---

### 3. setMobBehavior(string $worldName, bool $enabled): void

设置指定世界的生物行为权限。

**参数：**
- `$worldName` - 世界文件夹名称
- `$enabled` - `true` 允许，`false` 禁止

**示例：**
```php
$mebWorldProtect = $this->getServer()->getPluginManager()->getPlugin("MEBWorldProtect");
if ($mebWorldProtect !== null) {
    // 禁用主世界的生物行为
    $mebWorldProtect->setMobBehavior("world", false);
}
```

---

### 4. isWorldAdmin(string $playerName, string $worldName): bool

检查玩家是否是指定世界的管理员。

**参数：**
- `$playerName` - 玩家名称
- `$worldName` - 世界文件夹名称

**返回值：**
- `bool` - `true` 是管理员，`false` 不是

---

### 5. addWorldAdmin(string $worldName, string $playerName): bool

添加世界管理员。

**参数：**
- `$worldName` - 世界文件夹名称
- `$playerName` - 玩家名称

**返回值：**
- `bool` - `true` 添加成功，`false` 已经是管理员

---

### 6. removeWorldAdmin(string $worldName, string $playerName): bool

移除世界管理员。

**参数：**
- `$worldName` - 世界文件夹名称
- `$playerName` - 玩家名称

**返回值：**
- `bool` - `true` 移除成功，`false` 不是管理员

---

### 7. getWorldAdmins(string $worldName): array

获取世界管理员列表。

**参数：**
- `$worldName` - 世界文件夹名称

**返回值：**
- `array` - 管理员名称数组

---

## 配置文件结构

### config.yml

```yaml
language: zh_CN

default:
  restore_mob_behavior: true  # 默认允许生物行为
  admins: []

worlds:
  world:
    restore_mob_behavior: true
    admins:
      - "admin1"
      - "admin2"
  
  dungeon:
    restore_mob_behavior: false  # 副本禁止生物行为
    admins:
      - "admin1"
```

---

## 第三方插件集成指南

### 步骤1：检查插件是否存在

```php
$mebWorldProtect = $this->getServer()
    ->getPluginManager()
    ->getPlugin("MEBWorldProtect");

if ($mebWorldProtect === null || !$mebWorldProtect->isEnabled()) {
    // 插件不存在，使用默认行为
    return;
}
```

### 步骤2：调用API

```php
// 检查生物行为权限
$worldName = $entity->getWorld()->getFolderName();
if (!$mebWorldProtect->canMobBehave($worldName)) {
    // 该世界禁止生物行为，跳过AI
    return;
}

// 执行你的插件逻辑
```

### 步骤3：错误处理

```php
try {
    if (method_exists($mebWorldProtect, "canMobBehave")) {
        return $mebWorldProtect->canMobBehave($worldName);
    }
} catch (\Throwable $e) {
    // API调用失败，记录日志
    $this->getLogger()->warning("调用MEBWorldProtect API失败: " . $e->getMessage());
    return true; // 默认允许
}
```

---

## 软依赖配置

在你的 `plugin.yml` 中添加软依赖：

```yaml
name: YourPlugin
version: 1.0.0
api: [5.0.0]
softdepend: [MEBWorldProtect]
```

这样PocketMine会确保MEBWorldProtect在你的插件之前加载（如果存在）。

---

## 最佳实践

### 1. 始终检查插件存在性
```php
if ($plugin === null || !$plugin->isEnabled()) {
    // 默认行为
}
```

### 2. 使用 method_exists 检查
```php
if (method_exists($plugin, "canMobBehave")) {
    // 调用API
}
```

### 3. 提供回退方案
```php
try {
    return $plugin->canMobBehave($worldName);
} catch (\Throwable $e) {
    return true; // 默认允许
}
```

### 4. 不要直接读取配置文件
❌ **错误做法：**
```php
$config = yaml_parse_file($plugin->getDataFolder() . "config.yml");
```

✅ **正确做法：**
```php
$config = $plugin->getWorldConfig($worldName);
```

---

## 版本兼容性

- **MEBWorldProtect** 1.0.0+
- **PocketMine-MP** 5.0.0+

---

## 支持

如有问题请访问：
- GitHub: https://github.com/MengBaofh/MEBWorldProtect
- 文档：查看本文件
