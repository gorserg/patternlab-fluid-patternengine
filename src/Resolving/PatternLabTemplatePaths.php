<?php
declare(strict_types=1);

namespace NamelessCoder\FluidPatternEngine\Resolving;

use TYPO3Fluid\Fluid\View\TemplatePaths;
use PatternLab\Config;

class PatternLabTemplatePaths extends TemplatePaths
{

    public function getPartialPathAndFilename($partialName)
    {
        //find valid partial name by wildcard
        $partialName = $this->getValidPartialName($partialName);
        return parent::getPartialPathAndFilename((new PartialNamingHelper())->determinePatternCleanName($partialName));
    }

    public function getLayoutPathAndFilename($layoutName = 'default')
    {
        print 'layoutName1 = $layoutName\n';
        // drop prefix (layouts-page-1col -> page-1col)
        $layoutName = str_replace('layouts-', '', $layoutName);
        print 'layoutName2 = $layoutName\n';

        $paths = $this->getLayoutRootPaths();

        //find relative layout path and name in layouts folder
        if ($layoutName !== 'default') {
            $dirIterator = new \RecursiveDirectoryIterator($paths[1]);
            foreach (new \RecursiveIteratorIterator($dirIterator) as $d) {
                if (preg_match('~' . $paths[1] . '(\S*' . $layoutName . '.fluid)~', strval($d), $matches)) {
                    $layoutName = $matches[1];
                    break;
                }
            }
        }

        return $this->resolveFileInPaths($paths, $layoutName);
    }


    private function getValidPartialName($partialName)
    {
        $shortNameRegex = Config::getOption('renderTemplatesMatches')['shortName'];
        if(!$shortNameRegex)
            $shortNameRegex = '/^[0-9-]*(?P<part>[a-zA-z0-9-_]+)\/[0-9-]*(\g<part>)$/';
        if (preg_match($shortNameRegex, $partialName, $matches) && $patternDirName = $this->searchPatternDirByShortNameDir($matches[1])) {
            $partialName = $this->searchPatternsFullNameByShortNameRecursive($patternDirName, $matches[2]);
        }
        print 'partialName = $partialName\n';
        return $partialName;
    }

    private function searchPatternDirByShortNameDir($shortNameDir)
    {
        $dirs = scandir($this->getPatternSourceDir());
        foreach ($dirs as $dir) {
            if (strpos($dir, $shortNameDir) !== false) return $dir;
        }
        return false;
    }

    private function searchPatternsFullNameByShortNameRecursive($dir, $shortName)
    {
        $searchDir = $this->getPatternSourceDir() . DIRECTORY_SEPARATOR . $dir;
        $path = '';
        $files = scandir($searchDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (!is_dir($searchDir . DIRECTORY_SEPARATOR . $file)) {
                if (strpos($file, $shortName) !== false && strpos($file, '.fluid') !== false) {
                    $path = $dir . DIRECTORY_SEPARATOR . str_replace('.fluid', '', $file);
                    break;
                }
            } else {
                $path = $this->searchPatternsFullNameByShortNameRecursive($dir . DIRECTORY_SEPARATOR . $file, $shortName);
            }
        }
        return $path;
    }

    private function getPatternSourceDir()
    {
        return Config::getOption("patternSourceDir");
    }
}
