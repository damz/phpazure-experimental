<?php
if (! defined ( 'PHPUnit_MAIN_METHOD' )) {
	define ( 'PHPUnit_MAIN_METHOD', 'Microsoft_Azure_QueueTest::main' );
}

require_once 'PHPUnit/Framework.php';

require_once 'Microsoft/Azure/Storage/Queue.php';

class Microsoft_Azure_QueueTest extends PHPUnit_Framework_TestCase {
	
	protected static $queuePrefix = "phpqueuetest";
	
	protected static $uniqId = 0;
	
	protected $queue;
	
	protected $queueName = null;
	
	protected $_tempQueues = array ();
	
	public function __construct() {
		require_once 'TestConfiguration.php';
		self::$uniqId = mt_rand ( 0, 10000 );
	}
	
	protected function generateQueueName() {
		self::$uniqId ++;
		$name = self::$queuePrefix . self::$uniqId;
		$this->_tempQueues [] = $name;
		return $name;
	}
	
	/**
	 * Test setup
	 */
	protected function setUp() {
		//$this->queueName = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		//$this->queue = $storageClient->createQueue ( $this->queueName );
	}
	
	/**
	 * Test teardown
	 */
	protected function tearDown() {
		//		if(!is_null( $this->queueName)){
		//			try{
		//				$storageClient = $this->_createStorageClient ();
		//				$storageClient->deleteQueue($this->queueName);
		//			}catch(Exception $e){
		//				// ignore
		//			}
		//		}
		if (count ( $this->_tempQueues ) > 0) {
			$storageClient = $this->_createStorageClient ();
			foreach ( $this->_tempQueues as $queue )
				try {
					$storageClient->deleteQueue ( $queue );
				} catch ( Exception $e ) {
					// ignore
				}
			
			$this->_tempQueues = array ();
		}
	
	}
	
	private function _queueExists($result, $qeueName) {
		foreach ( $result as $table )
			if ($table->Name == $qeueName) {
				return true;
			}
		return false;
	}
	
	/**
     * Test list queues
     */
    public function testListQueues()
    {
    	$count = 3;
    	$queueNames = array();
    	$storageClient = $this->_createStorageClient ();
    	for($i = 0 ; $i < $count ; $i ++){
    		$name = $this->generateQueueName ();
    		$queueNames[] = $name;
    		$storageClient->createQueue ( $name );
    	}
    	
    	$result = $storageClient->listQueues();
    	   	
    	foreach ($queueNames as $name)
    		$this->assertTrue( $this->_queueExists($result, $name));
    }
    
 	/**
     * Test list queues with prefix
     */
    public function testListQueues_Prefix()
    {
    	$prefix = "listprefix";
    	$count = 3;
    	$storageClient = $this->_createStorageClient ();
    	for($i = 0 ; $i < $count ; $i ++){
    		$name = $prefix . $i;    		
    		$this->_tempQueues[] = $name;
    		$storageClient->createQueue ( $name );
    	}
    	
    	$result = $storageClient->listQueues($prefix);
    	$this->assertEquals($count, count($result));  
    	
    	$result = $storageClient->listQueues("");
    	$this->assertEquals($count, count($result));    	
    	
    }
    
    /**
     * Test list queues with maxresult
     *
     */
     public function testListQueues_Maxresult()
    {
    	$count = 3;
    	$storageClient = $this->_createStorageClient ();
    	for($i = 0 ; $i < $count ; $i ++){
    		$name = $this->generateQueueName ();
    		$storageClient->createQueue ( $name );
    	}
    	
    	$result = $storageClient->listQueues(null,1);
    	$this->assertEquals(1, count($result));
    	
    	$result = $storageClient->listQueues("",0);
    	$this->assertEquals(0, count($result));
    	
    	$result = $storageClient->listQueues("",100);
    	$this->assertTrue(count($result)>= 3);
    	
    	try{
    		$storageClient->listQueues("",-1);
    	}catch(Exception $e){
    		
    		$this->assertEquals("One of the request inputs is out of range.", $e->getMessage());
    	}
    }
    
	
	/**
	 * Test create queue
	 */
	public function testCreateQueue() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$result = $storageClient->createQueue ( $name );
		$this->assertEquals ( $name, $result->Name );
		
		$result = $storageClient->listQueues ();
		$this->assertTrue ( count ( $result ) > 0 );
		
