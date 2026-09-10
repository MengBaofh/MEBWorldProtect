# MEBWorldProtect

一个功能强大的PocketMine-MP世界保护插件，提供全面的保护规则、液体流动控制和生物行为管理。

## 功能特性

- 🛡️ **全面保护系统**：13种可独立配置的保护规则
- 🌊 **液体流动控制**：独立控制水和岩浆的流动
- 🐾 **生物行为管理**：恢复或限制生物的移动和攻击能力
- 👥 **世界管理员系统**：为每个世界单独添加管理员
- 🎨 **完整GUI支持**：可选的MEBForms图形界面（所有功能均有GUI）
- 🌍 **按世界配置**：每个世界独立设置保护选项
- 🔒 **分级权限管理**：全局管理员和世界管理员分离
- 🌐 **多语言支持**：支持中文和英文
- ⚙️ **灵活配置**：支持指令和GUI两种配置方式

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
| **mob** | 恢复生物原版行为 |

## 指令

| 指令 | 描述 | 权限 |
|------|------|------|
| `/mebwp help` | 显示帮助信息 | MEBWorldProtect.ge |
| `/mebwp` | 打开GUI界面 | MEBWorldProtect.ge |
| `/mebwp info [世界名]` | 查看世界保护信息 | MEBWorldProtect.ge |
| `/mebwp list` | 列出所有已保护的世界 | MEBWorldProtect.ge |
| `/mebwp listadmin [世界名]` | 查看世界管理员列表 | MEBWorldProtect.ge |
| `/mebwp enable <世界名>` | 启用世界保护 | MEBWorldProtect.admin |
| `/mebwp disable <世界名>` | 禁用世界保护 | MEBWorldProtect.admin |
| `/mebwp set <世界名> <规则> <true\|false>` | 设置保护规则 | 世界管理员 |
| `/mebwp addadmin <世界名> <玩家>` | 添加世界管理员 | MEBWorldProtect.admin |
| `/mebwp removeadmin <世界名> <玩家>` | 移除世界管理员 | MEBWorldProtect.admin |
| `/mebwp reload` | 重载配置 | MEBWorldProtect.admin |

### 规则名称

```
break, place, interact, container, pvp, pve, explosion, fire, water, lava, drop, pickup, mob
```

## 权限

- `MEBWorldProtect.ge`（默认：所有玩家）- 基础权限，可使用查询命令
- `MEBWorldProtect.admin`（默认：OP）- 全局管理权限，可无视所有保护规则并管理所有世界

## 权限系统

### 两级权限管理

1. **全局管理员**（`MEBWorldProtect.admin` 或 MEBSociety 最高权限）
   - 可以无视所有世界的保护规则
   - 可以启用/禁用任何世界的保护
   - 可以添加/移除任何世界的管理员
   - 可以配置任何世界的保护规则

2. **世界管理员**（通过命令添加）
   - 只能无视指定世界的保护规则
   - 可以配置被授权世界的保护规则
   - 可以为被授权世界添加/移除其他管理员
   - 无法启用/禁用世界保护（仅全局管理员可以）

### MEBSociety 集成

如果安装了 MEBSociety 插件，MEBSociety 的"最高权限"玩家将自动获得全局管理员权限，无需额外配置。

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

### 恢复下界生物行为
```
/mebwp set nether mob true
```

### 查看当前世界信息
```
/mebwp info
```

### 查看世界管理员列表
```
/mebwp listadmin world
```

### 使用GUI管理（推荐）
```
/mebwp gui
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

