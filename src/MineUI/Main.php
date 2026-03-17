<?php

declare(strict_types=1);

namespace MineUI;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\Server;

use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase {

    public function onEnable(): void {
        $this->saveDefaultConfig();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {

        if(!$sender instanceof Player) {
            return true;
        }

        switch($command->getName()) {

            case "mines":
                $this->openMinesUI($sender);
                return true;

            case "mine":
                if(!isset($args[0])) {
                    $sender->sendMessage("§cUsage: /mine <name>");
                    return true;
                }

                $this->teleportToMine($sender, strtoupper($args[0]));
                return true;
        }

        return false;
    }

    private function openMinesUI(Player $player): void {
        $config = $this->getConfig();

        $form = new SimpleForm(function(Player $player, $data) {
            if($data === null) return;

            $mines = array_keys($this->getConfig()->get("mines"));
            $mineKey = $mines[$data] ?? null;

            if($mineKey !== null) {
                $this->teleportToMine($player, $mineKey);
            }
        });

        $form->setTitle($config->getNested("form.title"));
        $form->setContent($config->getNested("form.content"));

        foreach($config->get("mines") as $key => $mine) {

            $hasPerm = $player->hasPermission($mine["permission"]);

            $status = $hasPerm
                ? $config->getNested("status-format.unlocked")
                : $config->getNested("status-format.locked");

            $format = $config->getNested("button-format.format");

            $buttonText = str_replace(
                ["{name}", "{status}"],
                [$mine["display"], $status],
                $format
            );

            $form->addButton($buttonText);
        }

        $player->sendForm($form);
    }

    private function teleportToMine(Player $player, string $mineKey): void {
        $config = $this->getConfig();

        $mine = $config->getNested("mines.$mineKey");

        if($mine === null) {
            $player->sendMessage($config->getNested("messages.not-found"));
            return;
        }

        if(!$player->hasPermission($mine["permission"])) {
            $player->sendMessage($mine["upgrade-message"] ?? $config->getNested("messages.upgrade"));
            return;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($mine["world"]);

        if($world === null) {
            $player->sendMessage("§cWorld not loaded.");
            return;
        }

        $pos = new Position($mine["x"], $mine["y"], $mine["z"], $world);
        $player->teleport($pos);

        $msg = str_replace("{mine}", $mine["display"], $config->getNested("messages.teleported"));
        $player->sendMessage($msg);
    }
}