		$this->assertTrue ( $this->_queueExists ( $result, $name ) );
	}
	
	/**
	 * Test create queue with invalid queue names.
	 *
	 */
	public function testCreateQueue_InvalidNames() {
		$errorMessage = "Queue name does not adhere to queue naming conventions. See http://msdn.microsoft.com/en-us/library/dd179349.aspx for more information.";
		
		// Queue name must be lowercase
		// The first letter in the queue name must be alphanumeric
		// The last letter in the queue name must be alphanumeric
		$invalid_names = array ("Queue", "-queue", "queue-", "qu--eue", "a", "ab", "--c", "--", "", str_repeat ( "a", 64 ) );
		$storageClient = $this->_createStorageClient ();
		foreach ( $invalid_names as $name ) {
			try {
				$storageClient->createQueue ( $name );
				$this->fail ( "invalid queue name" );
			} catch ( Exception $e ) {
				$this->assertEquals ( $errorMessage, $e->getMessage () );
			}
		}
	}
	
	public function testPutMessage() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->peekMessages ( $name );
		$this->assertEquals ( $content, $message [0]->MessageText );
	}
	
	/**
	 * After ttl is overtime, the message should be die
	 * Specifies the time-to-live interval for the message, in seconds.
	 *
	 */
	public function testPutMessageWithShortTTL() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content, 5 ); // TTL is 5 second
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->peekMessages ( $name );
		$this->assertEquals ( 0, sizeof ( $message ) );
	}
	
