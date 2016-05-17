<?php

namespace Securetrading\Http\Tests\Unit;

require_once(__DIR__ . '/helpers/CoreMocks.php');

class CurlTest extends \Securetrading\Unittest\UnittestAbstract {
  public function setUp() {
    $this->_logMock = $this->getMockForAbstractClass('\Psr\Log\LoggerInterface');
  }

  public function tearDown() {
    \Securetrading\Unittest\CoreMocker::releaseCoreMocks();
  }

  public function _newInstance(array $configData = array()) { // Note - public so can be called from a closure.
    return new \Securetrading\Http\Curl($this->_logMock, $configData);
  }

  private function _mockCurlSetAndExec(&$calls) {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', 'curl_exec_rv');
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_setopt', function($ch, $k, $v) use (&$calls) {
      if (!array_key_exists($k, $calls)) {
	$calls[$k] = array(
	  'count' => 1,
	  'values' => array($v),
	);
      }
      else {
	$calls[$k]['count'] += 1;
	$calls[$k]['values'][] = $v;
      }
    });
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_setopt_array', function($ch, array $curlData) use (&$calls) {
      foreach($curlData as $k => $v) {
	if (!array_key_exists($k, $calls)) {
	  $calls[$k] = array(
	    'count' => 1,
	    'values' => array($v),
	  );
	}
	else {
	  $calls[$k]['count'] += 1;
	  $calls[$k]['values'][] = $v;
	}
      }
    });
    return $calls;
  }

  private function _testingCurlSetoptWrapper(array $expectedCurlKeys, $functionBody) {
    $calls = array();
    $this->_mockCurlSetAndExec($calls);
    
    $functionBody();
    
    foreach($expectedCurlKeys as $expectedCurl) {
      $expectedKey = $expectedCurl[0];
      $expectedCount = $expectedCurl[1];
      $expectedValues = isset($expectedCurl[2]) ? $expectedCurl[2] : array();
      if ($expectedCount === 0) {
	$this->assertTrue(!isset($calls[$expectedKey]));
      }
      else {
	$this->assertEquals($expectedCount, $calls[$expectedKey]['count']);
	if (is_callable($expectedValues)) {
	  $this->assertEquals(true, $expectedValues($calls[$expectedKey]['values']));
	}
	else {
	  $this->assertEquals($expectedValues, $calls[$expectedKey]['values']);
	}
      }
    }
  }

  /**
   * @dataProvider providerSend
   */
  public function testSend() {
    $args = func_get_args();
    $requestMethod = array_shift($args);
    $requestBody = array_shift($args);
    $configData = array_shift($args);
    $that = $this;

    $this->_testingCurlSetoptWrapper($args, function() use ($that, $requestMethod, $requestBody) {
      if ($requestBody) {
	$returnValue = $that->_newInstance()->send($requestMethod, $requestBody);
      }
      else {
	$returnValue = $that->_newInstance()->send($requestMethod);
      }
      $that->assertEquals('curl_exec_rv', $returnValue);
    });
  }

  public function providerSend() {
    $this->_addDataSet(
      'PATCH',
      'request_body',
      array(),
      array(CURLOPT_CUSTOMREQUEST, 1, array('PATCH')),
      array(CURLOPT_POSTFIELDS, 1, array('request_body'))
    );
    $this->_addDataSet(
      'PATCH',
      null,
      array(),
      array(CURLOPT_CUSTOMREQUEST, 1, array('PATCH')),
      array(CURLOPT_POSTFIELDS, 0)
    );
    return $this->_getDataSets();
  }

  /**
   * 
   */
  public function testGet() {
    $that = $this;
    $this->_testingCurlSetoptWrapper(array(), function() use ($that) {
      $returnValue = $that->_newInstance()->get();
      $that->assertEquals('curl_exec_rv', $returnValue);
    });
  }

  /**
   * @dataProvider providerPost
   */
  public function testPost() {
    $args = func_get_args();
    $postArg = array_shift($args);
    $configData = array_shift($args);
    $that = $this;
    $this->_testingCurlSetoptWrapper($args, function() use ($that, $postArg) {
      if ($postArg) {
	$returnValue = $that->_newInstance()->post($postArg);
      }
      else {
	$returnValue = $that->_newInstance()->post();
      }
      $that->assertEquals('curl_exec_rv', $returnValue);
    });
  }

  public function providerPost() {
    $this->_addDataSet(
      'request_body',
      array(),
      array(CURLOPT_POST, 1, array(1)),
      array(CURLOPT_POSTFIELDS, 1, array('request_body'))
    );
    $this->_addDataSet(
      null,
      array(),
      array(CURLOPT_POST, 1, array(1)),
      array(CURLOPT_POSTFIELDS, 1, array(''))
    );
    return $this->_getDataSets();
  }

  /**
   * @dataProvider provider_prepareCurl
   */
  public function test_prepareCurl() {
    $args = func_get_args();
    $configData = array_shift($args);

    $that = $this;
    $this->_testingCurlSetoptWrapper($args, function() use ($that, $configData) {
      $that->_newInstance($configData)->post('request_body');
    });
  }

  public function provider_prepareCurl() {
    $this->_addDataSet(
      array(),
      array(CURLOPT_FOLLOWLOCATION, 1, array(true))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_URL, 1, array(''))
    );
    $this->_addDataSet(
      array('url' => 'http://www.securetrading.com'),
      array(CURLOPT_URL, 1, array('http://www.securetrading.com'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_USERAGENT, 1, array(''))
    );
    $this->_addDataSet(
      array('user_agent' => 'our_user_agent'),
      array(CURLOPT_USERAGENT, 1, array('our_user_agent'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_SSL_VERIFYPEER, 1, array(2))
    );
    $this->_addDataSet(
      array('ssl_verify_peer' => false),
      array(CURLOPT_SSL_VERIFYPEER, 1, array(false))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_SSL_VERIFYHOST, 1, array(true))
    );
    $this->_addDataSet(
      array('ssl_verify_host' => 0),
      array(CURLOPT_SSL_VERIFYHOST, 1, array(0))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_CONNECTTIMEOUT, 1, array(5))
    );
    $this->_addDataSet(
      array('connect_timeout' => 10),
      array(CURLOPT_CONNECTTIMEOUT, 1, array(10))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_TIMEOUT, 1, array(60))
    );
    $this->_addDataSet(
      array('timeout' => 30),
      array(CURLOPT_TIMEOUT, 1, array(30))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_HTTPHEADER, 1, array(array()))
    );
    $this->_addDataSet(
      array('http_headers' => array('Content-Type: text/xml', 'Origin: http://www.securetrading.com')),
      array(CURLOPT_HTTPHEADER, 1, array(array('Content-Type: text/xml', 'Origin: http://www.securetrading.com')))
    );

    $this->_addDataSet(
      array(),
      array(CURLOPT_VERBOSE, 1, array(true))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_STDERR, 1, function(array $values) { return is_resource($values[0]); })
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_CAINFO, 0)
    );
    $this->_addDataSet(
      array('ssl_cacertfile' => ''),
      array(CURLOPT_CAINFO, 0)
    );
    $this->_addDataSet(
      array('ssl_cacertfile' => '/tmp/cert.pem'),
      array(CURLOPT_CAINFO, 1, array('/tmp/cert.pem'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_PROXY, 0)
    );
    $this->_addDataSet(
      array('proxy_host' => ''),
      array(CURLOPT_PROXY, 0)
    );
    $this->_addDataSet(
      array('proxy_host' => 'http://www.securetrading.com'),
      array(CURLOPT_PROXY, 1, array('http://www.securetrading.com'))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_PROXYPORT, 0)
    );
    $this->_addDataSet(
      array('proxy_port' => ''),
      array(CURLOPT_PROXYPORT, 0)
    );
    $this->_addDataSet(
      array('proxy_port' => '8080'),
      array(CURLOPT_PROXYPORT, 1, array('8080'))
    );

    $this->_addDataSet(
      array('username' => 'user@securetrading.com'),
      array(CURLOPT_HTTPAUTH, 0),
      array(CURLOPT_USERPWD, 0)
    );
    $this->_addDataSet(
      array('password' => 'password'),
      array(CURLOPT_HTTPAUTH, 0),
      array(CURLOPT_USERPWD, 0)
    );
    $this->_addDataSet(
      array('username' => 'user@securetrading.com', 'password' => 'password'),
      array(CURLOPT_HTTPAUTH, 1, array(CURLAUTH_BASIC)),
      array(CURLOPT_USERPWD, 1, array('user@securetrading.com:password'))
    );
    $this->_addDataSet(
      array('curl_options' => array(CURLOPT_VERBOSE => false, CURLOPT_URL => 'http://www.test.com', CURLOPT_CRLF => true)),
      array(CURLOPT_VERBOSE, 2, array(true, false)),
      array(CURLOPT_URL, 2, array("", "http://www.test.com")),
      array(CURLOPT_CRLF, 1, array(true))
    );
    $this->_addDataSet(
      array(),
      array(CURLOPT_RETURNTRANSFER, 1, array(true))
      );
    return $this->_getDataSets();
  }

  /**
   *
   */
  public function testGetResponseCode() {
    $that = $this;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_getinfo', function($ch, $curlinfoConstant) use ($that) {
      $that->assertEquals(CURLINFO_HTTP_CODE, $curlinfoConstant);
      return 'returned_value';
    });
    $returnValue = $this->_newInstance()->getResponseCode();
    $this->assertEquals('returned_value', $returnValue);
  }

  // Note - getLogData() not unit tested.

  /**
   *
   */
  public function testGetInfo() {
    $that = $this;
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_getinfo', function($ch, $curlinfoConstant) use ($that) {
      $that->assertEquals(CURLINFO_HTTP_CODE, $curlinfoConstant);
      return 'returned_value';
    });
    $returnValue = $this->_newInstance()->getInfo(CURLINFO_HTTP_CODE);
    $this->assertEquals('returned_value', $returnValue);
  }

  // Note - _sendAndReceive() not unit tested.

  public function test_SendAndReceiveWithRetries() {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', true);
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_errno', CURLE_COULDNT_CONNECT);
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('time', 40);
    
    $this->_logMock
      ->expects($this->exactly(4))
      ->method('error')
      ->withConsecutive(
        array(sprintf('Failed to connect to http://www.test.com on attempt 1.  Max attempts: 4.  Connect attempts timeout: 26.  cURL error: %s.  Sleeping for 1 second(s).', CURLE_COULDNT_CONNECT)),
        array(sprintf('Failed to connect to http://www.test.com on attempt 2.  Max attempts: 4.  Connect attempts timeout: 26.  cURL error: %s.  Sleeping for 1 second(s).', CURLE_COULDNT_CONNECT)),
        array(sprintf('Failed to connect to http://www.test.com on attempt 3.  Max attempts: 4.  Connect attempts timeout: 26.  cURL error: %s.  Sleeping for 1 second(s).', CURLE_COULDNT_CONNECT)),
	array(sprintf('Failed to connect to http://www.test.com on attempt 4.  Max attempts: 4.  Connect attempts timeout: 26.  cURL error: %s.  Sleeping for 1 second(s).', CURLE_COULDNT_CONNECT))
      )
    ;
    
    $curl = $this->_newInstance(array(
      'connect_timeout' => 1,
      'sleep_seconds' => 1,
      'connect_attempts_timeout' => 26,
      'connect_attempts' => 4,
      'url' => 'http://www.test.com',
    ));
    $actualReturnValue = $this->_($curl, '_sendAndReceiveWithRetries');
    $this->assertEquals(true, $actualReturnValue);
  }

  /**
   *
   */
  public function test_exec() {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_exec', true);
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('curl_errno', 0);
    $returnValue = $this->_($this->_newInstance(), '_exec');
    $this->assertEquals(array(true, 0), $returnValue);
  }
  
  /**
   * @dataProvider provider_canRetry
   */
  public function test_canRetry($startTime, $i, $expectedReturnValue) {
    \Securetrading\Unittest\CoreMocker::mockCoreFunction('time', 40);
    $curl = $this->_newInstance(array(
      'connect_timeout' => 5,
      'sleep_useconds' => 1,
      'connect_attempts_timeout' => 26,
      'connect_attempts' => 10,
    ));
    $actualReturnValue = $this->_($curl, '_canRetry', $startTime, $i);
    $this->assertEquals($expectedReturnValue, $actualReturnValue);
  }

  public function provider_canRetry() {
    $this->_addDataSet(21, 0, true);
    $this->_addDataSet(20, 0, true);
    $this->_addDataSet(19, 0, false);
    $this->_addDataSet(21, 9, true);
    $this->_addDataSet(21, 10, false);
    return $this->_getDataSets();
  }

  /**
   * @expectedException \Securetrading\Http\CurlException
   * @expectedExceptionCode \Securetrading\Http\CurlException::CODE_BAD_RESULT
   */
  public function test_checkResult_WhenError() {
    $this->_($this->_newInstance(), '_checkResult', false);
  }

  /**
   *
   */
  public function test_checkResult_WhenNoError() {
    $curl = $this->_newInstance();
    $returnValue = $this->_($curl, '_checkResult', true);
    $this->assertSame($curl, $returnValue);
  }
}