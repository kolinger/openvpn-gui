<?php

use Nette\Utils\Strings;



/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class Configuration extends \Nette\Object
{

	const TEMP_DIR = 'certificates';

	/**
	 * @var Nette\DI\Container
	 */
	private $container;

	/**
	 * @var SSH
	 */
	private $ssh;

	/**
	 * @var string
	 */
	private $remotePath;



	/**
	 * @param Nette\DI\Container $container
	 * @param SSH $ssh
	 */
	public function __construct(\Nette\DI\Container $container, SSH $ssh)
	{
		$this->ssh = $ssh;
		$this->container = $container;
		$this->remotePath = $container->parameters['rsaDir'] . '/keys';
	}



	/**
	 * @return string
	 */
	public function getTempDir()
	{
		$tempDir = $this->container->parameters['tempDir'] . '/' . static::TEMP_DIR;
		if (!is_dir($tempDir)) {
			mkdir($tempDir);
		}

		$tempDir .= '/' . Strings::random();
		mkdir($tempDir);

		return $tempDir;
	}



	/**
	 * @param Account $account
	 * @return array
	 */
	public function getFiles(Account $account)
	{
		return array(
			'ca.crt',
			$account->getUserName() . '.crt',
			$account->getUserName() . '.key',
		);
	}



	/**
	 * @param Account $account
	 * @param string $file
	 */
	public function createConfiguration(Account $account, $file)
	{
		$template = new \Nette\Templating\FileTemplate();
		$template->registerFilter(new Nette\Latte\Engine);
		$template->setFile(__DIR__ . '/../templates/openvpn.latte');
		$template->userName = $account->getUsername();
		$template->save($file);
	}



	/**
	 * @param array $files
	 * @param string $file
	 * @param string $tempDir
	 */
	public function createArchive(array $files, $file, $tempDir)
	{
		$zip = new \ZipArchive;
		$zip->open($file, ZipArchive::CREATE);
		foreach ($files as $file) {
			$zip->addFile($tempDir . '/' . $file, $file);
		}
		$zip->close();
	}



	/**
	 * @param Account $account
	 * @return string
	 */
	public function downloadAndZipFiles(Account $account)
	{
		// init
		$tempDir = $this->getTempDir();
		$files = $this->getFiles($account);

		// download
		foreach ($files as $file) {
			if (file_exists($tempDir . '/' . $file)) {
				unlink($tempDir . '/' . $file);
			}
			$this->ssh->download($this->remotePath . '/' . $file, $tempDir . '/' . $file);
		}

		// configuration
		$this->createConfiguration($account, $tempDir . '/gateway.ovpn');
		$files[] = 'gateway.ovpn';

		// zip
		$this->createArchive($files, $tempDir . '.zip', $tempDir);

		// cleanup
		foreach ($files as $file) {
			unlink($tempDir . '/' . $file);
		}
		rmdir($tempDir);

		return $tempDir . '.zip';
	}

}