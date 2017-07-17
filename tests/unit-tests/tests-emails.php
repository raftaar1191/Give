<?php

/**
 * Class Test_emails
 */
class Test_emails extends Give_Unit_Test_Case {

	protected $_payment_id = null;
	protected $_key = null;
	protected $_post = null;
	protected $_payment_key = null;

	public function setUp() {
		parent::setUp();

		$payment_id         = Give_Helper_Payment::create_simple_payment();
		$this->_payment_key = give_get_payment_key( $payment_id );
		$this->_payment_id  = $payment_id;
		$this->_key         = $this->_payment_key;

		$this->_transaction_id = 'FIR3SID3';

		give_set_payment_transaction_id( $payment_id, $this->_transaction_id );
		give_insert_payment_note( $payment_id, sprintf( /* translators: %s: Paypal transaction id */
			esc_html__( 'PayPal Transaction ID: %s', 'give' ), $this->_transaction_id ) );
		// Make sure we're working off a clean object caching in WP Core.
		// Prevents some payment_meta from not being present.
		clean_post_cache( $payment_id );
		update_postmeta_cache( array( $payment_id ) );
	}

	public function tearDown() {
		parent::tearDown();

		Give_Helper_Payment::delete_payment( $this->_payment_id );
	}

	/**
	 * Check if all the email class exists or not.
	 */
	public function test_email_class_exists() {
		// Check if Email class exists or not.
		$this->assertTrue( class_exists( 'Give_Emails' ) );

		// Check if Email Template Tags class exists or not.
		$this->assertTrue( class_exists( 'Give_Email_Template_Tags' ) );
	}

	/**
	 * Check if send email to donor and admin function exists.
	 */
	public function test_email_send_function() {
		$this->assertTrue( function_exists( 'give_email_donation_receipt' ) );
		$this->assertTrue( function_exists( 'give_admin_email_notice' ) );
	}

	public function test_ExpectFooActualFoo() {
		$this->expectOutputString( $this->_payment_id );
		print $this->_payment_id;
	}


	public function test_ExpectFooActualFoosdfsdfs() {
		$this->expectOutputString( $this->_payment_key );
		print $this->_payment_key;
	}
}