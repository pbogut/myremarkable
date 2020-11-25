<?php

class RoboFile extends \Robo\Tasks
{
    public function rmCopyFiles($sshIp)
    {
        $fileList = $this->getConfig('copy-files', []);
        foreach ($fileList as $src => $dst) {
            echo "$src -> $dst\n";
            $this->taskExec('scp')
                ->args([$src, "root@$sshIp:$dst"])
                ->run();
        }

    }

    protected function getConfig($key, $default = null)
    {
        $json = file_get_contents('configuration.json');
        $data = json_decode($json, true);

        $tmp = $data;
        foreach (explode('.', $key) as $oneKey) {
            if (!isset($tmp[$oneKey])) {
                return $default;
            }
            $tmp = $tmp[$oneKey];
        }

        return $tmp ?? $default;
    }
}
