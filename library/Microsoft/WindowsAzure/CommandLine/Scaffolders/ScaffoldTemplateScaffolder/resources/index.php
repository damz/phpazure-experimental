<?php
/**
 * @command-handler $Name$Scaffolder
 * 
 * @command-handler-description Enter a description here for $Name$Scaffolder...
 * @command-handler-header Optional $Name$Scaffolder header information.
 * @command-handler-footer 
 * @command-handler-footer Optional $Name$Scaffolder footer information.
 */ 
class $Name$Scaffolder
	extends Microsoft_WindowsAzure_CommandLine_PackageScaffolder_PackageScaffolderAbstract
{
	/**
	 * Runs the $Name$Scaffolder.
	 * 
	 * @command-name Run
	 * @command-description Runs the $Name$Scaffolder.
	 * 
	 * @command-parameter-for $scaffolderFile Microsoft_Console_Command_ParameterSource_Argv --Phar Required. The scaffolder Phar file path. This is injected automatically.
	 * @command-parameter-for $rootPath Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile --OutputPath|-out Required. The path to create the Windows Azure project structure. This is injected automatically. 
	 * @command-parameter-for $name Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile|Microsoft_Console_Command_ParameterSource_Env --Name|-n Required. The name. This is a sample argument definition.
	 */
	public function runCommand($scaffolderFile, $rootPath, $name)
	{
		// Sample: load Phar (if needed)
		$phar = new Phar($scaffolderFile);
		
		// Sample: extract to disk
		$this->log('Extracting resources...');
		$this->createDirectory($rootPath);
		$this->extractResources($phar, $rootPath);
		$this->log('Extracted resources.');
		
		// Sample: apply transforms
		$this->log('Applying transforms...');
		$this->applyTransforms($rootPath, array(
			'Name' => $name
		));
		$this->log('Applied transforms.');
	}
}