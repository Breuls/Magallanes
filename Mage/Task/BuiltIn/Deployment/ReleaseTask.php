<?php
/*
* This file is part of the Magallanes package.
*
* (c) Andrés Montañez <andres@andresmontanez.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Mage\Task\BuiltIn\Deployment;

use Mage\Task\AbstractTask;
use Mage\Task\Releases\IsReleaseAware;
use Mage\Task\Releases\SkipOnOverride;

/**
 * Task for Releasing a Deploy
 *
 * @author Andrés Montañez <andres@andresmontanez.com>
 */
class ReleaseTask extends AbstractTask implements IsReleaseAware, SkipOnOverride
{
	/**
	 * (non-PHPdoc)
	 * @see \Mage\Task\AbstractTask::getName()
	 */
    public function getName()
    {
        return 'Releasing [built-in]';
    }

    /**
     * Releases a Deployment: points the current symbolic link to the release directory
     * @see \Mage\Task\AbstractTask::run()
     */
    public function run()
    {
        if ($this->getConfig()->release('enabled', false) == true) {
            $releasesDirectory = $this->getConfig()->release('directory', 'releases');
            $symlink = $this->getConfig()->release('symlink', 'current');

            if (substr($symlink, 0, 1) == '/') {
                $releasesDirectory = rtrim($this->getConfig()->deployment('to'), '/') . '/' . $releasesDirectory;
            }

            $currentCopy = $releasesDirectory . '/' . $this->getConfig()->getReleaseId();

            // Remove symlink if exists; create new symlink and change owners
            $command = 'rm -f ' . $symlink
                     . ' ; '
                     . 'ln -sf ' . $currentCopy . ' ' . $symlink;
            $result = $this->runCommandRemote($command);

            // Fetch the user and group from base directory, make user/group owner of newly deployed release
            $userGroup = '';
            $resultFetch = $this->runCommandRemote('ls -ld . | awk \'{print \$3":"\$4}\'', $userGroup);

            if ($resultFetch && $userGroup != '') {
                $commands = [
                    'chown -h ' . $userGroup . ' ' . $symlink,
                    'chown -R ' . $userGroup . ' ' . $currentCopy,
                    'chown ' . $userGroup . ' ' . $releasesDirectory
                ];
                // don't check the output result: if chown fails, fail silently
                $this->runCommandRemote(implode(' && ', $commands));
            }

            return $result;

        } else {
            return false;
        }
    }
}

