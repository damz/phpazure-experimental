[Reflection.Assembly]::LoadWithPartialName("Microsoft.WindowsAzure.ServiceRuntime")

$overridePhpIni = Test-Path ../php/php.ini
$roleRoot = (Resolve-Path ../..).ToString()

if ($overridePhpIni -eq 'True') {
	[Environment]::SetEnvironmentVariable('PHPRC', $roleRoot + '\approot\php', 'Machine')
	$message = 'Using php.ini found at location ' + $roleRoot + '\approot\php'
	Write-Host $message

	$rdRoleId = [Environment]::GetEnvironmentVariable("RdRoleId", "Machine")
	[Environment]::SetEnvironmentVariable("RdRoleId", [Microsoft.WindowsAzure.ServiceRuntime.RoleEnvironment]::CurrentRoleInstance.Id, "Machine")
	if ([Microsoft.WindowsAzure.ServiceRuntime.RoleEnvironment]::CurrentRoleInstance.Id.Contains('_IN_')) {
		if ($rdRoleId -ne [Microsoft.WindowsAzure.ServiceRuntime.RoleEnvironment]::CurrentRoleInstance.Id) {
		    Restart-Computer
		}
	}
}