public function testPutMessageWithTTLIs0() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content, 0 ); // TTL is 0 second
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->peekMessages ( $name );
		$this->assertEquals ( 0, sizeof ( $message ) );
	}
	
	/**
	 * MAX_SIZE shoud be : Microsoft_Azure_Storage_Queue::MAX_MESSAGE_SIZE
	 *
	 * This test take a long time.
	 */
	//	public function testPutExtendSizeMessage() {
	//		$name = $this->generateQueueName ();
	//		$storageClient = $this->_createStorageClient ();
	//		$storageClient->createQueue ( $name );
	//		
	//		// small then MAX_SZIE
	//		try {
	//			$content = str_repeat ( "a", Microsoft_Azure_Storage_Queue::MAX_MESSAGE_SIZE - 1 );
	//			$storageClient->putMessage ( $name, $content );
	//		} catch ( Exception $e ) {
	//			$this->fail ( $e->getMessage () );
	//		}
	//		
	//		//equal MAX_SZIE
	//		try {
	//			$content = str_repeat ( "a", Microsoft_Azure_Storage_Queue::MAX_MESSAGE_SIZE );
	//			$storageClient->putMessage ( $name, $content );
	//		} catch ( Exception $e ) {
	//			$this->fail ( $e->getMessage () );
	//		}
	//		
	//		//large than MAX_SIZE
	//		try {
	//			$content = str_repeat ( "a", Microsoft_Azure_Storage_Queue::MAX_MESSAGE_SIZE + 1 );
	//			$storageClient->putMessage ( $name, $content );
	//		} catch ( Exception $e ) {
	//			//Large extend, should throw exception
	//			$this->assertTrue ( $e != null );
	//			$this->assertEquals ( "Message is too big. Message content should be < 8KB.", $e->getMessage () );
	//		}
	//	}
	

	protected function waitForMessageAppear($time = 30) {
		sleep ( $time ); // wait for the messages to appear in the queue...
	}
	
	public function testPeekMessage() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->peekMessages ( $name );
		$this->assertEquals ( $content, $message [0]->MessageText );
	}
	
	/**
	 * 'Peek' operation not change the message visibility.
	 *
	 */
	public function testPeekMessageAlreadyPeeked() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->peekMessages ( $name );
		$this->assertEquals ( $content, $message [0]->MessageText );
		
		//peek again 
		$message = $storageClient->peekMessages ( $name );
		$this->assertEquals ( 1, sizeof ( $message ) );
	}
	
	/**
	 * Min allowed 1 and Max allowed 32 per one peek operation
	 *
	 */
	public function testPeekMessageNumberofmessage() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 40 messages
		$content = "Simple message";
		for($i = 0; $i < 40; $i ++) {
			$storageClient->putMessage ( $name, $content . "_" . $i );
		}
		
		$number_of_message = 33;
		try {
			$storageClient->peekMessages ( $name, $number_of_message );
		} catch ( Exception $e ) {
			$this->assertEquals ( "Invalid number of messages to retrieve.", $e->getMessage () );
		}
		
		$number_of_message = 0;
		
		try {
			$storageClient->peekMessages ( $name, $number_of_message );
		} catch ( Exception $e ) {
			$this->assertEquals ( "Invalid number of messages to retrieve.", $e->getMessage () );
		}
		
		$number_of_message = 20;
		
		//peek order as the put order?
		try {
			$messages = $storageClient->peekMessages ( $name, $number_of_message );
			$this->assertEquals ( $number_of_message, sizeof ( $messages ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Peek messages order same with put message? NO
	 *
	 */
	public function testPeekMessageOrder() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 40 messages
		$content = "Simple message";
		for($i = 0; $i < 40; $i ++) {
			$storageClient->putMessage ( $name, $content . "_" . $i );
		}
		
		$this->waitForMessageAppear ();
		
		$unorder = true;
		$number_of_message = 20;
		//peek order as the put order? NO
		try {
			$messages = $storageClient->peekMessages ( $name, $number_of_message );
			$this->assertEquals ( $number_of_message, sizeof ( $messages ) );
			for($i = 0; $i < $number_of_message; $i ++) {
				$msg = $messages [$i];
				$match = ($content . "_" . $i == $msg->MessageText); // maybe not match
				if (! $match) {
					$this->assertTrue ( $unorder );
					break;
				}
			}
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Get an empty array when get message from a empty queue
	 *
	 */
	public function testPeekMessageFromEmptyQueue() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		try {
			$message = $storageClient->peekMessages ( $name );
			$this->assertEquals ( 0, sizeof ( $message ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	/**
	 * Get an empty array when get message from a empty queue
	 *
	 */
	public function testGetMessageFromEmptyQueue() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		try {
			$message = $storageClient->getMessages ( $name );
			$this->assertEquals ( 0, sizeof ( $message ) );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	public function testGetMessage() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->getMessages ( $name );
		$this->assertEquals ( $content, $message [0]->MessageText );
	}
	
	/**
	 * getMessage() operation change the message visiblility.
	 */
	public function testGetMessageAlreadyGot() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->getMessages ( $name );
		$this->assertEquals ( $content, $message [0]->MessageText );
		
		//visibilitytimeout is not setted(default 30 s), message is also visible.
		$message = $storageClient->getMessages ( $name );
		$this->assertEquals ( 0, sizeof ( $message ) );
	}
	
	/**
	 * getMessage() operation change the message visiblility.
	 * 
	 * Re-get the message wait message un-visible timeout
	 * 
	 * See Optional. An integer value that specifies the message's visibility timeout in seconds. The maximum value is 2 hours. The default message visibility timeout is 30 seconds.
	 */
	public function testGetMessageAlreadyGotAfterTimeout() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$message = $storageClient->getMessages ( $name );
		$this->assertEquals ( $content, $message [0]->MessageText );
		
		$defaultTimeout = 30 + 15; // A little large than 30 for some net delay.
		

		//Default timeout is 30
		$this->waitForMessageAppear ( $defaultTimeout );
		$message = $storageClient->getMessages ( $name );
		//Messsage is visible again
		$this->assertEquals ( $content, $message [0]->MessageText );
	}
	
	/**
	 * wait timeout setted before and delay 15 second. (For a variety of causes  the timeout is not accurate )
	 */
	public function testGetMessageWithVisibleTimeoutSetting() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$timeout = 10;
		$message = $storageClient->getMessages ( $name, 1, $timeout );
		$this->assertEquals ( $content, $message [0]->MessageText );
		//Default timeout is 30
		$this->waitForMessageAppear ( $timeout + 15 );
		$message = $storageClient->getMessages ( $name );
		$this->assertEquals ( $content, $message [0]->MessageText );
	}
	
	/**
	 * visiblitytimeout should also not allow 0 since 0 is invalid
	 */
	public function testGetMessageWithVisibleTimeoutSettingZero() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ();
		
		$timeout = 0; // 0 is invalid
		try {
			$storageClient->getMessages ( $name, 1, $timeout );
			$this->fail ( "A exception should be thowrn when Visibility timeout is 0" );
		} catch ( Exception $e ) {
			$this->assertEquals ( "Microsoft_Azure_Exception", get_class ( $e ) );
			$this->assertEquals ( "Visibility timeout is invalid. Maximum value is 2 hours (7200 seconds).", $e->getMessage () );
		}
	}
	
	/**
	 * visiblitytimeout must <= 7200
	 */
	public function testGetMessageWithVisibleTimeoutSetting7200() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ( 10 );
		
		$timeout = 7200; // MAX 7200 seconds is valid
		try {
			$message = $storageClient->getMessages ( $name, 1, $timeout );
			$this->assertEquals ( $content, $message [0]->MessageText );
		} catch ( Exception $e ) {
			$this->fail ( $e->getMessage () );
		}
	}
	
	//Test get messages with invalid numofmessages.
public function testGetMessageWithInvalidNumOfMessages() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$count = 30;
		
		$content = "Simple message";
		for($i = 0; $i < $count; $i ++) {
			$storageClient->putMessage ( $name, $content . "_" . $i );
		}
		$this->waitForMessageAppear ( 10 );
		
		$invalid_num = array (0, - 1, 33, "", 2.0);
		
		foreach ( $invalid_num as $num ) {
			$exceptionThrown = false;
			try {
				$message = $storageClient->getMessages ( $name, $num );
			} catch ( Exception $e ) {
				$exceptionThrown = true;
				$this->assertEquals ( "Invalid number of messages to retrieve.", $e->getMessage () );
			}
			$this->assertTrue ( $exceptionThrown );
		}
	}
	
	//Test Test get messages with valid numofmessages.
public function testGetMessageWithValidNumOfMessages(){
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$count = 20;
		
		$content = "Simple message";
		for($i = 0; $i < $count; $i ++) {
			$storageClient->putMessage ( $name, $content . "_" . $i );
		}
		$this->waitForMessageAppear ( 10 );
		
		$message = $storageClient->getMessages ( $name, 1);
		print_r($message);
		$this -> assertEquals(1, count($message));
		
		$this->waitForMessageAppear ( 40 );
		
		$message = $storageClient->getMessages ( $name, 32);
		$this -> assertEquals(20, count($message));
		
	}
	
	/**
	 * Test create queue with metadata.
	 *
	 */
	public function testCreateQueue_Metadata() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$metadata = array ("comment" => "test", "value" => 1 );
		$storageClient->createQueue ( $name, $metadata );
		
		$result = $storageClient->getQueueMetadata ( $name );
		foreach ( $metadata as $key => $value )
			$this->assertEquals ( $value, $result [$key] );
	
	}
	
	//Not give a detailed exception message for create queues that exist.
	public function testCreateQueueConflict() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		try {
			$storageClient->createQueue ( $name );
			$storageClient->createQueue ( $name );
			$this->fail ( "Queue name already exist." );
		} catch ( Exception $e ) {
		    $this->assertEquals ( "The specified queue already exist.", $e->getMessage () );
		}
	}
	
	public function testGetQueue_MessageCount() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$count = 3;
		$content = "Simple message";
		for($i = 0; $i < $count; $i ++)
			$storageClient->putMessage ( $name, $content );
		
		$this->waitForMessageAppear ( 10 );
		$this->assertEquals ( $count, $storageClient->getQueue ( $name )->ApproximateMessageCount );
	}
	
	/**
	 * Test get queue with metadata.
	 *
	 */
	public function testGetQueue_Metadata() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$metadata = array ("comment" => "test", "value" => 1 );
		$storageClient->createQueue ( $name, $metadata );
		
		$result = $storageClient->getQueue ( $name );
		$this->assertEquals ( $name, $result->name );
		foreach ( $metadata as $key => $value )
			$this->assertEquals ( $value, $result->metadata [$key] );
	
	}
	
	public function testDeleteQueue() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$result = $storageClient->listQueues ();
		$this->assertTrue ( $this->_queueExists ( $result, $name ) );
		
		$storageClient->deleteQueue ( $name );
		$result = $storageClient->listQueues ();
		$this->assertFalse ( $this->_queueExists ( $result, $name ) );
	
	}
	
	//Not give a detailed exception message for delete queues that not exist.
	public function testDeleteQueueTwice() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		try {
			$storageClient->deleteQueue ( $name );
			$storageClient->deleteQueue ( $name );
			$this->fail ( "For the second operation, the Queue not exist." );
		} catch ( Exception $e ) {
		    $this->assertEquals ( "The specified queue does not exist.", $e->getMessage () );
		}
	}
	
	public function testDeleteQueueNotExist() {
		$name = "nosuchqueuetodelete";
		$storageClient = $this->_createStorageClient ();
		try {
			$storageClient->deleteQueue ( $name );
			$this->fail ( "The Queue not exist." );
		} catch ( Exception $e ) {
			$this->assertEquals ( "The specified queue does not exist.", $e->getMessage () );
		}
	}
	
	public function testSetAndGetMetadata() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$storageClient->setQueueMetadata ( $name, array ("aa" => "bb" ) );
		$this->assertEquals ( array ("aa" => "bb" ), $storageClient->getQueueMetadata ( $name ) );
	}
	
	/**
	 * Test queue name validatation
	 *
	 */
	public function testValidQueueName() {
		/**
		 * Queue name lenght should be >=3 and <64 and can't contain '--'. Can't start with -
		 */
		$invalid_names = array ("-abcd", "a", "ab", "c--c", "--c", "--", "", str_repeat ( "a", 64 ) );
		foreach ( $invalid_names as $name ) {
			$this->assertFalse ( Microsoft_Azure_Storage_Queue::isValidQueueName ( $name ) );
		}
		
		$valid_names = array ("abc", "queue-test", "0123", "a01234", str_repeat ( 'a', 63 ) );
		foreach ( $valid_names as $name ) {
			$this->assertTrue ( Microsoft_Azure_Storage_Queue::isValidQueueName ( $name ) );
		}
	}
	
	//Test delete messages
	public function testDeleteMessages() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		//put 30 messages.
		$content = "Simple message";
		for($i = 0; $i < 30; $i ++) {
			$storageClient->putMessage ( $name, $content . "_" . $i );
		}
		
		sleep ( 10 ); //wait for the messages to appear in the queue.
		
		//delete two exist messages.
		$message1 = $storageClient->getMessages ( $name, 2 );
		foreach ( $message1 as $message ) {
			$storageClient->deleteMessage ( $name, $message );
		}
		
		sleep ( 10 );
		$message2 = $storageClient->getMessages ( $name, 30 );
		
		$this->assertEquals(2, count($message1));
		$this->assertEquals(28, count($message2));
		
	}
	
	//Delete messages that does not exist.
