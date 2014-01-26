<?php
	require_once 'src/kashflow.php';

	class kashflowTest extends PHPUnit_Framework_TestCase
	{
		public function testGetCustomersFailsWithoutCredentials()
		{
			$kashflow = new Kashflow('', '');

			$customers = $kashflow->GetCustomers();

			$this->assertArrayHasKey('ErrorMsg', $customers, 'ErrorMsg not found when no credentials given');

			$this->assertTrue(
				$customers['ErrorMsg'] === 'Incorrect username or password',
				'Correct message not sent back to the user with no login details'
			);
		}

		public function testGetCustomersFailsWithBadCredentials()
		{
			$kashflow = new Kashflow('1234567890', '1234567890');

			$customers = $kashflow->GetCustomers();

			$this->assertArrayHasKey('ErrorMsg', $customers, 'ErrMsg not found when bad credentials given');

			$this->assertTrue(
				$customers['ErrorMsg'] === 'Incorrect username or password',
				'Correct message not sent back to the user for incorrect login details'
			);
		}
	}
 