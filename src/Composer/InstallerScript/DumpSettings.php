<?php
declare(strict_types=1);
namespace Helhum\TYPO3\ConfigHandling\Composer\InstallerScript;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Helmut Hummel <info@helhum.io>
 *  All rights reserved
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Composer\Script\Event as ScriptEvent;
use Composer\Util\Filesystem;
use Helhum\TYPO3\ConfigHandling\SettingsFiles;
use Helhum\Typo3Console\Mvc\Cli\CommandDispatcher;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

class DumpSettings implements InstallerScript
{
    public function run(ScriptEvent $event): bool
    {
        return $this->generateLocalConfigurationFile($event);
    }

    private function generateLocalConfigurationFile(ScriptEvent $event): bool
    {
        $io = $event->getIO();
        $fileSystem = new Filesystem();
        $fileSystem->ensureDirectoryExists(getenv('TYPO3_PATH_APP') . '/config');
        $fileSystem->ensureDirectoryExists(getenv('TYPO3_PATH_APP') . '/var/cache/code/cache_core');
        $localConfigurationFile = getenv('TYPO3_PATH_ROOT') . '/typo3conf/LocalConfiguration.php';
        $mainSettingsFile = SettingsFiles::getSettingsFile(true);
        if (!$this->allowGeneration($localConfigurationFile)) {
            if (!file_exists($mainSettingsFile)) {
                $commandDispatcher = CommandDispatcher::createFromComposerRun();
                $commandDispatcher->executeCommand(
                    'settings:extract',
                    [
                        '-c',
                        $mainSettingsFile,
                    ]
                );
                $io->writeError(
                    '<info>TYPO3: Migrated settings from LocalConfiguration.php to config/settings.yaml.</info>',
                    $io::VERBOSE
                );
            } else {
                $io->writeError(
                    sprintf(
                        '<error>TYPO3: Can not extract settings from LocalConfiguration.php to %s because the latter is already present.</error>',
                        basename($mainSettingsFile)
                    )
                );

                return false;
            }
        }

        $io->writeError(
            '<info>TYPO3: Writing LocalConfiguration.php</info>',
            true,
            $io::VERBOSE
        );

        return file_put_contents(
            $localConfigurationFile,
            <<<'FILE'
<?php
// Auto generated by helhum/typo3-config-handling
// Do not edit this file
return [
];
FILE
        ) > 0;
    }

    private function allowGeneration(string $file): bool
    {
        if (!file_exists($file)) {
            return true;
        }

        return strpos(file_get_contents($file), 'Auto generated by helhum/typo3-config-handling') !== false;
    }
}