public function testDeleteMessagesNotExist() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		sleep ( 10 ); 
		
		$message = $storageClient->getMessages ( $name );
		
		try{
			$storageClient->deleteMessage ( $name, $message[0] );
			$storageClient->deleteMessage ( $name, $message[0] );
			$this->fail("the message specified not exist.");
		}catch (Exception $e){
			$this->assertEquals("The specified message does not exist.",$e->getMessage());
		}
	}
	
	//Delete the message which retrieved using "peekMessages". Can not be deleted.
	public function testDeleteMessagesNoProp() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$content = "Simple message";
		$storageClient->putMessage ( $name, $content );
		
		sleep ( 10 ); 
		
		$message = $storageClient->peekMessages ( $name );
		
		try{
			$storageClient->deleteMessage ( $name, $message[0] );
			
			$this->fail("peekmessage not contain a valid pop receipt");
		}catch (Exception $e){
			$this->assertEquals("A message retrieved using \"peekMessages\" can NOT be deleted! Use \"getMessages\" instead.",$e->getMessage());
		}
	}
	
	/**
	 * Test clear messages
	 *
	 */
	public function testClearMessages() {
		$name = $this->generateQueueName ();
		$storageClient = $this->_createStorageClient ();
		$storageClient->createQueue ( $name );
		
		$max_ttl = 7 * 24 * 60 * 60;
		
		//put 
		$count = 10;
		for($i = 1; $i <= $count; $i ++) {
			$content = "Simple message";
			$storageClient->putMessage ( $name, $content . "_" . $i, rand ( 30, $max_ttl ) );
		}
		
		$this->waitForMessageAppear (); // wait
		$visiblitilyimeout = 30;
		//Make some message is unvisible in 30 second
		$storageClient->getMessages ( $name, 3, $visiblitilyimeout );
		
		$storageClient->clearMessages ( $name );
		
		$messages = $storageClient->getMessages ( $name, $count );
		$this->assertEquals ( 0, sizeof ( $messages ) );
	
	}
	
	public static function main() {
		$suite = new PHPUnit_Framework_TestSuite ( "Microsoft_Azure_QueueTest" );
		$result = PHPUnit_TextUI_TestRunner::run ( $suite );
	}
	
	private function _createStorageClient() {
		return new Microsoft_Azure_Storage_Queue ( QUEUE_HOST, STORAGE_ACCOUNT, STORAGE_KEY, false, Microsoft_Azure_RetryPolicy::retryN ( 10, 250 ) );
	}
}

?>