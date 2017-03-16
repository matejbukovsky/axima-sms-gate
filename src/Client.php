<?php
/**
 * @author Tomáš Blatný
 */

namespace Axima\SmsGate;


use DateTime;
use GuzzleHttp\ClientInterface;
use SimpleXMLElement;

class Client
{

	/** @var string */
	public static $sendMessageUrl = 'https://smsgateapi.sluzba.cz/apixml30/receiver';

	/** @var string */
	public static $deliveryMessageUrl = 'https://smsgateapi.sluzba.cz/apixml30/sender';

	/** @var string */
	public static $confirmMessageUrl = 'https://smsgateapi.sluzba.cz/apixml30/confirm';

	/** @var string */
	public static $infoUrl = 'https://smsgateapi.sluzba.cz/apixml30/info/credit';

	/** @var ClientInterface */
	private $guzzleClient;

	/** @var string */
	private $login;

	/** @var string */
	private $password;


	public function __construct(ClientInterface $guzzleClient, $login, $password)
	{
		$this->guzzleClient = $guzzleClient;
		$this->login = $login;
		$this->password = $password;
	}


	/**
	 * @param string $text
	 * @param string $number
	 * @param bool|NULL $requestConfirmation
	 * @param DateTime|NULL $sendAt
	 * @return array
	 * @throws ClientException
	 * @throws SmsGateException
	 */
	public function sendSms($text, $number, $requestConfirmation = NULL, DateTime $sendAt = NULL)
	{
		$text = trim((string) $text);
		$number = trim((string) $number);

		if ($text === '') {
			throw new ClientException('Please provide a non-empty text message.');
		}
		if (!Validators::validateNumber($number)) {
			throw new ClientException('Please provide a phone number in a valid format.');
		}
		if ($sendAt !== NULL) {
			$sendAt = $sendAt->format('YmdHis');
		}

		if ($requestConfirmation === TRUE) {
			$requestConfirmation = 20;
		} elseif ($requestConfirmation === FALSE) {
			$requestConfirmation = 0;
		} elseif ($requestConfirmation !== NULL) {
			throw new ClientException('Request confirmation parameter may be only TRUE/FALSE or NULL.');
		}

		$response = $this->guzzleClient->post(self::$sendMessageUrl, array(
			'query' => array(
				'login' => $this->login,
				'password' => $this->password,
			),
			'headers' => array(
				'Content-type' => 'text/xml'
			),
			'body' => $this->getXml($text, $number, $requestConfirmation, $sendAt),
		));
		if ($response->getStatusCode() !== 200) {
			$xml = simplexml_load_string($response->getBody()->getContents());
			throw new SmsGateException((string) $xml->message, $response->getStatusCode());
		}

		$xml = simplexml_load_string($response->getBody()->getContents());
		$message = $xml->message;

		return array(
			'id' => (string) $message->id,
			'parts' => (string) $message->parts,
			'price' => (string) $message->price,
			'credit' => (string) $xml->credit,
		);
	}


	/**
	 * @return string[]
	 * @throws SmsGateException
	 */
	public function getDeliveryReports()
	{
		$response = $this->guzzleClient->post(self::$deliveryMessageUrl, array(
			'query' => array(
				'login' => $this->login,
				'password' => $this->password,
				'query_answer_message' => 0,
				'query_delivery_report' => 1,
				'count' => 30,
			),
			'headers' => array(
				'Content-type' => 'text/xml'
			),
		));

		if ($response->getStatusCode() !== 200) {
			$xml = simplexml_load_string($response->getBody()->getContents());
			throw new SmsGateException((string) $xml->message, $response->getStatusCode());
		}

		$xml = simplexml_load_string($response->getBody()->getContents());

		$result = array();
		foreach ($xml as $message) {
			$result[(string) $message->id] = (string) $message->delivery_timestamp;
		}
		return $result;
	}


	/**
	 * @param string $id
	 * @throws SmsGateException
	 */
	public function confirmDeliveryReport($id)
	{
		$response = $this->guzzleClient->get(self::$confirmMessageUrl, array(
			'query' => array(
				'login' => $this->login,
				'password' => $this->password,
				'type' => 'delivery_report',
				'id' => $id,
			),
			'headers' => array(
				'Content-type' => 'text/xml'
			),
		));
		if ($response->getStatusCode() !== 200) {
			throw new SmsGateException('Error while sending request.', $response->getStatusCode());
		}
	}


	/**
	 * @return array
	 * @throws SmsGateException
	 */
	public function getAccountStatus()
	{
		$response = $this->guzzleClient->get(self::$infoUrl, array(
			'query' => array(
				'login' => $this->login,
				'password' => $this->password,
			),
			'headers' => array(
				'Content-type' => 'text/xml'
			),
		));

		if ($response->getStatusCode() !== 200) {
			throw new SmsGateException('Error while sending request.', $response->getStatusCode());
		}

		$xml = simplexml_load_string($response->getBody()->getContents());

		$result = array(
			'credit' => (string) $xml->credit,
			'priceCz' => (string) $xml->price_cz_sms,
			'priceSk' => (string) $xml->price_sk_sms,
			'priceOther' => (string) $xml->price_other_sms,
		);
		return $result;
	}


	/**
	 * @param string $text
	 * @param string $number
	 * @param string|NULL $drRequest
	 * @param string|NULL $sendAt
	 * @return string
	 * @throws ClientException
	 */
	private function getXml($text, $number, $drRequest = NULL, $sendAt = NULL)
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><outgoing_message/>');
		$xml->addChild('text', $text);
		$xml->addChild('recipient', $number);
		if ($drRequest !== NULL) {
			$xml->addChild('dr_request', $drRequest);
		}
		if ($sendAt !== NULL) {
			$xml->addChild('send_at', $sendAt);
		}
		$result = $xml->asXML();
		if ($result === FALSE) {
			throw new ClientException('Unable to create XML document.');
		}
		return $result;
	}

}
