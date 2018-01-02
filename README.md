# Axima sms gate
PHP implementation of sms.sluzba.cz API.

## Installation

	composer matejbukovsky/axima-sms-gate

## Usage

	use Axima\SmsGate\Client;
	use GuzzleHttp\Client as GuzzleClient;
	use Axima\SmsGate\Message;

	$client = new Client(new GuzzleClient, 'login', 'password'); // create sender
	$message = new Message('Hello world!', ['123465789', '987654321']); // create message
	$message->addPhone('123465798');// possibly add another number
	$message->setSendAt(new \DateTimeImmutable('+ 2 minutes')); // send after 2 minutes
	$count = $this->client->sendSms($message); // returns number of sent messages