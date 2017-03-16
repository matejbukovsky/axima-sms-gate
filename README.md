# php-sms-gate
PHP implementation of sms.sluzba.cz API.

## Installation

	composer require axima/php-sms-gate

## Usage

	use Axima\SmsGate\Client;
	use GuzzleHttp\Client as GuzzleClient;
	
	$client = new Client(new GuzzleClient, 'login', 'password');
	$client->sendSms(123456789, 'Hello there!');
	
To request confirmation, you can also provide third parameter to method `sendSms`, like this:

	$client->sendSms(123456789, 'Hello there!', TRUE);
	
Setting it to `FALSE` disables confirmation for this SMS. Setting it to `NULL` will use user's default settings.

4th parameter can be used to schedule sending of SMS.

	$client->sendSms(123456789, 'Hello there!', TRUE, new DateTime('+10 minutes'));
	
## Bug reports, feature requests

Please use GitHub issue tracker / pull requests.
