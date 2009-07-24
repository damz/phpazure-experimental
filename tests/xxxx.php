<?php
// Reset containers and tables

/** Error reporting */
error_reporting(E_ALL | E_STRICT);

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . '../library/');

/** Microsoft_Azure_Storage_Table */
require_once 'Microsoft/Azure/Storage/Table.php';

/** Microsoft_Azure_Storage_Blob */
require_once 'Microsoft/Azure/Storage/Blob.php';

/** Microsoft_Azure_Storage_Queue */
require_once 'Microsoft/Azure/Storage/Queue.php';


// table
$storageClient = new Microsoft_Azure_Storage_Table('table.core.windows.net', 'phpstorage', 'WXuEUKMijV/pxUu5/RhDn1bYRuFlLSbmLUJJWRqYQ/uxbMpEx+7S/jo9sT3ZIkEucZGbEafDuxD1kwFOXf3xyw==');
$result1 = $storageClient->listTables();
var_dump($result1);
foreach ($result1 as $table) {
    $storageClient->deleteTable($table->Name);
}

// blob
$storageClient = new Microsoft_Azure_Storage_Blob('blob.core.windows.net', 'phpstorage', 'WXuEUKMijV/pxUu5/RhDn1bYRuFlLSbmLUJJWRqYQ/uxbMpEx+7S/jo9sT3ZIkEucZGbEafDuxD1kwFOXf3xyw==');
$result2 = $storageClient->listContainers();
var_dump($result2);
foreach ($result2 as $container) {
    $storageClient->deleteContainer($container->Name);
}

// queue
$storageClient = new Microsoft_Azure_Storage_Queue('queue.core.windows.net', 'phpstorage', 'WXuEUKMijV/pxUu5/RhDn1bYRuFlLSbmLUJJWRqYQ/uxbMpEx+7S/jo9sT3ZIkEucZGbEafDuxD1kwFOXf3xyw==');
$result3 = $storageClient->listQueues();
var_dump($result3);
foreach ($result3 as $queue) {
    $storageClient->deleteQueue($queue->Name);
}