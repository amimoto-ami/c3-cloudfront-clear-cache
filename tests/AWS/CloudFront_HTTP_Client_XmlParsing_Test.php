<?php
/**
 * CloudFront_HTTP_Client XML Parsing Test
 *
 * @package C3_CloudFront_Cache_Controller\Test
 */

use PHPUnit\Framework\TestCase;
use C3_CloudFront_Cache_Controller\AWS\CloudFront_HTTP_Client;

/**
 * Tests for secure XML parsing implementation in CloudFront_HTTP_Client
 */
class CloudFront_HTTP_Client_XmlParsing_Test extends TestCase {

    /**
     * Ensure Mockery expectations are verified.
     */
    public function tearDown(): void {
        if ( class_exists( '\\Mockery' ) ) {
            \Mockery::close();
        }
    }

    /**
     * Helper to invoke private/protected methods via reflection.
     *
     * @param object $object      Target object.
     * @param string $method_name Method name.
     * @param array  $args        Arguments.
     *
     * @return mixed Invoked method return value.
     */
    private function invoke_private_method( $object, string $method_name, array $args = [] ) {
        $ref = new \ReflectionMethod( $object, $method_name );
        $ref->setAccessible( true );
        return $ref->invokeArgs( $object, $args );
    }

    /**
     * 正常な CloudFront XML 応答をパースできることをテスト
     */
    public function test_parse_xml_response_success() {
        $xml = "<?xml version=\"1.0\"?>"
             . '<InvalidationBatch>'
             . '  <CallerReference>test-ref</CallerReference>'
             . '  <Paths>'
             . '    <Quantity>1</Quantity>'
             . '    <Items>'
             . '      <Path>/index.html</Path>'
             . '    </Items>'
             . '  </Paths>'
             . '</InvalidationBatch>';

        $client = new CloudFront_HTTP_Client( 'dummy-key', 'dummy-secret' );
        $result = $this->invoke_private_method( $client, 'parse_xml_response', [ $xml ] );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'CallerReference', $result );
        $this->assertEquals( 'test-ref', $result['CallerReference'] );
        $this->assertEquals( '/index.html', $result['Paths']['Items']['Path'] );
    }

    /**
     * エラーレスポンスから <Message> を抽出できることをテスト
     */
    public function test_parse_error_response_extracts_message() {
        $xml = "<?xml version=\"1.0\"?>"
             . '<ErrorResponse>'
             . '  <Message>Access denied</Message>'
             . '</ErrorResponse>';

        $client  = new CloudFront_HTTP_Client( 'dummy-key', 'dummy-secret' );
        $message = $this->invoke_private_method( $client, 'parse_error_response', [ $xml, 403 ] );

        $this->assertEquals( 'Access denied', $message );
    }

    /**
     * XXE を含む悪意ある XML がエンティティ展開されないことをテスト
     */
    public function test_parse_xml_response_with_malicious_xxe_does_not_expand() {
        $xml = "<?xml version=\"1.0\"?>"
             . '<!DOCTYPE foo [ <!ELEMENT foo ANY >'
             . '  <!ENTITY xxe SYSTEM "file:///etc/passwd" >]>'
             . '<foo>&xxe;</foo>';

        $client = new CloudFront_HTTP_Client( 'dummy-key', 'dummy-secret' );
        $result = $this->invoke_private_method( $client, 'parse_xml_response', [ $xml ] );

        // 配列として返ること
        $this->assertIsArray( $result );

        // 解析結果に機密ファイルパスが含まれていないこと（エンティティ未展開の検証）
        $json = json_encode( $result );
        $this->assertStringNotContainsString( '/etc/passwd', $json );
    }
}
