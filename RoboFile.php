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

    public function rmReboot($sshIp)
    {
        if ($this->io()->confirm('Do you want to reboot your tablet?', false)) {
            $this->taskExec('ssh')
                 ->args(["root@$sshIp", '-t', '/sbin/reboot'])
                 ->run();
        }
    }

    public function rmCopyTemplates($sshIp)
    {

        $templatesRemote = "root@$sshIp:/usr/share/remarkable/templates";
        $templatesSrc = "$templatesRemote/templates.json";
        $templatesTmp = "templates/templates.tmp";

        $result = $this->taskExec('scp')
            ->args([$templatesSrc, $templatesTmp])
            ->run()
            ->wasSuccessful();

        if (!$result) {
            throw new Exception('Can not copy templates file from reMarkable tablet.');
        }

        $templatesJson = file_get_contents($templatesTmp);
        $templatesOrig = json_decode($templatesJson, true);
        $templatesData = $templatesOrig['templates'] ?? null;

        $templatesNew = $this->getConfig('templates', []);

        if (!$templatesData) {
            throw new Exception("Can not parse templates file '$templatesTmp'.");
        }

        // get custom templates name list
        $newNames = array_map(
            function ($tpEl) {
                return $tpEl['name'];
            }, $templatesNew
        );

        $newFiles = array_map(
            function ($tpEl) {
                return $tpEl['filename'] . '.png';
            }, $templatesNew
        );

        // remove custom templates from list
        $templatesData = array_filter(
            $templatesData, function ($tpEl) use ($newNames) {
                return !in_array($tpEl['name'], $newNames);
            }
        );

        $begining = array_slice($templatesData, 0, 2);
        $ending = array_slice($templatesData, 2);
        $tempatesOrig['templates'] = array_merge($begining, $templatesNew, $ending);
        file_put_contents($templatesTmp, json_encode($tempatesOrig));

        foreach ($newFiles as $tpFile) {
            $result = $this->taskExec('scp')
                ->args([$tpFile, "$templatesRemote/$tpFile"])
                ->dir('templates')
                ->run()
                ->wasSuccessful();

            if (!$result) {
                throw new Exception('Can not copy template file, does it exists?');
            }
        }

        $result = $this->taskExec('scp')
            ->args([$templatesTmp, $templatesSrc])
            ->run()
            ->wasSuccessful();

        if ($result) {
            $this->taskExec('rm')
                ->args([$templatesTmp])
                ->run();
        }
    }

    public function rmConvertTemplates()
    {
        foreach (glob('templates/*.odt') as $tpFile) {
            $this->rmConvertTemplate(basename($tpFile));
        }
    }

    public function rmConvertTemplate($name)
    {
        if (!file_exists("templates/$name")) {
            throw new Exception("Template file '$name' is missing.");
        }

        $pngName = preg_replace('/\..*$/', '.png', $name);

        $this->taskExec('libreoffice')
            ->dir('templates')
            ->args(['--convert-to', 'png', $name])
            ->run();

        $this->taskExec('convert')
            ->dir('templates')
            ->args([$pngName, '-resize', '1404x1872!', $pngName])
            ->run();
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
