<?php
namespace TYPO3\Deploy\Domain\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Deploy".               *
 *                                                                        *
 *                                                                        */

use \TYPO3\Deploy\Domain\Model\Node;
use \TYPO3\Deploy\Domain\Model\Deployment;

/**
 * A shell command service
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ShellCommandService {

	/**
	 * Execute a shell command
	 *
	 * @param string $command The shell command to execute
	 * @param \TYPO3\Deploy\Domain\Model\Node $node Node to execute command against, NULL means localhost
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment
	 * @param boolean $ignoreErrors If this command should ignore exit codes unequeal zero
	 * @return mixed The output of the shell command or FALSE if the command returned a non-zero exit code and $ignoreErrors was enabled.
	 */
	public function execute($command, Node $node, Deployment $deployment, $ignoreErrors = FALSE) {
		if ($node === NULL || $node->getHostname() === 'localhost') {
			list($exitCode, $returnedOutput) = $this->executeLocalCommand($command, $deployment);
		} else {
			list($exitCode, $returnedOutput) = $this->executeRemoteCommand($command, $node, $deployment);
		}
		if ($ignoreErrors !== TRUE && $exitCode !== 0) {
			throw new \Exception('Command returned non-zero return code', 1311007746);
		}
		return ($exitCode === 0 ? $returnedOutput : FALSE);
	}

	/**
	 * Execute a shell command locally
	 *
	 * @param string $command
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment
	 * @return array
	 */
	protected function executeLocalCommand($command, Deployment $deployment) {
		$deployment->getLogger()->log('    (localhost): "' . $command . '"', LOG_DEBUG);
		$returnedOutput = '';

		$fp = popen($command, 'r');
		while (($line = fgets($fp)) !== FALSE) {
			$deployment->getLogger()->log('> ' . $line);
			$returnedOutput .= $line;
		}
		$exitCode = pclose($fp);

		return array($exitCode, $returnedOutput);
	}


	/**
	 * Execute a shell command via SSH
	 *
	 * @param string $command
	 * @param \TYPO3\Deploy\Domain\Model\Node $node
	 * @param \TYPO3\Deploy\Domain\Model\Deployment $deployment
	 * @return array
	 */
	protected function executeRemoteCommand($command, Node $node, Deployment $deployment) {
		$deployment->getLogger()->log('    $' . $node->getName() . ': "' . $command . '"', LOG_DEBUG);
		$username = $node->getOption('username');
		$hostname = $node->getHostname();
		$returnedOutput = '';

		// TODO Get SSH options from node or deployment
		$fp = popen('ssh -A ' . $username . '@' . $hostname . ' ' . escapeshellarg($command) . ' 2>&1', 'r');
		while (($line = fgets($fp)) !== FALSE) {
			$deployment->getLogger()->log('    > ' . rtrim($line));
			$returnedOutput .= $line;
		}
		$exitCode = pclose($fp);

		return array($exitCode, $returnedOutput);
	}

}
?>