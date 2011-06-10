<?php
/**
 * Copyright (c) 2009 - 2011, RealDolmen
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of RealDolmen nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY RealDolmen ''AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL RealDolmen BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Microsoft
 * @package    Microsoft_Console
 * @subpackage Exception
 * @version    $Id: Exception.php 55733 2011-01-03 09:17:16Z unknown $
 * @copyright  Copyright (c) 2009 - 2011, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 */

/**
 * @see Microsoft_AutoLoader
 */
require_once dirname(__FILE__) . '/../../AutoLoader.php';

/**
 * Package commands
 * 
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure_CommandLine
 * @copyright  Copyright (c) 2009 - 2011, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
 * 
 * @command-handler package
 * @command-handler-description Windows Azure Package commands
 * @command-handler-header Windows Azure SDK for PHP
 * @command-handler-header Copyright (c) 2009 - 2011, RealDolmen (http://www.realdolmen.com)
 * @command-handler-footer 
 * @command-handler-footer All commands support the --ConfigurationFile or -F parameter.
 * @command-handler-footer The parameter file is a simple INI file carrying one parameter
 * @command-handler-footer value per line. It accepts the same parameters as one can
 * @command-handler-footer use from the command line command.
 */
class Microsoft_WindowsAzure_CommandLine_Package
	extends Microsoft_Console_Command
{	
	/**
	 * Scaffolds a Windows Azure project structure which can be customized before packaging.
	 * 
	 * @command-name Scaffold
	 * @command-description Scaffolds a Windows Azure project structure which can be customized before packaging.
	 * 
	 * @command-parameter-for $path Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile --Path|-p Required. The path to create the Windows Azure project structure.
	 * @command-parameter-for $scaffolder Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile|Microsoft_Console_Command_ParameterSource_Env --Scaffolder|-s Optional. The path to the scaffolder to use. Defaults to Scaffolders/DefaultScaffolder.phar 
	 */
	public function scaffoldCommand($path, $scaffolder, $argv)
	{
		// Default parameter value
		if ($scaffolder == '') {
			$scaffolder = dirname(__FILE__) . '/Scaffolders/DefaultScaffolder.phar';
		}
		$scaffolder = realpath($scaffolder);
		
		// Verify scaffolder
		if (!is_file($scaffolder)) {
			throw new Microsoft_Console_Exception('Could not locate the given scaffolder: ' . $scaffolder);
		}
		
		// Include scaffolder
		$archive = new Phar($scaffolder);
		include $scaffolder;
		if (!class_exists('Scaffolder')) {
			throw new Microsoft_Console_Exception('Could not locate a class named Scaffolder in the given scaffolder: ' . $scaffolder . '. Make sure the scaffolder package contains a file named index.php and contains a class named Scaffolder.');
		}
		
		// Cleanup $argv
		$options = array();
		foreach ($argv as $arg) {
			list($key, $value) = explode(':', $arg, 2);
			while (substr($key, 0, 1) == '-') {
				$key = substr($key, 1);
			}
			$options[$key] = $value;
		}
		
		// Run scaffolder
		$scaffolderInstance = new Scaffolder();
		$scaffolderInstance->invoke($archive, $path, $options);
	}
	

	/**
	 * Packages a Windows Azure project structure.
	 * 
	 * @command-name Create
	 * @command-description Packages a Windows Azure project structure.
	 * 
	 * @command-parameter-for $path Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile --Path|-p Required. The path to package.
	 * @command-parameter-for $runDevFabric Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile --RunDevFabric|-dev Required. Switch. Run and deploy to the Windows Azure development fabric.
	 * @command-parameter-for $outputPath Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile --OutputPath|-out Optional. The output path for the resulting package. 
	 */
	public function createPackageCommand($path, $runDevFabric, $outputPath)
	{
		// Create output paths
		if ($outputPath == '') {
			$outputPath = realpath($path . '/../');
		}
		$packageOut = $outputPath . '/' . basename($path) . '.cspkg';

		// Find Windows Azure SDK bin folder
		$windowsAzureSdkFolderCandidates = array_merge(
			isset($_SERVER['ProgramFiles']) ? glob($_SERVER['ProgramFiles'] . '\Windows Azure SDK\*\bin', GLOB_NOSORT) : array(),
			isset($_SERVER['ProgramFiles(x86)']) ? glob($_SERVER['ProgramFiles(x86)'] . '\Windows Azure SDK\*\bin', GLOB_NOSORT) : array(),
			isset($_SERVER['ProgramW6432']) ? glob($_SERVER['ProgramW6432'] . '\Windows Azure SDK\*\bin', GLOB_NOSORT) : array()
		);
		if (count($windowsAzureSdkFolderCandidates) == 0) {
			throw new Microsoft_Console_Exception('Could not locate the Windows Azure SDK. Download the tools from www.azure.com or using the Web Platform Installer.');
		}
		$cspack = '"' . $windowsAzureSdkFolderCandidates[0] . '\cspack.exe' . '"';
		$csrun = '"' . $windowsAzureSdkFolderCandidates[0] . '\csrun.exe' . '"';
		
		// Open the ServiceDefinition.csdef file and check for role paths
		$serviceDefinitionFile = $path . '/ServiceDefinition.csdef';
		if (!file_exists($serviceDefinitionFile)) {
			throw new Microsoft_Console_Exception('Could not locate ServiceDefinition.csdef at ' . $serviceDefinitionFile . '.');
		}
		$serviceDefinition = simplexml_load_file($serviceDefinitionFile);
		$xmlRoles = array();
		if ($serviceDefinition->WebRole) {
			if (count($serviceDefinition->WebRole) > 1) {
	    		$xmlRoles = array_merge($xmlRoles, $serviceDefinition->WebRole);
			} else {
	    		$xmlRoles = array_merge($xmlRoles, array($serviceDefinition->WebRole));
	    	}
		}
		if ($serviceDefinition->WorkerRole) {
			if (count($serviceDefinition->WorkerRole) > 1) {
	    		$xmlRoles = array_merge($xmlRoles, $serviceDefinition->WorkerRole);
			} else {
	    		$xmlRoles = array_merge($xmlRoles, array($serviceDefinition->WorkerRole));
	    	}
		}
    		
		// Build '/role:' command parameter
		$roleArgs = array();
		foreach ($xmlRoles as $xmlRole) {
			if ($xmlRole["name"]) {
				$roleArgs[] = '/role:' . $xmlRole["name"] . ';' . realpath($path . '/' . $xmlRole["name"]);
			}
		}
		
		// Build command
		$command = $cspack;
		$args = array(
			$path . '\ServiceDefinition.csdef',
			implode(' ', $roleArgs),
			'/out:' . $packageOut
		);
		if ($runDevFabric) {
			$args[] = '/copyOnly';
		}
		passthru($command . ' ' . implode(' ', $args));
		
		// Can we copy a configuration file?
		$serviceConfigurationFile = $path . '/ServiceConfiguration.cscfg';
		$serviceConfigurationFileOut = $outputPath . '/ServiceConfiguration.cscfg';
		if (file_exists($serviceConfigurationFile) && !file_exists($serviceConfigurationFileOut)) {
			copy($serviceConfigurationFile, $serviceConfigurationFileOut);
		}
		
		// Do we have to start the development fabric?
		if ($runDevFabric) {
			passthru($csrun . ' /devstore:start');
			passthru($csrun . ' /devfabric:start');
			passthru($csrun . ' /removeAll');
			passthru($csrun . ' /run:"' . $packageOut . ';' . $serviceConfigurationFileOut . '" /launchBrowser');
		}
	}
	
	/**
	 * Creates a scaffolder from a given path.
	 * 
	 * @command-name CreateScaffolder
	 * @command-description Creates a scaffolder from a given path.
	 * 
	 * @command-parameter-for $rootPath Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile --Path|-p Required. The path to package into a scaffolder.
	 * @command-parameter-for $scaffolderFile Microsoft_Console_Command_ParameterSource_Argv|Microsoft_Console_Command_ParameterSource_ConfigFile --OutFile|-out Required. The filename of the scaffolder.
	 */
	public function createScaffolderCommand($rootPath, $scaffolderFile)
	{
		$archive = new Phar($scaffolderFile);
		$archive->buildFromIterator(
			new RecursiveIteratorIterator(
				new SourceControlFilteredRecursiveFilterIterator(
					new RecursiveDirectoryIterator(realpath($rootPath)))),
		realpath($rootPath));
	}
}
Microsoft_Console_Command::bootstrap($_SERVER['argv']);

class SourceControlFilteredRecursiveFilterIterator
	extends RecursiveFilterIterator {
	public static $filters = array('.svn', '.git');
 
    public function accept() {
    	return !in_array(
    	$this->current()->getFilename(), self::$filters, true);
    }
}