<?php
/**
 * $Name$ scaffolder
 */
class Scaffolder
	extends Microsoft_WindowsAzure_CommandLine_PackageScaffolder_PackageScaffolderAbstract
{
	/**
	 * Invokes the $Name$ scaffolder.
	 *
	 * @param Phar $phar Phar archive containing the current scaffolder.
	 * @param string $root Path Root path.
	 * @param array $options Options array (key/value).
	 */
	public function invoke(Phar $phar, $rootPath, $options = array())
	{
		// Sample: check for a parameter
		if (empty($options['Name'])) {
			throw new Microsoft_Console_Exception('Missing argument for scaffolder: Name');
		}
		
		// Sample: extract to disk
		$this->log('Extracting resources...');
		$this->createDirectory($rootPath);
		$this->extractResources($phar, $rootPath);
		$this->log('Extracted resources.');
		
		// Sample: apply transforms
		$this->log('Applying transforms...');
		$this->applyTransforms($rootPath, $options);
		$this->log('Applied transforms.');
	}
}
